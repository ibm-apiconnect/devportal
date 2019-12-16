#!/bin/bash

function error_handler() {
  echo "Error occurred in script `basename $0` at line: ${1}." >&2
  echo "Line exited with status: ${2}" >&2
}

trap 'error_handler ${LINENO} $?' ERR

set -o errexit
set -o errtrace
set -o nounset

NVM_DIR="$HOME/.nvm"
. "$NVM_DIR/nvm.sh" \
nvm use --lts

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

sed -i 's/expose_php =.*/expose_php = Off/g' /etc/opt/rh/rh-php72/php.ini

mkdir /web/ssl
openssl req -batch -x509 -nodes -days 365000 -newkey rsa:2048 -keyout /etc/nginx/ssl/hostmaster.key -out /etc/nginx/ssl/hostmaster.crt -sha256

mysql=( su - aegir -c "mysql --protocol=socket" )
DEVPORTAL_USER=aegir

# Workaround for using MySQL with overlay2 in Docker; prevents startup issues
find /var/lib/mysqldata/mysql -type f -exec touch {} \;
chown -R mysql:mysql /var/lib/mysqldata/mysql /var/log/mysqllog/mysql

mysqld --wsrep-new-cluster --user=mysql --datadir="/var/lib/mysqldata/mysql" --log-bin="/var/log/mysqllog/mysql/mysql-bin.log" --log-bin-index="/var/log/mysqllog/mysql/mysql-bin.index"  &
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
cd /var/aegir/platforms

#find the most recent platform
PLATDIR=$(ls -1tr | tail -1)
if [[ -z "$PLATDIR" ]]
then
	echo "ERROR: Could not find a platform to run test on"
	exit 1
fi

echo "Running tests for platform: $PLATDIR"
cd $PLATDIR

chmod a+x modules/apictest/setupbehat.sh
chmod a+x modules/apictest/runbehat.sh
# capture response of the tests so we can get the logs if we need to
set +e

export BUILD_ID=build
echo "Running standard behat tests"
chmod -R o+rx /root
su aegir -c "drush @127.0.0.1 upwd admin --password='Qwert123'"
su aegir -c "drush @127.0.0.1 -y en dblog"
su aegir -c "drush --verbose @127.0.0.1 pm-uninstall big_pipe dynamic_page_cache honeypot"
su aegir -c "drush @127.0.0.1 state-set ibm_apim.site_namespace \"1234.5678\""
su aegir -c "drush @127.0.0.1 state-set ibm_apim.site_client_secret foo"
su aegir -c "drush @127.0.0.1 state-set ibm_apim.site_client_id bar"
su aegir -c "drush @127.0.0.1 config-set user.settings verify_mail 0"
su aegir -c "drush @127.0.0.1 createkey bar"
su aegir -c "drush @127.0.0.1 ucon \"{\\\"catalog\\\":{\\\"type\\\":\\\"catalog\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"37b2d814-fdfa-44d3-a34e-0bbd2326b671\\\",\\\"name\\\":\\\"test\\\",\\\"title\\\":\\\"test\\\",\\\"summary\\\":null,\\\"shadow_id\\\":\\\"e1c8f200-2116-11e8-9c30-6f436fab2280\\\",\\\"shadow\\\":false,\\\"owner_url\\\":\\\"\\\/api\\\/user-registries\\\/68935f56-acf8-4705-8c09-e3c73f9adf8d\\\/b343e890-0d28-4097-b6f8-69196c101c60\\\/users\\\/d1fa58a8-e5c7-4e90-bdfa-027f2d97760c\\\",\\\"metadata\\\":null,\\\"created_at\\\":\\\"2018-03-06T08:17:58.325Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:17:58.325Z\\\",\\\"org_url\\\":\\\"\\\/api\\\/orgs\\\/59c2c2ce-f691-49ff-bca7-69332cde79e0\\\",\\\"url\\\":\\\"\\\/api\\\/catalogs\\\/59c2c2ce-f691-49ff-bca7-69332cde79e0\\\/37b2d814-fdfa-44d3-a34e-0bbd2326b671\\\"},\\\"catalog_setting\\\":{\\\"type\\\":\\\"catalog_setting\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"name\\\":\\\"catalog-setting\\\",\\\"title\\\":null,\\\"summary\\\":null,\\\"shadow_id\\\":\\\"e1f77c11-2116-11e8-bea7-af54441cd0b1\\\",\\\"shadow\\\":false,\\\"application_lifecycle\\\":{\\\"enabled\\\":false},\\\"consumer_self_service_onboarding\\\":true,\\\"custom_notification_templates_enabled\\\":false,\\\"email_sender\\\":{\\\"custom\\\":false,\\\"name\\\":null,\\\"address\\\":null},\\\"hash_client_secret\\\":false,\\\"invitation_ttl\\\":172800,\\\"portal\\\":{\\\"type\\\":\\\"drupal\\\",\\\"endpoint\\\":\\\"https:\\\/\\\/portal.cd.argo-sl.dev.ciondemand.com\\\/myorg\\\/test\\\",\\\"portal_service_url\\\":\\\"\\\/api\\\/orgs\\\/59c2c2ce-f691-49ff-bca7-69332cde79e0\\\/portal-services\\\/94bfdd82-df90-430a-8eed-695e3fce66d3\\\"},\\\"product_lifecycle_approvals\\\":null,\\\"production_mode\\\":false,\\\"spaces_enabled\\\":false,\\\"task_self_approval\\\":false,\\\"user_registry_default_url\\\":\\\"\\\/api\\\/catalogs\\\/59c2c2ce-f691-49ff-bca7-69332cde79e0\\\/37b2d814-fdfa-44d3-a34e-0bbd2326b671\\\/catalog-user-registries\\\/c83918a1-a377-45d2-9cbf-41b893a49c46\\\",\\\"v5_endpoint_substitution_behavior\\\":{\\\"enabled\\\":null,\\\"base_endpoints\\\":null,\\\"unenforced_api_base_endpoint\\\":null},\\\"vanity_api_endpoint\\\":{\\\"enabled\\\":false,\\\"base_endpoint\\\":null},\\\"metadata\\\":null,\\\"created_at\\\":\\\"2018-03-06T08:17:59.817Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:18:29.725Z\\\",\\\"org_url\\\":\\\"\\\/api\\\/orgs\\\/59c2c2ce-f691-49ff-bca7-69332cde79e0\\\",\\\"catalog_url\\\":\\\"\\\/api\\\/catalogs\\\/59c2c2ce-f691-49ff-bca7-69332cde79e0\\\/37b2d814-fdfa-44d3-a34e-0bbd2326b671\\\",\\\"url\\\":\\\"\\\/api\\\/catalogs\\\/59c2c2ce-f691-49ff-bca7-69332cde79e0\\\/37b2d814-fdfa-44d3-a34e-0bbd2326b671\\\/settings\\\"},\\\"configured_catalog_user_registries\\\":[{\\\"type\\\":\\\"configured_catalog_user_registry\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"c83918a1-a377-45d2-9cbf-41b893a49c46\\\",\\\"name\\\":\\\"test-catalog\\\",\\\"title\\\":\\\"test Catalog User Registry\\\",\\\"summary\\\":\\\"test Catalog User Registry\\\",\\\"shadow_id\\\":\\\"e1f75500-2116-11e8-88ca-fcb833641144\\\",\\\"shadow\\\":false,\\\"original_id\\\":\\\"6fd14e95-8d07-4ec1-8fdb-a27d20c5141e\\\",\\\"owned\\\":true,\\\"integration_url\\\":\\\"\\\/api\\\/cloud\\\/integrations\\\/user-registry\\\/3be0df78-b5cd-4497-a393-782082950613\\\",\\\"registry_type\\\":\\\"lur\\\",\\\"user_managed\\\":true,\\\"user_registry_managed\\\":true,\\\"onboarding\\\":\\\"active\\\",\\\"case_sensitive\\\":true,\\\"identity_providers\\\":[{\\\"name\\\":\\\"test-idp\\\",\\\"title\\\":\\\"test Identity Provider\\\"}],\\\"metadata\\\":{\\\"id\\\":\\\"c84463a4-858a-41b8-9215-58139ac274c2\\\",\\\"name\\\":\\\"d99e7b91-7139-45b7-9753-4c8e5748182d\\\"},\\\"created_at\\\":\\\"2018-03-06T08:17:59.690Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:17:59.690Z\\\",\\\"org_url\\\":\\\"\\\/api\\\/orgs\\\/59c2c2ce-f691-49ff-bca7-69332cde79e0\\\",\\\"catalog_url\\\":\\\"\\\/api\\\/catalogs\\\/59c2c2ce-f691-49ff-bca7-69332cde79e0\\\/37b2d814-fdfa-44d3-a34e-0bbd2326b671\\\",\\\"user_registry_url\\\":\\\"\\\/api\\\/user-registries\\\/59c2c2ce-f691-49ff-bca7-69332cde79e0\\\/6fd14e95-8d07-4ec1-8fdb-a27d20c5141e\\\",\\\"url\\\":\\\"\\\/api\\\/catalogs\\\/59c2c2ce-f691-49ff-bca7-69332cde79e0\\\/37b2d814-fdfa-44d3-a34e-0bbd2326b671\\\/catalog-user-registries\\\/c83918a1-a377-45d2-9cbf-41b893a49c46\\\"}],\\\"permissions\\\":[{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"d8d1880e-ebd9-4ee2-baeb-2f82ebed70ac\\\",\\\"name\\\":\\\"member:manage\\\",\\\"title\\\":\\\"member:manage\\\",\\\"shadow_id\\\":\\\"b79400c0-2115-11e8-9fa2-22b7048ee06a\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"org\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.270Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.270Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/org\\\/d8d1880e-ebd9-4ee2-baeb-2f82ebed70ac\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"3e1652f1-e2f5-4d4f-9f58-2e11c312a148\\\",\\\"name\\\":\\\"member:view\\\",\\\"title\\\":\\\"member:view\\\",\\\"shadow_id\\\":\\\"b79204f0-2115-11e8-b8af-cc95ccd1f2d0\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"org\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.255Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.255Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/org\\\/3e1652f1-e2f5-4d4f-9f58-2e11c312a148\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"752e7ec8-583c-4ae6-8c58-a2e56116e38a\\\",\\\"name\\\":\\\"settings:manage\\\",\\\"title\\\":\\\"settings:manage\\\",\\\"shadow_id\\\":\\\"b78fe210-2115-11e8-a057-d34d49377135\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"org\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.242Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.242Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/org\\\/752e7ec8-583c-4ae6-8c58-a2e56116e38a\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"b0e7b96f-f49b-4c3b-a0c5-d72042eb4372\\\",\\\"name\\\":\\\"settings:view\\\",\\\"title\\\":\\\"settings:view\\\",\\\"shadow_id\\\":\\\"b78d4a00-2115-11e8-9916-6ce395809341\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"org\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.226Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.226Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/org\\\/b0e7b96f-f49b-4c3b-a0c5-d72042eb4372\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"01b72da2-ad89-4b2a-b2b2-c245151c7732\\\",\\\"name\\\":\\\"topology:manage\\\",\\\"title\\\":\\\"topology:manage\\\",\\\"shadow_id\\\":\\\"b79894a0-2115-11e8-8a44-e039ad7013d4\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"org\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.298Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.298Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/org\\\/01b72da2-ad89-4b2a-b2b2-c245151c7732\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"e9b21e62-96ad-4bca-b00d-702bf00d06f6\\\",\\\"name\\\":\\\"topology:view\\\",\\\"title\\\":\\\"topology:view\\\",\\\"shadow_id\\\":\\\"b7964ab0-2115-11e8-a153-94653a7db269\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"org\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.284Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.284Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/org\\\/e9b21e62-96ad-4bca-b00d-702bf00d06f6\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"e120f488-5921-45a8-a936-d34d4686ab8c\\\",\\\"name\\\":\\\"view\\\",\\\"title\\\":\\\"view\\\",\\\"shadow_id\\\":\\\"b79d0170-2115-11e8-82f3-9653f352898b\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"org\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.313Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.313Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/org\\\/e120f488-5921-45a8-a936-d34d4686ab8c\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"81c86a24-c9e1-4fb5-a4e2-a137eef079c7\\\",\\\"name\\\":\\\"app-analytics:view\\\",\\\"title\\\":\\\"app-analytics:view\\\",\\\"shadow_id\\\":\\\"b7eb2170-2115-11e8-aed8-b3f0b37e9153\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"consumer\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.842Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.842Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/consumer\\\/81c86a24-c9e1-4fb5-a4e2-a137eef079c7\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"e63e4ebc-8787-4901-8f42-16dba9aa8edd\\\",\\\"name\\\":\\\"app-dev:manage\\\",\\\"title\\\":\\\"app-dev:manage\\\",\\\"shadow_id\\\":\\\"b7e4dfe0-2115-11e8-8dbf-eea0bddc70dc\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"consumer\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.801Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.801Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/consumer\\\/e63e4ebc-8787-4901-8f42-16dba9aa8edd\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"47d90d81-5080-4882-b270-778334fdbf50\\\",\\\"name\\\":\\\"app:manage\\\",\\\"title\\\":\\\"app:manage\\\",\\\"shadow_id\\\":\\\"b7e61860-2115-11e8-9233-6810ca326298\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"consumer\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.810Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.810Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/consumer\\\/47d90d81-5080-4882-b270-778334fdbf50\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"e20ca0b3-a2f0-419f-9a99-177c6050c98d\\\",\\\"name\\\":\\\"app:view\\\",\\\"title\\\":\\\"app:view\\\",\\\"shadow_id\\\":\\\"b7e3a760-2115-11e8-ad7a-deb6f2d419a3\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"consumer\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.794Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.794Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/consumer\\\/e20ca0b3-a2f0-419f-9a99-177c6050c98d\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"2c9d4ee3-9348-4159-972f-69ea1ffda5ab\\\",\\\"name\\\":\\\"product:view\\\",\\\"title\\\":\\\"product:view\\\",\\\"shadow_id\\\":\\\"b7e247d0-2115-11e8-93a6-fa8174340361\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"consumer\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.785Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.785Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/consumer\\\/2c9d4ee3-9348-4159-972f-69ea1ffda5ab\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"bc1a91ee-ed73-4a79-bfa1-3dae1f277d08\\\",\\\"name\\\":\\\"subscription:manage\\\",\\\"title\\\":\\\"subscription:manage\\\",\\\"shadow_id\\\":\\\"b7e99ad0-2115-11e8-9d29-a971869a16d8\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"consumer\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.831Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.831Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/consumer\\\/bc1a91ee-ed73-4a79-bfa1-3dae1f277d08\\\"},{\\\"type\\\":\\\"permission\\\",\\\"api_version\\\":\\\"2.0.0\\\",\\\"id\\\":\\\"04ee1b04-4f3a-4061-8b99-5fce9a4f346c\\\",\\\"name\\\":\\\"subscription:view\\\",\\\"title\\\":\\\"subscription:view\\\",\\\"shadow_id\\\":\\\"b7e79f00-2115-11e8-9162-a9cec62e6f8e\\\",\\\"shadow\\\":false,\\\"permission_type\\\":\\\"consumer\\\",\\\"created_at\\\":\\\"2018-03-06T08:09:39.819Z\\\",\\\"updated_at\\\":\\\"2018-03-06T08:09:39.819Z\\\",\\\"url\\\":\\\"\\\/consumer-api\\\/consumer\\\/permissions\\\/consumer\\\/04ee1b04-4f3a-4061-8b99-5fce9a4f346c\\\"}]}\" "

#Install iptables
yum -y update
yum -y install iptables sudo

echo "aegir	ALL=(ALL) 	NOPASSWD: ALL" >> /etc/sudoers

(su aegir <<- EOF
  export NVM_DIR=/root/.nvm
  . $NVM_DIR/nvm.sh
  nvm use --lts
  . modules/apictest/setupbehat.sh 127.0.0.1 TRUE

  #Turn off the network by blocking all external network access
  sudo iptables -A OUTPUT -s 127.0.0.1 -j ACCEPT
  sudo iptables -A INPUT -s 127.0.0.1 -j ACCEPT
  sudo iptables -A INPUT -j DROP
  sudo iptables -A OUTPUT -j DROP

  echo "All network connectivity turned off"
  ./rununittests.sh

  ./runbehat.sh
EOF
) && RC=$? || RC=$?

if [[ "$RC" -ne 0 ]]
then
  echo "Dumping dblog"
  su aegir -c "drush @127.0.0.1 watchdog-show --count=1000 --extended" >/tmp/watchdog/watchdog.log 2>&1

  echo "Copying any html failure pages to /tmp/behat"
  while read FILE
  do
    cp -a $FILE /tmp/behat/
  done < <(find /tmp/behat-* -type d -maxdepth 1)
  mkdir /tmp/behat/test3/

  exit "$RC"
fi
