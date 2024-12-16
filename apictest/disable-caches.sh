#!/bin/bash

CURRENT_USER=$(id | sed 's/uid.*(\(.*\)) gid.*/\1/')
if [ $CURRENT_USER != 'aegir' ] && [ $CURRENT_USER != 'jenkins' ];
then
  echo "ERROR: Script must be run as aegir or jenkins."
  exit 1
fi

APICTESTDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
BASEDIR=$APICTESTDIR/../..

# arg 1 : the site path pointing to platform-dir/sites/sitedir
if [[ -z "$1" ]]
then
  echo "ERROR: missing argument 1 which should be the site directory for the site where caching is to be disabled."
  exit 1
else
  SITEDIR=$1
fi

# In Jenkins runs, the sitedir isn't writeable so force it to be
chmod u+w $SITEDIR
cd $SITEDIR

if [[ ! -e "local.settings.php" ]]
then
  echo "local.settings.php doesn't exist. Creating a blank file"

  echo '<?php' > local.settings.php
  echo '' >> local.settings.php
fi

CACHE_DISABLED=`grep '// Disable caches' local.settings.php`

if [[ "$CACHE_DISABLED" == "// Disable caches" ]]
then
  echo "Caches are already disabled for the site you selected. Not making any changes"
else

  echo "Backing up your existing local.settings.php file before making any changes!"

  chmod +w local.settings.php
  cp local.settings.php local.settings.php.bak

  echo "" >> local.settings.php
  echo "" >> local.settings.php
  echo "// Disable caches" >> local.settings.php
  echo "// Pull in dev services which includes a null cache" >> local.settings.php
  echo "\$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';" >> local.settings.php
  echo "" >> local.settings.php
  echo "// Disable a bunch of caches for our testing purposes" >> local.settings.php
  echo "\$config['system.performance']['css']['preprocess'] = FALSE;" >> local.settings.php
  echo "\$config['system.performance']['js']['preprocess'] = FALSE;" >> local.settings.php
  echo "\$settings['cache']['bins']['render'] = 'cache.backend.null';" >> local.settings.php
  echo "\$settings['cache']['bins']['discovery_migration'] = 'cache.backend.memory';" >> local.settings.php
  echo "\$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';" >> local.settings.php
  echo "\$settings['extension_discovery_scan_tests'] = TRUE;" >> local.settings.php
  echo "\$settings['rebuild_access'] = TRUE;" >> local.settings.php

  echo "" >> local.settings.php
  echo "// End of disable caches inserted section" >> local.settings.php

#  chmod -w local.settings.php

fi

exit 0
