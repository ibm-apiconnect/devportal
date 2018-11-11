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

namespace Drupal\consumerorg\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to edit the consumerorg owner.
 */
class ChangeOrgOwnerForm extends FormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $orgNode;

  protected $consumerOrgService;
  protected $userUtils;
  protected $currentOrg;

  /**
   * ChangeOrgOwnerForm constructor.
   *
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumer_org_service
   * @param \Drupal\consumerorg\Form\UserUtils $user_utils
   */
  public function __construct(ConsumerOrgService $consumer_org_service, UserUtils $user_utils) {
    $this->consumerOrgService = $consumer_org_service;
    $this->userUtils = $user_utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('ibm_apim.user_utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'consumerorg_change_owner_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    if (!$userUtils->checkHasPermission('settings:manage')) {
      $message = t('Permission denied.');
      drupal_set_message($message, 'error');

      $form = array();
      $form['description'] = array('#markup' => '<p>' . t('You do not have sufficient access to perform this action.') . '</p>');

      $form['actions'] = array('#type' => 'actions');
      $form['actions']['cancel'] = array(
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#href' => 'myorg',
        '#attributes' => array('class' => array('button'))
      );
      $themeHandler = \Drupal::service('theme_handler');
      if ($themeHandler->themeExists('bootstrap')) {
        $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }

      return $form;
    } else {
      $org = $userUtils->getCurrentConsumerOrg();
      $this->currentOrg = $this->consumerOrgService->getConsumerOrgAsObject($org['url']);
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $org['url']);
      $nids = $query->execute();
      $this->orgNode = NULL;
      if (isset($nids) && !empty($nids)) {
        $productnid = array_shift($nids);
        $this->orgNode = Node::load($productnid);
      }
      $members = $this->consumerOrgService->getMembers($this->orgNode->consumerorg_url->value);
      if ($members) {
        $values = array();
        // If there is only one member, do not allow change
        if (count($members) === 1) {
          drupal_set_message(t('Cannot change ownership: only one user in developer organization %org', array("%org" => $this->orgNode->getTitle())), 'error');
        }
        else {

          foreach ($members as $member) {
            // Don't include the current user in the list
            $user = User::load(\Drupal::currentUser()->id());
            if ($member->getUser()->getUrl() != $user->get('apic_url')->value) {
              $values[$member->getUrl()] = $member->getUser()->getUsername();
            }
          }
          $form['new_owner'] = array(
            '#title' => t('New Owner'),
            '#type' => 'select',
            '#description' => t('Select the user to take ownership.'),
            '#options' => $values
          );

          // These are the roles to assign to the old owner now that he isn't the owner any more
          $roles = $this->currentOrg->getRoles();
          if (isset($roles) && count($roles) > 1) {
            $roles_array = array();
            $default_role = NULL;
            foreach ($roles as $role) {
              if($role->getName() !== 'owner' && $role->getName() !== 'member') {
                $roles_array[$role->getUrl()] = $role->getTitle();
              }
              if($role->getName() === 'developer') {
                $default_role = $role->getUrl();
              }
            }

            $form['role'] = array(
              '#type' => 'radios',
              '#title' => t('Assign Role'),
              '#default_value' => $default_role,
              '#options' => $roles_array,
              '#description' => t('Select the new role for the previous owner.')
            );
          }

          $form['actions']['#type'] = 'actions';
          $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit'),
          );
          $form['actions']['cancel'] = array(
            '#type' => 'link',
            '#title' => t('Cancel'),
            '#url' => $this->getCancelUrl(),
            '#attributes' => ['class' => ['button', 'apicSecondary']]
          );
          $themeHandler = \Drupal::service('theme_handler');
          if ($themeHandler->themeExists('bootstrap')) {
            $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('ok');
            $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
          }
        }
      }
      else {
        drupal_set_message(t('Failed to retrieve member list for developer organization %org', array("%org" => $this->orgNode->getTitle())), 'error');

        $form = array();
        $form['description'] = array('#markup' => '<p>' . t('Could not get member list for this organization so can not transfer ownership.') . '</p>');

        $form['cancel'] = array(
          '#type' => 'link',
          '#title' => t('Cancel'),
          '#url' => Url::fromRoute('ibm_apim.myorg'),
          '#attributes' => array('class' => array('button'))
        );
        $themeHandler = \Drupal::service('theme_handler');
        if ($themeHandler->themeExists('bootstrap')) {
          $form['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
        }
        return $form;
      }

      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('ibm_apim.myorg');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $new_owner = $form_state->getValue('new_owner');
    $role = $form_state->getValue('role');
    $orgurl = $this->orgNode->consumerorg_url->value;

    if (empty($new_owner)) {
      drupal_set_message(t('A new owner is required.'), 'error');
    }
    else {
      // update APIm
      $selected_user_url = \Drupal::service('ibm_apim.apim_utils')->createFullyQualifiedUrl($new_owner);

      $newdata = array('new_owner_member_url' => $selected_user_url);
      if ($role !== null && !empty($role)) {
        $newdata['old_owner_new_role_urls'] = [$role];
      }

      $url = $orgurl . '/transfer-owner';
      $result = ApicRest::post($url, json_encode($newdata));
      if (isset($result) && ($result->code == 200 && $result->code < 300)) {
        // update our db too
        if (isset($result) && ($result->code == 200 && $result->code < 300)) {
          // update our db too
          $org = $this->consumerOrgService->getConsumerOrgAsObject($this->orgNode->consumerorg_url->value);
          $current_owner = $org->getOwnerUrl();
          $members = $org->getMembers();
          $newmembers = [];
          foreach($members as $list_member) {
            if ($current_owner === $list_member->getUrl()) {
              $list_member->setRoleUrls(array($role));
              $newmembers[] = $list_member;
            } else {
              $newmembers[] = $list_member;
            }
          }
          $org->setMembers($newmembers);
          $org->setOwnerUrl($new_owner);
          $this->consumerOrgService->createOrUpdateNode($org, 'internal');

          drupal_set_message(t('Organization owner updated.'));
          $current_user = \Drupal::currentUser();
          \Drupal::logger('consumerorg')->notice('Consumer organization owner for @orgname changed by @username', array(
            '@orgname' => $this->orgNode->getTitle(),
            '@username' => $current_user->getAccountName()
          ));
        }

        drupal_set_message(t('Organization owner updated.'));
        $current_user = \Drupal::currentUser();
        \Drupal::logger('consumerorg')->notice('Consumer organization owner for @orgname changed by @username', array(
          '@orgname' => $this->orgNode->getTitle(),
          '@username' => $current_user->getAccountName()
        ));

        $session_store = \Drupal::service('tempstore.private')->get('ibm_apim');
        $session_store->set('consumer_organizations', NULL);

        $this->userUtils->refreshUserData();
        $this->userUtils->setCurrentConsumerorg($orgurl);
        \Drupal::service('cache_tags.invalidator')
          ->invalidateTags(array('config:block.block.consumerorganizationselection'));
        // invalidate myorg page cache
        \Drupal::service('cache_tags.invalidator')->invalidateTags(array('myorg:url:' . $orgurl));
      }
    }
    $form_state->setRedirectUrl(Url::fromRoute('ibm_apim.myorg'));
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
