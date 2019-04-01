#!/bin/bash
# This file lives in the travis-ci subdirectory

set -e $DRUPAL_TI_DEBUG

cd $TRAVIS_BUILD_DIR/$SITE_NAME

##########################################################
# Linter 1 - Make sure our translation strings are clean #
##########################################################

# unlike our other modules, potx has already been patched by this point
drush --yes en potx
drush potx --folder=profiles/apim_profile
