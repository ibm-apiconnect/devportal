#!/bin/bash

function error_handler() {
  echo "Error occurred in script `basename $0` at line: ${1}."
  echo "Line exited with status: ${2}"
}

trap 'error_handler ${LINENO} $?' ERR

set -o errexit
set -o errtrace
set -o nounset

if [ -f /tmp/module_name.txt ]; then
 export MODULE_NAME=`cat /tmp/module_name.txt`
fi

export PATH=/usr/local/bin:$PATH

export PORTAL_DB_SERVICE_NAME=there.is.no.service
export SERVICE_NAME=there.is.no.service
export WEB_HOST=127.0.0.1
export CONTAINER=admin

echo statefulset > /etc/devportal/CONTAINER_SET_TYPE
MY_DIR=/opt/ibm
cp /etc/devportal/nginx_ssl.conf /web/pre.d/
sed -i 's/\*:4443/*:443/' /var/aegir/config/server_master/nginx/vhost.d/127.0.0.1
sed -i 's+127.0.0.1:9000+unix:/var/run/pid/php7.1-fpm.sock+' /var/aegir/config/includes/nginx_vhost_common.conf
(cat /etc/nginx/nginx.conf;echo;echo "user aegir;" ) > /etc/nginx/nginx.conf.tmp
mv -f /etc/nginx/nginx.conf.tmp /etc/nginx/nginx.conf

sed -i 's/access_log.*/access_log \/dev\/stdout main;\n    error_log \/dev\/stderr;/' /etc/nginx/nginx.conf

sed -i -e 's/^listen\.owner.*/listen.owner = aegir/' -e 's/^listen\.group.*/;listen.group = aegir/' /etc/opt/rh/rh-php72/php-fpm.d/www.conf

sed -i -e 's/4443/443/' -e '/ssl_dhparam/d' -e 's/gulag/limreq/' -e 's/brotli_static/#brotli_static/' /web/pre.d/nginx_ssl.conf

sed -i -e 's/gulag/limreq/' -e 's/more_set_headers/#more_set_headers/' /etc/nginx/conf.d/aegir.conf

cat /etc/nginx/conf.d/aegir.conf

mkdir /web/ssl
openssl req -batch -x509 -nodes -days 365000 -newkey rsa:2048 -keyout /etc/nginx/ssl/hostmaster.key -out /etc/nginx/ssl/hostmaster.crt -sha256

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

# need to remove this as there is another one somewhere and nginx barfs if there are 2
sed -i '/log_format/,+4d' /etc/nginx/conf.d/aegir.conf

/usr/sbin/nginx &
npid=$!

su aegir -c "scl enable rh-php72 -- php-fpm -OF" &
ppid=$!

echo "Running test setup"
#find the most recent platform
PLATDIR=$(ls -1tr /var/aegir/platforms/ | tail -1)
if [[ -z "$PLATDIR" ]]
then
  echo "ERROR: Could not find a platform to run test on"
  exit 1
fi

echo "Using platform: $PLATDIR"

cd /var/aegir/platforms/$PLATDIR

chmod a+x modules/apictest/setupbehat.sh
chmod a+x modules/apictest/runbehat.sh
# capture response of the tests so we can get the logs if we need to
set +e

export BUILD_ID=build
export CONTAINER=admin
echo "Running standard behat tests"
chmod -R o+rx /root
su aegir -c "drush @127.0.0.1 upwd admin --password='Qwert123'"
su aegir -c "drush @127.0.0.1 -y en dblog"
su aegir -c "drush --verbose @127.0.0.1 pm-uninstall big_pipe dynamic_page_cache honeypot"
su aegir -c "drush @127.0.0.1 state-set ibm_apim.site_namespace \"1234.5678\""
su aegir -c "drush @127.0.0.1 state-set ibm_apim.site_client_secret foo"
su aegir -c "drush @127.0.0.1 state-set ibm_apim.site_client_id bar"
su aegir -c "drush @127.0.0.1 createkey bar"
su aegir -c "drush @127.0.0.1 config-set user.settings verify_mail 0"
su aegir -c "drush @127.0.0.1 uconfile \"testdata/siteconfig.json\""
echo "Translation linting start"
su aegir -c "drush @127.0.0.1 en potx"
su aegir -c "drush @127.0.0.1 potx --modules=$MODULE_NAME"
echo "Translation linting complete"
echo "Warming caches"
su aegir -c "drush @127.0.0.1 warmer:setcdn https://127.0.0.1"
su aegir -c "drush @127.0.0.1 warmer:enqueue entity,cdn"
#do any entity updates
su aegir -c "drush @127.0.0.1 updatedb --entity-updates" || true

#check for use of deprecated functions (but ignore errors for now)
curl -O -L https://github.com/mglaman/drupal-check/releases/latest/download/drupal-check.phar
mv drupal-check.phar /var/aegir/platforms/drupal-check
chmod +x /var/aegir/platforms/drupal-check
/var/aegir/platforms/drupal-check ./modules/$MODULE_NAME || true

if [ -d modules/$MODULE_NAME/features ]; then

  if [ -z $MODULE_NAME ]; then
    export TESTS_TO_RUN="" # All tests
  else
    export TESTS_TO_RUN=" modules/$MODULE_NAME/features"
    echo "Running $MODULE_NAME tests"
  fi

  su aegir -c "export PATH=/usr/local/bin:$PATH && . modules/apictest/setupbehat.sh 127.0.0.1 TRUE && ./runbehat.sh $TESTS_TO_RUN" && RC=$? || RC=$?

  if [[ "$RC" -ne 0 ]]
  then
    echo "Dumping dblog"
    su aegir -c "drush @127.0.0.1 watchdog-show --count=1000 --extended" >/tmp/watchdog/watchdog.log 2>&1

    echo "Copying any html failure pages to /tmp/behat"
    while read FILE
    do
      cp -a $FILE /tmp/behat/
    done < <(find /tmp/behat-* -type d -maxdepth 1)

    exit "$RC"
  fi
else
  echo "No features directory, so no behat tests being run"
fi
