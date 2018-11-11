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

namespace Drupal\mail_subscribers\Service;

use Drupal\Core\Mail\MailFormatHelper;
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
  public function mailProductOwners($mailParams = [], $from = [], $langcode) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $product_nid = $mailParams['product'];
    if (isset($mailParams['plan']['name'])) {
      $plan_name = $mailParams['plan']['name'];
    }
    else {
      $plan_name = NULL;
    }

    $product = Node::load($product_nid);

    $to_list = $this->getProductSubscribingOwners($product->apic_url->value . ':' . $plan_name);

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $to_list, $from);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to owners subscribing to product %product', [
        '%product' => $product_nid,
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
  public function mailProductMembers($mailParams = [], $from = [], $langcode) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $product_nid = $mailParams['product'];
    if (isset($mailParams['plan']['name'])) {
      $plan_name = $mailParams['plan']['name'];
    }
    else {
      $plan_name = NULL;
    }

    $product = Node::load($product_nid);
    $to_list = $this->getProductSubscribingMembers($product->apic_url->value . ':' . $plan_name);

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $to_list, $from);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to members subscribing to product %product', [
        '%product' => $product_nid,
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
  public function mailApiOwners($mailParams = [], $from = [], $langcode) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $api_nid = $mailParams['api'];

    $to_list = $this->getApiSubscribingOwners($api_nid);

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $to_list, $from);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to owners subscribing to API %api', [
        '%api' => $api_nid,
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
  public function mailApiMembers($mailParams = [], $from = [], $langcode) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $api_nid = $mailParams['api'];

    $to_list = $this->getApiSubscribingMembers($api_nid);

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $to_list, $from);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to members subscribing to API %api', [
        '%api' => $api_nid,
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
  public function mailAllOwners($mailParams = [], $from = [], $langcode) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $to_list = $this->getAllSubscribingOwners();

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $to_list, $from);

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
  public function mailAllMembers($mailParams, $from = [], $langcode) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $to_list = $this->getAllSubscribingMembers();

    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $to_list, $from);

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
  public function getProductSubscribingOwners($plan) {
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
  public function getProductSubscribingMembers($plan) {
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
  public function getProductSubscribers($plan, $type = 'members') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['plan' => $plan, 'type' => $type]);

    $orgs = [];
    // get subscribed apps
    if (isset($plan)) {
      $parts = explode(':', $plan);
      $product_url = $parts[0];
      if (count($parts) > 1) {
        $planname = $parts[1];
      }

      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      if (isset($planname)) {
        $query->condition('application_subscriptions.value', $product_url.'";s:4:"plan";s:' . \strlen($planname) . ':"' . $planname . '"', 'CONTAINS');
      }
      else {
        $query->condition('application_subscriptions.value', $product_url, 'CONTAINS');
      }
      $nids = $query->execute();
      if (isset($nids) && !empty($nids)) {
        $nodes = Node::loadMultiple($nids);
        if (isset($nodes)) {
          foreach ($nodes as $node) {
            $orgs[] = $node->application_consumer_org_url->value;
          }
        }
      }
    }
    $recipients = [];
    // get users in those orgs
    if (isset($orgs) && is_array($orgs)) {
      foreach ($orgs as $org) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'consumerorg');
        $query->condition('consumerorg_url.value', $org);
        $nids = $query->execute();
        if (isset($nids) && !empty($nids)) {
          $nodes = Node::loadMultiple($nids);
          if (isset($nodes) && is_array($nodes)) {
            foreach ($nodes as $node) {
              $org_recipients = [];
              if ($type === 'members') {
                if (isset($node->consumerorg_members->value)) {
                  $members = unserialize($node->consumerorg_members->value);
                  foreach ($members as $member) {
                    if (isset($member['email']) && !empty($member['email'])) {
                      $org_recipients[] = $member['email'];
                    }
                  }
                }
              }
              $consumerorg_owner = null;
              $consumerorg_owner_url = $node->consumerorg_owner->value;
              $consumerorg_owner_account = \Drupal::service('auth_apic.usermanager')->findUserByUrl($consumerorg_owner_url);
              if ($consumerorg_owner_account !== NULL && \Drupal::service('email.validator')
                  ->isValid($consumerorg_owner_account->getEmail())) {
                $consumerorg_owner = $consumerorg_owner_account->getEmail();
              }
              if (isset($consumerorg_owner) && !empty($consumerorg_owner)) {
                $org_recipients[] = $consumerorg_owner;
              }
              if (!empty($org_recipients)) {
                $recipients[] = implode(',', $org_recipients);
              }
            }
          }
        }
      }
    }
    $recipients = array_unique($recipients);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, count($recipients));
    return $recipients;
  }

  /**
   * Get subscription owners for a given API NID
   *
   * @param $apinid
   *
   * @return array
   */
  function getApiSubscribingOwners($apinid) {
    return $this->getApiSubscribers($apinid, 'owners');
  }

  /**
   * Get all consumer organization members subscribed to a given API NID
   *
   * @param $apinid
   *
   * @return array
   */
  function getApiSubscribingMembers($apinid) {
    return $this->getApiSubscribers($apinid, 'members');
  }

  /**
   * Get subscribers for a given API NID
   *
   * @param $apinid
   * @param string $type
   *
   * @return array
   */
  function getApiSubscribers($apinid, $type = 'members') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['apinid' => $apinid, 'type' => $type]);
    $orgs = [];
    // get products containing this api
    if (isset($apinid)) {
      $api = Node::load($apinid);
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('product_apis.value', $api->apic_ref->value, 'CONTAINS');

      $prod_nids = $query->execute();
      if (isset($prod_nids)) {
        // get subscribed apps to those products
        foreach ($prod_nids as $prod_nid) {
          $product = Node::load($prod_nid);
          if (isset($product)) {
            $query = \Drupal::entityQuery('node');
            $query->condition('type', 'application');
            $query->condition('application_subscriptions.value', $product->apic_url->value, 'CONTAINS');
            $appnids = $query->execute();
            if (isset($appnids)) {
              $appnodes = Node::loadMultiple($appnids);
              if (isset($appnodes)) {
                foreach ($appnodes as $app) {
                  $orgs[] = $app->application_consumer_org_url->value;
                }
              }
            }
          }
        }
      }
    }

    $recipients = [];
    // get users in those orgs
    if (isset($orgs) && is_array($orgs)) {
      foreach ($orgs as $org) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'consumerorg');
        $query->condition('consumerorg_url.value', $org);
        $nids = $query->execute();
        if (isset($nids)) {
          $nodes = Node::loadMultiple($nids);
          if (isset($nodes) && is_array($nodes)) {
            foreach ($nodes as $node) {
              $org_recipients = [];
              if ($type === 'members') {
                if (isset($node->consumerorg_members->value)) {
                  $members = unserialize($node->consumerorg_members->value);
                  foreach ($members as $member) {
                    if (isset($member['email']) && !empty($member['email'])) {
                      $org_recipients[] = $member['email'];
                    }
                  }
                }
              }
              $consumerorg_owner = null;
              $consumerorg_owner_url = $node->consumerorg_owner->value;
              $consumerorg_owner_account = \Drupal::service('auth_apic.usermanager')->findUserByUrl($consumerorg_owner_url);
              if ($consumerorg_owner_account !== NULL && \Drupal::service('email.validator')
                  ->isValid($consumerorg_owner_account->getEmail())) {
                $consumerorg_owner = $consumerorg_owner_account->getEmail();
              }
              if (isset($consumerorg_owner) && !empty($consumerorg_owner)) {
                $org_recipients[] = $consumerorg_owner;
              }
              if (!empty($org_recipients)) {
                $recipients[] = implode(',', $org_recipients);
              }
            }
          }
        }
      }
    }
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
  public function getAllSubscribingOwners() {
    return $this->getAllSubscribers('owners');
  }

  /**
   * Get all consumer organization members subscribed to a given plan reference ('product:version:plan')
   * To include all plans for a given product then simply specify 'product:version'
   *
   * @return array
   */
  public function getAllSubscribingMembers() {
    return $this->getAllSubscribers('members');
  }

  /**
   * @param string $type
   *
   * @return array
   */
  public function getAllSubscribers($type = 'members') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $type);
    $recipients = [];
    // get users in all orgs
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $nids = $query->execute();
    if (isset($nids)) {
      $nodes = Node::loadMultiple($nids);
      if (isset($nodes) && is_array($nodes)) {
        foreach ($nodes as $node) {
          $org_recipients = [];
          if ($type === 'members') {
            if (isset($node->consumerorg_members->value)) {
              $members = unserialize($node->consumerorg_members->value);
              foreach ($members as $member) {
                if (isset($member['email']) && !empty($member['email'])) {
                  $org_recipients[] = $member['email'];
                }
              }
            }
          }
          $consumerorg_owner = null;
          $consumerorg_owner_url = $node->consumerorg_owner->value;
          $consumerorg_owner_account = \Drupal::service('auth_apic.usermanager')->findUserByUrl($consumerorg_owner_url);
          if ($consumerorg_owner_account !== NULL && \Drupal::service('email.validator')
              ->isValid($consumerorg_owner_account->getEmail())) {
            $consumerorg_owner = $consumerorg_owner_account->getEmail();
          }
          if (isset($consumerorg_owner) && !empty($consumerorg_owner)) {
            $org_recipients[] = $consumerorg_owner;
          }
          if (!empty($org_recipients)) {
            $recipients[] = implode(',', $org_recipients);
          }
        }
      }
    }
    $recipients = array_unique($recipients);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, count($recipients));
    return $recipients;
  }

  /**
   * @param $mailParams
   * @param array $to_list
   * @param array $from
   *
   * @return int
   * @throws \Exception
   */
  public function sendEmail($mailParams, $to_list = [], $from = []) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    module_load_include('helpers.inc', 'mail_subscribers');
    $site_config = \Drupal::config('system.site');
    if (!isset($from['name']) || empty($from['name'])) {
      $from['name'] = $site_config->get('name');
    }
    if (!isset($from['mail']) || empty($from['mail'])) {
      $from['mail'] = $site_config->get('mail');
    }

    $operations = [];
    $langcode = $mailParams['langcode'];
    $languages = \Drupal::languageManager()->getStandardLanguageList();
    $language = isset($languages[$langcode]) ? $languages[$langcode] : \Drupal::languageManager()
      ->getCurrentLanguage()
      ->getId();
    // We transform receipt, priority in headers, merging them to the user defined headers.
    $headers = _mail_subscribers_headers($mailParams['receipt'], $mailParams['priority'], $from['mail'], $mailParams['headers']);

    if ($mailParams['message']['format'] === 'plain_text') {
      $plain_format = TRUE;
      $headers['Content-Type'] = 'text/plain';
    }
    else {
      $plain_format = FALSE;
      $headers['Content-Type'] = 'text/html';
    }

    if (isset($mailParams['carbon_copy']) && $mailParams['carbon_copy'] == TRUE) {
      $to_list[] = $from['mail'];
    }
    $rules_enabled = \Drupal::moduleHandler()->moduleExists('rules');

    $mail_body = $mailParams['message']['value'];
    if ((!\Drupal::moduleHandler()->moduleExists('mimemail') || (\Drupal::config('mimemail.settings')
            ->get('format') === 'plain_text')) && $plain_format == FALSE
    ) {
      // seem to have been given HTML but need to send in plaintext
      $mail_body = MailFormatHelper::htmlToText($mailParams['message']['value']);
    }
    $sent = 0;
    foreach ($to_list as $to) {
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
          'body' => $mail_body,
          'headers' => $headers,
        ];
        //$message['format'] = $headers['Content-Type'];

        if (isset($mailParams['direct']) && $mailParams['direct'] == TRUE) {
          $operations[] = ['mail_subscribers_batch_deliver', [$message]];
        }
        else {
          _mail_subscribers_prepare_mail($message);
          // Queue the message to the spool table.
          db_insert('mail_subscribers_spool')->fields($message)->execute();
          if ($rules_enabled) {
            $event = new MailAddedEvent($message);
            $event_dispatcher = \Drupal::service('event_dispatcher');
            $event_dispatcher->dispatch(MailAddedEvent::EVENT_NAME, $event);
          }
        }
        $sent++;
      }
    }

    if (isset($mailParams['direct']) && $mailParams['direct'] == TRUE) {
      $batch = [
        'operations' => $operations,
        'finished' => 'mail_subscribers_batch_deliver_finished',
        'progress_message' => t('Sent @current of @total messages.'),
      ];
      batch_set($batch);
    }
    else {
      if (\Drupal::moduleHandler()->moduleExists('rules')) {
        $event = new AllMailAddedEvent(count($to_list));
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch(AllMailAddedEvent::EVENT_NAME, $event);
      }
    }
    // return the number of emails sent
    $rc = $sent;

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    return $rc;
  }
}
