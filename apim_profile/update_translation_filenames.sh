#!/usr/local/bin/bash
#TODO update to /bin/bash ... but this will fail on mac default systems.

if (( ${BASH_VERSION%%.*} < 4 ))
then
#You need to be running at least bash 4, not installed by default on a mac. Use homebrew to install bash to /usr/local/bin/bash
#https://coderwall.com/p/dmuxma/upgrade-bash-on-your-mac-os
  echo "Minimum of bash v4 required."
  exit 1
fi

DRUPAL_CORE_VERSION="8.7.7"

TGZ_UNPACK_DIR=$1

function print_tgz_dir_usage() {
  echo ""
  echo "You need to unpack the latest .tgz into a directory and provide that path as a parameter."
  echo "For example:"
  echo "  $0 /tmp/portal_unpack/devportal"
  exit 1
}

if [ -z $TGZ_UNPACK_DIR ]
then
  echo "ERROR: no platform .tgz unpack dir provided"
  print_tgz_dir_usage
fi

if [ ! -d $TGZ_UNPACK_DIR ]
then
  echo "ERROR: platform .tgz unpack directory ( $TGZ_UNPACK_DIR ) doesn't exist"
  print_tgz_dir_usage
fi

echo "calculating current modules"
declare -A CURRENTPROJECTS

INFO_YMLS=`find "$TGZ_UNPACK_DIR/modules" -name "*.info.yml"`

for INFO_YML in $INFO_YMLS
do
  #use the filename rather than project otherwise we pick up test modules in some cases
  REQUIRED_PROJECT=`basename $INFO_YML .info.yml`
  REQUIRED_VERSION=`cat $INFO_YML | grep "^version: " | cut -d" " -f2 | sed -e "s/^'//" -e "s/'$//"`

  CURRENTPROJECTS[$REQUIRED_PROJECT]=$REQUIRED_VERSION

done

# Drupal core is treated specially by locale module, all core modules are grouped together so we can't get that
# information in thh same way.
echo "Adding drupal core manually"
CURRENTPROJECTS["drupal"]=$DRUPAL_CORE_VERSION

#TODO - check for composer.lock file.
#
#function get_version_for_installed_project() {
#  local PROJECT=$1
#  local RESULT=`cat  composer.lock  | jq -r '.packages[] | if (.type == "drupal-module" or .type == "drupal-theme" ) and (.name|split("/"))[1] == "'${PROJECT}'" then .extra.drupal.version else empty end'`
#  echo $RESULT
#}
#

cd po_files
pwd
TRANSLATION_FILES=`ls`


for TRANSLATION_FILE in $TRANSLATION_FILES
do
#  echo "processing file: $TRANSLATION_FILE"
  LANGUAGE=`echo $TRANSLATION_FILE | awk -F. '{print $(NF-1)}'`
  PROJECT_VERSION=`basename $TRANSLATION_FILE .${LANGUAGE}.po`

  # sometimes we don't have a version, if there is no - character
  if [[ $PROJECT_VERSION = *"-"* ]]
  then
    PROJECT=`echo $PROJECT_VERSION | cut -d- -f1`
    CURRENT_FILE_VERSION=`echo $PROJECT_VERSION | cut -d- -f2-`
  else
    PROJECT=$PROJECT_VERSION
    CURRENT_FILE_VERSION=
  fi
  TARGET_VERSION=${CURRENTPROJECTS[$PROJECT]}


  echo "$PROJECT ($LANGUAGE)"
  echo "   current:" $CURRENT_FILE_VERSION
  echo "    needed:" $TARGET_VERSION
  echo "--------------------"

  if [ -z $TARGET_VERSION ]
  then
    echo "  No target version, no action."
    echo "--------------------"
    continue
  fi

  if [ -z $CURRENT_FILE_VERSION ]
  then

    echo "  No current version found."
    echo "  Update needed NONE to $TARGET_VERSION"
    mv $TRANSLATION_FILE $PROJECT-$TARGET_VERSION.$LANGUAGE.po
    echo "--------------------"
    continue
  fi

  if [ $CURRENT_FILE_VERSION == $TARGET_VERSION ]
  then
    echo "  No action required"
    echo "--------------------"
    continue
  fi

  echo "  Update needed $CURRENT_FILE_VERSION to $TARGET_VERSION"
  mv $TRANSLATION_FILE $PROJECT-$TARGET_VERSION.$LANGUAGE.po
  echo "--------------------"


done

cd ..
pwd



