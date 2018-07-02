#!/usr/bin/env bash

# use potx module to export .pot files (templates) for every project, both enabled and not.
# .pot files are english only template files, i.e. no translations.

echo "Exporting existing strings for NLS export."

if [[ -z ${1} ]]
then
  echo "ERROR. No platform directory provided."
  exit 1
else
  PLATFORMDIR=${1}
  if [[ ! -d $PLATFORMDIR ]]
  then
    echo "Platform directory doesn't exist: $PLATFORMDIR"
    exit 1
  fi
fi

# output directory for generated .pot files.
EXPORTDIR="/tmp/translation_files/required_pots"

if [[ ! -d $EXPORTDIR ]]
then
  mkdir -vp $EXPORTDIR
fi

# potx drops files into the platform dir.
# the filename is configurable (a bit) but the location isn't so we'll work with what we are given.
POTXDEFAULTFILENAME="$PLATFORMDIR/general.pot"

if [[ -f $POTXDEFAULTFILENAME ]]
then
  echo "Error: $POTXDEFAULTFILENAME found before the start of the export. "
  echo "It will be overwritten and we don't want you to lose it! "
  echo "So please check it. Then either move or delete it."
  exit 1
fi

DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
#TODO - we could check that we are in a site directory based on PLATFORMDIR?

function error_handler() {
	echo "Error occurred in script `basename $0` at line: ${1}."
	echo "Line exited with status: ${2}"
}

trap 'error_handler ${LINENO} $?' ERR

set -o errexit
set -o errtrace
set -o nounset



#Include ALL_MODULE_LIST, ALL_THEME_LIST variables
. $DIR/project_list.sh

echo ""
echo "NON CORE MODULES"
echo ""

## loop over all modules
while IFS=, read -r PROJECT_NAME PROJECT_VERSION
do
  echo $PROJECT_NAME " processing... "

  if [[ -z $PROJECT_VERSION ]]
  then
    VERSION=""
  else
    VERSION="-$PROJECT_VERSION"
  fi

  drush potx --modules=$PROJECT_NAME && RC=$? || RC=$?
  if [[ ! -f $POTXDEFAULTFILENAME ]]
  then
    echo "Error: $POTXDEFAULTFILENAME expected but not found."
  else
    mv $POTXDEFAULTFILENAME $EXPORTDIR/$PROJECT_NAME$VERSION.pot
  fi
  echo "... $PROJECT_NAME complete."

done <<< "$NONCORE_MODULE_LIST"



echo ""
echo "CORE MODULES"
echo ""


CORE_MODULES=
VERSION=
while IFS=, read -r PROJECT_NAME PROJECT_VERSION
do
   CORE_MODULES="$CORE_MODULES,$PROJECT_NAME"
   VERSION=$PROJECT_VERSION
done <<< "$CORE_MODULE_LIST"

# strip the leading ,
CORE_MODULES=${CORE_MODULES:1}

# don't loop over the modules, this misses core/lib, core/theme etc
#drush potx --modules=$CORE_MODULES && RC=$? || RC=$?
# instead take everything from under core
drush potx --folder=$PLATFORMDIR/core && RC=$? || RC=$?
if [[ ! -f $POTXDEFAULTFILENAME ]]
then
    echo "Error: $POTXDEFAULTFILENAME expected but not found."
else
    mv $POTXDEFAULTFILENAME $EXPORTDIR/'drupal-'$VERSION.pot
fi
echo "... core modules complete."



echo ""
echo "THEMES"
echo ""

# urgh - potx doesn't have a --theme option!
while IFS=, read -r PROJECT_NAME PROJECT_VERSION
do
  echo $PROJECT_NAME " processing... "

  if [[ -z $PROJECT_VERSION ]]
  then
    VERSION=""
  else
    VERSION="-$PROJECT_VERSION"
  fi

  if [[ -d $PLATFORMDIR/themes/$PROJECT_NAME ]]
  then
    echo "found theme in $PLATFORMDIR/themes/$PROJECT_NAME"
    THEMELOCATION=$PLATFORMDIR/themes/$PROJECT_NAME
  elif [[ -d $PLATFORMDIR/core/themes/$PROJECT_NAME ]]
  then
    echo "found theme in $PLATFORMDIR/core/themes/$PROJECT_NAME"
    THEMELOCATION=$PLATFORMDIR/core/themes/$PROJECT_NAME
  else
    echo "$PROJECT_NAME theme cannot be found. SKIPPING."
  fi

  if [[ ! -z $THEMELOCATION ]]
  then
    drush potx --folder=$THEMELOCATION && RC=$? || RC=$?
    if [[ ! -f $POTXDEFAULTFILENAME ]]
    then
      echo "Error: $POTXDEFAULTFILENAME expected but not found."
    else
      mv $POTXDEFAULTFILENAME $EXPORTDIR/$PROJECT_NAME$VERSION.pot
    fi
  fi

  echo "... themes complete."

done <<< "$ALL_THEME_LIST"

echo ""
echo "apim_profile"
echo ""

# potx also doesn't have a profile option but we have strings in our profile that
# need translating
echo "apim_profile processing... "

if [[ -d $PLATFORMDIR/profiles/apim_profile ]]
then
  echo "found profile at $PLATFORMDIR/profiles/apim_profile"
  FOLDERLOCATION=$PLATFORMDIR/profiles/apim_profile
else
  echo "apim_profile folder cannot be found. SKIPPING."
fi

if [[ ! -z $FOLDERLOCATION ]]
then
  drush potx --folder=$FOLDERLOCATION && RC=$? || RC=$?
  if [[ ! -f $POTXDEFAULTFILENAME ]]
  then
    echo "Error: $POTXDEFAULTFILENAME expected but not found."
  else
    mv $POTXDEFAULTFILENAME $EXPORTDIR/apim_profile.pot
  fi
fi

echo "... apim_profile complete"

echo "Exports complete."
