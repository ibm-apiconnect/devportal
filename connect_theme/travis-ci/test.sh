#!/bin/bash
# This file lives in the travis-ci subdirectory

set -e $DRUPAL_TI_DEBUG

# Run PHPUnit tests and submit code coverage statistics.
# Removed as these have been done in travis-before-script.sh
#drupal_ti_ensure_drupal
#drupal_ti_ensure_module_linked
cd $DRUPAL_TI_DRUPAL_DIR/core

$DRUPAL_TI_DRUPAL_DIR/vendor/bin/phpunit $DRUPAL_TI_PHPUNIT_ARGS

# work around issue in travis builds where node path can't be found.
#echo "EVIL HACK TO WORK AROUND ISSUE INSTALLING NPM MODULES"
#mkdir -p $TRAVIS_BUILD_DIR/vendor/drupal/drupal-extension
#mkdir $DRUPAL_TI_DRUPAL_DIR/vendor/drupal/drupal-extension/node_modules
#ln -s $DRUPAL_TI_DRUPAL_DIR/vendor/drupal/drupal-extension/node_modules $TRAVIS_BUILD_DIR/vendor/drupal/drupal-extension/node_modules


# install drupal-extension npm modules (zombie)
#cd $DRUPAL_TI_DRUPAL_DIR/vendor/drupal/drupal-extension
#npm install

# Run behat ourselves too
#cd $DRUPAL_TI_DRUPAL_DIR

# Our behat.yml.travis file contains variables that need replacing so do that
# in the same way that drupal-ti would have done it
# Create a dynamic script.
#{
#  echo "#!/bin/bash"
#  echo "cat <<EOF > $DRUPAL_TI_BEHAT_YML"
#  cat "$DRUPAL_TI_BEHAT_YML"
#  echo "EOF"
#} >> .behat.yml.sh

# Execute the script.
#. .behat.yml.sh

# And now we can run behat
#./vendor/bin/behat --config=$DRUPAL_TI_BEHAT_YML "${ARGS[@]}"