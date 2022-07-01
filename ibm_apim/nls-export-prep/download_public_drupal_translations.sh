#!/usr/bin/env bash

echo "Downloading existing translation files for all modules"

# Download all translations for all projects (modules, themes) and place them in a known
# location on the portal node.
#   - ftp.drupal.org - publicly available translations.
#
# This is done in 2 passes based on the way things are packaged up on ftp.drupal.org:
# - non-core modules are downloaded individually.
# - core modules are combined into a single .po file per language.
DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

function error_handler() {
	echo "Error occurred in script `basename $0` at line: ${1}."
	echo "Line exited with status: ${2}"
}

trap 'error_handler ${LINENO} $?' ERR

set -o errexit
set -o errtrace
set -o nounset

CHANGED=0
ERROR=0

function downloadNonCore() {
  local project_list=$(echo "$1" | tr ' ' '\n')
  local -n dllangs="$2"
  local project_name=''
  local project_version=''
  local messages=()
  # loop over all project,version entries
  while IFS=, read -r project_name project_version
  do
    messages+=("$project_name ($project_version) start")
    if [[ -z $project_version ]]
    then
      messages+=("  No version available - SKIPPING")
    else
      local failed_langs=
      for lang in "${dllangs[@]}"
      do
        local url="http://ftp.drupal.org/files/translations/8.x/$project_name/$project_name-$project_version.$lang.po"

        wget -q $url --no-check-certificate && RC=$? || RC=$?
        if [[ $RC -ne 0 ]]
        then
          messages+=("  $lang - NO DOWNLOAD AVAILABLE")
          ERROR=1
          failed_langs="$failed_langs $lang"
        else
          messages+=("  $lang - DOWNLOADED")
        fi

      done # LANGS
    fi

    if [[ -n "$failed_langs" ]]; then
      messages+=("  Failed to download$failed_langs translations for $project_name")
    fi
    messages+=("$project_name ($project_version) complete")

    printf '%s\n' "${messages[@]}"

  done <<< "$project_list"
}

# Use drush to list the modules. This will return all of the projects for the site.
# Other options here included parsing the composer.lock file.
# Format of list will be csv - machine_name,version.
# Note version is often empty, in this case we will skip it.
#PROJECT_LIST=( mailsystem,8.x-4.1 )
#Include PROJECT_LIST variable
. $DIR/project_list.sh

# all core modules (including experimental) are available in a single package.
CORE_PROJECT_NAME="drupal"
# get the core projects so we can see the version of core we have loaded.
CORE_PROJECT_VERSION=$(drush pm-list --core --field=version --format=csv | head -1)

#DLLANGS=( de )
#Include DLLANGS variable
. $DIR/supported_languages.sh

DLDIR=/tmp/translation_files/existing_drupal_pos

if [[ ! -d $DLDIR ]]
then
  mkdir -vp $DLDIR
fi

cd $DLDIR

echo " DOWNLOADING NON-CORE MODULES "

downloadNonCore "$SUB_PROJECT_LIST_ONE" DLLANGS &
LIST_ONE_PID=$!
downloadNonCore "$SUB_PROJECT_LIST_TWO" DLLANGS &
LIST_TWO_PID=$!
downloadNonCore "$SUB_PROJECT_LIST_THREE" DLLANGS &
LIST_THREE_PID=$!
downloadNonCore "$SUB_PROJECT_LIST_FOUR" DLLANGS &
LIST_FOUR_PID=$!

wait $LIST_ONE_PID
wait $LIST_TWO_PID
wait $LIST_THREE_PID
wait $LIST_FOUR_PID

echo ""
echo " DOWNLOADING CORE MODULES "
echo ""

FAILED_LANGS=
for LANG in "${DLLANGS[@]}"
do

   URL="http://ftp.drupal.org/files/translations/8.x/$CORE_PROJECT_NAME/$CORE_PROJECT_NAME-$CORE_PROJECT_VERSION.$LANG.po"

#   #echo "Attempting to download from $URL to $LANG.po in $PWD"
   wget -q $URL --no-check-certificate && RC=$? || RC=$?
   if [[ $RC -ne 0 ]]
   then
     echo "  $LANG - FAILED"

     ERROR=1
     FAILED_LANGS="$FAILED_LANGS $LANG"
   else
     echo "  $LANG - DOWNLOADED"
# TODO: Reinstate check for existing files in sites/all/files/translations.
##		 TARGET=$DIR/po/$FILE_NAME
##		 if [[ "$(md5sum lang.po| awk {'print $1'})" != "$(md5sum $TARGET 2>/dev/null | awk {'print $1'})" ]]
##			then
##				CHANGED=1
##				RM_PAT=$(echo $FILE_NAME | sed 's/-7.x-.*/-/' | sed 's/^drupal-7.*/drupal-/')*.$DLLANG.po
##				rm -f $DIR/po/$RM_PAT
##				echo "Installing new language file at $TARGET"
##				mv -f lang.po $DIR/po/$FILE_NAME
##			fi
   fi

done # LANGS

# for core we need to be less tolerant of failed downloads as we would expect them always to be available.
if [[ ! -z "$FAILED_LANGS" ]]
then
  echo "  Failed to download$FAILED_LANGS translations for $CORE_PROJECT_NAME" >&2
  exit 1
else
  echo "$CORE_PROJECT_NAME ($CORE_PROJECT_VERSION) complete"
fi

echo "Download of existing modules complete."
exit 0
