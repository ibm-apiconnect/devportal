<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\drushadmin\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Database\Database;
use Throwable;

/**
 * Class DrushAdminCommands.
 *
 * @package Drupal\drushadmin\Commands
 */
class DrushAdminCommands extends DrushCommands {

  /**
   * Implementation of command <code>drush theme-delete theme_name</code>
   *
   * This command deletes theme_name if it is not enabled.
   * A comma separated list of themes can be provided.
   *
   * @param $theme_name - The machine name of the theme (comma separate multiple themes).
   *
   * @command theme-delete
   * @usage drush theme-delete [theme_name]
   *   Uninstall one or more custom themes. Delete one or more custom themes. It will fail if the theme is still enabled.
   */
  public function drush_drushadmin_theme_delete($theme_name): void {
    if ($theme_name !== NULL) {
      $themes = \Drupal::service('theme_handler')->listInfo();
      $customThemes = \Drupal::service('ibm_apim.utils')->getCustomThemeDirectories();
      $inputThemes = explode(',', $theme_name);
      foreach ($inputThemes as $inputTheme) {
        // Check if the specified theme is disabled and is a custom theme
        if (!array_key_exists($inputTheme, $themes) && in_array($inputTheme, $customThemes, TRUE)) {
          $item_path = \Drupal::service('extension.list.theme')->getPath($inputTheme);
          if (isset($item_path) && !empty($item_path)) {
            \Drupal::service('ibm_apim.utils')->file_delete_recursive($item_path);
            // clear all caches otherwise reinstalling the same theme will fail
            try {
              drupal_flush_all_caches();
            } catch (Throwable $e) {
            }
            \Drupal::logger("drushadmin")->notice("!theme_name deleted.", ['!theme_name' => $inputTheme]);
          }
        }
        else {
          \Drupal::logger("drushadmin")
            ->error("!theme_name not deleted. It is either still activated or is not a custom theme.", ['!theme_name' => $inputTheme]);
        }
      }
    }
  }

  /**
   * Implementation of command <code>drush module-delete module_name</code>
   *
   * This command deletes module_name if it is not enabled.
   * A comma separated list of modules can be provided.
   *
   * @param $module_name - The machine name of the module (comma separate multiple modules).
   *
   * @command module-delete
   * @usage drush module-delete [module_name]
   *   Uninstall one or more custom modules. Delete one or more custom modules. It will fail if the module is still enabled.
   */
  public function drush_drushadmin_module_delete($module_name): void {
    if ($module_name !== NULL) {
      $modules = \Drupal::service('ibm_apim.utils')->getDisabledCustomModules();
      $inputModules = explode(',', $module_name);
      foreach ($inputModules as $inputModule) {
        // Check if the specified module is a disabled custom module
        if (array_key_exists($inputModule, $modules)) {
          \Drupal::service('ibm_apim.module')->deleteModulesOnFileSystem([$inputModule], FALSE);
          // clear all caches otherwise reinstalling the same module will fail
          try {
            drupal_flush_all_caches();
          } catch (Throwable $e) {
          }
          \Drupal::logger("drushadmin")->notice("!module_name deleted.", ['!module_name' => $inputModule]);
        }
        else {
          \Drupal::logger("drushadmin")
            ->error("!module_name not deleted. It is either still activated or is not a custom module.", ['!module_name' => $inputModule]);
        }
      }
    }
  }

  /**
   * This command clears any existing user ip bans.
   * A comma separated list of modules can be provided.
   *
   * @command clearbans
   * @usage drush clearbans
   *   Clear any existing user or IP bans.
   */
  public function drush_drushadmin_clearbans(): void {
    $dbConnection = Database::getConnection();
    $schema = \Drupal::database()->schema();
    if ($dbConnection !== NULL && $schema !== NULL && $schema->tableExists("ban_ip")) {
      $dbConnection->truncate('ban_ip')->execute();
    }
    if ($dbConnection !== NULL && $schema !== NULL && $schema->tableExists("flood")) {
      $dbConnection->truncate('flood')->execute();
    }
    if ($dbConnection !== NULL && $schema !== NULL && $schema->tableExists("blocked_ips")) {
      $dbConnection->truncate('blocked_ips')->execute();
    }
    \Drupal::logger("drushadmin")->notice("All bans cleared.");
  }

  /**
   * @param $type - The entity type to delete all content from
   *
   * @command deleteall-entities
   * @usage drush deleteall-entities [type]
   *   Delete all entities of a given type.
   */
  public function drush_drushadmin_deleteall_entities($type): void {
    if ($type !== NULL) {
      switch ($type) {
        case 'event_log':
          Database::getConnection()->truncate('event_logs')->execute();
          break;
        case 'apic_app_application_creds':
          Database::getConnection()->truncate('apic_app_application_creds')->execute();
          break;
        case 'apic_app_application_subs':
          Database::getConnection()->truncate('apic_app_application_subs')->execute();
          break;
        default:
          \Drupal::logger("drushadmin")->error("Unknown entity type");
          return;
      }
      \Drupal::logger("drushadmin")->error("All entities of type %type deleted.", ['%type' => $type]);
    }
  }

  /**
   * @param $type - The node type to delete all content from
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @command deleteall-nodes
   * @usage drush deleteall-nodes [type]
   *   Delete all nodes of a given type
   */
  public function drush_drushadmin_deleteall_nodes($type): void {
    if ($type !== NULL) {
      $result = \Drupal::entityQuery("node")
        ->condition("type", $type)
        ->accessCheck(FALSE)
        ->execute();

      $storage_handler = \Drupal::entityTypeManager()->getStorage("node");
      if (isset($result) && !empty($result)) {
        foreach (array_chunk($result, 50) as $chunk) {
          $entities = $storage_handler->loadMultiple($chunk);
          $storage_handler->delete($entities);
        }
      }
      \Drupal::logger("drushadmin")->error("All nodes of type %type deleted.", ['%type' => $type]);
    }
  }

  /**
   * @param string|null $ur_url - The URL of the user registry to delete all users from, leave blank for all
   *
   * @command deleteall-users
   * @usage drush deleteall-users [ur_url]
   *   Delete all users
   */
  public function drush_drushadmin_deleteall_users(string $ur_url = NULL): void {
    $query = \Drupal::entityQuery("user")->accessCheck(FALSE);
    if ($ur_url !== NULL) {
      $query->condition("apic_user_registry_url", $ur_url);
    }
    $result = $query->execute();

    foreach ($result as $id) {

      // DO NOT DELETE THE ADMIN USER!
      if ((int) $id > 1) {
        user_cancel([], $id, 'user_cancel_reassign');
        $performBatch = TRUE;
      }
    }

    if (!empty($performBatch)) {
      \Drupal::logger('drushadmin')->notice('Processing batch delete of users...');
      $batch = &batch_get();
      $batch['progressive'] = FALSE;
      batch_process();
    }
    if ($ur_url !== NULL) {
      \Drupal::logger("drushadmin")->error("All users from user registry %type have been deleted.", ['%type' => $ur_url]);
    }
    else {
      \Drupal::logger("drushadmin")->error("All users (except admin) have been deleted.");
    }
  }

}
