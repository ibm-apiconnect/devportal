#!/bin/bash

function error_handler() {
  echo "Error occurred in script `basename $0` at line: ${1}."
  echo "Line exited with status: ${2}"
}

trap 'error_handler ${LINENO} $?' ERR

set -o errexit
set -o errtrace
set -o nounset

# BUILD_TEMPLATE - in case we need to merge back with apim_profile script.
export BUILD_TEMPLATE=$1

export PORTAL_DB_SERVICE_NAME=there.is.no.service
export SERVICE_NAME=there.is.no.service
mkdir -p /var/devportal/storenosync
echo "localhost:127.0.0.1" > /var/devportal/storenosync/cluster_member_list
chown aegir:aegir /var/devportal/storenosync/cluster_member_list

echo statefulset > /etc/devportal/CONTAINER_SET_TYPE
mkdir -p /web/vhost.d /web/pre.d /web/subdir.d
chown -R aegir:aegir /web

mysql=( su - aegir -c "mysql --protocol=socket" )
DEVPORTAL_USER=aegir

# Workaround for using MySQL with overlay2 in Docker; prevents startup issues
find /var/lib/mysqldata/mysql -type f -exec touch {} \;
chown -R mysql:mysql /var/lib/mysqldata/mysql /var/log/mysqllog/mysql

mysqld --wsrep-new-cluster --user=mysql --datadir="/var/lib/mysqldata/mysql" --log-bin="/var/log/mysqllog/mysql/mysql-bin.log" --log-bin-index="/var/log/mysqllog/mysql/mysql-bin.index" &
pid=$!

for i in {30..0}; do
  if echo 'SELECT 1' | "${mysql[@]}" &> /dev/null; then
    break
  fi
  echo 'MySQL init process in progress...'
  sleep 1
done
if [ "$i" = 0 ]; then
  echo >&2 'MySQL init process failed.'
  exit 1
fi

mysql -e "set global pxc_strict_mode=DISABLED; set global show_compatibility_56=ON; set global pxc_maint_transition_period=0;"

bash /tmp/data/portal.sql.sh
rm -f /var/devportal/store
mkdir -p /var/devportal/store
chown -R aegir:aegir /var/devportal

echo 'AAAAB3NzaC1yc2EA8SapSFmyIR3XMvjcz8bKhPOZKkzq88Fl2XHSBcZdU8ZnNDMYum5PDvr7z9bJz0P836CxEgAAAAdzc2gtcnNhADisSD9eGSPdvBqBsS+83ZUTLLc0Lb/kkhBNOftYDCxGAaUORPh5BUDHWFjJbrUAAAAHc3NoLXJzYQCCp4EID3d5UYsC34TrMbHhsJDd4Uk07FgufFBm4RfXQvHyxJoltdOCmBA2CKl0J1j5q9+zMn9iVlbcO/uf7UY4Hd64IZXXiFKyMWkc3ExpsXYoVioYuwRKbl2hAsY7ENeGq105zbmYxjzqSyZhj6/RXOJQjJt40GIQqw+0yX0XFPyqqKhyytjRobAfBvSLabc7w/JVnTxdO/Cqfw==' > /var/devportal/store/enckey

MY_DIR=/opt/ibm

DISTRIBUTION=ibm_apim_devportal
DISTRIBUTION_V8_TGZ_PATTERN_PREFIX=$DISTRIBUTION-8.x-
DISTRIBUTION_V8_TGZ_PATTERN_POSTFIX=.tgz
DISTRIBUTION_V8_TGZ_PATTERN=$DISTRIBUTION_V8_TGZ_PATTERN_PREFIX*$DISTRIBUTION_V8_TGZ_PATTERN_POSTFIX

echo "v8 $DISTRIBUTION_V8_TGZ_PATTERN"
cd $MY_DIR/upgrade
DISTRIBUTION_V8_TGZ=$(ls $DISTRIBUTION_V8_TGZ_PATTERN | head -1)

echo "v8 $DISTRIBUTION_V8_TGZ"

if [[ $BUILD_TEMPLATE == "TRUE" ]]
then
  BUILD_V8_DIST_VER=$(echo $DISTRIBUTION_V8_TGZ | sed 's/ibm_apim_devportal-\(.*\).tgz/\1/')
  SITE_TEMPLATE_V8_NAME=site-template-devportal-$BUILD_V8_DIST_VER.tgz

  echo "v8 template $SITE_TEMPLATE_V8_NAME"

  echo "Need to build new $SITE_TEMPLATE_V8_NAME"
  rm -f $MY_DIR/templates/*8.x*
fi

ln -fs /bin/true /usr/sbin/sendmail

mkdir /web/platforms
mkdir /web/backups
chown aegir:aegir /web/platforms
cp -a /etc/devportal/drush /var/aegir/.drush
mkdir -p /var/aegir/.drush/cache
chown aegir:aegir /var/aegir/.drush/cache

sed -i "s/'master_db.*/'master_db' => 'mysql:\/\/root:root@127.0.0.1',/" /var/aegir/.drush/server_localhost.alias.drushrc.php

#Add the Drupal 8 platform
su $DEVPORTAL_USER -c "PATH=$PATH:$MY_DIR/bin AEGIR_ROOT=/var/aegir $MY_DIR/bin/upgrade_devportal \! -i $MY_DIR/upgrade/$DISTRIBUTION_V8_TGZ -n"

#find the most recent platform
PLATDIR=$(ls -1tr /var/aegir/platforms/ | tail -1)
if [[ -z "$PLATDIR" ]]
then
  echo "ERROR: Could not find a platform to run test on"
  exit 1
fi

echo "Using platform: $PLATDIR"

if [[ -d "/tmp/modules/" ]]
then
  #we are in a module specific build, i.e. not an apim_profile build

  for MODULE_TO_PATCH in `ls /tmp/modules`
  do
    MODULE_NAME=$MODULE_TO_PATCH
    echo "Patching $MODULE_NAME into platform"
    MODULE_DIR="/var/aegir/platforms/$PLATDIR/modules/$MODULE_NAME/"
    if [[ -d $MODULE_DIR ]]
    then
      rm -rfv $MODULE_DIR
      mkdir -v $MODULE_DIR
      cp -rv /tmp/modules/$MODULE_TO_PATCH/* $MODULE_DIR
    else
      echo "Unable to patch module $MODULE_NAME to $MODULE_DIR"
      exit 1
    fi
  done

  MODULE_UNDER_TEST=`cat /tmp/module_name.txt`
  echo "Running unit tests"
  cd /var/aegir/platforms/$PLATDIR/core
  ../vendor/bin/phpunit ../modules/$MODULE_UNDER_TEST/tests/src/Unit/
  echo "Unit tests complete."
fi

if [[ $BUILD_TEMPLATE == "TRUE" ]]
then
  SITE_TEMPLATE_OPTS=""
  #check whether we need to skip translation loading
  if [[ -f /tmp/jenkins ]]
  then
    cat /tmp/jenkins
    source /tmp/jenkins
    if [[ "$LOAD_TRANSLATIONS" == "true" ]]
    then
      echo "Translation files will be loaded."
    else
      echo "Setting option to skip translations in site_template script."
      SITE_TEMPLATE_OPTS="-t"
    fi
  else
    echo "Jenkins data not available"
  fi

  chown aegir:aegir /opt/ibm/templates

  su $DEVPORTAL_USER -c "PATH=$PATH:$MY_DIR/bin AEGIR_ROOT=/var/aegir site_template \! -l -u -v $SITE_TEMPLATE_OPTS $(basename $(ls -1d /var/aegir/platforms/*8.x*))"
else
  echo "Creating site."
  mkdir -p /var/devportal/storenosync
  touch /var/devportal/storenosync/hosts
  chown $DEVPORTAL_USER:$DEVPORTAL_USER  /var/devportal/storenosync/hosts
  su $DEVPORTAL_USER -c "PATH=$PATH:$MY_DIR/bin AEGIR_ROOT=/var/aegir set_apim_host https://test.mgmt"
  su $DEVPORTAL_USER -c "PATH=$PATH:$MY_DIR/bin AEGIR_ROOT=/var/aegir create_site a.b https://localhost email@is.invalid clientid clientsecret" || true
  echo "Site creation complete."
fi

if ! kill -s TERM "$pid" || ! wait "$pid"; then
  echo >&2 'MySQL init process failed.'
  exit 1
fi
