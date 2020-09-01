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

namespace Drupal\mail_subscribers\Service;

use Drupal\Component\Utility\EmailValidator;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class MailService {

  protected $emailValidator;
    
  protected $nodeStorage;
  
  protected $userStorage;

  public function __construct(ApicUserStorageInterface $user_storage,
                              EntityTypeManagerInterface $entityTypeManager,
                              EmailValidator $email_validator) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->subscriptionStorage = $entityTypeManager->getStorage('apic_app_application_subs');
    $this->userStorage = $user_storage;
    $this->emailValidator = $email_validator;
  }


  /**
   * Mail owners subscribing to a specific plan (if specified) or product (if it isn't)
   *
   * @param $mailParams
   * @param array $from
   * @param $langcode
   *
   * @return int
   * @throws \Exception
   */
  public function mailProductOwners($mailParams = [], $from = [], $langcode): int {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $productNid = $mailParams['product'];
    $planName = $mailParams['plan']['name'] ?? NULL;

    $product = Node::load($productNid);

    $toList = $this->getProductSubscribingOwners($product->apic_url->value . ':' . $planName);

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $toList, $from, $productNid);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to owners subscribing to product %product', [
        '%product' => $productNid,
      ]);

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    }
    return $rc;
  }

  /**
   * Mail members subscribing to a specific plan (if specified) or product (if it isn't)
   *
   * @param $mailParams
   * @param array $from
   * @param $langcode
   *
   * @return int
   * @throws \Exception
   */
  public function mailProductMembers($mailParams = [], $from = [], $langcode): int {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $productNid = $mailParams['product'];
    $planName = $mailParams['plan']['name'] ?? NULL;

    $product = Node::load($productNid);
    $toList = $this->getProductSubscribingMembers($product->apic_url->value . ':' . $planName);

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $toList, $from, $productNid);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to members subscribing to product %product', [
        '%product' => $productNid,
      ]);
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    }
    return $rc;
  }

  /**
   * Mail owners subscribing to plans including a given API
   *
   * @param $mailParams
   * @param array $from
   * @param $langcode
   *
   * @return int
   * @throws \Exception
   */
  public function mailApiOwners($mailParams = [], $from = [], $langcode): int {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $apiNid = $mailParams['api'];

    $toList = $this->getApiSubscribingOwners($apiNid);

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $toList, $from, $apiNid);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to owners subscribing to API %api', [
        '%api' => $apiNid,
      ]);
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    }
    return $rc;
  }

  /**
   * Mail members subscribing to plans including a given API
   *
   * @param array $mailParams
   * @param array $from
   * @param $langcode
   *
   * @return int
   * @throws \Exception
   */
  public function mailApiMembers($mailParams = [], $from = [], $langcode): int {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $apiNid = $mailParams['api'];

    $toList = $this->getApiSubscribingMembers($apiNid);

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $toList, $from, $apiNid);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to members subscribing to API %api', [
        '%api' => $apiNid,
      ]);
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    }
    return $rc;
  }

  /**
   * Mail all consumerorg owners
   *
   * @param array $mailParams
   * @param array $from
   * @param $langcode
   *
   * @return int
   * @throws \Exception
   */
  public function mailAllOwners($mailParams = [], $from = [], $langcode): int {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $toList = $this->getAllSubscribingOwners();

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $toList, $from);

    \Drupal::logger('mail_subscribers')->info('Sent email to all consumer organization owners');

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    }
    return $rc;
  }

  /**
   * Mail all consumerorg members
   *
   * @param $mailParams
   * @param array $from
   * @param $langcode
   *
   * @return int
   * @throws \Exception
   */
  public function mailAllMembers($mailParams, $from = [], $langcode): int {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $toList = $this->getAllSubscribingMembers();

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $toList, $from);

    \Drupal::logger('mail_subscribers')->info('Sent email to all consumer organization members');
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    }
    return $rc;
  }

  /**
   * Get subscription owners for a given plan reference ('product:version:plan')
   * To include all plans for a given product then simply specify 'product:version'
   *
   * @param $plan
   *
   * @return array
   */
  public function getProductSubscribingOwners($plan): array {
    return $this->getProductSubscribers($plan, 'owners');
  }

  /**
   * Get all consumer organization members subscribed to a given plan reference ('product:version:plan')
   * To include all plans for a given product then simply specify 'product:version'
   *
   * @param $plan
   *
   * @return array
   */
  public function getProductSubscribingMembers($plan): array {
    return $this->getProductSubscribers($plan, 'members');
  }

  /**
   * Get subscribers for a given plan reference ('product:version:plan')
   * To include all plans for a given product then simply specify 'product:version'
   *
   * @param $plan
   * @param string $type
   *
   * @return array
   */
  public function getProductSubscribers($plan, $type = 'members'): array {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['plan' => $plan, 'type' => $type]);
    }

    $orgs = [];
    if ($plan !== NULL) {
      $planName = NULL;
      $parts = explode(':', $plan);
      $productUrl = $parts[0];
      if (count($parts) > 1) {
        $planName = $parts[1];
      }

      $query = $query = $this->subscriptionStorage->getQuery();
      $query->condition('product_url', $productUrl);
      if ( isset($planName) && !empty($planName) ) {  // ignore any static analysis tool complaints about this line
        $query->condition('plan',$planName);
      }
      $entityIds = $query->execute();
      if ($entityIds !== NULL && !empty($entityIds)) {
        foreach ($entityIds as $entityId) {
          $sub = $this->subscriptionStorage->load($entityId);
          $orgs[] = $sub->consumerorg_url();
        }
      }
      
    }
    // this is a more performant way to avoid doing array_merge in a loop
    // the inner empty array covers cases when no loops were made
    $recipients = [[]];
    // get users in those orgs
    if ($orgs !== NULL && \is_array($orgs)) {
      foreach ($orgs as $org) {
        $query = $this->nodeStorage->getQuery();
        $query->condition('type', 'consumerorg');
        $query->condition('consumerorg_url.value', $org);
        $nids = $query->execute();
        if ($nids !== NULL && !empty($nids)) {
          $nodes = $this->nodeStorage->loadMultiple($nids);
          if ($nodes !== NULL && \is_array($nodes)) {
            foreach ($nodes as $node) {
              $orgRecipients = [];
              if ($type === 'members') {
                $members = $node->get('consumerorg_members')->getValue();
                if (isset($members)) {
                  foreach ($members as $member) {
                    $memberValue = unserialize($member['value'], ['allowed_classes' => FALSE]);
                    $email = $memberValue['user']['mail'];
                    if (isset($email) && !empty($email)) {
                      $orgRecipients[] = $email;
                    }
                  }
                }
              }
              $consumerorgOwner = NULL;
              $consumerorgOwnerUrl = $node->get('consumerorg_owner')->value;
              $consumerorgOwnerAccount = $this->userStorage->loadUserByUrl($consumerorgOwnerUrl);
              if ($consumerorgOwnerAccount !== NULL && $this->emailValidator->isValid($consumerorgOwnerAccount->getEmail())) {
                $consumerorgOwner = $consumerorgOwnerAccount->getEmail();
              }
              if ($consumerorgOwner !== NULL && !empty($consumerorgOwner)) {
                $orgRecipients[] = $consumerorgOwner;
              }
              if (!empty($orgRecipients)) {
                $recipients[] = $orgRecipients;
              }
            }
          }
        }
      }
    }
    $recipients = array_merge(...$recipients);
    $recipients = array_unique($recipients);
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, count($recipients));
    }
    return $recipients;
  }

  /**
   * Get subscription owners for a given API NID
   *
   * @param $apiNid
   *
   * @return array
   */
  public function getApiSubscribingOwners($apiNid): array {
    return $this->getApiSubscribers($apiNid, 'owners');
  }

  /**
   * Get all consumer organization members subscribed to a given API NID
   *
   * @param $apiNid
   *
   * @return array
   */
  public function getApiSubscribingMembers($apiNid): array {
    return $this->getApiSubscribers($apiNid, 'members');
  }

  /**
   * Get subscribers for a given API NID
   *
   * @param $apiNid
   * @param string $type
   *
   * @return array
   */
  public function getApiSubscribers($apiNid, $type = 'members'): array {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['apiNid' => $apiNid, 'type' => $type]);
    }
    $orgs = [];
    // get products containing this api
    if ($apiNid !== NULL) {
      $api = $this->nodeStorage->load($apiNid);
      if ($api !== NULL) {
        $query = $this->nodeStorage->getQuery();
        $query->condition('type', 'product');
        $query->condition('product_apis.value', $api->get('apic_ref')->value, 'CONTAINS');

        $prodNids = $query->execute();
        if ($prodNids !== NULL) {
          // get subscribed apps to those products
          foreach ($prodNids as $prodNid) {
            $product = $this->nodeStorage->load($prodNid);
            if ($product !== NULL) {
              $query = $this->subscriptionStorage->getQuery();
              $query->condition('product_url', $product->get('apic_url')->value);
              $entityIds = $query->execute();
              if ($entityIds !== NULL && !empty($entityIds)) {
                foreach ($entityIds as $entityId) {
                  $sub = $this->subscriptionStorage->load($entityId);
                  $orgs[] = $sub->consumerorg_url();
                }
              }
            }
          }
        }
      }
    }

    // this is a more performant way to avoid doing array_merge in a loop
    // the inner empty array covers cases when no loops were made
    $recipients = [[]];
    // get users in those orgs
    if ($orgs !== NULL && \is_array($orgs)) {
      foreach ($orgs as $org) {
        $query = $this->nodeStorage->getQuery();
        $query->condition('type', 'consumerorg');
        $query->condition('consumerorg_url.value', $org);
        $nids = $query->execute();
        if ($nids !== NULL) {
          $nodes = $this->nodeStorage->loadMultiple($nids);
          if ($nodes !== NULL && \is_array($nodes)) {
            foreach ($nodes as $node) {
              $orgRecipients = [];
              if ($type === 'members') {
                $members = $node->get('consumerorg_members')->getValue();
                if (isset($members)) {
                  foreach ($members as $member) {
                    $memberValue = unserialize($member['value'], ['allowed_classes' => FALSE]);
                    $email = $memberValue['user']['mail'];
                    if (isset($email) && !empty($email)) {
                      $orgRecipients[] = $email;
                    }
                  }
                }
              }
              $consumerorgOwner = NULL;
              $consumerorgOwnerUrl = $node->get('consumerorg_owner')->value;
              $consumerorgOwnerAccount = $this->userStorage->loadUserByUrl($consumerorgOwnerUrl);
              if ($consumerorgOwnerAccount !== NULL && $this->emailValidator->isValid($consumerorgOwnerAccount->getEmail()) ){
                $consumerorgOwner = $consumerorgOwnerAccount->getEmail();
              }
              if ($consumerorgOwner !== NULL && !empty($consumerorgOwner)) {
                $orgRecipients[] = $consumerorgOwner;
              }
              if (!empty($orgRecipients)) {
                $recipients[] = $orgRecipients;
              }
            }
          }
        }
      }
    }
    $recipients = array_merge(...$recipients);
    $recipients = array_unique($recipients);
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, count($recipients));
    }
    return $recipients;
  }

  /**
   * Get subscription owners for a given plan reference ('product:version:plan')
   * To include all plans for a given product then simply specify 'product:version'
   *
   * @return array
   */
  public function getAllSubscribingOwners(): array {
    return $this->getAllSubscribers('owners');
  }

  /**
   * Get all consumer organization members subscribed to a given plan reference ('product:version:plan')
   * To include all plans for a given product then simply specify 'product:version'
   *
   * @return array
   */
  public function getAllSubscribingMembers(): array {
    return $this->getAllSubscribers('members');
  }

  /**
   * @param string $type
   *
   * @return array
   */
  public function getAllSubscribers($type = 'members'): array {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $type);
    }
    // this is a more performant way to avoid doing array_merge in a loop
    // the inner empty array covers cases when no loops were made
    $recipients = [[]];
    // get users in all orgs
    $query = $this->nodeStorage->getQuery();
    $query->condition('type', 'consumerorg');
    $nids = $query->execute();
    if ($nids !== NULL) {
      $nodes = $this->nodeStorage->loadMultiple($nids);
      if ($nodes !== NULL && \is_array($nodes)) {
        foreach ($nodes as $node) {
          $orgRecipients = [];
          if ($type === 'members') {
            $members = $node->get('consumerorg_members')->getValue();
            if (isset($members)) {
              foreach ($members as $member) {
                $memberValue = unserialize($member['value'], ['allowed_classes' => FALSE]);
                $email = $memberValue['user']['mail'];
                if (isset($email) && !empty($email)) {
                  $orgRecipients[] = $email;
                }
              }
            }
          }
          $consumerorgOwner = NULL;
          $consumerorgOwnerUrl = $node->get('consumerorg_owner')->value;
          $consumerorgOwnerAccount = $this->userStorage->loadUserByUrl($consumerorgOwnerUrl);
          if ($consumerorgOwnerAccount !== NULL && $this->emailValidator->isValid($consumerorgOwnerAccount->getEmail())) {
            $consumerorgOwner = $consumerorgOwnerAccount->getEmail();
          }
          if ($consumerorgOwner !== NULL && !empty($consumerorgOwner)) {
            $orgRecipients[] = $consumerorgOwner;
          }
          if (!empty($orgRecipients)) {
            $recipients[] = $orgRecipients;
          }
        }
      }
    }
    $recipients = array_merge(...$recipients);
    $recipients = array_unique($recipients);
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, count($recipients));
    }
    return $recipients;
  }

  /**
   * @param $mailParams
   * @param array $toList
   * @param array $from
   * @param int|null $nid
   *
   * @return int
   * @throws \Exception
   */
  public function sendEmail($mailParams, $toList = [], $from = [], $nid = NULL): int {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    module_load_include('helpers.inc', 'mail_subscribers');
    $siteConfig = \Drupal::config('system.site');
    if (!isset($from['name']) || empty($from['name'])) {
      $from['name'] = $siteConfig->get('name');
    }
    if (!isset($from['mail']) || empty($from['mail'])) {
      $from['mail'] = $siteConfig->get('mail');
    }

    $operations = [];
    $langcode = $mailParams['langcode'];
    $languages = \Drupal::languageManager()->getStandardLanguageList();
    $language = $languages[$langcode] ?? \Drupal::languageManager()->getCurrentLanguage()->getId();
    // We transform receipt, priority in headers, merging them to the user defined headers.
    $headers = _mail_subscribers_headers($mailParams['receipt'], $mailParams['priority'], $from['mail'], $mailParams['headers']);

    if ($mailParams['message']['format'] === 'plain_text') {
      $plainFormat = TRUE;
      $headers['Content-Type'] = 'text/plain';
    }
    else {
      $plainFormat = FALSE;
      $headers['Content-Type'] = 'text/html';
    }

    if ($mailParams['carbon_copy'] !== null && (boolean) $mailParams['carbon_copy'] === TRUE) {
      $toList[] = $from['mail'];
    }

    $mailBody = $mailParams['message']['value'];
    $moduleHandler = \Drupal::moduleHandler();
    if ($plainFormat === FALSE && (!$moduleHandler->moduleExists('mimemail') || (\Drupal::config('mimemail.settings')
            ->get('format') === 'plain_text'))) {
      // seem to have been given HTML but need to send in plaintext
      $mailBody = MailFormatHelper::htmlToText($mailParams['message']['value']);
    }
    if ($moduleHandler->moduleExists('token')) {
      $token_service = \Drupal::token();
      $current_user = \Drupal::currentUser();
      if (isset($current_user)) {
        $current_user = User::load($current_user->id());
      }
      $context = array('user' => $current_user);
      if ($nid !== null) {
        $context['node'] = Node::load($nid);
      }
      $mailBody = $token_service->replace($mailBody, $context);

      $mailParams['subject'] = $token_service->replace($mailParams['subject'], $context);
    }
    $sent = 0;
    foreach ($toList as $to) {
      $to = trim(strip_tags($to));
      if ($to !== NULL && !empty($to)) {
        $message = [
          'uid' => \Drupal::currentUser()->id(),
          'timestamp' => time(),
          'from_name' => $from['name'],
          'from_mail' => $from['mail'],
          'to_name' => $to,
          'to_mail' => $to,
          'subject' => strip_tags($mailParams['subject']),
          'body' => $mailBody,
          'headers' => $headers,
        ];
        //$message['format'] = $headers['Content-Type'];

        if ($mailParams['direct'] !== NULL && (boolean) $mailParams['direct'] === TRUE) {
          $operations[] = ['mail_subscribers_batch_deliver', [$message]];
        }
        else {
          _mail_subscribers_prepare_mail($message);
          // Queue the message to the spool table.
          $options = ['target' => 'default'];
          Database::getConnection($options['target'])
            ->insert('mail_subscribers_spool', $options)
            ->fields($message)
            ->execute();
        }
        $sent++;
      }
    }

    if ($mailParams['direct'] !== NULL && (boolean) $mailParams['direct'] === TRUE) {
      $batch = [
        'operations' => $operations,
        'finished' => 'mail_subscribers_batch_deliver_finished',
        'progress_message' => t('Sent @current of @total messages.'),
      ];
      batch_set($batch);
    }
    // return the number of emails sent
    $rc = $sent;

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    }
    return $rc;
  }
}
