--- core/lib/Drupal/Core/Flood/DatabaseBackend.php	2019-07-03 09:08:05.227808264 +0000
+++ core/lib/Drupal/Core/Flood/DatabaseBackend.php	2019-07-03 09:09:42.417269560 +0000
@@ -5,6 +5,7 @@
 use Drupal\Core\Database\DatabaseException;
 use Symfony\Component\HttpFoundation\RequestStack;
 use Drupal\Core\Database\Connection;
+use Drupal\Core\Site\Settings;
 
 /**
  * Defines the database flood backend. This is the default Drupal backend.
@@ -51,6 +52,13 @@
     if (!isset($identifier)) {
       $identifier = $this->requestStack->getCurrentRequest()->getClientIp();
     }
+    // Don't register attempt if IP has been whitelisted
+    if ( ($name == 'user.failed_login_ip') || ($name == 'user.password_request_ip') ){
+        $whitelist = Settings::get('reverse_proxy_addresses', []);
+        if (in_array($identifier, $whitelist)){
+          return;
+        }
+    }
     $try_again = FALSE;
     try {
       $this->doInsert($name, $window, $identifier);
