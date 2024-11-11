<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\drushadmin\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Database\Database;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Consolidation\OutputFormatters\StructuredData\ListDataFromKeys;
use Consolidation\OutputFormatters\Options\FormatterOptions;
use Drush\Utils\StringUtils;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Session\UserSession;

/**
 * Class DrushAdminCommands.
 *
 * @package Drupal\drushadmin\Commands
 */
class DrushAdminCommands extends DrushCommands {
  /**
   * @var DateFormatterInterface
   */
  protected $dateFormatter;

  public function __construct($dateFormatter)
  {
      $this->dateFormatter = $dateFormatter;
  }

  /**
   * Implementation of command <code>drush theme-delete theme_name</code>
   *
   * This command deletes theme_name if it is not enabled.
   * A comma separated list of themes can be provided.
   *
   * @param $theme_name - The machine name of the theme (comma separate multiple themes).
   * @option $dry-run Just return all the folder names of themes that will be deleted.
   *
   * @command theme-delete
   * @usage drush theme-delete [theme_name]
   *   Uninstall one or more custom themes. Delete one or more custom themes. It will fail if the theme is still enabled.
   */
  public function drush_drushadmin_theme_delete($theme_name, $options = ['dry-run' => false]): void {
    if ($theme_name !== NULL) {
      $theme = \Drupal::service('ibm_apim.utils')->getDisabledCustomExtensions('theme');
      $inputThemes = explode(',', $theme_name);
      if ($options['dry-run']) {
        $this->logger->notice("Dry Run detected listing themes that will be deleted:");
      }
      foreach ($inputThemes as $inputTheme) {
        if (!$options['dry-run']) {
          // Check if the specified module is a disabled custom module
          if (array_key_exists($inputTheme, $theme)) {
            \Drupal::service('ibm_apim.module')->deleteExtensionOnFileSystem('theme', [$inputTheme], FALSE);
            \Drupal::logger("drushadmin")->notice("@theme_name deleted.", ['@theme_name' => $inputTheme]);
          }
          else {
            \Drupal::logger("drushadmin")
              ->error("@theme_name not deleted. It is either still activated or is not a custom theme.", ['@theme_name' => $inputTheme]);
          }
        } else {
          $directory_path = DRUPAL_ROOT . DIRECTORY_SEPARATOR . \Drupal::service('extension.path.resolver')->getPath('theme', $inputTheme);
          $this->logger->notice(t("@directory_path", ['@directory_path' => $directory_path]));
        }
      }
    }
  }



/**
 * Print information about the specified user(s).
 *
 * @command apicuser:information
  * @param string $names A comma delimited list of user names.
  * @option $uid A comma delimited list of user ids to lookup (an alternative to names).
  * @option $mail A comma delimited list of emails to lookup (an alternative to names).
  * @option $role A comma delimited list to filter the search of user information for a role or roles.
  * @option $consumer-org A comma delimited list or consumer org urls to filter the search of user information for a consumer organization or organizations.
  * @option $login-after Filters to apply to the search of user information for users who have logged in after a specific date. Date format is m-d-Y or m-d-Y H:i:s
  * @option $login-before Filters to apply to the search of user information. for users who have logged in before a specific date. Date format is m-d-Y or m-d-Y H:i:s
  * @option $status A comma delimited list to filter the search of user information for a status or statuses of a user account. 'active' or 'blocked' are the two statuses
  * @option $apic-state A comma delimited list to filter the search of user information for an apic state or states.
  * @option $limit Limits the amount of results returned. Defaults to 50
  * @option $offset Offsets the where the results start from to aid pagination of results. Defaults to 0
  * @aliases auinf,apicuser-information
  * @usage drush apicuser:information someguy,somegal
  *   Display information about the someguy and somegal user accounts.
  * @usage drush apicuser:information --mail=someguy@somegal.com
  *   Display information for a given email account.
  * @usage drush apicuser:information --uid=5
  *   Display information for a given user id.
  * @usage drush auinf --role='administrator' --login-before="12/20/2023"
  *   Display information for all administrators.
  *
  */
  public function information(string $names = '', $options = ['format' => 'json', 'uid' => self::REQ, 'mail' => self::REQ, 'role' => self::REQ, 'consumer-org' => self::REQ, 'login-after' => self::REQ, 'login-before' => self::REQ, 'apic-state' => self::REQ, 'status' => self::REQ, 'limit' => self::REQ, 'offset' => self::REQ]) {
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    $accounts = [];
    $limit = $options['limit'] ?? '50';
    $offset = $options['offset'] ?? '0';
    $query = \Drupal::entityQuery('user')->sort('uid')->condition('uid', ['0', '1'], 'NOT IN')->accessCheck(FALSE);

    if ($roles = StringUtils::csvToArray($options['role'])) {
      // Remove the authenticated role as we need to check for status not 1
      if ($options['status'] == "blocked") {
        $key = array_search('authenticated', $roles);
        array_splice($roles, $key, 1);
      }
      if (in_array('authenticated', $roles)) {
        $query = $query->condition('status', 1);
        $key = array_search('authenticated', $roles);
        array_splice($roles, $key, 1);
      }
      if (count($roles) > 0) {
        $query = $query->condition('roles', $roles, 'IN');
      }
    }
    if ($consumer_org = StringUtils::csvToArray($options['consumer-org'])) {
      $query = $query->condition('consumerorg_url.value', $consumer_org, 'IN');
    }
    if ($loginBefore = strtotime($options['login-before'])) {
      $query = $query->condition('login.value', $loginBefore, '<');
    }
    if($loginAfter = strtotime($options['login-after'])) {
      $query = $query->condition('login.value', $loginAfter, '>');
    }
    if($status = $options['status']) {
      $status = $status == 'active' ? 1 : 0;
      $query = $query->condition('status', $status);
    }
    if($apicState = StringUtils::csvToArray($options['apic-state'])) {
      $query = $query->condition('apic_state.value', $apicState, 'IN');
    }
    if ($mails = StringUtils::csvToArray($options['mail'])) {
      $query = $query->condition('mail.value', $mails, 'IN');
    }
    if ($uids = StringUtils::csvToArray($options['uid'])) {
      $query = $query->condition('uid.value', $uids, 'IN');
    }
    if ($names = StringUtils::csvToArray($names)) {
      $query = $query->condition('name.value', $names, 'IN');
    }

    $total = count($query->execute());
    $ids = $query->range($offset, $limit)->execute();
    $limit = count($ids);
    if ($loaded = User::loadMultiple($ids)) {
      $accounts += $loaded;
    }

    if (empty($accounts)) {
        throw new \Exception(dt('Unable to find a matching user'));
    }

    foreach ($accounts as $account) {
        $users[] = $this->infoArray($account);
    }

    $results["total"] = $total;
    $results["offset"] = intval($offset);
    $results["limit"] = $limit;
    $results["users"] = $users;

    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }

    return $results;
  }

  /**
   * A flatter and simpler array presentation of a Drupal $user object.
   *
   * @param $account A user account
   */
  public function infoArray($account): array
  {
    $registryService = \Drupal::service('ibm_apim.user_registry');
    $registryUrl = $account->get('apic_user_registry_url')->value;
    $consumerOrgUrls = $account->get('consumerorg_url')->getValue();
    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
    $refinedCOrgUrls = [];
    foreach ($consumerOrgUrls as $index => $valueArray) {
      if (!empty($valueArray['value'])) {
        $consumerOrgName = $consumerOrgService->get($valueArray['value'])->getTitle();
      }
      if (!empty($consumerOrgName)) {
        $refinedCOrgUrls[] = $valueArray['value'] . " (" . $consumerOrgName . ")";
      }
    }
    $registryInfo = "";
    if (!empty($registryService) && !empty($registryUrl)) {
      $registryInfo = $registryUrl . " (" . $registryService->get($registryUrl)->getTitle() . ")";
    }
    return [
        'uid' => $account->id(),
        'name' => $account->getAccountName(),
        'mail' => $account->getEmail(),
        'roles' => $account->getRoles(),
        'status' => $account->isActive() ? 'active' : 'blocked',
        'apic_state' => $account->get('apic_state')->value ?? '',
        'last_login' => $this->dateFormatter->format($account->getLastLoginTime(), 'custom', 'm/d/Y H:i:s'),
        'user_registry' => $registryInfo,
        'consumer_orgs' => $refinedCOrgUrls,
    ];
  }

  /**
   * Implementation of command <code>drush module-delete module_name</code>
   *
   * This command deletes module_name if it is not enabled.
   * A comma separated list of modules can be provided.
   *
   * @param $module_name - The machine name of the module (comma separate multiple modules).
   * @option $dry-run Just return all the folder names of themes that will be deleted.
   *
   * @command module-delete
   * @usage drush module-delete [module_name]
   *   Uninstall one or more custom modules. Delete one or more custom modules. It will fail if the module is still enabled.
   */
  public function drush_drushadmin_module_delete($module_name, $options = ['dry-run' => false]): void {
    if ($module_name !== NULL) {
      $modules = \Drupal::service('ibm_apim.utils')->getDisabledCustomExtensions('module');
      if ($options['dry-run']) {
        $this->logger->notice("Dry Run detected listing modules that will be deleted:");
      }
      $inputModules = explode(',', $module_name);
      foreach ($inputModules as $inputModule) {
        if (!$options['dry-run']) {
          // Check if the specified module is a disabled custom module
          if (array_key_exists($inputModule, $modules)) {
            \Drupal::service('ibm_apim.module')->deleteExtensionOnFileSystem('module', [$inputModule], FALSE);
            \Drupal::logger("drushadmin")->notice("@module_name deleted.", ['@module_name' => $inputModule]);
          }
          else {
            \Drupal::logger("drushadmin")
              ->error("@module_name not deleted. It is either still activated or is not a custom module.", ['@module_name' => $inputModule]);
          }
        } else {
          $module_folder = \Drupal::service('extension.path.resolver')->getPath('module', $inputModule);
          if ($module_folder) {
            $directory_path = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $module_folder;
            $this->logger->notice(t("@directory_path", ['@directory_path' => $directory_path]));
          }
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
    $result = $query->accessCheck()->execute();
    $performBatch = FALSE;

    foreach ($result as $id) {
      // DO NOT DELETE THE ADMIN USER!
      if ((int) $id > 1) {
        user_cancel([], $id, 'user_cancel_reassign');
        $performBatch = TRUE;
      }
    }

    if ($performBatch) {
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

  /**
   * List content entity types.
   *
   * @param array $options
   *
   * @usage drush content:list-types
   *   List all content entity types.
   *
   * @command content:list-types
   * @aliases content-types
   */
  public function contentListTypes(array $options = ['format' => 'table']) {
    $blockedEntities = [
      "crop", "user", "api", "application", "consumerorg", "product", "event_log", "consumerorg_payment_method", "apic_app_application_subs",
      "apic_app_application_creds", "avatars_preview", "comment", "contact_message", "search_api_task", "vote", "vote_result"
    ];
    $contentTypes = \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE)['Content'];
    $result = [];
    foreach ($contentTypes as $contentType => $contentTypeObj) {
      if (in_array($contentType, $blockedEntities)) {
        continue;
      }
      $bundles = array_values(array_diff(array_keys(\Drupal::service('entity_type.bundle.info')->getBundleInfo($contentType)), $blockedEntities));
      if (count($bundles) === 1 && $contentType === $bundles[0]) {
          $bundles = [];
      }
      $result[$contentType] = $bundles;
    }

    if ($options['format'] === 'table') {
      $table = [];
      foreach ($result as $type => $bundles) {
          $row = [
            'Entity Type' => $type,
            'Bundles' => implode(', ', $bundles),
          ];
          $table[] = $row;
      }
      $result = $table;
    }
    return new ListDataFromKeys($result);
  }

  /**
   * List content entities.
   *
   * @param string $entity_type An entity type machine name.
   * @param array $options
   *
   * @option bundle Restrict list to the specified bundle.
   * @usage drush content:list node
   *   List all node entities.
   * @usage drush content:list node --bundle=article
   *   List all article entities.
   *
   * @command content:list
   * @aliases content-list
   */
  public function contentList(string $entity_type, array $options = ['bundle' => self::REQ, 'format' => 'table']): ?RowsOfFields {
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $contentTypes = \Drupal::service('entity_type.repository')->getEntityTypeLabels(TRUE)['Content'];
    $blockedEntities = [
      "crop", "user", "api", "application", "consumerorg", "product", "event_log", "consumerorg_payment_method", "apic_app_application_subs",
      "apic_app_application_creds", "avatars_preview", "comment", "contact_message", "search_api_task", "vote", "vote_result"
    ];

    if (empty($entity_type) || in_array($entity_type, $blockedEntities) || !in_array($entity_type, array_keys($contentTypes))) {
      return null;
    }

    $entityTable = [];
    $entityNids = $this->getQuery($entity_type, null, $options);
    // Filter out blocked API taxonomy terms
    if ($entity_type == 'taxonomy_term' || $entity_type == 'node') {
      $result = \Drupal::entityQuery('taxonomy_term')
            ->condition('name', 'APIs')
            ->condition('parent', 'forums')
            ->accessCheck()
            ->execute();
      $apisTaxonomyID = !empty($result) ? array_shift($result) : '';
      if (!empty($apisTaxonomyID)) {
        if($entity_type == 'taxonomy_term') {
          $entityNids->condition('tid', $apisTaxonomyID, 'NOT IN')->condition('parent', $apisTaxonomyID, 'NOT IN');
        }
        if($entity_type == 'node') {
          $apiTaxonomy = \Drupal::entityQuery('taxonomy_term')
          ->condition('parent', $apisTaxonomyID)
          ->accessCheck()
          ->execute();

          $orGroup = \Drupal::entityQuery('node')->orConditionGroup()
          ->condition('taxonomy_forums', $apiTaxonomy, 'NOT IN')
          ->notExists('taxonomy_forums');

          $entityNids->condition($orGroup);
        }
      }
    }
    $entityNids = $entityNids->execute();
    foreach (array_chunk($entityNids, 50) as $chunk) {
      $entities = $entityTypeManager->getStorage($entity_type)->loadMultiple($chunk);
      foreach ($entities as $entity) {
        if (!in_array($entity->bundle(), $blockedEntities)) {
          $row = [
            'Title' => $entity->label(),
            'Langcode' => $entity->get('langcode')->value,
            'Entity ID' => $entity->id(),
            'Bundle' => $entity->bundle(),
            'UUID' => $entity->get('uuid')->value,
          ];
          $entityTable[] = $row;
        }
      }
    }
    return new RowsOfFields($entityTable);
  }

  protected function getQuery(string $entity_type, ?string $ids, array $options): QueryInterface {
    $entityTypeManager = \Drupal::service('entity_type.manager');

    $storage = $entityTypeManager->getStorage($entity_type);
    $query = $storage->getQuery()->accessCheck(false);
    if ($ids = StringUtils::csvToArray((string) $ids)) {
      $idKey = $entityTypeManager->getDefinition($entity_type)->getKey('id');
      $query = $query->condition($idKey, $ids, 'IN');
    } elseif ($options['bundle']) {
      if ($bundle = $options['bundle']) {
        $bundleKey = $entityTypeManager->getDefinition($entity_type)->getKey('bundle');
        $query = $query->condition($bundleKey, $bundle);
      }
    }
    return $query;
  }
}
