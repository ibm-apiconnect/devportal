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

namespace Drupal\ibm_apim\Service;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * User related functions.
 */
class UserUtils {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  private PrivateTempStore $tempStore;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * @var \Drupal\user\UserStorageInterface
   */
  private UserStorageInterface $userStorage;

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(AccountProxyInterface      $current_user,
                              PrivateTempStoreFactory    $temp_store_factory,
                              StateInterface             $state,
                              LoggerInterface            $logger,
                              EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->tempStore = $temp_store_factory->get('ibm_apim');
    $this->state = $state;
    $this->logger = $logger;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * Gets the current consumer org object from a session variable.
   *
   * This method cannot invoke any node operations or it will introduce cycles.
   *
   * @return null|array
   *    The the current consumer org object or NULL if a user does not
   *    belong to a consumer org or one is not set.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \JsonException
   */
  public function getCurrentConsumerorg(): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (!$this->currentUser->isAnonymous() && (int) $this->currentUser->id() !== 1) {
      $consumerorg = $this->tempStore->get('current_consumer_organization');
      if (isset($consumerorg) && !empty($consumerorg)) {
        $rc = $consumerorg;
      }
      else {
        $output = $this->setCurrentConsumerorg();
        $rc = $output;
      }
    }
    else {
      // Anonymous user, return empty array.
      $rc = ['url' => NULL];
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    return $rc;
  }


  /**
   * Resets the current consumer org object from a session variable.
   *
   * This is used when deleting the current consumer org.
   *
   * @return null|array
   *    The the current consumer org object or NULL if a user does not
   *    belong to a consumer org or one is not set.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException|\JsonException
   */
  public function resetCurrentConsumerorg(): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $rc = NULL;
    if (!$this->currentUser->isAnonymous() && (int) $this->currentUser->id() !== 1) {
      $this->tempStore->set('current_consumer_organization', NULL);
      $rc = $this->getCurrentConsumerorg();
    }
    else {
      // Anonymous user, return empty array.
      $rc = ['url' => NULL];
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    return $rc;
  }

  /**
   * Sets the current consumer org in a session variable.
   *
   * Users may belong to multiple consumer orgs.
   * If no url is passed in then uses the first from the list.
   *
   * This method cannot invoke any node operations or it will introduce cycles.
   *
   * @param string|null $org_url
   *
   * @return null|array
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \JsonException
   */
  public function setCurrentConsumerorg($org_url = NULL): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $org_url);
    $org = NULL;
    if (!$this->currentUser->isAnonymous() && (int) $this->currentUser->id() !== 1) {
      $org_urls = $this->loadConsumerorgs();
      $found = FALSE;
      if ($org_urls) {
        // if haven't specified an org url, select the first one
        if (!isset($org_url)) {
          $org_url = reset($org_urls);
        }
        if (in_array($org_url, $org_urls, FALSE)) {
          $found = TRUE;
          $org = ['url' => $org_url];
          $this->tempStore->set('current_consumer_organization', $org);
          \Drupal::service('cache_tags.invalidator')->invalidateTags(['consumerorg:' . Html::cleanCssIdentifier($org_url)]);
          \Drupal::service('cache_tags.invalidator')->invalidateTags(['apic_app:new_application']);
        }
        if ($found !== TRUE) {
          $this->tempStore->set('current_consumer_organization', NULL);
        }
      }
      else {
        $this->tempStore->set('current_consumer_organization', NULL);
      }

      $this->logger->notice('Setting current consumerorg to %data', ['%data' => json_encode($org, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)]);
    }
    else {
      $this->logger->warning('Cannot set current consumerorg for anonymous users or admin');
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $org);
    return $org;
  }

  /**
   * Set session variables for the current consumerorg
   *
   * @throws \Drupal\Core\TempStore\TempStoreException|\JsonException
   */
  public function setOrgSessionData(): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $this->tempStore->set('permissions', []);
    if (!$this->currentUser->isAnonymous() && (int) $this->currentUser->id() !== 1) {

      $current_org = $this->getCurrentConsumerorg();
      if (!isset($current_org['url'])) {
        $this->logger->debug('There is no current organization so attempting to set one.');
        // if current consumerorg not set then invoke it and try again
        $current_org = $this->setCurrentConsumerorg();
      }

      if (isset($current_org['url'])) {

        $consumerorg_url = $current_org['url'];
        $org_urls = $this->loadConsumerorgs();

        if ($org_urls && isset($consumerorg_url) && in_array($consumerorg_url, $org_urls, FALSE)) {
          $consumerOrg = \Drupal::service('ibm_apim.consumerorg')->get($consumerorg_url);
          // store the current consumerorg in the session
          if (isset($consumerOrg)) {
            $org = ['url' => $consumerOrg->getUrl(), 'name' => $consumerOrg->getName()];
            $this->logger->debug('Storing current org in session: %org', ['%org' => serialize($org)]);
            $this->tempStore->set('current_consumer_organization', $org);

            // total permissions for user is all permissions of all roles that the user has
            $perms = [];
            $user = User::load($this->currentUser->id());
            if ($user !== NULL) {
              $roles = $consumerOrg->getRolesForMember($user->get('apic_url')->value);

              foreach ($roles as $role) {
                $permURLs = $role->getPermissions();
                foreach ($permURLs as $permission) {
                  if (strpos($permission, '/') > -1) {
                    $permission_name = \Drupal::service('ibm_apim.permissions')->get($permission)['name'];
                    if (empty($permission_name)) {
                      $this->logger->warning('No permission found for %url', ['%url' => $permission]);
                    }
                    else {
                      $perms[] = $permission_name;
                    }
                  }
                  else {
                    $perms[] = $permission;
                  }
                }
              }
              $perms = array_unique($perms);
            }
            else {
              $this->logger->warning('Unable to load current user so cannot update roles and permissions');
            }
            $this->logger->debug('Storing permissions in session: %perms', ['%perms' => \serialize($perms)]);
            $this->tempStore->set('permissions', $perms);
          }
          else {
            $this->logger->warning('No consumerorg retrieved for %consumerorg_url', ['%consumerorg_url' => $consumerorg_url]);
          }
        }
      }
      else {
        $this->logger->warning('Current consumer organization could not be set.');
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Load the user's consumer organizations and store them in a session variable.
   * The consumer org is used in most IBM APIm API calls, storing it saves a lot
   * of extra calls.
   *
   * This method cannot invoke any node operations or it will introduce cycles.
   *
   * @return array|null
   */
  public function loadConsumerorgs(): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $return = NULL;
    if (!$this->currentUser->isAnonymous() && (int) $this->currentUser->id() !== 1) {
      $orgs = [];
      // use the consumerorg urls list set on the user object
      $account = User::load($this->currentUser->id());
      if ($account !== NULL && !empty($account->consumerorg_url->getValue())) {
        $org_urls = $account->consumerorg_url->getValue();
        foreach ($org_urls as $index => $valueArray) {
          $nextOrgUrl = $valueArray['value'];
          $orgs[] = $nextOrgUrl;
        }
      }
      $return = $orgs;
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $return);
    return $return;
  }

  /**
   * Return all of the consumer orgs the current user owns.
   */
  public function loadOwnedConsumerorgs(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $user = User::load($this->currentUser->id());

    $owned = [];
    if ($user !== NULL) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_owner.value', $user->get('apic_url')->value);
      $nids = $query->execute();
      if (isset($nids) && !empty($nids)) {
        foreach (array_chunk($nids, 50) as $chunk) {
          $nodes = Node::loadMultiple($chunk);
          foreach ($nodes as $node) {
            $owned[] = $node->consumerorg_url->value;
          }
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $owned);
    return $owned;
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addConsumerOrgToUser($orgUrl, $account = NULL): bool {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $orgUrl);
    }
    $found = FALSE;
    $account_id = NULL;

    if ($account === NULL && !$this->currentUser->isAnonymous() && (int) $this->currentUser->id() !== 1) {
      $account = User::load($this->currentUser->id());
    }

    if ($account !== NULL) {
      $account_id = (int) $account->id();
    }

    if (!$this->currentUser->isAnonymous() && (int) $account_id !== 1 && $account !== NULL) {
      $org_urls = $account->get('consumerorg_url')->getValue();

      if (!empty($org_urls)) {
        // update the consumerorg urls list set on the user object
        $this->logger->debug('updating consumerorg urls list set on the user object');
        foreach ($org_urls as $index => $valueArray) {
          if ($valueArray['value'] === $orgUrl) {
            $found = TRUE;
          }
        }
      }
      if (!$found) {
        $this->logger->debug('adding org to consumerorg urls list %orgUrl', ['%orgUrl' => $orgUrl]);
        $org_urls[] = ['value' => $orgUrl];
        $account->set('consumerorg_url', $org_urls);
        $account->save();
        $found = TRUE;
      }
    }
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $account_id);
    }
    return $found;
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeConsumerOrgFromUser($orgUrl, $account = NULL): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $orgUrl);
    $return = NULL;
    if ($account === NULL && !$this->currentUser->isAnonymous() && (int) $this->currentUser->id() !== 1) {
      $account = User::load($this->currentUser->id());
    }
    // update the consumerorg urls list set on the user object
    if (!$this->currentUser->isAnonymous() && (int) $account->id() !== 1 && !empty($account->consumerorg_url->getValue())) {
      $org_urls = $account->consumerorg_url->getValue();
      $new_org_urls = [];
      foreach ($org_urls as $index => $valueArray) {
        if ($valueArray['value'] !== $orgUrl) {
          $new_org_urls[] = $valueArray;
        }
      }
      if (empty($new_org_urls)) {
        $account->delete();
      } else {
        $account->set('consumerorg_url', $new_org_urls);
        $account->save();
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Method to see if a user has an explicit permission rather than just by virtue of being an admin
   *
   * @param $string
   * @param null $account
   *
   * @return bool
   */
  public function explicitUserAccess($string, $account = NULL): bool {
    if ($account !== NULL) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$string, $account->id()]);
    }
    else {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$string, NULL]);
    }

    if (!isset($account)) {
      $account = $this->currentUser;
    }

    // To reduce the number of SQL queries, we cache the user's permissions
    // in a static variable.
    // Use the advanced drupal_static() pattern, since this is called very often.
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['perm'] = &drupal_static(__FUNCTION__);
    }
    $perm = &$drupal_static_fast['perm'];
    if (!isset($perm[$account->id()])) {
      $role_permissions = user_role_permissions($account->getRoles());

      $perms = [];
      foreach ($role_permissions as $one_role) {
        $perms += $one_role;
      }
      $perm[$account->id()] = $perms;
    }
    $return = in_array($string, array_values($perm[$account->id()]), TRUE);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $return);
    return $return;
  }

  /**
   * Check if the current user has a specific permission
   *
   * @param null $perm
   *
   * @return bool
   */
  public function checkHasPermission($perm = NULL): bool {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $perm);
    }
    $return = FALSE;
    if (!$this->currentUser->isAnonymous() && (int) $this->currentUser->id() !== 1 && isset($perm) && !empty($perm)) {
      $perms = $this->tempStore->get('permissions');
      if (isset($perms) && !empty($perms)) {
        $return = in_array($perm, $perms, FALSE);
      }
      else {
        // the user has no permissions at all. send them away.
        $this->logger->debug('user has no permissions set - redirecting to no perms page');
        $response = new RedirectResponse(Url::fromRoute('ibm_apim.noperms')->toString());
        $response->send();
      }
    }
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $return);
    }
    return $return;
  }

  /**
   * Decrypt data using APIC encryption (AES-256-CBC)
   *
   * @param $data
   *
   * @return bool|string
   */
  public function decryptData($data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $data);
    $return = FALSE;
    $ibm_apim_site_url = $this->state->get('ibm_apim.site_url');
    if (isset($ibm_apim_site_url) && !empty($ibm_apim_site_url)) {
      // if no leading protocol then assume https
      if (mb_strpos($ibm_apim_site_url, 'https://') !== 0 && mb_strpos($ibm_apim_site_url, 'http://') !== 0) {
        $ibm_apim_site_url = 'https://' . $ibm_apim_site_url;
      }
      exec('bash -c "/usr/local/bin/node /home/admin/bgsync/decrypt_token.js -p ' . escapeshellarg($ibm_apim_site_url) . ' -e ' . escapeshellarg($data) . ' 2> >(ADMIN_USER=$USER ~admin/bin/background_sync_logger >> /var/log/devportal/decrypt.log)"', $output, $rc);
      if (isset($rc) && $rc !== 0) {
        $this->logger->notice('Decryption returned %rc. Output: %data', [
          '%rc' => $rc,
          '%data' => var_export($output),
        ]);
        $return = FALSE;
      }
      else {
        $decrypted = $output[0];
        $return = $decrypted;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $return);
    return $return;
  }

}
