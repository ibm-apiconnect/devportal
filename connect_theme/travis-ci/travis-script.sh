#!/bin/bash
# This file lives in the travis-ci subdirectory

set -e $DRUPAL_TI_DEBUG

# Run PHPUnit tests and submit code coverage statistics.
drupal_ti_ensure_drupal

cd $DRUPAL_TI_DRUPAL_DIR/core

$DRUPAL_TI_DRUPAL_DIR/vendor/bin/phpunit $DRUPAL_TI_PHPUNIT_ARGS