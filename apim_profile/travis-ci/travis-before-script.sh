#!/bin/bash -x
# Simple script to install drupal for travis-ci running.

set -e $DRUPAL_TI_DEBUG

TMP_DIR="$TRAVIS_BUILD_DIR/tmp"

# Ensure the right Drupal version is installed.
if [ -d "$DRUPAL_TI_DRUPAL_DIR" ]
then
	return
fi

# HHVM env is broken: https://github.com/travis-ci/travis-ci/issues/2523.
PHP_VERSION=`phpenv version-name`
if [ "$PHP_VERSION" = "hhvm" ]
then
	# Create sendmail command, which links to /bin/true for HHVM.
	BIN_DIR="$TRAVIS_BUILD_DIR/../drupal_travis/bin"
	mkdir -p "$BIN_DIR"
	ln -s $(which true) "$BIN_DIR/sendmail"
	export PATH="$BIN_DIR:$PATH"
fi

# Create database and install Drupal.
mysql -e "create database $DRUPAL_TI_DB"

# copy installation profile
mkdir -p $TRAVIS_BUILD_DIR/$SITE_NAME/profiles
cp -rf $TRAVIS_BUILD_DIR/apim_profile $TRAVIS_BUILD_DIR/$SITE_NAME/profiles

mkdir -p $TRAVIS_BUILD_DIR/$SITE_NAME/libraries
cp -rf $TRAVIS_BUILD_DIR/libraries/* $TRAVIS_BUILD_DIR/$SITE_NAME/libraries

cp -f $TRAVIS_BUILD_DIR/composer.* $TRAVIS_BUILD_DIR/$SITE_NAME

cd $TRAVIS_BUILD_DIR/$SITE_NAME

composer install

# pull in our modules & theme
composer require drupal/apictest drupal/ghmarkdown drupal/socialblock drupal/featuredcontent drupal/ibm_apim drupal/mail_subscribers drupal/auth_apic drupal/apic_api drupal/apic_app drupal/consumerorg drupal/product drupal/themegenerator drupal/connect_theme --prefer-source

# remove the tags vocabulary file from consumerorg since its in our installation profile
# has to be in consumerorg module for travis tests to pass
rm -f $TRAVIS_BUILD_DIR/$SITE_NAME/modules/consumerorg/config/install/taxonomy.vocabulary.tags.yml

# apply patches to drupal
cd $TRAVIS_BUILD_DIR
for PATCH in $(cd patches && ls -1)
do
 patch -f --directory $TRAVIS_BUILD_DIR/$SITE_NAME -p0 -i ../patches/$PATCH
done

# copy our override files
cp -rf $TRAVIS_BUILD_DIR/overrides/* $TRAVIS_BUILD_DIR/$SITE_NAME

mkdir -p $TMP_DIR
cd $TMP_DIR
chmod a+x ${TRAVIS_BUILD_DIR}/getdeps.sh
mkdir -p $TRAVIS_BUILD_DIR/$SITE_NAME/modules/apic_api/explorer
${TRAVIS_BUILD_DIR}/getdeps.sh ${TMP_DIR} "false" "null" "false"
cp -rf $TMP_DIR/explorer/package/dist/* $TRAVIS_BUILD_DIR/$SITE_NAME/modules/apic_api/explorer
mkdir -p $TRAVIS_BUILD_DIR/$SITE_NAME/modules/ibm_apim/analytics
#cp -rf $TMP_DIR/analytics/package/dist/* $TRAVIS_BUILD_DIR/$SITE_NAME/modules/ibm_apim/analytics

cd $TRAVIS_BUILD_DIR/$SITE_NAME
php -d sendmail_path=$(which true) ~/.composer/vendor/bin/drush.php --yes site-install $DRUPAL_TI_INSTALL_PROFILE --db-url="$DRUPAL_TI_DB_URL" --account-pass=Qwert123
drush use $(pwd)#default

# Clear caches and run a web server.
drupal_ti_clear_caches
drupal_ti_run_server

# Start xvfb and selenium.
#drupal_ti_ensure_xvfb
#drupal_ti_ensure_webdriver