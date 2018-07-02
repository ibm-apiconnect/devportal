<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2017
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\consumerorg\Service;

use Drupal\auth_apic\UserManagerResponse;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\Event\ConsumerorgCreateEvent;
use Drupal\consumerorg\Event\ConsumerorgUpdateEvent;
use Drupal\consumerorg\Event\ConsumerorgDeleteEvent;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\State;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\Interfaces\PermissionsServiceInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\node\NodeInterface;
use \Drupal\Core\TempStore\PrivateTempStoreFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class to work with the consumerorg content type, takes input from the JSON returned by
 * IBM API Connect
 */
class ConsumerOrgService {

  private $logger;
  private $state;
  private $siteconfig;
  private $permissions;
  private $eventDispatcher;
  private $currentUser;
  private $userQuery;
  private $moduleHandler;
  private $apimServer;
  private $session;
  private $userUtils;
  private $cacheTagsInvalidator;
  private $memberService;
  private $roleService;


  /**
   * ApicUserManager constructor.
   *
   * @param \Drupal\consumerorg\Service\Psr\Log\LoggerInterface|\Psr\Log\LoggerInterface $logger
   *   Logger
   * @param \Drupal\core\State\State $state
   *   State service.
   * @param \Drupal\ibm_apim\Service\SiteConfig $site_config
   * @param \Drupal\ibm_apim\Service\Permissions $permissions
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Entity\Query\QueryFactory $entityQuery
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\ibm_apim\Service\ManagementServerInterface $apim
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\ibm_apim\Service\UserUtils $user_utils
   *
   * @internal param \Drupal\consumerorg\Service\UserRegistryService $user_registry_service User registry service.*   User registry service.
   */
  public function __construct(LoggerInterface $logger,
                              State $state,
                              SiteConfig $site_config,
                              PermissionsServiceInterface $permissions,
                              EventDispatcherInterface $event_dispatcher,
                              AccountProxyInterface $current_user,
                              QueryFactory $entityQuery,
                              ModuleHandlerInterface $module_handler,
                              ManagementServerInterface $apim,
                              PrivateTempStoreFactory $temp_store_factory,
                              UserUtils $user_utils,
                              CacheTagsInvalidatorInterface $invalidator,
                              MemberService $member_service,
                              RoleService $role_service
                              ) {
    $this->logger = $logger;
    $this->state = $state;
    $this->siteconfig = $site_config;
    $this->permissions = $permissions;
    $this->eventDispatcher = $event_dispatcher;
    $this->currentUser = $current_user;
    $this->userQuery = $entityQuery->get('user');
    $this->moduleHandler = $module_handler;
    $this->apimServer = $apim;
    $this->session = $temp_store_factory->get('ibm_apim');
    $this->userUtils = $user_utils;
    $this->cacheTagsInvalidator = $invalidator;
    $this->memberService = $member_service;
    $this->roleService = $role_service;
  }

  /**
   * Create a new consumer org. Calls consumer api to create in apim and handles local nodes and state as well.

   * @param string $name
   *   Organization name
   *
   * @return UserManagerResponse
   */
  public function create(string $name) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $response = new UserManagerResponse();

    $org = new ConsumerOrg();
    $org->setName($name);

    $apim_response = $this->apimServer->createConsumerOrg($org);

    if (isset($apim_response) && $apim_response->getCode() === 201) {
      $org->setUrl(\Drupal::service('ibm_apim.apim_utils')->removeFullyQualifiedUrl($apim_response->getData()['url']));
      $org->setId($apim_response->getData()['id']);
      $org->setTitle($name);
      $org->setOwnerUrl($apim_response->getData()['owner_url']);

      $this->createNode($org);
      $response->setMessage(t('Consumer organization created successfully.'));
      $this->logger->notice('Consumer organization @orgname created by @username', array('@orgname' => $name, '@username' => $this->currentUser->getAccountName()));

      $this->session->set('consumer_organizations', NULL);
      $this->userUtils->loadConsumerorgs();
      $this->cacheTagsInvalidator->invalidateTags(array('config:block.block.consumerorganizationselection'));
      $response->setSuccess(TRUE);
      // invalidate the devorg select block cache
      $this->cacheTagsInvalidator->invalidateTags(array('consumer_org_select_block:uid:' . $this->currentUser->id()));
    }
    else {
      $response->setMessage(t('Failed to create consumer organization.'), 'error');
      $this->logger->error('Failed to create consumer organization. response: @response', array('@response' => serialize($apim_response->getData())));

      // If user is not in an org, log them out.
      $other_orgs = $this->getList();
      if(empty($other_orgs)) {
        $response->setRedirect('/user/logout');
      }
    }
    $response->setRedirect('<front>');
    ibm_apim_exit_trace(__FUNCTION__, $response->success());
    return $response;
  }

  /**
   * Delete consumer org in apim and locally.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @return \Drupal\auth_apic\UserManagerResponse
   */
  public function delete(ConsumerOrg $org) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $response = new UserManagerResponse();

    $apim_response = $this->apimServer->deleteConsumerOrg($org);


    if (isset($apim_response) && $apim_response->getCode() === 200) {

      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $org->getUrl());
      $nids = $query->execute();
      $orgNode = NULL;
      if (isset($nids) && !empty($nids)) {
        $this->deleteNode(array_shift($nids));
      }
      // invalidate the devorg select block cache
      $this->cacheTagsInvalidator->invalidateTags(array('consumer_org_select_block:uid:' . $this->currentUser->id()));

      $this->logger->notice('Organization @orgname deleted by @username', array(
          '@orgname' => $org->getName(),
          '@username' => $this->currentUser->getAccountName()
        ));

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
   * @param ConsumerOrg $consumer
   * @return int|null|string
   */
  public function createNode(ConsumerOrg $consumer) {
    ibm_apim_entry_trace(__FUNCTION__, $consumer->getUrl());

    if ($consumer->getUrl() !== NULL) {
      // find if there is an existing node for this consumerorg
      // using id from swagger doc
      // if so then clone it and base new node on that.
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $consumer->getUrl())->sort('nid', 'ASC');
      $nids = $query->execute();
      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        $oldnode = Node::load($nid);
      }
    }

    if (isset($oldnode) && $oldnode->id()) {

      // duplicate node
      $node = $oldnode->createDuplicate();

      // wipe all our fields to ensure they get set to new values
      $node->set('apic_hostname', NULL);
      $node->set('apic_catalog_id', NULL);
      $node->set('apic_provider_id', NULL);
      $node->set('consumerorg_memberlist', NULL);
      $node->set('consumerorg_members', NULL);
//      $node->set('consumerorg_communities', NULL);
      $node->set('consumerorg_id', NULL);
      $node->set('consumerorg_url', NULL);
      $node->set('consumerorg_name', NULL);
      $node->set('consumerorg_owner', NULL);
      $node->set('consumerorg_roles', NULL);
      $node->set('consumerorg_tags', NULL);
    }
    else {
      $node = Node::create(array(
        'type' => 'consumerorg',
      ));
    }

    // get the update method to do the update for us
    $node = $this->updateNode($node, $consumer, 'internal');
    if (isset($node)) {
      $this->logger->notice('Consumer organization @consumerorg created', array('@consumerorg' => $node->getTitle()));
      // Calling all modules implementing 'hook_consumerorg_create':
      $this->moduleHandler->invokeAll('consumerorg_create', array('node' => $node, 'data' => $consumer));

      if ($this->moduleHandler->moduleExists('rules')) {
        // Set the args twice on the event: as the main subject but also in the
        // list of arguments.
        $event = new ConsumerorgCreateEvent($node, ['consumerorg' => $node]);
        $this->eventDispatcher->dispatch(ConsumerorgCreateEvent::EVENT_NAME, $event);
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
    return $node->id();
  }

  /**
   * Update an existing consumerorg
   *
   * @param $node
   * @param ConsumerOrg $consumer
   * @param string $event
   * @return NodeInterface|null
   */
  public function updateNode(NodeInterface $node, ConsumerOrg $consumer, $event = 'content_refresh') {
    ibm_apim_entry_trace(__FUNCTION__, $consumer->getUrl());
    if (isset($node)) {
      $utils = \Drupal::service( 'ibm_apim.utils');
      $apim_utils = \Drupal::service( 'ibm_apim.apim_utils');
      $hostvariable = $this->siteconfig->getApimHost();
      $node->setTitle($utils->truncate_string($consumer->getTitle()));
      $node->setPromoted(NODE_NOT_PROMOTED);
      $node->set("apic_hostname", $hostvariable);
      $node->set("apic_provider_id", $this->siteconfig->getOrgId());
      $node->set("apic_catalog_id", $this->siteconfig->getEnvId());
      $node->set('consumerorg_id', $consumer->getId());
      $node->set('consumerorg_url', $consumer->getUrl());
      $node->set('consumerorg_name', $utils->truncate_string($consumer->getName(),128));
      $node->set('consumerorg_owner', $consumer->getOwnerUrl());
      // TODO: consumerorg tags
//      if ($consumer->getTags() === NULL) {
//        $consumer->setTags(array());
//      }
//      $node->set('consumerorg_tags', $consumer->getTags());

      $roles = array();
      if ($consumer->getRoles() !== NULL) {
        // Role objects need to be flattened for storage in the DB
        foreach ($consumer->getRoles() as $role) {
          $roles[] = serialize($role);
        }
      }

      $node->set('consumerorg_roles', $roles);

      // we need to store members information in 2 formats:
      // members = url, user_url, state, roles
      // memberlist = user_url

      $members = array();
      $memberlist = array();

      // when a user creates a new org, we don't initially have all the data we need in the response
      // if we don't do the below, the new org doesn't appear in the corg selector block
      if(empty($consumer->getMembers()) && !empty($consumer->getOwnerUrl())) {

        // munge the urls because the apim side gives us too much
        $corg_url = $apim_utils->removeFullyQualifiedUrl($consumer->getUrl());
        $owner_url = $apim_utils->removeFullyQualifiedUrl($consumer->getOwnerUrl());

        $account = User::load($this->currentUser->id());

        if($account->apic_url->value === $owner_url) {
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

      if ($consumer->getMembers() !== NULL) {
        foreach ($consumer->getMembers() as $member) {
          $memberlist[] = $member->getUserUrl();
//          $new_member = new Member();rray(
//            'url' => $member->getUrl(),
//            'user_url' => $member->getUserUrl(),
//            'state' => $member->getState(),
//            'roles' => $member->getRoleUrls()
//          );
          $members[] = serialize($member);

          if ($member->getUser() !== NULL && $member->getUser()->getUsername() !== NULL) {
            $account = user_load_by_name($member->getUser()->getUsername());
            if ($account) {
              // consumerorg_id is a multi value field which Drupal represents using a FieldItemList class
              // this causes headaches as seen below....
              $consumerorg_urls = $account->consumerorg_url->getValue();
              if (!isset($consumerorg_urls)) {
                $consumerorg_urls = [];
              }
              // Add the consumerorg if it isn't already associated with this user
              if (!$this->isConsumerorgAssociatedWithAccount($consumer->getUrl(), $account)) {
                $consumerorg_urls[] = $consumer->getUrl();
                $account->set('consumerorg_url', $consumerorg_urls);
                $account->save();
              }
            }
          }

        }
      }

      $node->set('consumerorg_members', $members);
      $node->set('consumerorg_memberlist', $memberlist);
      $node->save();
      if (isset($node) && $event != 'internal') {
        $this->logger->notice('Consumer organization @consumerorg updated', array('@consumerorg' => $node->getTitle()));
        // Calling all modules implementing 'hook_consumerorg_update':
        $this->moduleHandler->invokeAll('consumerorg_update', array('node' => $node, 'data' => $consumer));

        if ($this->moduleHandler->moduleExists('rules')) {
          // Set the args twice on the event: as the main subject but also in the
          // list of arguments.
          $event = new ConsumerorgUpdateEvent($node, ['consumerorg' => $node]);
          $this->eventDispatcher->dispatch(ConsumerorgUpdateEvent::EVENT_NAME, $event);
        }
      }
      ibm_apim_exit_trace(__FUNCTION__, NULL);
      return $node;
    }
    else {
      $this->logger->error('Update consumerorg: no node provided.', array());
      ibm_apim_exit_trace(__FUNCTION__, NULL);
      return NULL;
    }
  }

  /**
   * Create a new consumerorg if one doesnt already exist for that consumer organization reference
   * Update one if it does
   *
   * @param ConsumerOrg $consumer
   * @param $event
   * @return bool
   */
  public function createOrUpdateNode(ConsumerOrg $consumer, $event) {
    ibm_apim_entry_trace(__FUNCTION__, $consumer->getUrl());
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $consumer->getUrl());

    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      $this->updateNode($node, $consumer, $event);
      $createdOrUpdated = FALSE;
    }
    else {
      // no existing node for this consumerorg so create one
      $this->createNode($consumer, $event);
      $createdOrUpdated = TRUE;
    }
    ibm_apim_exit_trace(__FUNCTION__, $createdOrUpdated);
    return $createdOrUpdated;
  }

  /**
   * Create a new consumerorg if one doesnt already exist for that consumer organization reference
   * Update one if it does
   *
   * @param $invitation
   * @param $event
   * @return bool
   */
  public function createOrUpdateInvitation($invitation, $event) {
    ibm_apim_entry_trace(__FUNCTION__, null);
    $createdOrUpdated = null;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $invitation['consumer_org_url']);

    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      $invites = array();
      foreach ($node->consumerorg_invites->getValue() as $arrayValue) {
        $unserialized = unserialize($arrayValue['value']);
        $invites[] = $unserialized;
      }
      $found = false;
      foreach($invites as $key=>$value) {
        if ($value['url'] == $invitation['url']) {
          $invites[$key] = $invitation;
          $found = true;
        }
      }
      if ($found == false) {
        $invites[] = $invitation;
      }
      $serialized_invites = array();
      foreach ($invites as $key => $invite) {
        if (isset($invite['url'])) {
          $invite['url'] = \Drupal::service('ibm_apim.apim_utils')->removeFullyQualifiedUrl($invite['url']);
        }
        $serialized_invites[] = serialize($invite);
      }
      $node->set('consumerorg_invites', $serialized_invites);
      $node->save();

      $createdOrUpdated = !$found;
    } else {
      // no node found, ignore
    }
    ibm_apim_exit_trace(__FUNCTION__, $createdOrUpdated);
    return $createdOrUpdated;
  }

  /**
   * Delete an invitation
   *
   * @param $invitation
   * @param $event
   * @return bool
   */
  public function deleteInvitation($invitation, $event) {
    ibm_apim_entry_trace(__FUNCTION__, null);
    $createdOrUpdated = null;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $invitation['consumer_org_url']);

    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      $invites = array();
      foreach ($node->consumerorg_invites->getValue() as $arrayValue) {
        $unserialized = unserialize($arrayValue['value']);
        if ($unserialized['url'] != $invitation['url']) {
          $invites[] = $unserialized;
        }
      }
      $serialized_invites = array();
      foreach ($invites as $key => $invite) {
        $serialized_invites[] = serialize($invite);
      }
      $node->set('consumerorg_invites', $serialized_invites);
      $node->save();
    } else {
      // no node found, ignore
    }
    ibm_apim_exit_trace(__FUNCTION__, null);
    return $createdOrUpdated;
  }

  /**
   * Delete a consumerorg by NID
   * @param $nid
   */
  public function deleteNode($nid) {
    ibm_apim_entry_trace(__FUNCTION__, $nid);
    $node = Node::load($nid);
    $consumerorg_url = $node->consumerorg_url->value;
    // remove the consumerorg assignment from all users since we're deleting it
    $query = $this->userQuery;
    $query->condition('consumerorg_url.value', $consumerorg_url, 'CONTAINS');
    $results = $query->execute();
    if (isset($results) && !empty($results)) {
      $uids = array_values($results);
      if (!empty($uids)) {
        $accounts = User::loadMultiple($uids);
        foreach ($accounts as $account) {
          if ($this->isConsumerorgAssociatedWithAccount($consumerorg_url, $account)) {
            $consumerorg_urls = $account->consumerorg_url->getValue();
            // This is not a simple array but an array of consumerorg_id[0] = array("value"=>orgid)
            // Hence additional complication in removing elements from the array
            foreach ($account->consumerorg_url->getValue() as $index => $valueArray) {
              $nextExistingConsumerorgUrl = $valueArray['value'];
              if ($nextExistingConsumerorgUrl == $consumerorg_url) {
                unset($consumerorg_urls[$index]);
              }
            }
            $account->set('consumerorg_url', $consumerorg_urls);
            $account->save();
          }
        }
      }
    }

    // Calling all modules implementing 'hook_consumerorg_delete':
    $this->moduleHandler->invokeAll('consumerorg_delete', array('node' => $node));
    if ($this->moduleHandler->moduleExists('rules')) {
      // Set the args twice on the event: as the main subject but also in the
      // list of arguments.
      $event = new ConsumerorgDeleteEvent($node, ['consumerorg' => $node]);

      $this->eventDispatcher->dispatch(ConsumerorgDeleteEvent::EVENT_NAME, $event);
    }
    $this->logger->notice('Consumer organization @consumerorg deleted', array('@consumerorg' => $node->getTitle()));

    $node->delete();
    unset($node);
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * Get an instance of a consumer org node and return a ConsumerOrg object.
   *
   * @param $nid
   *   Node id.
   *
   * @return \Drupal\consumerorg\ApicType\ConsumerOrg
   */
  public function retrieveNode($nid) {
    ibm_apim_entry_trace(__FUNCTION__, $nid);

    $node = Node::load($nid);

    $org = new ConsumerOrg();

    if($node->getTitle()) {
      $org->setTitle($node->getTitle());
    }
    if($node->consumerorg_name) {
      $org->setName($node->get('consumerorg_name')->value);
    }
    if($node->consumerorg_url) {
      $org->setUrl($node->get('consumerorg_url')->value);
    }
    if($node->consumerorg_id) {
      $org->setId($node->get('consumerorg_id')->value);
    }
    if($node->consumerorg_owner) {
      $org->setOwnerUrl($node->get('consumerorg_owner')->value);
    }
    if($node->consumerorg_roles) {
      $roles = $node->get('consumerorg_roles')->getValue();
      foreach($roles as $role){
        $org->addRole(unserialize($role['value']));
      }
    }
    if($node->consumerorg_members) {
      $members = array();
      foreach ($node->consumerorg_members->getValue() as $arrayValue) {
        $members[] = unserialize($arrayValue['value']);
      }
      $org->setMembers($members);
    }
    // TODO: consumerorg_memberlist?
    //TODO: $org->setTags($node->get('consumerorg_tags')->value);

    ibm_apim_exit_trace(__FUNCTION__, $org);
    return $org;
  }

  /**
   * Get the members for a given consumerorg
   *
   * @param $orgUrl
   * @return array|null
   */
  public function getMembers($orgUrl) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $returnValue = NULL;
    if (isset($orgUrl)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $orgUrl);
      $nids = $query->execute();
      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        $members = array();
        foreach ($node->consumerorg_members->getValue() as $arrayValue) {
          $members[] = unserialize($arrayValue['value']);
        }
        $returnValue = $members;
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Get the available roles for a given consumerorg
   *
   * @param $orgUrl
   * @return array|null
   */
  public function getRoles($orgUrl) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $returnValue = NULL;
    if (isset($orgUrl)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $orgUrl);
      $nids = $query->execute();
      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        $returnValue = $node->consumerorg_roles->value;
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Get a single consumerorg by the consumerorg url
   *
   * @param string $orgUrl
   *   The APIC UID of the consumerorg to find.
   * @param int $admin
   *   If set to 1 then bypass the access check on the memberlist
   * @return Node
   *   The Node loaded from the database that matches the consumerorg uid.
   */
  public function get($orgUrl, $admin = 0) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $result = NULL;
    $nid = NULL;

    if (!$this->currentUser->isAnonymous()) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $orgUrl);

      // TODO: need to check member list against user url. Not currently stored for user.
//      if ($admin != 1 && $this->currentUser->id() != 1) {
//        $query->condition('consumerorg_memberlist.value', $this->currentUser->getAccountName(), 'CONTAINS');
//      }

      $results = $query->execute();

      if (isset($results) && !empty($results)) {
        $nid = array_shift($results);
        $result = $this->retrieveNode($nid);
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, $nid);
    return $result;
  }

  /**
   * Database based query to get list of consumerorgs for the current user
   * @return array
   */
  public function getList() {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $list = array();
    $account = User::load($this->currentUser->id());
    if (!$this->currentUser->isAnonymous() && $this->currentUser->id() != 1) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_memberlist.value', $account->apic_url->value, 'CONTAINS');

      $results = $query->execute();
      if (isset($results) && !empty($results)) {
        $list = array_values($results);
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, $list);
    return $list;
  }

  /**
   * A list of all the IBM created fields for this content type
   *
   * @return array
   */
  public function getIBMFields() {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $ibmfields = array(
      'apic_hostname',
      'apic_provider_id',
      'apic_catalog_id',
      'apic_rating',
      'apic_tags',
      'apic_pathalias',
      'consumerorg_id',
      'consumerorg_invites',
      'consumerorg_members',
      'consumerorg_memberlist',
      'consumerorg_name',
      'consumerorg_owner',
      'consumerorg_tags',
      'consumerorg_url'
    );
    ibm_apim_exit_trace(__FUNCTION__, $ibmfields);
    return $ibmfields;
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
  public function isConsumerorgAssociatedWithAccount($consumerorg_url, $account) {
    if (isset($account)) {
      ibm_apim_entry_trace(__FUNCTION__, array($consumerorg_url, $account->getUsername()));
    }
    else {
      ibm_apim_entry_trace(__FUNCTION__, array($consumerorg_url));
    }

    $returnValue = FALSE;
    // We want to do in_array($newOrgId, $account->consumerorg_ids) but consumerorg_ids is not a simple array.
    // It is an array of arrays like this :
    //   $consumerorg_ids[0] = array("value" => "theOrgId");
    //   $consumerorg_ids[1] = array("value" => "theOtherOrgId");
    // Hence the below code rather than the much simpler in_array call we might want to have made!
    foreach ($account->consumerorg_url->getValue() as $index => $valueArray) {
      $nextExistingConsumerorgUrl = $valueArray['value'];
      if ($nextExistingConsumerorgUrl == $consumerorg_url) {
        $returnValue = TRUE;
      }
    }

    ibm_apim_exit_trace(__FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Invite a new user into an organization.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $email_address
   * @param string|NULL $role
   * @return \Drupal\auth_apic\UserManagerResponse
   */
  public function inviteMember(ConsumerOrg $org, string $email_address, string $role = NULL) {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = new UserManagerResponse();
    $apim_response = $this->apimServer->postMemberInvitation($org, $email_address, $role);
    if (isset($apim_response) && $apim_response->getCode() === 201) {
      $response->setSuccess(TRUE);
      $this->logger->notice('New member @invitee invited to @orgname by @username', array('@orgname' => $org->getTitle(), '@invitee' => $email_address, '@username' => $this->currentUser->getAccountName()));
    }
    else {
      $response->setSuccess(FALSE);
      $this->logger->error('Unable to invite user @username to @orgname', array('@username' => $this->currentUser->getAccountName(), '@orgname' => $org->getTitle()));
    }

    ibm_apim_exit_trace(__FUNCTION__, NULL);
    return $response;

  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $title
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   */
  public function editOrgTitle(ConsumerOrg $org, string $title) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // update APIm
    $newdata = array('title' => $title);

    $response = new UserManagerResponse();
    $apim_response = $this->apimServer->patchConsumerOrg($org, $newdata);
    if (isset($apim_response) && $apim_response->getCode() === 200) {

      // TODO: this should be done via other functions in this service rather than explicitly here.
      // update our DB as well.
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $org->getUrl());
      $nids = $query->execute();
      $orgNode = NULL;
      if (isset($nids) && !empty($nids)) {
        $productnid = array_shift($nids);
        $orgNode = Node::load($productnid);
        $orgNode->setTitle($title);
        $orgNode->save();
      }

      $this->session->set('consumer_organizations', NULL);

      $this->userUtils->refreshUserData();
      $this->userUtils->setCurrentConsumerorg($org->getUrl());
      $this->cacheTagsInvalidator->invalidateTags(array('config:block.block.consumerorganizationselection'));

      $response->setSuccess(TRUE);

      // invalidate the devorg select block cache
      $this->cacheTagsInvalidator->invalidateTags(array('consumer_org_select_block:uid:' . $this->currentUser->id()));

      $this->logger->notice('Consumer organization @orgname updated by @username', array('@orgname' => $title, '@username' => $this->currentUser->getAccountName()));

    }
    else {
      $response->setSuccess(FALSE);
      $this->logger->error('Unable to update @orgurl to @orgname', array('@orgurl' => $org->getUrl(), '@orgname' => $title));
    }

    ibm_apim_exit_trace(__FUNCTION__, NULL);
    return $response;

  }

  /**
   * Create a ConsumerOrg object from a JSON string representation
   * e.g. as returned by a call to the consumer-api or provided in webhooks/snapshots.
   *
   * @param $json
   *
   * @return ConsumerOrg
   */
  public function createFromJSON($json) {

    ibm_apim_entry_trace(__FUNCTION__, NULL);

    $org = NULL;

    if(is_string($json)) {
      $json = json_decode($json, 1);
    }

    // some webhooks have the data at different levels.
    if (array_key_exists('portal_consumer_orgs', $json)) {
      $this->logger->debug('Using portal_consumer_orgs object for consumer org payload.');
      $json = $json['portal_consumer_orgs'];
    }

    // ignore any create or update webhooks that dont have the minimum number or roles and at least 1 member
    // workaround to avoid https://github.ibm.com/velox/apim/issues/6227
    if (isset($json['roles']) && count($json['roles'])>4 && isset($json['members']) && !empty($json['members'])) {
      $org = new ConsumerOrg();

      if(isset($json['consumer_org']['name'])) {
        $org->setName($json['consumer_org']['name']);
      }
      if(isset($json['consumer_org']['title'])) {
        $org->setTitle($json['consumer_org']['title']);
      }
      if(isset($json['consumer_org']['summary'])) {
        $org->setSummary($json['consumer_org']['summary']);
      }
      if(isset($json['consumer_org']['id'])) {
        $org->setId($json['consumer_org']['id']);
      }
      if(isset($json['consumer_org']['state'])) {
        $org->setState($json['consumer_org']['state']);
      }
      if(isset($json['consumer_org']['created_at'])) {
        $org->setCreatedAt($json['consumer_org']['created_at']);
      }
      if(isset($json['consumer_org']['updated_at'])) {
        $org->setUpdatedAt($json['consumer_org']['updated_at']);
      }
      if(isset($json['consumer_org']['url'])) {
        $org->setUrl($json['consumer_org']['url']);
      }
      if(isset($json['consumer_org']['org_url'])) {
        $org->setOrgUrl($json['consumer_org']['org_url']);
      }
      if(isset($json['consumer_org']['catalog_url'])) {
        $org->setCatalogUrl($json['consumer_org']['catalog_url']);
      }
      if(isset($json['consumer_org']['owner_url'])) {
        $org->setOwnerUrl($json['consumer_org']['owner_url']);
      }
      if(isset($json['roles'])) {
        $roles = array();
        foreach($json['roles'] as $role){
          $roles[] = $this->roleService->createFromJSON($role);
        }
        $org->setRoles($roles);
      }

      if(isset($json['members'])) {
        $members = array();
        foreach($json['members'] as $member) {
          $members[] = $this->memberService->createFromJSON($member);
        }
        $org->setMembers($members);
      }
    }

    ibm_apim_exit_trace(__FUNCTION__, $org);
    return $org;

  }

  /**
   * Create a ConsumerOrg object from a JSON string representation
   * e.g. as returned by GET /me
   * Note, this doesn't handle roles, permissions or members as they are a different format in that response.
   *
   * @param $json
   *
   * @return ConsumerOrg
   */
  public function createFromMeResponseJSON($json) {

    ibm_apim_entry_trace(__FUNCTION__, $json);

    $org = new ConsumerOrg();

    $apim_utils = \Drupal::service('ibm_apim.apim_utils');

    if(is_string($json)) {
      $json = json_decode($json, 1);
    }

    if(isset($json['org']['name'])) {
      $org->setName($json['org']['name']);
    }
    if(isset($json['org']['title'])) {
      $org->setTitle($json['org']['title']);
    }
    if(isset($json['org']['summary'])) {
      $org->setSummary($json['org']['summary']);
    }
    if(isset($json['org']['id'])) {
      $org->setId($json['org']['id']);
    }
    if(isset($json['org']['state'])) {
      $org->setState($json['org']['state']);
    }
    if(isset($json['org']['created_at'])) {
      $org->setCreatedAt($json['org']['created_at']);
    }
    if(isset($json['org']['updated_at'])) {
      $org->setUpdatedAt($json['org']['updated_at']);
    }
    if(isset($json['org']['url'])) {
      $org->setUrl($apim_utils->removeFullyQualifiedUrl($json['org']['url']));
    }
    if(isset($json['org']['owner_url'])) {
      $org->setOwnerUrl($apim_utils->removeFullyQualifiedUrl($json['org']['owner_url']));
    }

    ibm_apim_exit_trace(__FUNCTION__, $org);
    return $org;

  }

  /**
   * Returns a JSON representation of a consumer organization
   *
   * @param $url
   * @return string (JSON)
   */
  public function getConsumerOrgAsJson($url) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array('url' => $url));
    $output = null;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $url);

    $nids = $query->execute();

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('serialization')) {
        $serializer = \Drupal::service('serializer');
        $output = $serializer->serialize($node, 'json', ['plugin_id' => 'entity']);
      } else {
        \Drupal::logger('consumerorg')->notice('getConsumerOrgAsJson: serialization module not enabled', array());
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }

}
