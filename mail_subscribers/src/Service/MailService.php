<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\mail_subscribers\Service;

use Drupal\Component\Utility\EmailValidator;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\node\Entity\Node;

class MailService {

  /**
   * @var \Drupal\Component\Utility\EmailValidator \
   */
  protected EmailValidator $emailValidator;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $nodeStorage;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface
   */
  protected ApicUserStorageInterface $userStorage;

  /**
   * @var int
   */
  protected int $sent;

  /**
   * @var array
   */
  protected array $operations;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $subscriptionStorage;

  /**
   * @param \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface $user_storage
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Component\Utility\EmailValidator $email_validator
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(ApicUserStorageInterface   $user_storage,
                              EntityTypeManagerInterface $entityTypeManager,
                              EmailValidator             $email_validator) {
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->subscriptionStorage = $entityTypeManager->getStorage('apic_app_application_subs');
    $this->userStorage = $user_storage;
    $this->emailValidator = $email_validator;

    $this->sent = 0;
    $this->operations = [];
  }


  /**
   * Mail owners subscribing to a specific plan (if specified) or product (if it isn't)
   *
   * @param array $mailParams
   * @param array $from
   * @param $langcode
   *
   * @return int
   * @throws \Exception
   */
  public function mailProductOwners(array $mailParams, array $from, $langcode): int {
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
   * @param array $mailParams
   * @param array $from
   * @param $langcode
   *
   * @return int
   * @throws \Exception
   */
  public function mailProductMembers(array $mailParams, array $from, $langcode): int {
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
   * @param array $mailParams
   * @param array $from
   * @param $langcode
   *
   * @return int
   * @throws \Exception
   */
  public function mailApiOwners(array $mailParams, array $from, $langcode): int {
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
  public function mailApiMembers(array $mailParams, array $from, $langcode): int {
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
  public function mailAllOwners(array $mailParams, array $from, $langcode): int {
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
   * @param array $mailParams
   * @param array $from
   * @param $langcode
   *
   * @return int
   * @throws \Exception
   */
  public function mailAllMembers(array $mailParams, array $from, $langcode): int {
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
  public function getProductSubscribers($plan, string $type = 'members'): array {
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

      $query = $this->subscriptionStorage->getQuery();
      $query->condition('product_url', $productUrl);
      if (isset($planName) && !empty($planName)) {  // ignore any static analysis tool complaints about this line
        $query->condition('plan', $planName);
      }
      $entityIds = $query->execute();
      if ($entityIds !== NULL && !empty($entityIds)) {
        foreach ($entityIds as $entityId) {
          $sub = $this->subscriptionStorage->load($entityId);
          if (!in_array($sub->consumerorg_url(), $orgs, TRUE)) {
            $orgs[] = $sub->consumerorg_url();
          }
        }
      }
    }

    $recipients = [];
    // get users in those orgs
    if ($orgs !== NULL && \is_array($orgs)) {
      foreach ($orgs as $org) {
        $query = $this->nodeStorage->getQuery();
        $query->condition('type', 'consumerorg');
        $query->condition('consumerorg_url.value', $org);
        $nids = $query->execute();
        if ($nids !== NULL && !empty($nids)) {
          $recipients += $this->getConsumerOrgRecipients($nids, $type);
        }
      }
    }

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
   * Get all eligible recipients for the given consumer org
   *
   * @param $consumerOrgNids
   * @param $type
   *
   * @return array
   */
  public function getConsumerOrgRecipients($consumerOrgNids, $type): array {
    $recipients = [];

    $nodes = $this->nodeStorage->loadMultiple($consumerOrgNids);
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
        // Dont add the owner address if it is already in the recipients array
        if ($consumerorgOwner !== NULL && !empty($consumerorgOwner) && !in_array($consumerorgOwner, $orgRecipients, TRUE)) {
          $orgRecipients[] = $consumerorgOwner;
        }
        if (!empty($orgRecipients)) {
          $recipients[$node->id()] = $orgRecipients;
        }
      }
    }

    return $recipients;
  }

  /**
   * Get subscribers for a given API NID
   *
   * @param $apiNid
   * @param string $type
   *
   * @return array
   */
  public function getApiSubscribers($apiNid, string $type = 'members'): array {
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
          // get the consumer orgs of subscribed apps to those products
          foreach ($prodNids as $prodNid) {
            $product = $this->nodeStorage->load($prodNid);
            if ($product !== NULL) {
              $query = $this->subscriptionStorage->getQuery();
              $query->condition('product_url', $product->get('apic_url')->value);
              $entityIds = $query->execute();
              if ($entityIds !== NULL && !empty($entityIds)) {
                foreach ($entityIds as $entityId) {
                  $sub = $this->subscriptionStorage->load($entityId);
                  if (!in_array($sub->consumerorg_url(), $orgs, TRUE)) {
                    $orgs[] = $sub->consumerorg_url();
                  }
                }
              }
            }
          }
        }
      }
    }

    $recipients = [];
    // get users in those orgs
    if ($orgs !== NULL && \is_array($orgs)) {
      foreach ($orgs as $org) {
        $query = $this->nodeStorage->getQuery();
        $query->condition('type', 'consumerorg');
        $query->condition('consumerorg_url.value', $org);
        $nids = $query->execute();
        if ($nids !== NULL) {
          $recipients += $this->getConsumerOrgRecipients($nids, $type);
        }
      }
    }
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
  public function getAllSubscribers(string $type = 'members'): array {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $type);
    }

    $recipients = [];
    // get users in all orgs
    $query = $this->nodeStorage->getQuery();
    $query->condition('type', 'consumerorg');
    $nids = $query->execute();
    if ($nids !== NULL) {
      $recipients += $this->getConsumerOrgRecipients($nids, $type);
    }
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
  public function sendEmail($mailParams, array $toList = [], array $from = [], ?int $nid = NULL): int {
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

    $langcode = $mailParams['langcode'];
    $languages = \Drupal::languageManager()->getStandardLanguageList();
    $language = $languages[$langcode] ?? \Drupal::languageManager()->getCurrentLanguage()->getId();
    // We transform receipt, priority in headers, merging them to the user defined headers.
    $headers = _mail_subscribers_headers($mailParams['receipt'], $mailParams['priority'], $from['mail'], $mailParams['headers']);

    if ($mailParams['message']['format'] === 'plain_text') {
      $headers['Content-Type'] = 'text/plain';
    }
    else {
      $headers['Content-Type'] = 'text/html';
    }

    if ($mailParams['carbon_copy'] !== NULL && (boolean) $mailParams['carbon_copy'] === TRUE) {
      $headers['Bcc'] = $from['mail'];
    }

    $sendOriginal = NULL;

    if ($mailParams['send_original'] !== NULL && (boolean) $mailParams['send_original'] === TRUE) {
      $sendOriginal = $from['mail'];
    }

    $mailBody = $mailParams['message']['value'];

    $mailProperties = [
      'mailParams' => $mailParams,
      'toList' => $toList,
      'from' => $from,
      'mailBody' => $mailBody,
      'headers' => $headers,
      'sendOriginal' => $sendOriginal,
    ];

    $this->processMail($mailProperties);

    if ($mailParams['direct'] !== NULL && (boolean) $mailParams['direct'] === TRUE) {
      $batch = [
        'operations' => $this->operations,
        'finished' => 'mail_subscribers_batch_deliver_finished',
        'progress_message' => t('Sent @current of @total messages.'),
      ];
      batch_set($batch);
    }
    // return the number of emails sent
    $rc = $this->sent;

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $rc);
    }
    return $rc;
  }

  /**
   * Builds up the mail content for each intended recipient based on the object type (flow) that
   * has been initiated.  The relevant context is constructed for token replacement based on the flow.
   *
   * @param $mailProperties
   *
   * @throws \Exception
   */
  public function processMail($mailProperties): void {
    // Loop through each consumer org and process it's recipient list
    foreach ($mailProperties['toList'] as $orgId => $recipients) {
      if (!is_array($recipients)) {
        continue;
      }

      // Set the consumer-org context for token replacement
      $consumerOrg = Node::load($orgId);
      $context['consumer-org'] = $consumerOrg;

      if ($mailProperties['mailParams']['objectType'] === 'all') {
        // Process the recipients per consumer org
        $this->processRecipients($mailProperties, $recipients, $context);
      }
      else {

        switch ($mailProperties['mailParams']['objectType']) {
          case 'all':
            break;
          case 'product':
            // Set the product context for token replacement
            $product = Node::load($mailProperties['mailParams']['product']);

            $this->processConsumerOrgProductApplications($consumerOrg, $product, NULL, $mailProperties, $recipients, $context);
            break;
          case 'api':
            // Set the api context for token replacement
            $api = Node::load($mailProperties['mailParams']['api']);
            $context['api'] = $api;

            // Get the products containing the given api
            $products = $this->getProductsByApi($api);

            // Get the applications belonging to the consumer org that is subscribed to each product containing the api
            foreach ($products as $product) {
              $this->processConsumerOrgProductApplications($consumerOrg, $product, NULL, $mailProperties, $recipients, $context);
            }
            break;
          case 'plan':
            // Set the product context for token replacement
            $product = Node::load($mailProperties['mailParams']['product']);

            // Set the plan context for token replacement
            $planName = $mailProperties['mailParams']['plan']['name'];
            $plan = $this->getPlanFromProduct($planName, $product);
            $context['product-plan'] = $plan;

            $this->processConsumerOrgProductApplications($consumerOrg, $product, $planName, $mailProperties, $recipients, $context);
            break;
        }
      }
    }

    if ($mailProperties['sendOriginal'] !== NULL) {
      $this->queueMail($mailProperties, $mailProperties['sendOriginal']);
      $this->sent++;
    }
  }

  /**
   * Iterates through a list of recipients replacing any tokens in the mail body or subject before
   * queuing the mail for delivery
   *
   * @param $mailProperties
   * @param $recipients
   * @param $context
   *
   * @throws \Exception
   */
  public function processRecipients($mailProperties, $recipients, $context): void {
    $moduleHandler = \Drupal::moduleHandler();

    // Make a copy of the body and subject templates so we can substitute any tokens for each recipient
    $mailBodyTemplate = $mailProperties['mailBody'];
    $subjectTemplate = $mailProperties['mailParams']['subject'];

    foreach ($recipients as $to) {
      $to = trim(strip_tags($to));
      if ($to !== NULL && !empty($to)) {
        if ($moduleHandler->moduleExists('token')) {
          unset($context['user']);
          $token_service = \Drupal::token();
          // Add the individual recipient's user object to the context for token replacement
          $user = user_load_by_mail($to);
          if (isset($user)) {
            $context['user'] = $user;
          }
          else {
            \Drupal::logger('mail_subscribers')
              ->warning('Did not find a user object for the recipient @to.  Any user tokens in the message subject or body will not be substituted', ['@to' => $to]);
          }

          // Replace any tokens in the subject and body
          $mailProperties['mailBody'] = $token_service->replace($mailBodyTemplate, $context);
          $mailProperties['mailParams']['subject'] = $token_service->replace($subjectTemplate, $context);
        }

        $this->queueMail($mailProperties, $to);
        $this->sent++;
      }
    }
  }

  /**
   * Builds up a message associative array and queues it to the spool table
   *
   * @param $mailProperties
   * @param $to
   *
   * @throws \Exception
   */
  public function queueMail($mailProperties, $to): void {
    $message = [
      'uid' => \Drupal::currentUser()->id(),
      'timestamp' => time(),
      'from_name' => $mailProperties['from']['name'],
      'from_mail' => $mailProperties['from']['mail'],
      'to_name' => $to,
      'to_mail' => $to,
      'subject' => strip_tags($mailProperties['mailParams']['subject']),
      'body' => $mailProperties['mailBody'],
      'headers' => $mailProperties['headers'],
    ];
    //$message['format'] = $headers['Content-Type'];

    if ($mailProperties['mailParams']['direct'] !== NULL && (boolean) $mailProperties['mailParams']['direct'] === TRUE) {
      //$operations = ['mail_subscribers_batch_deliver', [$message]];
      $this->operations[] = ['mail_subscribers_batch_deliver', [$message]];
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
  }

  /**
   * Gets the list of application nodes that are subscribed to the given product url within
   * the given consumer org url.  Pass an optional plan name to restrict results to applications
   * subscribed to a particular plan in the product
   *
   * @param $consumerOrgUrl
   * @param $productUrl
   * @param $planName
   *
   * @return mixed
   */
  public function getApplicationsByConsumerOrgProduct($consumerOrgUrl, $productUrl, $planName): mixed {
    $query = $this->subscriptionStorage->getQuery();
    $query->condition('product_url', $productUrl);
    $query->condition('consumerorg_url', $consumerOrgUrl);
    if (isset($planName) && !empty($planName)) {
      $query->condition('plan', $planName);
    }
    $appUrls = [];
    $entityIds = $query->execute();
    if ($entityIds !== NULL && !empty($entityIds)) {
      foreach ($entityIds as $entityId) {
        $sub = $this->subscriptionStorage->load($entityId);
        if ($sub !== NULL) {
          $appUrls[] = $sub->app_url();
        }
      }
    }

    if (isset($appUrls) && !empty($appUrls)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('apic_url.value', $appUrls, 'IN');
      $nids = $query->execute();
      if (isset($nids) && !empty($nids)) {
        return Node::loadMultiple($nids);
      }
    }
    return NULL;
  }

  /**
   * Gets the list of product nodes which contain the given API
   *
   * @param $api
   *
   * @return mixed
   */
  private function getProductsByApi($api) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('product_apis.value', $api->get('apic_ref')->value, 'CONTAINS');
    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      return Node::loadMultiple($nids);
    }
    return NULL;
  }

  /**
   * Finds and unserializes the plan with the given name from the given product
   *
   * @param $planName
   * @param $product
   *
   * @return mixed
   */
  private function getPlanFromProduct($planName, $product) {
    foreach ($product->product_plans->getValue() as $arrayValue) {
      $productPlan = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
      if ($productPlan['name'] === $planName) {
        return $productPlan;
      }
    }

    \Drupal::logger('mail_subscribers')->warning('Did not find a plan with the name @plan in the product @product', [
      '@plan' => $planName,
      '@product' => $product->product_name->value,
    ]);
    return NULL;
  }

  /**
   * @throws \Exception
   */
  private function processConsumerOrgProductApplications($consumerOrg, $product, $planName, $mailProperties, $recipients, $context): void {
    // Set the product context for token replacement
    $context['product'] = $product;

    // Get the applications belonging to the consumer org that is subscribed to the product in question
    $apps = $this->getApplicationsByConsumerOrgProduct($consumerOrg->consumerorg_url->value, $product->apic_url->value, $planName);

    foreach ($apps as $app) {
      // Set the application context for token replacement
      $context['application'] = $app;
      $this->processRecipients($mailProperties, $recipients, $context);
    }
  }

}
