#!/bin/bash

function error_handler() {
  echo "Error occurred in script `basename $0` at line: ${1}."
  echo "Line exited with status: ${2}"
}

trap 'error_handler ${LINENO} $?' ERR

set -o errexit
set -o errtrace
set -o nounset

export PORTAL_DB_SERVICE_NAME=there.is.no.service
export SERVICE_NAME=there.is.no.service
export CONTAINER=admin
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

MY_DIR=/opt/ibm

DISTRIBUTION=ibm_apim_devportal
DISTRIBUTION_V8_TGZ_PATTERN_PREFIX=$DISTRIBUTION-8.x-
DISTRIBUTION_V8_TGZ_PATTERN_POSTFIX=.tgz
DISTRIBUTION_V8_TGZ_PATTERN=$DISTRIBUTION_V8_TGZ_PATTERN_PREFIX*$DISTRIBUTION_V8_TGZ_PATTERN_POSTFIX

echo "v8 $DISTRIBUTION_V8_TGZ_PATTERN"
cd $MY_DIR/upgrade
DISTRIBUTION_V8_TGZ=$(ls $DISTRIBUTION_V8_TGZ_PATTERN | head -1)

echo "v8 $DISTRIBUTION_V8_TGZ"

BUILD_V8_DIST_VER=$(echo $DISTRIBUTION_V8_TGZ | sed 's/ibm_apim_devportal-\(.*\).tgz/\1/')
SITE_TEMPLATE_V8_NAME=site-template-devportal-$BUILD_V8_DIST_VER.tgz

echo "v8 template $SITE_TEMPLATE_V8_NAME"

echo "Need to build new $SITE_TEMPLATE_V8_NAME"
rm -f $MY_DIR/templates/*8.x*

ln -fs /bin/true /usr/sbin/sendmail

mkdir /web/platforms
mkdir /web/backups
chown aegir:aegir /web/platforms
cp -a /etc/devportal/drush /var/aegir/.drush
mkdir -p /var/aegir/.drush/cache
chown aegir:aegir /var/aegir/.drush/cache

sed -i "s/'master_db.*/'master_db' => 'mysql:\/\/root:root@127.0.0.1',/" /var/aegir/.drush/server_localhost.alias.drushrc.php

#Add the Drupal 8 platform
su $DEVPORTAL_USER -c "PATH=$PATH:$MY_DIR/bin AEGIR_ROOT=/var/aegir $MY_DIR/bin/upgrade_devportal -i $MY_DIR/upgrade/$DISTRIBUTION_V8_TGZ -n"

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

su $DEVPORTAL_USER -c "PATH=$PATH:$MY_DIR/bin AEGIR_ROOT=/var/aegir site_template -l -u -v $SITE_TEMPLATE_OPTS $(basename $(ls -1d /var/aegir/platforms/*8.x*))"

if ! kill -s TERM "$pid" || ! wait "$pid"; then
  echo >&2 'MySQL init process failed.'
  exit 1
fi
