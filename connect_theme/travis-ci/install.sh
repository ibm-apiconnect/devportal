#!/bin/bash -x
# This file lives in the travis-ci subdirectory

set -e $DRUPAL_TI_DEBUG

# The code below is a modified copy-paste of drupal_ti_ensure_drupal from https://github.com/LionsAd/drupal_ti/blob/master/functions/drupal.sh
# and drupal_ti_install_drupal from https://github.com/LionsAd/drupal_ti/blob/master/environments/drupal-8.sh
# We've done this because we need to modify the installation profile. See https://github.ibm.com/apimesh/devportal-auth-apic/pull/108
# for the explanation.

if [[ -d "$DRUPAL_TI_DRUPAL_DIR" ]]
then
    return
fi

# HHVM env is broken: https://github.com/travis-ci/travis-ci/issues/2523.
PHP_VERSION=`phpenv version-name`
if [[ "$PHP_VERSION" = "hhvm" ]]
then
    echo "IN HHVM BLOCK"
    # Create sendmail command, which links to /bin/true for HHVM.
    BIN_DIR="$TRAVIS_BUILD_DIR/../drupal_travis/bin"
    mkdir -p "$BIN_DIR"
    ln -s $(which true) "$BIN_DIR/sendmail"
    export PATH="$BIN_DIR:$PATH"
fi

# Create database and install Drupal.
mysql -e "create database $DRUPAL_TI_DB"

mkdir -p "$DRUPAL_TI_DRUPAL_BASE"
cd "$DRUPAL_TI_DRUPAL_BASE"

git clone --depth 1 --branch "$DRUPAL_TI_CORE_BRANCH" http://git.drupal.org/project/drupal.git
cd drupal
composer install

# Ensure the module is linked into the codebase.
# code from drupal_ti_ensure_module_linked
# Ensure we are in the right directory.
cd "$DRUPAL_TI_DRUPAL_DIR"
if [[ -L "$DRUPAL_TI_THEMES_PATH/$DRUPAL_TI_MODULE_NAME" ]]
then
    #return
    echo "symlink to $DRUPAL_TI_THEMES_PATH/$DRUPAL_TI_MODULE_NAME in place."
else
    echo "***  NO symlink to $DRUPAL_TI_THEMES_PATH/$DRUPAL_TI_MODULE_NAME "
fi

# Explicitly set the repository as 0 and 1 override the default repository as
# the local repository must be the first in the list.
composer config repositories.0 path $TRAVIS_BUILD_DIR
composer config repositories.1 composer https://packages.drupal.org/8
composer require drupal/$DRUPAL_TI_MODULE_NAME *@dev

#fi

#cd "$DRUPAL_TI_DRUPAL_DIR"

# require our dependencies explicitly.
composer require drupal/bootstrap

#Enable this module
#cd "$DRUPAL_TI_DRUPAL_DIR"
#drush --yes en $DRUPAL_TI_MODULE_NAME

# APIC insertion here : we need to modify the minimal installation profile so that our behat tests
# have an administrator role to use (and probably other stuff in the future)
# pull in our modules & theme

cp -rf $DRUPAL_TI_DRUPAL_DIR/core/profiles/minimal $DRUPAL_TI_DRUPAL_DIR/profiles/portalminimal
rm -f $DRUPAL_TI_DRUPAL_DIR/profiles/portalminimal/minimal.*
cp $DRUPAL_TI_DRUPAL_DIR/core/profiles/standard/config/install/user.role.administrator.yml $DRUPAL_TI_DRUPAL_DIR/profiles/portalminimal/config/install/
cp -rf $TRAVIS_BUILD_DIR/travis-ci/portalminimal.* $DRUPAL_TI_DRUPAL_DIR/profiles/portalminimal

php -d sendmail_path=$(which true) ~/.composer/vendor/bin/drush.php --yes -v site-install portalminimal --db-url="$DRUPAL_TI_DB_URL" --debug
cd $DRUPAL_TI_DRUPAL_DIR/sites/default

drush st --full

drush pm-list --type=module --status=enabled

drush pm-list --type=theme

# clear caches
drush cr

# start a web server on port 8080, run in the background; wait for initialization
LOGDIR="/tmp/travis-logs"
LOGFILE="$LOGDIR/webserver.log"

mkdir "$LOGDIR"
echo "Starting webserver, logging to $LOGFILE and stdout"
echo "***** portal tests webserver logs start ******" > $LOGFILE
{ drush runserver "$DRUPAL_TI_WEBSERVER_URL:$DRUPAL_TI_WEBSERVER_PORT" 2>&1 | tee $LOGFILE ; } &

echo "waiting for webserver to start"

PORT="$DRUPAL_TI_WEBSERVER_PORT"

COUNT=0
# Try to connect to the port via netcat.
# netstat is not available on the container builds.
until nc -w 1 localhost "$PORT"
do
    sleep 1
    COUNT=$[COUNT+1]
    echo "attempted to contact webserver: $COUNT"
    if [ $COUNT -gt 10 ]
    then
        echo "Error: Timeout while waiting for webserver." 1>&2
        exit 1
    fi
done

# clear caches
drush cr

echo "webserver now online and ready to test... go nuts at it."

