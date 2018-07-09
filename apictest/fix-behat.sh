#!/bin/bash

BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/../..

# support travis where modules are one level deeper in modules/contrib
if [ ! -d "${BASEDIR}/vendor" ]; then
  BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/../../..
fi

# This small script will write out, apply and then remove a patch file.
#
# The patch fixes an issue in the drupal-driver component of behat.
#

# WARNING - run this file only once. Patching files multiple times is going to break stuff :)

# write out patch files

cat >rawdrupalcontext.patch << EOF
--- a/src/Drupal/DrupalExtension/Context/RawDrupalContext.php	2017-08-24 08:40:43.794075773 +0000
+++ b/src/Drupal/DrupalExtension/Context/RawDrupalContext.php	2017-08-24 08:42:20.286145873 +0000
@@ -549,7 +549,7 @@
     \$element = \$this->getSession()->getPage();
     \$element->fillField(\$this->getDrupalText('username_field'), \$user->name);
     \$element->fillField(\$this->getDrupalText('password_field'), \$user->pass);
-    \$submit = \$element->findButton(\$this->getDrupalText('log_in'));
+    \$submit = \$element->findButton('Sign in');
     if (empty(\$submit)) {
       throw new \Exception(sprintf("No submit button at %s", \$this->getSession()->getCurrentUrl()));
     }
@@ -559,10 +559,10 @@

     if (!\$this->loggedIn()) {
       if (isset(\$user->role)) {
-        throw new \Exception(sprintf("Unable to determine if logged in because 'log_out' link cannot be found for user '%s' with role '%s'", \$user->name, \$user->role));
+        throw new \Exception(sprintf("Unable to determine if logged in because 'Sign out' link cannot be found for user '%s' with role '%s'", \$user->name, \$user->role));
       }
       else {
-        throw new \Exception(sprintf("Unable to determine if logged in because 'log_out' link cannot be found for user '%s'", \$user->name));
+        throw new \Exception(sprintf("Unable to determine if logged in because 'Sign out' link cannot be found for user '%s'", \$user->name));
       }
     }
   }
@@ -614,7 +614,7 @@

     // As a last resort, if a logout link is found, we are logged in. While not
     // perfect, this is how Drupal SimpleTests currently work as well.
-    if (\$page->findLink(\$this->getDrupalText('log_out'))) {
+    if (\$page->findLink(\$this->getDrupalText('Sign out'))) {
       return TRUE;
     }


EOF

# Apply patch

patch -t --verbose ${BASEDIR}/vendor/drupal/drupal-extension/src/Drupal/DrupalExtension/Context/RawDrupalContext.php rawdrupalcontext.patch

# Remove patch
rm rawdrupalcontext.patch