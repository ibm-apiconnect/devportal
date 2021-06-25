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

namespace Drupal\auth_apic\Service;

use Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface;
use Drupal\Component\Datetime\Time;
use Drupal\Core\State\StateInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\encrypt\Exception\EncryptException;
use Drupal\encrypt\Exception\EncryptionMethodCanNotDecryptException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class OidcStateService implements OidcStateServiceInterface {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

  /**
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  private EncryptServiceInterface $encryptService;

  /**
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  private EncryptionProfileManagerInterface $encryptionProfileManager;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  private Session $session;

  /**
   * @var \Drupal\Component\Datetime\Time
   */
  private $time;

  private $encryptionProfile = NULL;


  public const STATE_KEY = 'auth_apic.oidc_state';

  public const ENCRYPTION_PROFILE_NAME = 'socialblock';

  public function __construct(StateInterface $state,
                              EncryptServiceInterface $encrypt_service,
                              EncryptionProfileManagerInterface $encrypt_profile_manager,
                              LoggerInterface $logger,
                              Session $session,
                              Time $time){
    $this->state = $state;
    $this->encryptService = $encrypt_service;
    $this->encryptionProfileManager = $encrypt_profile_manager;
    $this->logger = $logger;
    $this->session = $session;
    $this->time = $time;
  }

  /**
   * @inheritDoc
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  public function store($data) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $key = NULL;
    // we need a registry url to generate a key
    if (isset($data['registry_url'])) {
      // KEY = created_time:registry_url:sessionid
      $key = $this->time->getCurrentTime() . ':' . $data['registry_url'] . ':' . $this->session->getId();
    }
    else {
      $this->logger->error('unable to establish unique key');
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'unable to establish unique key');
      }
      return NULL;
    }

    $all_state = $this->getAllOidcState();
    $encrypted_key = $this->encryptService->encrypt($key, $this->getEncryptionProfile());
    if (isset($encrypted_key)) {
      $encrypted_data = $this->encryptService->encrypt(serialize($data), $this->getEncryptionProfile());
      $all_state[$key] = $encrypted_data;
      $this->saveAllOidcState($all_state);
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'encrypted key');
      }
      return $encrypted_key;
    }
    else {
      $this->logger->error('unable to generate encrypted key');
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'unable to generate encrypted key');
      }
      return NULL;
    }

  }

  /**
   * @inheritDoc
   */
  public function get(string $key) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $all_state = $this->getAllOidcState();
    $decrypted_key = $this->encryptService->decrypt($key, $this->getEncryptionProfile());
    $encrypted_data = $all_state[$decrypted_key];
    $data = $this->encryptService->decrypt($encrypted_data, $this->getEncryptionProfile());

    if (isset($data)) {
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'item returned');
      }
      return unserialize($data, ['allowed_classes' => FALSE]);
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return NULL;

  }

  /**
   * @inheritDoc
   */
  public function delete(string $key): bool {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $all_state = $this->getAllOidcState();
    $decrypted_key = $this->encryptService->decrypt($key, $this->getEncryptionProfile());
    if (isset($decrypted_key)) {

      if (isset($all_state[$decrypted_key])) {
        unset($all_state[$decrypted_key]);
        $this->saveAllOidcState($all_state);
        if (function_exists('ibm_apim_exit_trace')) {
          ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, TRUE);
        }
        return TRUE;
      }

      $this->logger->warning('Unable to delete item from oidc state');
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, FALSE);
      }
      return FALSE;

    }

    $this->logger->error('Unable to decrypt key');
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, FALSE);
    }
    return FALSE;

  }

  /**
   * @inheritDoc
   */
  public function prune() {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $prune_count = 0;
    $all_state = $this->getAllOidcState();
    $now = $this->time->getCurrentTime();
    $TTL = 86400; # 24hrs
    foreach ($all_state as $key => $encrypted_value) {
      // TODO: $key contains timestamp
      try {
        $value = $this->encryptService->decrypt($encrypted_value, $this->getEncryptionProfile());
      } catch (EncryptException | EncryptionMethodCanNotDecryptException $e) {
      }
      if (isset($value['created']) && ($now > ((int) $value['created'] + $TTL))) {
        unset($all_state[$key]);
        $prune_count++;
      }
    }
    $this->saveAllOidcState($all_state);

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $prune_count);
    }
    return $prune_count;
  }


  private function getEncryptionProfile(): ?\Drupal\encrypt\EncryptionProfileInterface {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    if (!$this->encryptionProfile) {
      $profile = $this->encryptionProfileManager->getEncryptionProfile(self::ENCRYPTION_PROFILE_NAME);
      if (isset($profile)) {
        $this->encryptionProfile = $profile;
      }
      else {
        $this->logger->error('Unable to locate %profile_name encryption profile.', array('%profile_name' => self::ENCRYPTION_PROFILE_NAME));
        if (function_exists('ibm_apim_exit_trace')) {
          ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
        }
        return NULL;
      }
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'encryption profile');
    }
    return $this->encryptionProfile;
  }

  /**
   * @return array|mixed
   */
  public function getAllOidcState() {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $all_state = $this->state->get(self::STATE_KEY);
    if (!isset($all_state)) {
      $this->logger->debug('Unable to retrieve %key from state, initializing.', array('%key' => self::STATE_KEY));
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'initialized array');
      }
      return [];
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return unserialize($all_state, ['allowed_classes' => FALSE]);
  }

  /**
   * @param $state
   */
  public function saveAllOidcState($state): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $this->state->set(self::STATE_KEY, serialize($state));
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }


}
