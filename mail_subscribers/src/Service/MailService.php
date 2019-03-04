<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\mail_subscribers\Service;

use Drupal\consumerorg\ApicType\Member;
use Drupal\Core\Database\Database;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\mail_subscribers\Event\AllMailAddedEvent;
use Drupal\mail_subscribers\Event\MailAddedEvent;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class MailService {

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
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

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

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
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
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

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
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
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
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $apiNid = $mailParams['api'];

    $toList = $this->getApiSubscribingOwners($apiNid);

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $toList, $from, $apiNid);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to owners subscribing to API %api', [
        '%api' => $apiNid,
      ]);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
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
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $apiNid = $mailParams['api'];

    $toList = $this->getApiSubscribingMembers($apiNid);

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $toList, $from, $apiNid);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to members subscribing to API %api', [
        '%api' => $apiNid,
      ]);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
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
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $toList = $this->getAllSubscribingOwners();

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $toList, $from);

    \Drupal::logger('mail_subscribers')->info('Sent email to all consumer organization owners');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
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
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $toList = $this->getAllSubscribingMembers();

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $toList, $from);

    \Drupal::logger('mail_subscribers')->info('Sent email to all consumer organization members');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
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
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['plan' => $plan, 'type' => $type]);

    $orgs = [];
    // get subscribed apps
    if ($plan !== NULL) {
      $planName = NULL;
      $parts = explode(':', $plan);
      $productUrl = $parts[0];
      if (count($parts) > 1) {
        $planName = $parts[1];
      }

      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      if ($planName !== NULL) {
        $query->condition('application_subscriptions.value', $productUrl . '";s:4:"plan";s:' . \strlen($planName) . ':"' . $planName . '"', 'CONTAINS');
      }
      else {
        $query->condition('application_subscriptions.value', $productUrl, 'CONTAINS');
      }
      $nids = $query->execute();
      if ($nids !== NULL && !empty($nids)) {
        $nodes = Node::loadMultiple($nids);
        if ($nodes !== NULL) {
          foreach ($nodes as $node) {
            $orgs[] = $node->application_consumer_org_url->value;
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
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'consumerorg');
        $query->condition('consumerorg_url.value', $org);
        $nids = $query->execute();
        if ($nids !== NULL && !empty($nids)) {
          $nodes = Node::loadMultiple($nids);
          if ($nodes !== NULL && \is_array($nodes)) {
            foreach ($nodes as $node) {
              $orgRecipients = [];
              if ($type === 'members') {
                $members = $node->consumerorg_members->getValue();
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
              $consumerorgOwnerUrl = $node->consumerorg_owner->value;
              $consumerorgOwnerAccount = \Drupal::service('auth_apic.usermanager')
                ->findUserByUrl($consumerorgOwnerUrl);
              if ($consumerorgOwnerAccount !== NULL && \Drupal::service('email.validator')
                  ->isValid($consumerorgOwnerAccount->getEmail())) {
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
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, count($recipients));
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
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['apiNid' => $apiNid, 'type' => $type]);
    $orgs = [];
    // get products containing this api
    if ($apiNid !== NULL) {
      $api = Node::load($apiNid);
      if ($api !== NULL) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'product');
        $query->condition('product_apis.value', $api->apic_ref->value, 'CONTAINS');

        $prodNids = $query->execute();
        if ($prodNids !== NULL) {
          // get subscribed apps to those products
          foreach ($prodNids as $prodNid) {
            $product = Node::load($prodNid);
            if ($product !== NULL) {
              $query = \Drupal::entityQuery('node');
              $query->condition('type', 'application');
              $query->condition('application_subscriptions.value', $product->apic_url->value, 'CONTAINS');
              $appNids = $query->execute();
              if ($appNids !== NULL) {
                $appNodes = Node::loadMultiple($appNids);
                if ($appNodes !== NULL) {
                  foreach ($appNodes as $app) {
                    $orgs[] = $app->application_consumer_org_url->value;
                  }
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
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'consumerorg');
        $query->condition('consumerorg_url.value', $org);
        $nids = $query->execute();
        if ($nids !== NULL) {
          $nodes = Node::loadMultiple($nids);
          if ($nodes !== NULL && \is_array($nodes)) {
            foreach ($nodes as $node) {
              $orgRecipients = [];
              if ($type === 'members') {
                $members = $node->consumerorg_members->getValue();
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
              $consumerorgOwnerUrl = $node->consumerorg_owner->value;
              $consumerorgOwnerAccount = \Drupal::service('auth_apic.usermanager')
                ->findUserByUrl($consumerorgOwnerUrl);
              if ($consumerorgOwnerAccount !== NULL && \Drupal::service('email.validator')
                  ->isValid($consumerorgOwnerAccount->getEmail())) {
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
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, count($recipients));
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
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $type);
    // this is a more performant way to avoid doing array_merge in a loop
    // the inner empty array covers cases when no loops were made
    $recipients = [[]];
    // get users in all orgs
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $nids = $query->execute();
    if ($nids !== NULL) {
      $nodes = Node::loadMultiple($nids);
      if ($nodes !== NULL && \is_array($nodes)) {
        foreach ($nodes as $node) {
          $orgRecipients = [];
          if ($type === 'members') {
            $members = $node->consumerorg_members->getValue();
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
          $consumerorgOwnerUrl = $node->consumerorg_owner->value;
          $consumerorgOwnerAccount = \Drupal::service('auth_apic.usermanager')->findUserByUrl($consumerorgOwnerUrl);
          if ($consumerorgOwnerAccount !== NULL && \Drupal::service('email.validator')
              ->isValid($consumerorgOwnerAccount->getEmail())) {
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
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, count($recipients));
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
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
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
    $rulesEnabled = \Drupal::moduleHandler()->moduleExists('rules');

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
          if ($rulesEnabled) {
            $event = new MailAddedEvent($message);
            $eventDispatcher = \Drupal::service('event_dispatcher');
            $eventDispatcher->dispatch(MailAddedEvent::EVENT_NAME, $event);
          }
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
    elseif (\Drupal::moduleHandler()->moduleExists('rules')) {
      $event = new AllMailAddedEvent(count($toList));
      $eventDispatcher = \Drupal::service('event_dispatcher');
      $eventDispatcher->dispatch(AllMailAddedEvent::EVENT_NAME, $event);
    }
    // return the number of emails sent
    $rc = $sent;

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    return $rc;
  }
}
