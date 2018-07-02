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

class MailService {

  /**
   * Mail owners subscribing to a specific plan (if specified) or product (if it isn't)
   *
   * @param $mailParams
   * @param array $from
   * @param $langcode
   * @return int
   */
  public function mailProductOwners($mailParams = array(), $from = array(), $langcode) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $product_nid = $mailParams['product'];
    if (isset($mailParams['plan'])) {
      $plan_name = $mailParams['plan'];
    } else {
      $plan_name = null;
    }

    $product = Node::load($product_nid);
    $plan_ref = '';
    if (isset($plan_name)) {
      $plans = array();
      foreach($product->product_plans->getValue() as $arrayValue){
        $plans[] = unserialize($arrayValue['value']);
      }
      foreach($plans as $plan){
        if ($plan_name['title'] == $plan['title']) {
          $plan_ref = ':' . $plan_name['title'];
        }
      }
    }
    $to_list = $this->getProductSubscribingOwners($product->apic_ref->value . $plan_ref);
    if (!\Drupal::moduleHandler()->moduleExists('mimemail') || (\Drupal::config('mimemail.settings')
          ->get('format') == 'plain_text')
    ) {
      $mailParams['message'] = MailFormatHelper::htmlToText($mailParams['message']);
    }
    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $to_list, $from);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to owners subscribing to product %product', array(
        '%product' => $product_nid
      ));

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    return $rc;
  }

  /**
   * Mail members subscribing to a specific plan (if specified) or product (if it isn't)
   *
   * @param $mailParams
   * @param array $from
   * @param $langcode
   * @return int
   */
  public function mailProductMembers($mailParams = array(), $from = array(), $langcode) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $product_nid = $mailParams['product'];
    if (isset($mailParams['plan'])) {
      $plan_name = $mailParams['plan'];
    } else {
      $plan_name = null;
    }

    $product = Node::load($product_nid);
    $plan_ref = '';
    if (isset($plan_name)) {
      $plans = array();
      foreach($product->product_plans->getValue() as $arrayValue){
        $plans[] = unserialize($arrayValue['value']);
      }
      foreach($plans as $plan){
        if ($plan_name['title'] == $plan['title']) {
          $plan_ref = ':' . $plan_name['title'];
        }
      }
    }
    $to_list = $this->getProductSubscribingMembers($product->apic_ref->value . $plan_ref);
    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $to_list, $from);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to members subscribing to product %product', array(
        '%product' => $product_nid
      ));
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    return $rc;
  }

  /**
   * Mail owners subscribing to plans including a given API
   *
   * @param $mailParams
   * @param array $from
   * @param $langcode
   * @return int
   */
  public function mailApiOwners($mailParams = array(), $from = array(), $langcode) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $api_nid = $mailParams['api'];

    $to_list = $this->getApiSubscribingOwners($api_nid);
    if (!\Drupal::moduleHandler()->moduleExists('mimemail') || (\Drupal::config('mimemail.settings')
          ->get('format') == 'plain_text')
    ) {
      $mailParams['message'] = MailFormatHelper::htmlToText($mailParams['message']);
    }
    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $to_list, $from);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to owners subscribing to API %api', array(
        '%api' => $api_nid
      ));

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    return $rc;
  }

  /**
   * Mail members subscribing to plans including a given API
   *
   * @param $mailParams
   * @param array $from
   * @param $langcode
   * @return int
   */
  public function mailApiMembers($mailParams = array(), $from = array(), $langcode) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $api_nid = $mailParams['api'];

    $to_list = $this->getApiSubscribingMembers($api_nid);
    if (!\Drupal::moduleHandler()->moduleExists('mimemail') || (\Drupal::config('mimemail.settings')
          ->get('format') == 'plain_text')
    ) {
      $mailParams['message'] = MailFormatHelper::htmlToText($mailParams['message']);
    }
    $mailParams['langcode'] = $langcode;

    $rc = $this->sendEmail($mailParams, $to_list, $from);

    \Drupal::logger('mail_subscribers')
      ->info('Sent email to members subscribing to API %api', array(
        '%api' => $api_nid
      ));

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    return $rc;
  }

  /**
   * Mail all consumerorg owners
   *
   * @param $mailParams
   * @param array $from
   * @param $langcode
   * @return int
   */
  public function mailAllOwners($mailParams = array(), $from = array(), $langcode) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $to_list = $this->getAllSubscribingOwners();
    if (!\Drupal::moduleHandler()->moduleExists('mimemail') || (\Drupal::config('mimemail.settings')
          ->get('format') == 'plain_text')
    ) {
      $mailParams['message'] = MailFormatHelper::htmlToText($mailParams['message']);
    }
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
   * @return int
   */
  public function mailAllMembers($mailParams, $from = array(), $langcode) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $to_list = $this->getAllSubscribingMembers();
    if (!\Drupal::moduleHandler()->moduleExists('mimemail') || (\Drupal::config('mimemail.settings')
          ->get('format') == 'plain_text')
    ) {
      $mailParams['message'] = MailFormatHelper::htmlToText($mailParams['message']);
    }
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
   * @return array
   */
  public function getProductSubscribers($plan, $type = 'members') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array('plan' => $plan, 'type' => $type ));

    $orgs = array();
    // get subscribed apps
    if (isset($plan)) {
      $parts = explode(':', $plan);
      $product = $parts[0];
      $version = $parts[1];
      if (count($parts)>2) {
        $planname = $parts[2];
      }

      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      if (isset($planname)) {
        $query->condition('application_subscriptions.value', $product . ':' . $version . ':' . $planname, 'CONTAINS');
      }
      else {
        $query->condition('application_subscriptions.value', $product . ':' . $version, 'CONTAINS');
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
    $recipients = array();
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
              $org_recipients = array();
              if ($type == 'members') {
                if (isset($node->consumerorg_members->value)) {
                  $members = unserialize($node->consumerorg_members->value);
                  foreach ($members as $member) {
                    if (isset($member['email'])) {
                      $org_recipients[] = $member['email'];
                    }
                  }
                }
              }
              $consumerorg_owner = $node->consumerorg_owner->value;
              if (!\Drupal::service('email.validator')
                ->isValid($consumerorg_owner)
              ) {
                $account = user_load_by_name($consumerorg_owner);
                if ($account) {
                  $consumerorg_owner = $account->mail;
                }
              }
              $org_recipients[] = $consumerorg_owner;
              $recipients[] = implode(',', $org_recipients);
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
   * @return array
   */
  function getApiSubscribingOwners($apinid) {
    return $this->getApiSubscribers($apinid, 'owners');
  }

  /**
   * Get all consumer organization members subscribed to a given API NID
   *
   * @param $apinid
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
   * @return array
   */
  function getApiSubscribers($apinid, $type = 'members') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array('apinid' => $apinid, 'type' => $type ));
    $orgs = array();
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
            $query->condition('application_subscriptions.value', $product->apic_ref->value, 'CONTAINS');
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

    $recipients = array();
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
              $org_recipients = array();
              if ($type == 'members') {
                if (isset($node->consumerorg_members->value)) {
                  $members = unserialize($node->consumerorg_members->value);
                  foreach ($members as $member) {
                    if (isset($member['email'])) {
                      $org_recipients[] = $member['email'];
                    }
                  }
                }
              }
              $consumerorg_owner = $node->consumerorg_owner->value;
              if (!\Drupal::service('email.validator')
                ->isValid($consumerorg_owner)
              ) {
                $account = user_load_by_name($consumerorg_owner);
                if ($account) {
                  $consumerorg_owner = $account->mail;
                }
              }
              $org_recipients[] = $consumerorg_owner;
              $recipients[] = implode(',', $org_recipients);
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
   * @return array
   */
  public function getAllSubscribers($type = 'members') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $type);
    $recipients = array();
    // get users in all orgs
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $nids = $query->execute();
    if (isset($nids)) {
      $nodes = Node::loadMultiple($nids);
      if (isset($nodes) && is_array($nodes)) {
        foreach ($nodes as $node) {
          $org_recipients = array();
          if ($type == 'members') {
            if (isset($node->consumerorg_members->value)) {
              $members = unserialize($node->consumerorg_members->value);
              foreach ($members as $member) {
                if (isset($member['email'])) {
                  $org_recipients[] = $member['email'];
                }
              }
            }
          }
          $consumerorg_owner_url = $node->consumerorg_owner->value;
          $consumerorg_owner_account = \Drupal::service('auth_apic.usermanager')->findUserByUrl($consumerorg_owner_url);
          if ($consumerorg_owner_account !== NULL && \Drupal::service('email.validator')->isValid($consumerorg_owner_account->getEmail())) {
            $consumerorg_owner = $consumerorg_owner_account->getEmail();
          }
          if(isset($consumerorg_owner)) {
            $org_recipients[] = $consumerorg_owner;
          }
          $recipients[] = implode(',', $org_recipients);
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
   * @return int
   */
  public function sendEmail($mailParams, $to_list = array(), $from = array()) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    module_load_include('helpers.inc', 'mail_subscribers');
    $site_config = \Drupal::config('system.site');
    if (!isset($from['name']) || empty($from['name'])) {
      $from['name'] = $site_config->get('name');
    }
    if (!isset($from['mail']) || empty($from['mail'])) {
      $from['mail'] = $site_config->get('mail');
    }

    $operations = array();
    $langcode = $mailParams['langcode'];
    $languages = \Drupal::languageManager()->getStandardLanguageList();
    $language = isset($languages[$langcode]) ? $languages[$langcode] : \Drupal::languageManager()
      ->getCurrentLanguage()
      ->getId();
    // We transform receipt, priority in headers, merging them to the user defined headers.
    $headers = _mail_subscribers_headers($mailParams['receipt'], $mailParams['priority'], $from['mail'], $mailParams['headers']);

    if ($mailParams['message']['format'] == 'plain_text') {
      $plain_format = TRUE;
      $headers['Content-Type'] = 'text/plain';
    }
    else {
      $plain_format = FALSE;
      $headers['Content-Type'] = 'text/html';
    }

    if (isset($mailParams['carbon_copy']) && $mailParams['carbon_copy'] == true) {
      $to_list[] = $from['mail'];
    }
    $rules_enabled = \Drupal::moduleHandler()->moduleExists('rules');

    $mail_body = $mailParams['message']['value'];
    if ((!\Drupal::moduleHandler()->moduleExists('mimemail') || (\Drupal::config('mimemail.settings')
            ->get('format') == 'plain_text')) && $plain_format == FALSE
    ) {
      // seem to have been given HTML but need to send in plaintext
      $mail_body = MailFormatHelper::htmlToText($mailParams['message']['value']);
    }

    foreach ($to_list as $to) {
      $to = trim(strip_tags($to));
      $message = array(
        'uid' => \Drupal::currentUser()->id(),
        'timestamp' => time(),
        'from_name' => $from['name'],
        'from_mail' => $from['mail'],
        'to_name' => $to,
        'to_mail' => $to,
        'subject' => strip_tags($mailParams['subject']),
        'body' => $mail_body,
        'headers' => $headers,
      );
      //$message['format'] = $headers['Content-Type'];

      if (isset($mailParams['direct']) && $mailParams['direct'] == true) {
        $operations[] = array('mail_subscribers_batch_deliver', array($message));
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
    }

    if (isset($mailParams['direct']) && $mailParams['direct'] == true) {
      $batch = array(
        'operations' => $operations,
        'finished' => 'mail_subscribers_batch_deliver_finished',
        'progress_message' => t('Sent @current of @total messages.'),
      );
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
    $rc = count($to_list);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    return $rc;
  }
}
