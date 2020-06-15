<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apictest;

/**
 * If requested for the context in the behat.yml file the mock services
 * will be installed/ uninstalled.
 *
 * This is done in beforeSuite and afterSuite hooks.
 */
class MockServiceHandler {

  /**
   * Install the mock services for a given site.
   *
   * @param string $siteDirectory
   *   Site directory on the portal node.
   * @param string $modulesDirectory
   *   Location of modules in drupal - travis = modules/contrib, jenkins/stack = modules
   * @param string $userRegistry
   *   User registry specific mock to use - will load ibm_apim/src/Tests/ibm_apim_$userRegistry_mock.services.yml
   * @param boolean $mockSiteConfig
   *   Whether to use MockSiteConfig service or load individually mocked MockPermissionsService,
   *   MockUserRegistryService etc instead
   */
  public static function install($siteDirectory, $modulesDirectory, $userRegistry = 'lur', $mockSiteConfig = TRUE): void {
    /*
     * This code came from : http://drupal.stackexchange.com/questions/197046/how-to-inject-a-fake-class-into-container-when-running-webtestbase-tests#comment237939_197060
     * (code from the Redis module for Drupal 8)
     *
     * Write the containers_yaml update by hand, since writeSettings() doesn't
     * support this syntax.
     */
    print 'Installing mock services in ' . $siteDirectory . "\n";

    print "\n";
    print "MockServiceHandler parameters:\n";
    print "siteDirectory: $siteDirectory\n";
    print "modulesDirectory: $modulesDirectory\n";
    print "userRegistry: $userRegistry\n";
    print "mockSiteConfig: $mockSiteConfig\n";
    print "\n";

    // Build file names.
    $settingsPhp = $siteDirectory . '/settings.php';
    $originalSettingsPhp = $siteDirectory . '/settings.php.orig';

    // Copy existing settings.php to a backup file
    chmod($siteDirectory, 0777);
    if (!copy($settingsPhp, $originalSettingsPhp)) {
      print "WARNING : It wasn't possibly to create a backup of your current site/settings.php file.\n";
    }

    // Make a real path out of the potentially relative path
    $modulesDirectory = realpath("./$modulesDirectory");

    // Add the mocked services to settings.php
    chmod($settingsPhp, 0666);
    $contents = file_get_contents($settingsPhp);
    $contents .= "\n\n" . '$settings[\'container_yamls\'][] = \'' . $modulesDirectory . '/auth_apic/src/Tests/auth_apic_mock.services.yml\';';
    $contents .= "\n\n" . '$settings[\'container_yamls\'][] = \'' . $modulesDirectory . '/apic_app/src/Tests/application_mock.services.yml\';';
    $contents .= "\n\n" . '$settings[\'container_yamls\'][] = \'' . $modulesDirectory . '/ibm_apim/src/Service/Mocks/mock_ibm_apim.services.yml\';';

    if ($mockSiteConfig) {
      $contents .= "\n\n" . '$settings[\'container_yamls\'][] = \'' . $modulesDirectory . '/ibm_apim/src/Service/Mocks/mock_site_config.services.yml\';';
    }

    if ($userRegistry !== 'lur') {
      $userRegistryMockServices = $modulesDirectory . '/auth_apic/src/Tests/auth_apic' . $userRegistry . '_mock.services.yml';
      if (file_exists($userRegistryMockServices)) {
        print "\nLoading user registry specific ($userRegistry) services from $userRegistryMockServices.\n";
        $contents .= "\n\n" . '$settings[\'container_yamls\'][] = \'' . $modulesDirectory . '/auth_apic/src/Tests/auth_apic_' . $userRegistry . '_mock.services.yml\';';
      }
      else {
        print "\nERROR: user registry specific ($userRegistry) services file doesn't exist: $userRegistryMockServices\n\n";
      }
    }
    file_put_contents($settingsPhp, $contents);

    self::clearCaches();

    // TEMPORARY DEBUG CODE
    print "\n\n";
    print "Dumping end of settings.php\n";
    $output = [];
    exec("tail -n 5 $settingsPhp", $output);
    var_dump($output);
    // END TEMPORARY DEBUG CODE


    print "Installing mock services completed.\n";

  }

  /**
   * Uninstall the mock services for a given site.
   *
   * @param string $siteDirectory
   *   Site directory on the portal node.
   */
  public static function uninstall($siteDirectory): void {

    print 'Uninstalling mock services from ' . $siteDirectory . "\n";

    // Build file names
    $settingsPhp = $siteDirectory . '/settings.php';
    $originalSettingsPhp = $siteDirectory . '/settings.php.orig';

    // Restore original settings from backup file
    chmod($siteDirectory, 0777);
    chmod($settingsPhp, 0666);
    if (file_exists($settingsPhp) && file_exists($originalSettingsPhp) && !copy($originalSettingsPhp, $settingsPhp)) {
      print "WARNING: it was not possible to restore your site/settings.php file from a previous backup.\n";
    }

    self::clearCaches();

    // TEMPORARY DEBUG CODE
    print "\n\n";
    print "Dumping end of settings.php\n";
    $output = [];
    exec("tail -n 5 $settingsPhp", $output);
    var_dump($output);
    // END TEMPORARY DEBUG CODE

    print "Uninstalling mock services completed.\n";
  }

  /**
   * Clear drupal caches.
   */
  private static function clearCaches(): void {
    print 'Clearing caches...';
    drupal_flush_all_caches();
    print "done.\n";
  }

}
