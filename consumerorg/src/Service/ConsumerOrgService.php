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

namespace Drupal\consumerorg\Service;

use Drupal\auth_apic\UserManagerResponse;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Member;
use Drupal\consumerorg\ApicType\Role;
use Drupal\consumerorg\Entity\PaymentMethod;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\field\Entity\FieldConfig;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\ApicUserService;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\EventLogService;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Drupal\ibm_event_log\ApicType\ApicEvent;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;

/**
 * Class to work with the consumerorg content type, takes input from the JSON returned by
 * IBM API Connect
 */
class ConsumerOrgService {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  private SiteConfig $siteconfig;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  private ApimUtils $apimUtils;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  private QueryInterface $userQuery;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  private ManagementServerInterface $apimServer;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  private $session;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  private UserUtils $userUtils;

  /**
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  private $cacheTagsInvalidator;

  /**
   * @var \Drupal\consumerorg\Service\MemberService
   */
  private MemberService $memberService;

  /**
   * @var \Drupal\consumerorg\Service\RoleService
   */
  private RoleService $roleService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\ibm_apim\UserManagement\ApicAccountInterface
   */
  private ApicAccountInterface $accountService;

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserService
   */
  protected ApicUserService $userService;

  /**
   * @var \Drupal\ibm_apim\Service\EventLogService
   */
  protected EventLogService $eventLogService;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected Utils $utils;

  /**
   * ConsumerOrgService constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\Service\SiteConfig $site_config
   * @param \Drupal\ibm_apim\Service\ApimUtils $apimUtils
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface $apim
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\ibm_apim\Service\UserUtils $user_utils
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $invalidator
   * @param \Drupal\consumerorg\Service\MemberService $member_service
   * @param \Drupal\consumerorg\Service\RoleService $role_service
   * @param \Drupal\ibm_apim\UserManagement\ApicAccountInterface $account_service
   * @param ApicUserService $user_service
   * @param \Drupal\ibm_apim\Service\EventLogService $event_log_service
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(LoggerInterface $logger,
                              SiteConfig $site_config,
                              ApimUtils $apimUtils,
                              EventDispatcherInterface $event_dispatcher,
                              AccountProxyInterface $current_user,
                              EntityTypeManagerInterface $entity_type_manager,
                              ModuleHandlerInterface $module_handler,
                              ManagementServerInterface $apim,
                              PrivateTempStoreFactory $temp_store_factory,
                              UserUtils $user_utils,
                              CacheTagsInvalidatorInterface $invalidator,
                              MemberService $member_service,
                              RoleService $role_service,
                              ApicAccountInterface $account_service,
                              ApicUserService $user_service,
                              EventLogService $event_log_service,
                              Utils $utils
  ) {
    $this->logger = $logger;
    $this->siteconfig = $site_config;
    $this->apimUtils = $apimUtils;
    $this->eventDispatcher = $event_dispatcher;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->userQuery = $entity_type_manager->getStorage('user')->getQuery();
    $this->moduleHandler = $module_handler;
    $this->apimServer = $apim;
    $this->session = $temp_store_factory->get('ibm_apim');
    $this->userUtils = $user_utils;
    $this->cacheTagsInvalidator = $invalidator;
    $this->memberService = $member_service;
    $this->roleService = $role_service;
    $this->accountService = $account_service;
    $this->userService = $user_service;
    $this->eventLogService = $event_log_service;
    $this->utils = $utils;
  }

  /**
   * @param string $name
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \JsonException
   */
  public function create(string $name): UserManagerResponse {
    return $this->createFromArray(['title' => $name]);
  }

  /**
   * Create a new consumer org. Calls consumer api to create in apim and handles local nodes and state as well.
   *
   * @param array $values
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \JsonException
   */
  public function createFromArray(array $values): UserManagerResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $response = new UserManagerResponse();

    $org = new ConsumerOrg();

    $name = $values['title'];
    if (is_array($name) && isset($name[0]['value'])) {
      $name = $name[0]['value'];
    }
    elseif (isset($name[0])) {
      $name = array_values($name[0]);
    }
    $org->setName($name);

    //Custom fields should already be null but wipe anyway JIC
    $customFields = $this->getCustomFields();
    foreach ($customFields as $customField) {
      if (isset($values[$customField])) {
        $org->addCustomField($customField, $values[$customField]);
      }
    }

    $apimResponse = $this->apimServer->createConsumerOrg($org);

    if ($apimResponse !== NULL && ($apimResponse->getCode() === 201 || $apimResponse->getCode() === 202)) {
      if ($apimResponse->getCode() === 201) {
        $org->setUrl($apimResponse->getData()['url']);
        $org->setId($apimResponse->getData()['id']);
        $org->setTitle($name);
        $org->setOwnerUrl($apimResponse->getData()['owner_url']);
        $org->setTags($apimResponse->getData()['group_urls']);
        if (isset($apimResponse->getData()['roles'])) {
          $org->setRolesFromArray($apimResponse->getData()['roles']);
        }
        if (isset($apimResponse->getData()['members'])) {
          $org->setMembersFromArray($apimResponse->getData()['members']);
        }
        if (isset($apimResponse->getData()['created_at'])) {
          $org->setCreatedAt(strtotime($apimResponse->getData()['created_at']));
        }
        if (isset($apimResponse->getData()['updated_at'])) {
          $org->setUpdatedAt(strtotime($apimResponse->getData()['updated_at']));
        }
        if (isset($apimResponse->getData()['created_by'])) {
          $org->setCreatedBy($apimResponse->getData()['created_by']);
        }
        if (isset($apimResponse->getData()['updated_by'])) {
          $org->setUpdatedBy($apimResponse->getData()['updated_by']);
        }

        $this->createNode($org, 'consumer_org_create');
        $response->setMessage(t('Consumer organization created successfully.'));
        $this->logger->notice('Consumer organization @orgname created by @username', [
          '@orgname' => $name,
          '@username' => $this->currentUser->getAccountName(),
        ]);

        $this->session->set('consumer_organizations', NULL);
        $this->userUtils->addConsumerOrgToUser($apimResponse->getData()['url'], NULL);
        $this->userUtils->loadConsumerorgs();
        $this->cacheTagsInvalidator->invalidateTags(['config:block.block.consumerorganizationselection']);

        $this->userUtils->setCurrentConsumerorg($org->getUrl());
        $this->userUtils->setOrgSessionData();
        // invalidate the devorg select block cache
        $this->cacheTagsInvalidator->invalidateTags(['consumer_org_select_block:uid:' . $this->currentUser->id()]);
      } else {
        $response->setMessage(t('Your consumer organization was created successfully and is pending approval. You will receive an email once your request is approved.'));
        $this->logger->notice('Consumer organization @orgname created by @username is pending approval.', [
          '@orgname' => $name,
          '@username' => $this->currentUser->getAccountName(),
        ]);
      }
      $response->setSuccess(TRUE);
    }
    else {
      $response->setMessage(t('Failed to create consumer organization.'));
      $response->setSuccess(FALSE);
      $this->logger->error('Failed to create consumer organization. response: @response', ['@response' => serialize($apimResponse->getData())]);

      // If user is not in an org, log them out.
      $other_orgs = $this->getList();
      if (empty($other_orgs)) {
        $response->setRedirect('/user/logout');
      }
    }
    $response->setRedirect('<front>');
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $response->success());
    return $response;
  }

  /**
   * Delete consumer org in apim and locally.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param int $uidToIgnore - Does not delete this user from portal
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException|\JsonException
   */
  public function delete(ConsumerOrg $org, $uidToIgnore = null): UserManagerResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $response = new UserManagerResponse();

    $apimResponse = $this->apimServer->deleteConsumerOrg($org);


    if ($apimResponse !== NULL && $apimResponse->getCode() === 200) {

      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $org->getUrl());
      $nids = $query->accessCheck()->execute();
      $orgNode = NULL;
      if ($nids !== NULL && !empty($nids)) {
        $this->deleteNode(array_shift($nids), $uidToIgnore);
      }
      // invalidate the devorg select block cache
      $this->userUtils->removeConsumerOrgFromUser($org->getUrl(), NULL);
      $this->userUtils->resetCurrentConsumerorg();
      $this->userUtils->setOrgSessionData();
      if (!$this->currentUser->isAnonymous() && (int) $this->currentUser->id() !== 1) {
        $this->cacheTagsInvalidator->invalidateTags(['consumer_org_select_block:uid:' . $this->currentUser->id()]);
      }
      $this->logger->notice('Organization @orgname deleted by @username', [
        '@orgname' => $org->getName(),
        '@username' => $this->currentUser->getAccountName(),
      ]);

      $response->setSuccess(TRUE);
    }
    else {
      $response->setSuccess(FALSE);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $response;

  }

  /**
   * Create a new consumer organization node
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $consumer
   * @param string $event
   *
   * @return int|string|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function createNode(ConsumerOrg $consumer, $event = 'internal') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $consumer->getUrl());
    $oldNode = NULL;
    if ($consumer->getUrl() !== NULL) {
      // find if there is an existing node for this consumerorg
      // using id from swagger doc
      // if so then clone it and base new node on that.
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $consumer->getUrl())->sort('nid', 'ASC');
      $nids = $query->accessCheck()->execute();
      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $oldNode = Node::load($nid);
      }
    }

    if ($oldNode !== NULL && $oldNode->id() !== NULL) {

      // duplicate node
      $node = $oldNode->createDuplicate();

      // wipe all our fields to ensure they get set to new values
      $node->set('apic_hostname', NULL);
      $node->set('uid', 1);
      $node->set('apic_catalog_id', NULL);
      $node->set('apic_provider_id', NULL);
      $node->set('apic_created_at', NULL);
      $node->set('apic_updated_at', NULL);
      $node->set('consumerorg_memberlist', NULL);
      $node->set('consumerorg_members', NULL);
      //      $node->set('consumerorg_communities', NULL);
      $node->set('consumerorg_id', NULL);
      $node->set('consumerorg_url', NULL);
      $node->set('consumerorg_name', NULL);
      $node->set('consumerorg_owner', NULL);
      $node->set('consumerorg_roles', NULL);
      $node->set('consumerorg_tags', NULL);
      $node->set('consumerorg_invites', NULL);
      //Custom fields should already be null but wipe anyway JIC
      $customFields = $this->getCustomFields();
      foreach ($customFields as $customField) {
        $node->set($customField, NULL);
      }
    }
    else {
      $node = Node::create([
        'type' => 'consumerorg',
        'uid' => 1
      ]);
    }

    // get the update method to do the update for us
    $node = $this->updateNode($node, $consumer, 'internal');
    if ($node !== NULL) {
      // Add Activity Feed Event Log
      $eventEntity = new ApicEvent();
      $eventEntity->setArtifactType('consumer_org');
      if ($this->currentUser->isAuthenticated() && (int) $this->currentUser->id() !== 1) {
        $current_user = User::load($this->currentUser->id());
        if ($current_user !== NULL) {
          // we only set the user if we're running as someone other than admin
          // if running as admin then we're likely doing things on behalf of the admin
          // TODO we might want to check if there is a passed in user_url and use that too
          $eventEntity->setUserUrl($current_user->get('apic_url')->value);
        }
      }
      elseif ($consumer->getCreatedBy() !== NULL && $this->apimUtils->isConsumerApiURL($consumer->getCreatedBy())) {
        $eventEntity->setUserUrl($this->apimUtils->removeFullyQualifiedUrl($consumer->getCreatedBy()));
      }
      $timestamp = $node->apic_created_at->value;
      // if timestamp still not set default to current time
      if ($timestamp === NULL) {
        $timestamp = time();
      }
      $eventEntity->setTimestamp((int) $timestamp);
      $eventEntity->setEvent('create');
      $eventEntity->setArtifactUrl($consumer->getUrl());
      $eventEntity->setConsumerOrgUrl($consumer->getUrl());
      $eventEntity->setData(['orgName' => $consumer->getTitle()]);
      $this->eventLogService->createIfNotExist($eventEntity);

      // check if there is an updated event needed too
      if ($consumer->getUpdatedBy() !== NULL && $consumer->getUpdatedAt() !== NULL && $consumer->getCreatedAt() !== NULL && $consumer->getCreatedAt() !== $consumer->getUpdatedAt() && $this->apimUtils->isConsumerApiURL($consumer->getUpdatedBy())) {
        $eventEntity->setEvent('update');
        $eventEntity->setUserUrl($this->apimUtils->removeFullyQualifiedUrl($consumer->getUpdatedBy()));
        $eventEntity->setTimestamp($consumer->getUpdatedAt());
        $this->eventLogService->createIfNotExist($eventEntity);
      }

      $this->logger->notice('Consumer organization @consumerorg created', ['@consumerorg' => $node->getTitle()]);
      // Calling all modules implementing 'hook_consumerorg_create':
      $this->moduleHandler->invokeAll('consumerorg_create', ['node' => $node, 'data' => $consumer]);

    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $node->id();
  }

  /**
   * Update an existing consumerorg
   *
   * @param \Drupal\node\NodeInterface $node
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $consumer
   * @param string $event
   *
   * @return \Drupal\node\NodeInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function updateNode(NodeInterface $node, ConsumerOrg $consumer, $event = 'content_refresh'): ?NodeInterface {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $consumer->getUrl());
    $returnValue = NULL;
    if ($node !== NULL) {
      // Need to set these two before we calculate the old hash, as they are repesented as a
      // different type (string vs int etc.) when set compared to when queried from the DB
      // Being as they are always the same value anyway they should always match between old and new
      $node->setPromoted(NodeInterface::NOT_PROMOTED);
      $node->setPublished();

      $existingNodeHash = $this->utils->generateNodeHash($node, 'old-consumer_org');

      $hostvariable = $this->siteconfig->getApimHost();
      $node->setTitle($this->utils->truncate_string($consumer->getTitle()));
      $node->set('apic_hostname', $hostvariable);
      $node->set('apic_provider_id', $this->siteconfig->getOrgId());
      $node->set('apic_catalog_id', $this->siteconfig->getEnvId());
      $node->set('consumerorg_id', $consumer->getId());
      $node->set('consumerorg_url', $consumer->getUrl());
      $node->set('consumerorg_name', $this->utils->truncate_string($consumer->getName(), 128));
      $node->set('consumerorg_owner', $consumer->getOwnerUrl());
      $node->set('apic_created_at', $consumer->getCreatedAt());
      $node->set('apic_updated_at', $consumer->getUpdatedAt());

      if ($consumer->getTags() === NULL) {
        $consumer->setTags([]);
      }
      $node->set('consumerorg_tags', $consumer->getTags());
      $this->utils->saveCustomFields($node, $this->getCustomFields(), $consumer->getCustomFields(), FALSE, FALSE);

      $roles = [];
      if ($consumer->getRoles() !== NULL) {
        // Role objects need to be flattened for storage in the DB
        foreach ($consumer->getRoles() as $role) {
          $roles[] = serialize($role->toArray());
        }
      }

      $node->set('consumerorg_roles', $roles);

      // we need to store members information in 2 formats:
      // members = url, user_url, state, roles
      // memberlist = user_url

      $members = [];
      $memberlist = [];

      // when a user creates a new org, we don't initially have all the data we need in the response
      // if we don't do the below, the new org doesn't appear in the corg selector block
      if (empty($consumer->getMembers()) && !empty($consumer->getOwnerUrl())) {

        // munge the urls because the apim side gives us too much
        $corg_url = $this->apimUtils->removeFullyQualifiedUrl($consumer->getUrl());
        $owner_url = $this->apimUtils->removeFullyQualifiedUrl($consumer->getOwnerUrl());

        $account = User::load($this->currentUser->id());

        if ($account !== NULL && $account->apic_url->value === $owner_url) {
          $already_added = FALSE;
          if (!empty($account->consumerorg_url->getValue())) {
            $org_urls = $account->consumerorg_url->getValue();
            foreach ($org_urls as $index => $valueArray) {
              $nextOrgUrl = $valueArray['value'];
              if ($nextOrgUrl === $corg_url) {
                $already_added = TRUE;
                break;
              }
            }
          }

          if (!$already_added) {
            $account->consumerorg_url[] = $corg_url;
            $account->save();
          }
        }
      }

      // TODO: refactor needed - injecting this service creates circular dependency :(
      $userStorage = \Drupal::service('ibm_apim.user_storage');

      if ($consumer->getMembers() !== NULL) {
        foreach ($consumer->getMembers() as $member) {
          // Add Activity Feed Event Log
          $eventEntity = new ApicEvent();
          $eventEntity->setArtifactType('member');
          $timestamp = $member->getCreatedAt();
          // is_int not sufficient here, so use pattern matching instead
          if ($timestamp !== NULL && !preg_match("/^\d+$/", $timestamp)) {
            $timestamp = strtotime($timestamp);
          }
          // if timestamp still not set default to current time
          if ($timestamp === NULL) {
            $timestamp = time();
          }
          $eventEntity->setTimestamp($timestamp);
          $eventEntity->setEvent('create');
          $eventEntity->setArtifactUrl($member->getUrl());
          $eventEntity->setConsumerOrgUrl($member->getOrgUrl());
          $eventEntity->setData(['member' => $member->getUser()->getUsername(), 'orgName' => $consumer->getTitle()]);
          $this->eventLogService->createIfNotExist($eventEntity);

          // check if there is an updated event needed too
          if ($member->getUpdatedAt() !== NULL && $member->getCreatedAt() !== NULL && $member->getCreatedAt() !== $member->getUpdatedAt()) {
            $updateEventEntity = clone $eventEntity;
            $updateEventEntity->setEvent('update');
            $updateEventEntity->setTimestamp($member->getUpdatedAt());
            $this->eventLogService->createIfNotExist($updateEventEntity);
          }

          $memberlist[] = $member->getUserUrl();
          $memberArray = $member->toArray();
          $members[] = serialize($memberArray);
          $user = $member->getUser();
          if ($user !== NULL && $user->getUsername() !== NULL) {

            $userAccount = $userStorage->load($user);

            // if there isn't an account for this user then we have enough information to create and use it at this point.
            if ($userAccount === NULL) {
              $this->logger->notice('registering new account for %username based on member data.', ['%username' => $user->getUsername()]);
              try {
                $userAccount = $this->accountService->registerApicUser($user);
              } catch (Throwable $e) {
                // Quietly ignore errors from duplicate users to prevent webhooks from blowing up.
                $this->logger->notice('Failed creating apic user %username, ignoring exception', ['%username' => $user->getUsername()]);
              }
            }

            if ($userAccount) {
              // For all non-admin users, don't store their password in our database.
              // We have to do this here as well as inside updateLocalAccount so that the hashes
              // will match if nothing has changed
              if ((int) $userAccount->id() !== 1) {
                $userAccount->setPassword(NULL);
              }

              $existingUserAccountHash = $this->utils->generateNodeHash($userAccount, 'old-account');
              // consumerorg_url is a multi value field which Drupal represents using a FieldItemList class
              // this causes headaches as seen below....
              $consumerorg_urls = $userAccount->consumerorg_url->getValue();
              if ($consumerorg_urls === NULL) {
                $consumerorg_urls = [];
              }

              // Doesn't update the custom fields since they're not in the right format
              $updatedAccount = $this->accountService->updateLocalAccount($user, $userAccount, FALSE);
              if ($updatedAccount !== NULL) {
                $userAccount = $updatedAccount;
              }

              // Add the custom fields to the user
              $customFields = $this->userService->getMetadataFields();
              $this->utils->saveCustomFields($userAccount, $customFields, $memberArray['user'], TRUE, FALSE);

              // Add the consumerorg if it isn't already associated with this user
              if (!$this->isConsumerorgAssociatedWithAccount($consumer->getUrl(), $userAccount)) {
                $consumerorg_urls[] = $consumer->getUrl();
                $userAccount->set('consumerorg_url', $consumerorg_urls);
              }

              if ($this->utils->hashMatch($existingUserAccountHash, $userAccount, 'new-account')) {
                if ($event !== 'internal') {
                  $this->logger->notice('UserAccount @user not updated as the hash matched', ['@user' => $userAccount->id()]);}
              } else {
                $this->logger->notice('UserAccount @user updated', ['@user' => $userAccount->id()]);
                $userAccount->save();
              }
            }
          }

        }
      }

      $node->set('consumerorg_members', $members);
      $node->set('consumerorg_memberlist', $memberlist);
      $consumer_invites = $consumer->getInvites();
      $invites = [];
      if ($consumer_invites !== NULL) {
        foreach ($consumer_invites as $value) {
          $invites[] = serialize($value);
        }
      }
      $node->set('consumerorg_invites', $invites);

      if ($consumer->getPaymentMethods() !== NULL) {
        $paymentMethodService = \Drupal::service('consumerorg.paymentmethod');
        $node = $paymentMethodService->createOrUpdatePaymentMethodList($node, $consumer->getPaymentMethods());
      }

      if ($this->utils->hashMatch($existingNodeHash, $node, 'new-consumer_org')) {
        if ($event !== 'internal') {
          $this->logger->notice('Consumer organization @consumerorg not updated as the hash matched', ['@consumerorg' => $node->getTitle()]);
        }
      } else {
        $node->save();

        if ($node !== NULL && $event !== 'internal') {
          // Add Activity Feed Event Log
          $eventEntity = new ApicEvent();
          $eventEntity->setArtifactType('consumer_org');
          if ($this->currentUser->isAuthenticated() && (int) $this->currentUser->id() !== 1) {
            $current_user = User::load($this->currentUser->id());
            if ($current_user !== NULL) {
              // we only set the user if we're running as someone other than admin
              // if running as admin then we're likely doing things on behalf of the admin
              // TODO we might want to check if there is a passed in user_url and use that too
              $eventEntity->setUserUrl($current_user->get('apic_url')->value);
            }
          } elseif ($consumer->getUpdatedBy() !== NULL && $this->apimUtils->isConsumerApiURL($consumer->getUpdatedBy())) {
            $eventEntity->setUserUrl($this->apimUtils->removeFullyQualifiedUrl($consumer->getUpdatedBy()));
          }

          $timestamp = $node->apic_updated_at->value;
          // if timestamp still not set default to current time
          if ($timestamp === NULL) {
            $timestamp = time();
          }
          $eventEntity->setTimestamp((int) $timestamp);
          $eventEntity->setEvent('update');
          $eventEntity->setArtifactUrl($consumer->getUrl());
          $eventEntity->setConsumerOrgUrl($consumer->getUrl());
          $eventEntity->setData(['orgName' => $consumer->getTitle()]);

          // check if there is a create event needed too
          if ($consumer->getCreatedAt() !== NULL) {
            $createEventEntity = clone $eventEntity;
            $createEventEntity->setEvent('create');
            if ($this->apimUtils->isConsumerApiURL($consumer->getCreatedBy())) {
              $createEventEntity->setUserUrl($this->apimUtils->removeFullyQualifiedUrl($consumer->getCreatedBy()));
            }
            $createEventEntity->setTimestamp($consumer->getCreatedAt());
            $this->eventLogService->createIfNotExist($createEventEntity);
          }
          if ($consumer->getCreatedAt() !== $consumer->getUpdatedAt()) {
            if ($this->apimUtils->isConsumerApiURL($consumer->getUpdatedBy())) {
              $eventEntity->setUserUrl($this->apimUtils->removeFullyQualifiedUrl($consumer->getUpdatedBy()));
            }

            // intentionally create the create one before update so they appear in sequence in the db
            $this->eventLogService->createIfNotExist($eventEntity);
          }

          // Calling all modules implementing 'hook_consumerorg_update':
          $this->moduleHandler->invokeAll('consumerorg_update', ['node' => $node, 'data' => $consumer]);

          // invalidate myorg page cache
          $this->cacheTagsInvalidator->invalidateTags(['myorg:url:' . $consumer->getUrl()]);
          }
      }

      ibm_apim_exit_trace(__FUNCTION__, NULL);
      $returnValue = $node;
    }
    else {
      $this->logger->error('Update consumerorg: no node provided.', []);
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $returnValue;
  }

  /**
   * Create a new consumerorg if one doesnt already exist for that consumer organization reference
   * Update one if it does
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $consumer
   * @param $event
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function createOrUpdateNode(ConsumerOrg $consumer, $event): bool {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $consumer->getUrl());
    }

    if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $consumer->getUrl());

      $nids = $query->accessCheck()->execute();
      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        if ($node !== NULL) {
          $this->updateNode($node, $consumer, $event);
          $createdOrUpdated = FALSE;
        }
      }
      else {
        // no existing node for this consumerorg so create one
        $this->createNode($consumer, $event);
        $createdOrUpdated = TRUE;
      }
    }
    else {
      $this->logger->debug('unit test environment, createOrUpdateNode skipped');
      $createdOrUpdated = FALSE;
    }
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $createdOrUpdated);
    }
    return $createdOrUpdated;
  }

  /**
   * Create a new consumerorg if one doesnt already exist for that consumer organization reference
   * Update one if it does
   *
   * @param $invitation
   * @param $event
   *
   * @return bool|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdateInvitation($invitation, $event): ?bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $createdOrUpdated = NULL;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $invitation['consumer_org_url']);

    $nids = $query->accessCheck()->execute();
    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($node !== NULL) {
        $invites = [];
        foreach ($node->consumerorg_invites->getValue() as $arrayValue) {
          $unserialized = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
          $invites[] = $unserialized;
        }
        $found = FALSE;
        foreach ($invites as $key => $value) {
          if ($value['url'] === $invitation['url']) {
            $invites[$key] = $invitation;
            $found = TRUE;
          }
        }
        if ($found === FALSE) {
          $invites[] = $invitation;
        }
        $serialized_invites = [];
        foreach ($invites as $key => $invite) {
          if (isset($invite['url'])) {
            $invite['url'] = $this->apimUtils->removeFullyQualifiedUrl($invite['url']);
          }
          $serialized_invites[] = serialize($invite);
        }
        $node->set('consumerorg_invites', $serialized_invites);
        $node->save();

        $createdOrUpdated = !$found;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $createdOrUpdated);
    return $createdOrUpdated;
  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param $invitation
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function cancelInvitation(ConsumerOrg $org, $invitation): UserManagerResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = new UserManagerResponse();
    $apimResponse = $this->apimServer->deleteMemberInvitation($org, $invitation['id']);
    if ($apimResponse !== NULL && $apimResponse->getCode() === 200) {
      // update our DB
      $invites = [];
      foreach ($org->getInvites() as $existingInvite) {
        if ($existingInvite['url'] !== $invitation['url']) {
          $invites[] = $existingInvite;
        }
      }
      $org->setInvites($invites);
      $this->createOrUpdateNode($org, 'internal');
      $response->setSuccess(TRUE);

      // Add Activity Feed Event Log
      $eventEntity = new ApicEvent();
      $eventEntity->setArtifactType('invitation');
      if ($this->currentUser->isAuthenticated() && (int) $this->currentUser->id() !== 1) {
        $current_user = User::load($this->currentUser->id());
        if ($current_user !== NULL) {
          // we only set the user if we're running as someone other than admin
          // if running as admin then we're likely doing things on behalf of the admin
          // TODO we might want to check if there is a passed in user_url and use that too
          $eventEntity->setUserUrl($current_user->get('apic_url')->value);
        }
      }
      $eventEntity->setTimestamp(time());
      $eventEntity->setEvent('delete');
      $eventEntity->setArtifactUrl($invitation['url']);
      $eventEntity->setConsumerOrgUrl($invitation['consumer_org_url']);
      $eventEntity->setData(['orgName' => $org->getTitle()]);
      $this->eventLogService->createIfNotExist($eventEntity);
    }
    else {
      $response->setSuccess(FALSE);
      $this->logger->error('Unable to cancel invitation @invite to @orgname', [
        '@invite' => $invitation['id'],
        '@orgname' => $org->getTitle(),
      ]);
    }
    // invalidate myorg page cache
    $this->cacheTagsInvalidator->invalidateTags(['myorg:url:' . $org->getUrl()]);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $response;
  }

  /**
   * Delete an invitation, invoked from drush so just updates our db
   *
   * @param $invitation
   * @param $event
   *
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteInvitation($invitation, $event = 'internal'): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $invitation['consumer_org_url']);

    $nids = $query->accessCheck()->execute();
    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($node !== NULL) {
        $invites = [];
        foreach ($node->consumerorg_invites->getValue() as $arrayValue) {
          $unserialized = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
          if ($unserialized['url'] !== $invitation['url']) {
            $invites[] = $arrayValue['value'];
          }
        }
        $node->set('consumerorg_invites', $invites);
        $node->save();
        // invalidate myorg page cache
        $this->cacheTagsInvalidator->invalidateTags(['myorg:url:' . $invitation['consumer_org_url']]);

        // Add Activity Feed Event Log
        $eventEntity = new ApicEvent();
        $eventEntity->setArtifactType('invitation');
        if ($this->currentUser->isAuthenticated() && (int) $this->currentUser->id() !== 1) {
          $current_user = User::load($this->currentUser->id());
          if ($current_user !== NULL) {
            // we only set the user if we're running as someone other than admin
            // if running as admin then we're likely doing things on behalf of the admin
            // TODO we might want to check if there is a passed in user_url and use that too
            $eventEntity->setUserUrl($current_user->get('apic_url')->value);
          }
        }
        $eventEntity->setTimestamp(time());
        $eventEntity->setEvent('delete');
        $eventEntity->setArtifactUrl($invitation['url']);
        $eventEntity->setConsumerOrgUrl($invitation['consumer_org_url']);
        $eventData = ['orgName' => $node->getTitle()];
        if (isset($invitation['email'])) {
          $eventData['email'] = $invitation['email'];
        }
        $eventEntity->setData($eventData);
        $this->eventLogService->createIfNotExist($eventEntity);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete a consumerorg by NID
   *
   * @param $nid
   *
   * @return void
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteNode($nid, $uidToIgnore = null): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $nid);
    $node = Node::load($nid);
    $eventEntity = new ApicEvent();
    $corg = $this->getByNid($nid);
    $eventEntity->setConsumerOrgUrl($corg->getUrl());
    $eventEntity->setData(['orgName' => $corg->getTitle()]);
    $eventEntity->setArtifactType('consumer_org');
    $eventEntity->setTimestamp(time());
    $eventEntity->setEvent('delete');
    $eventEntity->setArtifactUrl($corg->getUrl());
    if ($node !== NULL) {
      $hookData = $this->createOrgHookData($node);
      // this hook will be used in the apic_app module to delete the applications
      $this->moduleHandler->invokeAll('consumerorg_pre_delete',
        [
          'node' => $node,
          'data' => $hookData,
        ]);

      $consumerorg_url = $node->consumerorg_url->value;

      // Delete all payment method entities for this consumerorg
      $query = \Drupal::entityQuery('consumerorg_payment_method');
      $query->condition('consumerorg_url.value', $consumerorg_url);

      $entityIds = $query->accessCheck()->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        foreach (array_chunk($entityIds, 50) as $chunk) {
          $paymentMethodEntities = PaymentMethod::loadMultiple($chunk);
          foreach ($paymentMethodEntities as $paymentMethodEntity) {
            $paymentMethodEntity->delete();
          }
        }
      }
      $performBatch = FALSE;
      // remove the consumerorg assignment from all users since we're deleting it
      $query = $this->userQuery;
      $query->condition('consumerorg_url.value', $consumerorg_url, 'CONTAINS');
      $results = $query->accessCheck()->execute();
      $performBatch = FALSE;
      if ($results !== NULL && !empty($results)) {
        $uids = array_values($results);
        if (!empty($uids)) {
          foreach (array_chunk($uids, 50) as $chunk) {
            $accounts = User::loadMultiple($chunk);
            foreach ($accounts as $account) {
              if ($this->isConsumerorgAssociatedWithAccount($consumerorg_url, $account)) {
                $consumerorg_urls = $account->consumerorg_url->getValue();
                // This is not a simple array but an array of consumerorg_id[0] = array("value"=>orgid)
                // Hence additional complication in removing elements from the array
                foreach ($account->consumerorg_url->getValue() as $index => $valueArray) {
                  $nextExistingConsumerorgUrl = $valueArray['value'];
                  if ($nextExistingConsumerorgUrl === $consumerorg_url) {
                    unset($consumerorg_urls[$index]);
                  }
                }
                if (empty($consumerorg_urls) && (!isset($uidToIgnore) || $account->id() !== $uidToIgnore)) {
                  user_cancel([], $account->id(), 'user_cancel_reassign');
                  $performBatch = TRUE;
                } else {
                  $account->set('consumerorg_url', $consumerorg_urls);
                  $account->save();
                }
              }
            }
          }
        }
      }
      if ($performBatch && !isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $batch = &batch_get();
        $batch['progressive'] = FALSE;
        batch_process();
      }
      // Calling all modules implementing 'hook_consumerorg_delete':
      $description = 'The consumerorg_delete hook is deprecated and will be removed. Please use the consumerorg_pre_delete or consumerorg_post_delete hook instead.';
      $this->moduleHandler->invokeAllDeprecated($description, 'consumerorg_delete', ['node' => $node]);

      $node->delete();

      $this->moduleHandler->invokeAll('consumerorg_post_delete',
        [
          'data' => $hookData,
        ]);
      $this->logger->notice('Consumer organization @consumerorg deleted', ['@consumerorg' => $node->getTitle()]);
      $this->eventLogService->createIfNotExist($eventEntity);
      unset($node);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Get an instance of a consumer org node and return a ConsumerOrg object.
   *
   * @param $nid
   *   Node id.
   *
   * @return \Drupal\consumerorg\ApicType\ConsumerOrg
   */
  public function getByNid($nid): ?ConsumerOrg {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $nid);
    $org = NULL;
    $node = Node::load($nid);
    if ($node !== NULL) {
      $org = new ConsumerOrg();

      if ($node->getTitle()) {
        $org->setTitle($node->getTitle());
      }
      if ($node->consumerorg_name) {
        $org->setName($node->get('consumerorg_name')->value);
      }
      if ($node->consumerorg_url) {
        $org->setUrl($node->get('consumerorg_url')->value);
      }
      if ($node->consumerorg_id) {
        $org->setId($node->get('consumerorg_id')->value);
      }
      if ($node->consumerorg_owner) {
        $org->setOwnerUrl($node->get('consumerorg_owner')->value);
      }
      if ($node->consumerorg_roles) {
        $roles = $node->get('consumerorg_roles')->getValue();
        $whitelist = [Role::class];
        foreach ($roles as $role) {
          $org->addRoleFromArray(unserialize($role['value'], ['allowed_classes' => $whitelist]));
        }
      }
      if ($node->consumerorg_members) {
        $members = [];
        $whitelist = [Member::class, ApicUser::class];
        foreach ($node->consumerorg_members->getValue() as $arrayValue) {
          $members[] = unserialize($arrayValue['value'], ['allowed_classes' => $whitelist]);
        }
        $org->setMembersFromArray($members);
      }
      if ($node->consumerorg_invites) {
        $invites = [];
        if ($node !== NULL) {
          foreach ($node->consumerorg_invites->getValue() as $arrayValue) {
            $invites[] = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
          }
        }
        $org->setInvites($invites);
      }
      if ($node->consumerorg_payment_method_refs) {
        $paymentMethods = [];
        // ConsumerOrg object needs arrays not entities on it
        $entities = $node->consumerorg_payment_method_refs->referencedEntities();
        foreach ($entities as $entity) {
          $paymentMethods[] = $entity->toArray();
        }
        $org->setPaymentMethods($paymentMethods);
      }
      if ($node->consumerorg_def_payment_ref) {
        $paymentMethods = [];
        // ConsumerOrg object needs arrays not entities on it
        $entities = $node->consumerorg_def_payment_ref->referencedEntities();
        foreach ($entities as $entity) {
          $paymentMethods[] = $entity->toArray();
        }
        $org->setDefaultPaymentMethod($paymentMethods);
      }
      // TODO: consumerorg_memberlist?
      if ($node->consumerorg_tags) {
        $org->setTags(array_column($node->get('consumerorg_tags')->getValue(), "value"));
      }
      $customFields = $this->getCustomFields();
      foreach ($customFields as $customField) {
        if (!empty($node->get($customField)->getValue())) {
          $org->addCustomField($customField, $node->get($customField)->getValue());
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $org !== NULL ? $org->getUrl() : NULL);
    return $org;
  }

  /**
   * Get the members for a given consumerorg
   *
   * @param $orgUrl
   *
   * @return array|null
   */
  public function getMembers($orgUrl): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = NULL;
    if ($orgUrl !== NULL) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $orgUrl);
      $nids = $query->accessCheck()->execute();
      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        $members = [];
        if ($node !== NULL) {
          $whitelist = [Member::class, ApicUser::class];
          foreach ($node->consumerorg_members->getValue() as $arrayValue) {
            $new_member = new Member();
            $new_member->createFromArray(unserialize($arrayValue['value'], ['allowed_classes' => $whitelist]));
            $members[] = $new_member;
          }
        }
        $returnValue = $members;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Get the available roles for a given consumerorg
   *
   * @param $orgUrl
   *
   * @return array|null
   */
  public function getRoles($orgUrl): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = NULL;
    if ($orgUrl !== NULL) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $orgUrl);
      $nids = $query->accessCheck()->execute();
      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        $roles = [];
        if ($node !== NULL) {
          foreach ($node->consumerorg_roles->getValue() as $arrayValue) {
            $role = new Role();
            $role->createFromArray(unserialize($arrayValue['value'], ['allowed_classes' => FALSE]));
            $roles[] = $role;
          }
        }
        $returnValue = $roles;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Refresh the org object from apim
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   *
   * @return \Drupal\consumerorg\ApicType\ConsumerOrg
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \JsonException|\Drupal\Core\Entity\EntityStorageException
   */
  public function getFromApim(ConsumerOrg $org): ConsumerOrg {
    $cOrg = $org;
    $serverResponse = $this->apimServer->get($org->getUrl());
    if ($serverResponse->getCode() === 200) {
      $responseOrg = $this->createFromJSON($serverResponse->getData());
      if ($responseOrg !== NULL) {
        $cOrg = $responseOrg;
        $this->createOrUpdateNode($cOrg, 'internal');
      }
    }
    return $cOrg;
  }

  /**
   * Get a single consumerorg by the consumerorg url
   *
   * @param string $url
   *   The APIC UID of the consumerorg to find.
   * @param int $admin
   *   If set to 1 then bypass the access check on the memberlist
   *
   * @return null|ConsumerOrg
   *   The Node loaded from the database that matches the consumerorg uid.
   */
  public function get(string $url, $admin = 0): ?ConsumerOrg {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $result = NULL;
    $nid = $this->getNid($url, $admin);
    if ($nid !== NULL) {
      $result = $this->getByNid($nid);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $nid);
    return $result;
  }

  /**
   * Get a single consumerorg node ID by the consumerorg url
   *
   * @param string $url
   *   The APIC UID of the consumerorg to find.
   * @param int $admin
   *   If set to 1 then bypass the access check on the memberlist
   *
   * @return null|string
   *   The Node loaded from the database that matches the consumerorg uid.
   */
  public function getNid(string $url, $admin = 0): ?string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $result = NULL;
    $nid = NULL;

    if (!$this->currentUser->isAnonymous()) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $url);

      // TODO: need to check member list against user url. Not currently stored for user.
      //      if ($admin != 1 && (int) $this->currentUser->id() !== 1) {
      //        $query->condition('consumerorg_memberlist.value', $this->currentUser->getAccountName(), 'CONTAINS');
      //      }

      $results = $query->accessCheck()->execute();

      if ($results !== NULL && !empty($results)) {
        $nid = array_shift($results);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $nid);
    return $nid;
  }

  /**
   * Get a single consumerorg node by the consumerorg url
   *
   * @param string $url
   *   The APIC UID of the consumerorg to find.
   *
   * @return Node
   *   The Node loaded from the database that matches the consumerorg uid.
   */
  public function getConsumerOrgAsNode(string $url): Node {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $result = NULL;
    $nid = NULL;

    if (!$this->currentUser->isAnonymous()) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $url);

      $results = $query->accessCheck()->execute();

      if ($results !== NULL && !empty($results)) {
        $nid = array_shift($results);
        $result = Node::load($nid);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $nid);
    return $result;
  }

  /**
   * Database based query to get list of consumerorgs for the current user
   *
   * @return array
   */
  public function getList(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $list = [];
    $account = User::load($this->currentUser->id());
    if ($account !== NULL && !$this->currentUser->isAnonymous() && (int) $this->currentUser->id() !== 1) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_memberlist.value', $account->apic_url->value, 'CONTAINS');

      $results = $query->accessCheck()->execute();
      if ($results !== NULL && !empty($results)) {
        $list = array_values($results);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $list;
  }

  /**
   * A list of all the IBM created fields for this content type
   *
   * @return array
   */
  public function getIBMFields(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $ibmfields = [
      'apic_hostname',
      'apic_provider_id',
      'apic_catalog_id',
      'apic_rating',
      'apic_tags',
      'apic_pathalias',
      'apic_created_at',
      'apic_updated_at',
      'consumerorg_id',
      'consumerorg_invites',
      'consumerorg_members',
      'consumerorg_memberlist',
      'consumerorg_name',
      'consumerorg_owner',
      'consumerorg_tags',
      'consumerorg_url',
      'consumerorg_roles',
      'consumerorg_payment_method_refs',
      'consumerorg_def_payment_ref',
    ];
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $ibmfields);
    return $ibmfields;
  }

  /**
   * Get a list of all the custom fields on this content type
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCustomFields(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $coreFields = ['title', 'vid', 'status', 'nid', 'revision_log', 'created', 'url_redirects'];
    $diff = [];
    $entity = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load('node.consumerorg.default');
    if ($entity !== NULL) {
      $components = $entity->getComponents();
      $keys = array_keys($components);
      $ibmFields = $this->getIBMFields();
      $merged = array_merge($coreFields, $ibmFields);
      $diff = array_diff($keys, $merged);

      // make sure we only include actual custom fields so check there is a field config
      foreach ($diff as $key => $field) {
        $fieldConfig = FieldConfig::loadByName('node', 'consumerorg', $field);
        if ($fieldConfig === NULL) {
          unset($diff[$key]);
        }
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $diff);
    return $diff;
  }

  /**
   * Function to check if a given consumerorg url is listed in the account->consumerorg_id array
   * associated with a given user account.
   *
   * @param $consumerorg_url
   * @param $account
   *
   * @return boolean
   */
  public function isConsumerorgAssociatedWithAccount($consumerorg_url, $account): bool {
    if ($account !== NULL) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$consumerorg_url, $account->getAccountName()]);
    }
    else {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$consumerorg_url]);
    }

    $returnValue = FALSE;
    // We want to do in_array($newOrgId, $account->consumerorg_ids) but consumerorg_ids is not a simple array.
    // It is an array of arrays like this :
    //   $consumerorg_ids[0] = array("value" => "theOrgId");
    //   $consumerorg_ids[1] = array("value" => "theOtherOrgId");
    // Hence the below code rather than the much simpler in_array call we might want to have made!
    foreach ($account->consumerorg_url->getValue() as $index => $valueArray) {
      $nextExistingConsumerorgUrl = $valueArray['value'];
      if ($nextExistingConsumerorgUrl === $consumerorg_url) {
        $returnValue = TRUE;
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Invite a new user into an organization.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $email_address
   * @param string|NULL $role
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function inviteMember(ConsumerOrg $org, string $email_address, string $role = NULL): UserManagerResponse {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = new UserManagerResponse();
    $apimResponse = $this->apimServer->postMemberInvitation($org, $email_address, $role);
    if ($apimResponse !== NULL && $apimResponse->getCode() === 201) {
      $data = $apimResponse->getData();
      if (array_key_exists('id', $data) && $data['id'] !== NULL) {
        $invitationId = $data['id'];
      }
      else {
        $invitationId = 'temp';
      }
      if (array_key_exists('url', $data) && $data['url'] !== NULL) {
        $invitationUrl = $this->apimUtils->removeFullyQualifiedUrl($data['url']);
      }
      else {
        $invitationUrl = 'temp';
      }
      $response->setSuccess(TRUE);
      $this->logger->notice('New member @invitee invited to @orgname by @username', [
        '@orgname' => $org->getTitle(),
        '@invitee' => $email_address,
        '@username' => $this->currentUser->getAccountName(),
      ]);
      // add a temp invite into the db so the content shows up in the UI straight away
      // this will be replaced with the right content by webhooks within a few seconds.
      $invites = $org->getInvites();
      $invites[] = [
        'type' => 'member_invitation',
        'api_version' => '2.0.0',
        'email' => $email_address,
        'shadow' => FALSE,
        'id' => $invitationId,
        'url' => $invitationUrl,
        'role_urls' => [0 => $role],
      ];

      $org->setInvites($invites);
      $this->createOrUpdateNode($org, 'internal');

      // Add Activity Feed Event Log
      $eventEntity = new ApicEvent();
      $eventEntity->setArtifactType('invitation');
      if ($this->currentUser->isAuthenticated() && (int) $this->currentUser->id() !== 1) {
        $current_user = User::load($this->currentUser->id());
        if ($current_user !== NULL) {
          // we only set the user if we're running as someone other than admin
          // if running as admin then we're likely doing things on behalf of the admin
          // TODO we might want to check if there is a passed in user_url and use that too
          $eventEntity->setUserUrl($current_user->get('apic_url')->value);
        }
      }
      $eventEntity->setTimestamp(time());
      $eventEntity->setEvent('create');
      $eventEntity->setArtifactUrl($org->getOrgUrl() . '/invitations/' . $invitationId);
      $eventEntity->setConsumerOrgUrl($org->getOrgUrl());
      $eventEntity->setData(['orgName' => $org->getTitle()]);
      $this->eventLogService->createIfNotExist($eventEntity);
    }
    else {
      $response->setSuccess(FALSE);
      $this->logger->error('Unable to invite user @username to @orgname', [
        '@username' => $this->currentUser->getAccountName(),
        '@orgname' => $org->getTitle(),
      ]);
    }
    // invalidate myorg page cache
    $this->cacheTagsInvalidator->invalidateTags(['myorg:url:' . $org->getUrl()]);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $response;

  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $inviteId
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   */
  public function resendMemberInvitation(ConsumerOrg $org, string $inviteId): UserManagerResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = new UserManagerResponse();
    $apimResponse = $this->apimServer->resendMemberInvitation($org, $inviteId);
    if ($apimResponse !== NULL && $apimResponse->getCode() === 200) {

      $response->setSuccess(TRUE);

      // Add Activity Feed Event Log
      $eventEntity = new ApicEvent();
      $eventEntity->setArtifactType('invitation');
      if ($this->currentUser->isAuthenticated() && (int) $this->currentUser->id() !== 1) {
        $current_user = User::load($this->currentUser->id());
        if ($current_user !== NULL) {
          // we only set the user if we're running as someone other than admin
          // if running as admin then we're likely doing things on behalf of the admin
          // TODO we might want to check if there is a passed in user_url and use that too
          $eventEntity->setUserUrl($current_user->get('apic_url')->value);
        }
      }
      $eventEntity->setTimestamp(time());
      $eventEntity->setEvent('resend_invitation');
      $eventEntity->setArtifactUrl($org->getOrgUrl() . '/invitations/' . $inviteId);
      $eventEntity->setConsumerOrgUrl($org->getOrgUrl());
      $eventEntity->setData(['orgName' => $org->getTitle()]);
      $this->eventLogService->createIfNotExist($eventEntity);
    }
    else {
      $response->setSuccess(FALSE);
      $this->logger->error('Unable to resend invitation @invite for @orgname', [
        '@invite' => $inviteId,
        '@orgname' => $org->getTitle(),
      ]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $response;
  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param \Drupal\consumerorg\ApicType\Member $member
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function deleteMember(ConsumerOrg $org, Member $member, $event = 'deleteMember'): UserManagerResponse {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $response = new UserManagerResponse();
    $apimResponse = $this->apimServer->deleteMember($member);
    if ($apimResponse !== NULL && $apimResponse->getCode() === 200) {
      // remove from our db too
      $members = $org->getMembers();
      $newMembers = [];
      foreach ($members as $list_member) {
        if ($member->getId() !== $list_member->getId()) {
          $newMembers[] = $list_member;
        }
      }
      $org->setMembers($newMembers);
      $this->createOrUpdateNode($org, $event);

      $this->logger->notice('Deleted @member (id = @id) from @org consumer org.', [
        '@member' => $member->getUser()->getUsername(),
        '@id' => $member->getId(),
        '@org' => $org->getName(),
      ]);
      $response->setSuccess(TRUE);

      // invalidate myorg page cache
      $this->cacheTagsInvalidator->invalidateTags(['myorg:url:' . $org->getUrl()]);
      // invalidate that user's consumerorg block cache & remove the org from their list
      $memberUser = $member->getUser();
      if ($memberUser !== NULL) {
        $userid = $memberUser->getId();
        if ($userid !== NULL && $userid !== '') {
          $drupalUser = User::load($userid);
          if ($drupalUser !== NULL) {
            $this->userUtils->removeConsumerOrgFromUser($org->getUrl(), $drupalUser);
          }
          $this->cacheTagsInvalidator->invalidateTags(['consumer_org_select_block:uid:' . $userid]);
        }
      }

      // Add Activity Feed Event Log
      $eventEntity = new ApicEvent();
      $eventEntity->setArtifactType('member');
      if ($this->currentUser->isAuthenticated() && (int) $this->currentUser->id() !== 1) {
        $current_user = User::load($this->currentUser->id());
        if ($current_user !== NULL) {
          // we only set the user if we're running as someone other than admin
          // if running as admin then we're likely doing things on behalf of the admin
          // TODO we might want to check if there is a passed in user_url and use that too
          $eventEntity->setUserUrl($current_user->get('apic_url')->value);
        }
      }
      $eventEntity->setTimestamp(time());
      $eventEntity->setEvent('delete');
      $eventEntity->setArtifactUrl($member->getUrl());
      $eventEntity->setConsumerOrgUrl($member->getOrgUrl());
      if (!isset($userid)) {
        $userid = $member->getId();
      }
      $eventEntity->setData(['member' => $userid, 'orgName' => $org->getTitle()]);
      $this->eventLogService->createIfNotExist($eventEntity);
    }
    else {
      $response->setSuccess(FALSE);
      $this->logger->error('Error deleting @member (id = @id)', [
        '@member' => $member->getUser()->getUsername(),
        '@id' => $member->getId(),
      ]);
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $response;
  }

  /**
   * @param \Drupal\consumerorg\ApicType\Member $member
   * @param string|NULL $role
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function changeMemberRole(Member $member, string $role = NULL): UserManagerResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    // update APIm
    $newdata = ['role_urls' => [$role]];

    $response = new UserManagerResponse();
    $apimResponse = $this->apimServer->patchMember($member, $newdata);
    if ($apimResponse !== NULL && $apimResponse->getCode() === 200) {
      $response->setSuccess(TRUE);
      $role = $this->apimUtils->removeFullyQualifiedUrl($role);
      $this->logger->notice('Member @member assigned new role @role by @username', [
        '@role' => $role,
        '@member' => $member->getUser()->getUsername(),
        '@username' => $this->currentUser->getAccountName(),
      ]);

      $org_url = $member->getOrgUrl();
      if ($org_url !== NULL) {
        $org = $this->getConsumerOrgAsObject($member->getOrgUrl());
        $members = $org->getMembers();
        $newMembers = [];
        foreach ($members as $list_member) {
          if ($member->getUrl() === $list_member->getUrl()) {
            $list_member->setRoleUrls([$role]);
          }
          $newMembers[] = $list_member;
        }
        $org->setMembers($newMembers);
        $this->createOrUpdateNode($org, 'internal');

        // Add Activity Feed Event Log
        $eventEntity = new ApicEvent();
        $eventEntity->setArtifactType('member');
        if ($this->currentUser->isAuthenticated() && (int) $this->currentUser->id() !== 1) {
          $current_user = User::load($this->currentUser->id());
          if ($current_user !== NULL) {
            // we only set the user if we're running as someone other than admin
            // if running as admin then we're likely doing things on behalf of the admin
            // TODO we might want to check if there is a passed in user_url and use that too
            $eventEntity->setUserUrl($current_user->get('apic_url')->value);
          }
        }
        $eventEntity->setTimestamp(time());
        $eventEntity->setEvent('update');
        $eventEntity->setArtifactUrl($member->getUrl());
        $eventEntity->setConsumerOrgUrl($member->getOrgUrl());
        $eventEntity->setData(['member' => $member->getUser()->getUsername(), 'orgName' => $org->getTitle()]);
        $this->eventLogService->createIfNotExist($eventEntity);
      }
      $this->cacheTagsInvalidator->invalidateTags(['myorg:url:' . $org_url]);
    }
    else {
      $response->setSuccess(FALSE);
      $this->logger->error('Unable to assign new role @role to @member by @username', [
        '@role' => $role,
        '@member' => $member->getUser()->getUsername(),
        '@username' => $this->currentUser->getAccountName(),
      ]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $response;
  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param $newOwnerUrl
   * @param null $newRole
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \JsonException
   */
  public function changeOrgOwner(ConsumerOrg $org, $newOwnerUrl, $newRole = NULL): UserManagerResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $response = new UserManagerResponse();
    $apimResponse = $this->apimServer->postTransferConsumerOrg($org, $newOwnerUrl, $newRole);
    if ($apimResponse !== NULL && $apimResponse->getCode() === 200) {
      $response->setSuccess(TRUE);
      $localNewOwnerUrl = $this->apimUtils->removeFullyQualifiedUrl($newOwnerUrl);
      $localRole = $this->apimUtils->removeFullyQualifiedUrl($newRole);

      // update our db too
      $current_owner = $org->getOwnerUrl();
      $members = $org->getMembers();
      $newMembers = [];
      $roles = $org->getRoles();
      $ownerRoleUrl = NULL;
      foreach ($roles as $role) {
        if ($role->getName() === 'owner') {
          $ownerRoleUrl = $role->getUrl();
        }
      }
      $oldOwnerUserUrl = $localNewOwnerUrl;
      foreach ($members as $list_member) {
        if ($current_owner === $list_member->getUserUrl()) {
          // give old owner their new role
          $list_member->setRoleUrls([$localRole]);
          $newMembers[] = $list_member;
          // save the old owner's user registry URL as we need it to set the new org ownerUrl
          $oldOwnerUserUrl = $list_member->getUserUrl();
        }
        elseif ($localNewOwnerUrl === $list_member->getUserUrl()) {
          // give new owner the owner role
          $list_member->setRoleUrls([$ownerRoleUrl]);
          $newMembers[] = $list_member;
        }
        else {
          $newMembers[] = $list_member;
        }
      }
      $org->setMembers($newMembers);
      $org->setOwnerUrl($oldOwnerUserUrl);
      $this->createOrUpdateNode($org, 'internal');

      $this->session->set('consumer_organizations', NULL);

      $this->userUtils->setCurrentConsumerorg($org->getUrl());
      $this->userUtils->setOrgSessionData();
      $this->cacheTagsInvalidator->invalidateTags(['config:block.block.consumerorganizationselection']);
      // invalidate myorg page cache
      $this->cacheTagsInvalidator->invalidateTags(['myorg:url:' . $org->getUrl()]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $response;
  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param array $values
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \JsonException
   */
  public function edit(ConsumerOrg $org, array $values): UserManagerResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $name = $values['title'];
    if (is_array($name) && isset($name[0]['value'])) {
      $name = $name[0]['value'];
    }
    elseif (isset($name[0])) {
      $name = array_values($name[0]);
    }
    // update APIm
    $newData = ['title' => $name];
    $customFields = $this->getCustomFields();
    if (!empty($customFields)) {
      $serverResponse = $this->apimServer->get($org->getUrl());
      $newData['metadata'] = [];
      if ($serverResponse->getCode() === 200) {
        if (isset($serverResponse->getData()['metadata'])) {
          $newData['metadata'] = $serverResponse->getData()['metadata'];
        }

        foreach ($customFields as $customField) {
          if (isset($values[$customField])) {
            $newData['metadata'][$customField] = json_encode($values[$customField], JSON_THROW_ON_ERROR);
          }
          else {
            $newData['metadata'][$customField] = "NULL";
          }
        }
      }
    }
    $response = new UserManagerResponse();
    $apimResponse = $this->apimServer->patchConsumerOrg($org, $newData);
    if ($apimResponse !== NULL && $apimResponse->getCode() === 200) {

      // TODO: this should be done via other functions in this service rather than explicitly here.
      // update our DB as well.
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $org->getUrl());
      $nids = $query->accessCheck()->execute();
      if ($nids !== NULL && !empty($nids)) {
        $orgNid = array_shift($nids);
        $orgNode = Node::load($orgNid);
        if ($orgNode !== NULL) {
          $orgNode->setTitle($name);
          // update any custom fields
          $customFields = $this->getCustomFields();
          if (!array_key_exists('metadata', $newData)) {
            $newData['metadata'] = [];
          }
          $this->utils->saveCustomFields($orgNode, $customFields, $newData['metadata'], TRUE);
          $orgNode->save();
        }
      }

      $this->session->set('consumer_organizations', NULL);

      $this->userUtils->setCurrentConsumerorg($org->getUrl());
      $this->userUtils->setOrgSessionData();
      $this->cacheTagsInvalidator->invalidateTags(['config:block.block.consumerorganizationselection']);

      $response->setSuccess(TRUE);

      // invalidate the devorg select block cache
      $this->cacheTagsInvalidator->invalidateTags(['consumer_org_select_block:uid:' . $this->currentUser->id()]);
      // invalidate myorg page cache
      $this->cacheTagsInvalidator->invalidateTags(['myorg:url:' . $org->getUrl()]);

      $this->logger->notice('Consumer organization @orgname updated by @username', [
        '@orgname' => $name,
        '@username' => $this->currentUser->getAccountName(),
      ]);

    }
    else {
      $response->setSuccess(FALSE);
      $this->logger->error('Unable to update @orgurl to @orgname', ['@orgurl' => $org->getUrl(), '@orgname' => $name]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $response;

  }

  /**
   * Remove a given user from all consumer orgs
   * This is called when a user deletes their account.
   *
   * @param string $userUrl
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteUserFromAllCOrgs(string $userUrl): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['userUrl' => $userUrl]);

    if ($userUrl !== NULL) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_memberlist.value', $userUrl, 'CONTAINS');

      $nids = $query->accessCheck()->execute();

      if ($nids !== NULL && !empty($nids)) {
        foreach (array_chunk($nids, 50) as $chunk) {
          $nodes = Node::loadMultiple($chunk);
          foreach ($nodes as $node) {
            if ($node !== NULL) {
              $memberlist = $node->consumerorg_memberlist->value;
              if (($key = array_search($userUrl, $memberlist, TRUE)) !== FALSE) {
                unset($memberlist[$key]);
              }
              $node->set('consumerorg_memberlist', $memberlist);
              if ($node->consumerorg_members) {
                $members = [];
                $whitelist = [Member::class, ApicUser::class];
                foreach ($node->consumerorg_members->getValue() as $arrayValue) {
                  $member = unserialize($arrayValue['value'], ['allowed_classes' => $whitelist]);
                  if ($member->getUserUrl() !== $userUrl) {
                    $members[] = $arrayValue['value'];
                  }
                }
                $node->set('consumerorg_members', $members);
              }
              $node->save();
            }
          }
        }
      }
      else {
        $this->logger->notice('deleteUserFromAllCOrgs: No consumer organization memberships found for user URL %userUrl', ['%userUrl' => $userUrl]);
      }
    }
    else {
      $this->logger->notice('deleteUserFromAllCOrgs: ERROR. NULL value provided for user URL', []);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Create a ConsumerOrg object from a JSON string representation
   * e.g. as returned by a call to the consumer-api or provided in webhooks/snapshots.
   *
   * @param $json
   *
   * @return null|ConsumerOrg
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \JsonException
   */
  public function createFromJSON($json): ?ConsumerOrg {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $org = NULL;

    if (\is_string($json)) {
      $json = json_decode($json, TRUE, 512, JSON_THROW_ON_ERROR);
    }

    // some webhooks have the data at different levels.
    if (\array_key_exists('portal_consumer_orgs', $json)) {
      $this->logger->debug('Using portal_consumer_orgs object for consumer org payload.');
      $json = $json['portal_consumer_orgs'];
    }

    // ignore any create or update webhooks that dont have the minimum number or roles and at least 1 member
    // workaround to avoid https://github.ibm.com/velox/apim/issues/6227
    if ($json['roles'] !== NULL && $json['members'] !== NULL && !empty($json['members']) && count($json['roles']) > 4) {
      $org = new ConsumerOrg();

      if (isset($json['consumer_org']['name'])) {
        $org->setName($json['consumer_org']['name']);
      }
      if (isset($json['consumer_org']['title'])) {
        $org->setTitle($json['consumer_org']['title']);
      }
      if (isset($json['consumer_org']['summary'])) {
        $org->setSummary($json['consumer_org']['summary']);
      }
      if (isset($json['consumer_org']['id'])) {
        $org->setId($json['consumer_org']['id']);
      }
      if (isset($json['consumer_org']['state'])) {
        $org->setState($json['consumer_org']['state']);
      }
      if (isset($json['consumer_org']['created_at'])) {
        $org->setCreatedAt(strtotime($json['consumer_org']['created_at']));
      }
      if (isset($json['consumer_org']['updated_at'])) {
        $org->setUpdatedAt(strtotime($json['consumer_org']['updated_at']));
      }
      if (isset($json['consumer_org']['created_by'])) {
        $org->setCreatedBy($json['consumer_org']['created_by']);
      }
      if (isset($json['consumer_org']['updated_by'])) {
        $org->setUpdatedBy($json['consumer_org']['updated_by']);
      }
      if (isset($json['consumer_org']['url'])) {
        $org->setUrl($json['consumer_org']['url']);
      }
      if (isset($json['consumer_org']['org_url'])) {
        $org->setOrgUrl($json['consumer_org']['org_url']);
      }
      if (isset($json['consumer_org']['catalog_url'])) {
        $org->setCatalogUrl($json['consumer_org']['catalog_url']);
      }
      if (isset($json['consumer_org']['owner_url'])) {
        $org->setOwnerUrl($json['consumer_org']['owner_url']);
      }
      if (isset($json['consumer_org']['group_urls'])) {
        $org->setTags($json['consumer_org']['group_urls']);
      }
      if (isset($json['consumer_org']['custom_fields'])) {
        $org->setTags($json['consumer_org']['custom_fields']);
      }
      $customFields = $this->getCustomFields();
      foreach ($customFields as $field) {
        if (isset($json['consumer_org']['metadata'][$field])) {
          try {
            $org->addCustomField($field, json_decode( $json['consumer_org']['metadata'][$field] , TRUE, 512, JSON_THROW_ON_ERROR));
          } catch (Throwable $e) {
            $org->addCustomField($field, $json['consumer_org']['metadata'][$field]);
          }
        }
      }
      $roles = [];
      foreach ($json['roles'] as $role) {
        $roles[] = $this->roleService->createFromJSON($role);
      }
      $org->setRoles($roles);

      $members = [];
      foreach ($json['members'] as $member) {
        $members[] = $this->memberService->createFromJSON($member);
      }
      $org->setMembers($members);

      if (isset($json['memberInvitations'])) {
        $org->setInvites($json['memberInvitations']);
      }
      else {
        $org->setInvites([]);
      }
      if (isset($json['payment_methods'])) {
        $org->setPaymentMethods($json['payment_methods']);
      }
      else {
        $org->setPaymentMethods([]);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $org);
    return $org;

  }

  /**
   * @param $url
   *
   * @return array
   */
  public function getConsumerOrgForDrush($url): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['url' => $url]);
    $output = NULL;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $url);

    $nids = $query->accessCheck()->execute();

    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($node !== NULL) {
        $output['url'] = $url;
        $output['id'] = $node->consumerorg_id->value;
        $output['name'] = $node->consumerorg_name->value;
        $output['owner'] = $node->consumerorg_owner->value;
        $output['created_at'] = $node->apic_created_at->value;
        $output['updated_at'] = $node->apic_updated_at->value;
        $members = [];
        foreach ($node->consumerorg_members->getValue() as $orgMember) {
          if ($orgMember['value'] !== NULL) {
            $members[] = unserialize($orgMember['value'], ['allowed_classes' => FALSE]);
          }
        }
        $output['members'] = $members;
        $roles = [];
        foreach ($node->consumerorg_roles->getValue() as $role) {
          if ($role['value'] !== NULL) {
            $roles[] = unserialize($role['value'], ['allowed_classes' => FALSE]);
          }
        }
        $output['roles'] = $roles;
        $invites = [];
        foreach ($node->consumerorg_invites->getValue() as $invite) {
          if ($invite['value'] !== NULL) {
            $invites[] = unserialize($invite['value'], ['allowed_classes' => FALSE]);
          }
        }
        $output['invites'] = $invites;
        $output['custom_fields'] = [];
        $customFields = $this->getCustomFields();
        foreach ($customFields as $customField) {
          $output['custom_fields'][$customField] = $node->get($customField)->getValue();
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }


  /**
   * Returns a JSON representation of a consumer organization
   *
   * @param string $url
   *
   * @return string (JSON)
   */
  public function getConsumerOrgAsJson(string $url): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['url' => $url]);
    $output = NULL;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $url);

    $nids = $query->accessCheck()->execute();

    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($this->moduleHandler->moduleExists('serialization')) {
        $serializer = \Drupal::service('serializer');
        $output = $serializer->serialize($node, 'json', ['plugin_id' => 'entity']);
      }
      else {
        $this->logger->notice('getConsumerOrgAsJson: serialization module not enabled', []);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }

  /**
   * Returns a consumer organization object
   *
   * @param string $url
   *
   * @return \Drupal\consumerorg\ApicType\ConsumerOrg
   */
  public function getConsumerOrgAsObject(string $url): ConsumerOrg {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['url' => $url]);
    $output = NULL;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $url);

    $nids = $query->accessCheck()->execute();

    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $output = $this->getByNid($nid);
    }
    else {
      $this->logger->notice('getConsumerOrgAsObject: Consumer Organization not found', []);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }

  /**
   * Create array of useful consumerorg data for use in hooks.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array
   */
  private function createOrgHookData(NodeInterface $node): array {
    $data = [];

    $data['nid'] = $node->id();
    if ($node->hasField('consumerorg_id') && !$node->get('consumerorg_id')->isEmpty()) {
      $data['id'] = $node->get('consumerorg_id')->value;
    }
    if ($node->hasField('consumerorg_name') && !$node->get('consumerorg_name')->isEmpty()) {
      $data['name'] = $node->get('consumerorg_name')->value;
    }
    if ($node->hasField('consumerorg_url') && !$node->get('consumerorg_url')->isEmpty()) {
      $data['url'] = $node->get('consumerorg_url')->value;
    }

    return $data;
  }

  /**
   * Delete a payment method
   *
   * @param $paymentMethodId
   *
   * @return \stdClass|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function deletePaymentMethod($paymentMethodId): RestResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $result = NULL;
    $paymentMethod = PaymentMethod::load($paymentMethodId);

    if ($paymentMethod !== NULL) {
      $org = $paymentMethod->consumerorg_url();
      $url = $org . '/payment-methods/' . $paymentMethod->uuid();

      $result = $this->apimServer->deletePaymentMethod($url);

      if (isset($result) && $result->getCode() >= 200 && $result->getCode() < 300) {
        // update the consumerorg to remove it from the list
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'consumerorg');
        $query->condition('consumerorg_url.value', $org);

        $nids = $query->accessCheck()->execute();
        if ($nids !== NULL && !empty($nids)) {
          $nid = array_shift($nids);
          $consumerOrg = Node::load($nid);
          if ($consumerOrg !== NULL) {
            $paymentMethods = $consumerOrg->consumerorg_payment_method_refs->referencedEntities();
            $newPaymentMethods = [];
            foreach ($paymentMethods as $individualPaymentMethod) {
              if ($individualPaymentMethod->id() !== $paymentMethodId) {
                $newPaymentMethods[] = ['target_id' => $individualPaymentMethod->id()];
              }
            }
            $consumerOrg->set('consumerorg_payment_method_refs', $newPaymentMethods);

            // check if this is the default payment method, is so wipe it
            $defaultPaymentMethod = $consumerOrg->consumerorg_def_payment_ref->referencedEntities();
            if (is_array($defaultPaymentMethod) && !empty($defaultPaymentMethod)) {
              $defaultPaymentMethod = array_shift($defaultPaymentMethod);
            }
            if (!empty($defaultPaymentMethod) && $defaultPaymentMethod->id() === $paymentMethodId) {
              $consumerOrg->set('consumerorg_def_payment_ref', []);
            }
            $consumerOrg->save();

            // Add Activity Feed Event Log
            $eventEntity = new ApicEvent();
            $eventEntity->setArtifactType('payment_method');
            if ($this->currentUser->isAuthenticated() && (int) $this->currentUser->id() !== 1) {
              $current_user = User::load($this->currentUser->id());
              if ($current_user !== NULL) {
                // we only set the user if we're running as someone other than admin
                // if running as admin then we're likely doing things on behalf of the admin
                // TODO we might want to check if there is a passed in user_url and use that too
                $eventEntity->setUserUrl($current_user->get('apic_url')->value);
              }
            }
            $eventEntity->setTimestamp(time());
            $eventEntity->setEvent('delete');
            $eventEntity->setArtifactUrl($url);
            $eventEntity->setConsumerOrgUrl($org);
            $eventEntity->setData(['method' => $paymentMethod->title(), 'orgName' => $consumerOrg->getTitle()]);
            $this->eventLogService->createIfNotExist($eventEntity);
          }
        }

        // delete the entity
        $paymentMethod->delete();

        // invalidate myorg page cache
        $this->cacheTagsInvalidator->invalidateTags(['myorg:url:' . $org]);
      }
      else {
        $this->logger->error('Unable to delete payment method @id', [
          '@id' => $paymentMethodId,
        ]);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $result;
  }

}
