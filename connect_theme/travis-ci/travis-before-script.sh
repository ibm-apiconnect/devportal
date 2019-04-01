#!/bin/bash
# This file lives in the travis-ci subdirectory

set -e $DRUPAL_TI_DEBUG

# Ensure the right Drupal version is installed.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

cd "$DRUPAL_TI_DRUPAL_DIR"

drush --yes en simpletest

mkdir -p $TRAVIS_BUILD_DIR/$SITE_NAME/themes/$DRUPAL_TI_MODULE_NAME

ln -sf "$TRAVIS_BUILD_DIR" "$TRAVIS_BUILD_DIR/$SITE_NAME/themes/$DRUPAL_TI_MODULE_NAME"

# Require that minimal profile enables this theme
cp $TRAVIS_BUILD_DIR/travis-ci/minimal.info.yml $DRUPAL_TI_DRUPAL_DIR/core/profiles/minimal