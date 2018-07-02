<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

use Drupal\Core\Session\AccountProxy;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Drupal\user\Entity\User;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Psr\Log\LoggerInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * User related functions.
 */
class UserUtils {

  private $currentUser;
  private $sessionStore;
  private $state;
  private $logger;

  public function __construct(AccountProxy $current_user, PrivateTempStoreFactory $temp_store_factory, StateInterface $state, LoggerInterface $logger) {
    $this->currentUser = $current_user;
    $this->sessionStore = $temp_store_factory->get('ibm_apim');
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * Method for modules like TFA to check password
   * If admin then use drupal core method
   * Else using APIM authentication then use our method.
   *
   * @param $current_pass
   * @param $account
   * @return bool
   */
  function checkPassword($current_pass, $account) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $rc = FALSE;
    $moduleHandler = \Drupal::service('module_handler');
    if ($this->currentUser->id() == 1) {
      // Check password. (from user.module user_validate_current_pass()).
      $uid = \Drupal::service('user.auth')->authenticate($account->getUsername(), $current_pass);

      if (isset($uid)) {
        $rc = TRUE;
      }
    }
    else {
      if ($moduleHandler->moduleExists('auth_apic')) {
        $rc = auth_apic_authenticate($account->getAccountName(), $current_pass);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    return $rc;
  }

  /**
   * Gets the current consumer org object from a session variable.
   *
   * This method cannot invoke any node operations or it will introduce cycles.
   *
   * @return array
   *    The the current consumer org object or NULL if a user does not
   *    belong to a consumer org or one is not set.
   */
  function getCurrentConsumerorg() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $rc = NULL;
    if (!$this->currentUser->isAnonymous() && $this->currentUser->id() != 1) {
      $consumerorg = $this->sessionStore->get('current_consumer_organization');
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
      $rc = array('url' => NULL);
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
   * @return array The form
   */
  function setCurrentConsumerorg($org_url = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $org_url);
    $org = NULL;
    if (!$this->currentUser->isAnonymous() && $this->currentUser->id() != 1) {
      $org_urls = $this->loadConsumerorgs();
      $found = FALSE;
      if ($org_urls && !empty($org_urls)) {
        // if haven't specified an org url, select the first one
        if (!isset($org_url)) {
          $org_url = reset($org_urls);
        }
        if (in_array($org_url, $org_urls)) {
          $found = TRUE;
          $org = array('url' => $org_url);
          $this->sessionStore->set('current_consumer_organization', $org);
        }
        if ($found != TRUE) {
          $this->sessionStore->set('current_consumer_organization', NULL);
        }
      }
      else {
        $this->sessionStore->set('current_consumer_organization', NULL);
      }

      $this->logger->notice('Setting current consumerorg to %data', array('%data' => json_encode($org, JSON_PRETTY_PRINT)));
    }
    else {
      $this->logger->notice('Cannot set current consumerorg for anonymous users or admin');
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $org);
    return $org;
  }

  /**
   * Set session variables for the current consumerorg
   * @throws \Drupal\user\TempStoreException
   */
  function setOrgSessionData() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $this->sessionStore->set('permissions', array());
    if (!$this->currentUser->isAnonymous() && $this->currentUser->id() != 1) {
      if (!isset($this->getCurrentConsumerorg()['url'])) {
        // if current consumerorg not set then invoke it and try again
        $this->setCurrentConsumerorg();
      }
      if (isset($this->getCurrentConsumerorg()['url'])) {
        $consumerorg_url = $this->getCurrentConsumerorg()['url'];
        $org_urls = $this->loadConsumerorgs();
        if ($org_urls && !empty($org_urls) && isset($consumerorg_url)) {
          if (in_array($consumerorg_url, $org_urls)) {
            $consumerOrg = \Drupal::service('ibm_apim.consumerorg')->get($consumerorg_url);
            // store the current consumerorg in the session
            if (isset($consumerOrg)) {
              $org = array('url' => $consumerOrg->getUrl(), 'name' => $consumerOrg->getName());
              $this->sessionStore->set('current_consumer_organization', $org);

              // total permissions for user is all permissions of all roles that the user has
              $perms = array();
              $user = User::load($this->currentUser->id());
              $roles = $consumerOrg->getRolesForMember($user->get('apic_url')->value);

              foreach ($roles as $role) {
                if ($role) {
                  $perms = array_merge($perms, $role->getPermissions());
                }
              }
              $this->sessionStore->set('permissions', $perms);
            }
          }
        }
      }
      else {
        $this->logger->notice('setOrgSessionData: Current consumer organization could not be set.');
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * update the cached user data (orgs list, roles, etc.)
   * @return mixed
   */
  function refreshUserData() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $return = NULL;
    if (!$this->currentUser->isAnonymous() && $this->currentUser->id() != 1) {
      $result = ApicRest::get('/me?expand=true');

      $this->sessionStore->set('userdata', NULL);

      if (isset($result) && ($result->code == 200) && $result->data != '') {
        $this->sessionStore->set('userdata', $result->data);
        if (isset($result->data['id'])) {
          $this->sessionStore->set('memberid', $result->data['id']);
        }
        $return = $result->data;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $return);
    return $return;
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
  function loadConsumerorgs() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $return = NULL;
    if (!$this->currentUser->isAnonymous() && $this->currentUser->id() != 1) {
      $orgs = array();
      // use the consumerorg urls list set on the user object
      $account = User::load($this->currentUser->id());
      if (!empty($account->consumerorg_url->getValue())) {
        $org_urls = $account->consumerorg_url->getValue();
        foreach ($org_urls as $index => $valueArray) {
          $nextOrgUrl = $valueArray['value'];
          array_push($orgs, $nextOrgUrl);
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
  function loadOwnedConsumerorgs() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $owned = array();
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_owner.value', $this->currentUser);
    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nodes = Node::loadMultiple($nids);
      foreach ($nodes as $node) {
        $owned[] = $node->consumerorg_url->value;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $owned);
    return $owned;
  }

  /**
   * Method to see if a user has an explicit permission rather than just by virtue of being an admin
   *
   * @param $string
   * @param null $account
   * @return bool
   */
  function explicitUserAccess($string, $account = NULL) {
    if (isset($account)) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array($string, $account->id()));
    }
    else {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array($string, NULL));
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

      $perms = array();
      foreach ($role_permissions as $one_role) {
        $perms += $one_role;
      }
      $perm[$account->id()] = $perms;
    }
    $return = isset($perm[$account->id()][$string]);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $return);
    return $return;
  }

  /**
   * Check if the current user has a specific permission
   *
   * @param null $perm
   * @return bool
   */
  function checkHasPermission($perm = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $perm);
    $return = FALSE;
    if (!$this->currentUser->isAnonymous() && $this->currentUser->id() != 1 && isset($perm) && !empty($perm)) {
      $perms = $this->sessionStore->get('permissions');
      if (isset($perms) && !empty($perms)) {
        $return = in_array($perm, $perms);
      }
      else {
        // the user has no permissions at all. send them away.
        $response = new RedirectResponse(Url::fromRoute('ibm_apim.noperms')->toString());
        $response->send();
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $return);
    return $return;
  }

  /**
   * Decrypt data using APIC encryption (AES-256-CBC)
   * @param $data
   * @return bool|string
   */
  function decryptData($data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $data);
    $return = FALSE;
    $ibm_apim_site_url = $this->state->get('ibm_apim.site_url');
    if (isset($ibm_apim_site_url) && !empty($ibm_apim_site_url)) {
      // if no leading protocol then assume https
      if (mb_strpos($ibm_apim_site_url, 'https://') !== 0 && mb_strpos($ibm_apim_site_url, 'http://') !== 0) {
        $ibm_apim_site_url = 'https://' . $ibm_apim_site_url;
      }
      exec('bash -c "/usr/local/bin/node /home/admin/bgsync/decrypt_token.js -p ' . escapeshellarg($ibm_apim_site_url) . ' -e ' . escapeshellarg($data) . ' 2> >(ADMIN_USER=$USER ~admin/bin/background_sync_logger >> /var/log/devportal/decrypt.log)"', $output, $rc);
      if (isset($rc) && $rc != 0) {
        $this->logger->notice('Decryption returned %rc. Output: %data', array(
          '%rc' => $rc,
          '%data' => var_export($output),
        ));
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
