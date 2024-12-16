#!/bin/bash
APICTESTDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
BASEDIR=$APICTESTDIR/../..

# arg1 : 'jenkins' if this is the drupal8 tgz build
#      : https://siteurl if running on your portal appliance

# arg2 : TRUE to enable mocks. FALSE to disable them.
#      : default is TRUE.

if [[ $1 == 'jenkins' ]]
then
  URL=127.0.0.1
  SITE_PATH=/home/jenkins/workspace/build/devportal/sites/default
  ROOT_PATH=/home/jenkins/workspace/build/devportal
  DEBUG_DUMP_DIR="/tmp/jenkins-build$BUILD_ID-testresults"
  # TODO: remove the paths in the jenkins output - need to get the delimiting correct on this...
  # BEHAT_OPTS=" --format junit --out behat_results --format pretty --format-settings='{\"paths\": false}' --out std "
  BEHAT_OPTS=" --colors -vv --format junit --out /tmp/test_results --format pretty --out std --strict "

  echo Installing drupal behat extensions
  cd $BASEDIR/vendor/drupal/drupal-extension
  npm install || true

  patch -p0 --directory=$BASEDIR -i $APICTESTDIR/patches/logout.patch
else
  if [[ -z "$1" ]]
  then
    echo "No site URL supplied"
    exit -4
  fi

  URL=$1
  URL=${URL/https:\/\//}
  URL=${URL/http:\/\//}
  URL=`echo $URL | sed 's/\/$//g'`

  if [[ ! -d /tmp/test_results ]]
  then
    mkdir /tmp/test_results
    chown aegir:aegir /tmp/test_results
  fi
  BEHAT_OPTS=' --colors -vv --strict --format junit --out /tmp/test_results --format pretty --out std '

  DIR=/opt/ibm/bin
  # ! parameter to common.func stops taking control of stdout/err among other things i don't understand, but
  # it stops buggering up the bash shell we run from, which is goodness.
  . ${DIR}/common.func !
  get_url_host_path_site_alias $URL

  SITE_PATH=$(grep site_path $AEGIR_ROOT/.drush/$SITE_ALIAS.alias.drushrc.php | sed "s/.*'site_path' => '\(.*\)',/\1/")
  ROOT_PATH=$(grep root $AEGIR_ROOT/.drush/$SITE_ALIAS.alias.drushrc.php | sed "s/.*'root' => '\(.*\)',/\1/")
  DEBUG_DUMP_DIR="/tmp"

  echo Extracting packaged behat drupal-extensions
  cd $BASEDIR/vendor
  tar -xf $APICTESTDIR/behat/behat-package.tar.gz

  echo Installing drupal behat extensions
  cd $BASEDIR/vendor/drupal/drupal-extension
  npm install || true

  patch -p0 --directory=$BASEDIR -i $APICTESTDIR/patches/logout.patch
fi

## default to using mocks if not explicitly passed in
USE_MOCKS=${2-TRUE}

mkdir -p /tmp/behat_gherkin_cache/v4.4-dev
chmod a+wx /tmp/behat_gherkin_cache/v4.4-dev

cp -f $APICTESTDIR/behat/yaml/behat.yml.template $BASEDIR/behat.yml
sed -i "s@PLATFORMDIR@$ROOT_PATH@" $BASEDIR/behat.yml
sed -i "s@SITEURL@https://$URL@" $BASEDIR/behat.yml
sed -i "s@SITEDIR@$SITE_PATH@" $BASEDIR/behat.yml
sed -i "s@DEBUGDUMPDIR@$DEBUG_DUMP_DIR@" $BASEDIR/behat.yml
sed -i "s@useMockServices: FALSE@useMockServices: $USE_MOCKS@" $BASEDIR/behat.yml

# If we have a testdata/scenario.json file matching the site URL to test against
# then we should use that data in our behat run. Any fullstack tests would then
# have real data to work with.
# First, convert the site url to a filename.json
TESTDATANAME=`echo $URL | sed 's%https://%%g' | sed 's%/%_%g'`
if [[ -e $BASEDIR/modules/apictest/testdata/$TESTDATANAME.json ]]
then
  echo "Found testdata scenario file for the specified siteurl. Real data can be used in the behat tests for this run.\n"
  sed -i "s@testDataScenario: mocked@testDataScenario: $TESTDATANAME@" $BASEDIR/behat.yml
fi

echo "Disabling site caches for the test run!"
echo "Running : $APICTESTDIR/disable-caches.sh $SITE_PATH"
$APICTESTDIR/disable-caches.sh $SITE_PATH

export APICTEST_BASEDIR=$BASEDIR
export APICTEST_BEHAT_OPTS=$BEHAT_OPTS

# remove big pipe as it prevents anything javascript related from rendering (e.g. drupal messages)
# remove honeypot as it gets in the way of form submissions
# remove check_dns so we can use @example.com email addresses in auth_apic tests
cd $SITE_PATH
drush pm-uninstall big_pipe honeypot check_dns r4032login

echo "Enabling errors and warnings level debug"
drush config-set system.logging error_level verbose

cd $APICTESTDIR

echo "Test setup exporting:"
echo "export APICTEST_BASEDIR=$APICTEST_BASEDIR"
echo "export APICTEST_BEHAT_OPTS=\"$APICTEST_BEHAT_OPTS\""
echo "Set the above before calling runbehat.sh"

echo "Test setup finished"
