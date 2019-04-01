#!/bin/bash
# This file lives in the travis-ci subdirectory

set -e $DRUPAL_TI_DEBUG

# Run PHPUnit tests.
cd $TRAVIS_BUILD_DIR/$SITE_NAME/core

# Disable warnings about deprecated parts of drupal core
# before we run the tests
export SYMFONY_DEPRECATIONS_HELPER=weak

$TRAVIS_BUILD_DIR/$SITE_NAME/vendor/bin/phpunit $DRUPAL_TI_PHPUNIT_ARGS