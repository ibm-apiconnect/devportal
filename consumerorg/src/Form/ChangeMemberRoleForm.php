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

use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to change the role of a consumerorg member.
 */
class ChangeMemberRoleForm extends FormBase {

  protected $consumerOrgService;

  protected $userUtils;

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $orgNode;

  /**
   * The id of the member to change role
   *
   * @var string
   */
  protected $memberId;

  /**
   * The member to change role
   *
   * @var \Drupal\consumerorg\ApicType\Member
   */
  protected $member;


  /**
   * Constructs an Org User Invitation Form.
   *
   * {@inheritdoc}
   *
   * @param ConsumerOrgService $consumerOrgService
   */
  public function __construct(ConsumerOrgService $consumer_org_service, UserUtils $user_utils) {
    $this->consumerOrgService = $consumer_org_service;
    $this->userUtils = $user_utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('ibm_apim.consumerorg'), $container->get('ibm_apim.user_utils'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'consumerorg_change_member_role_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $memberId = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (!$this->userUtils->checkHasPermission('settings:manage')) {
      $message = t('Permission denied.');
      drupal_set_message($message, 'error');

      $form = array();
      $form['description'] = array('#markup' => '<p>' . t('You do not have sufficient access to perform this action.') .'</p>');

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
    }
    else {
      $org = $this->userUtils->getCurrentConsumerOrg();
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
          drupal_set_message(t('Cannot change role: no other members in developer organization %org', array("%org" => $this->orgNode->getTitle())), 'error');
        }
        else {
          foreach ($members as $member) {
            if ($member->getId() == $memberId) {
              $this->memberId = $memberId;
              $this->member = $member;
            }
          }

          $roles = $this->consumerOrgService->getRoles($this->orgNode->consumerorg_url->value);
          if (count($roles) > 1) {
            foreach ($roles as $role) {
              // owner and member are special cases - ignore them
              if ($role->getName() !== 'owner' && $role->getName() !== 'member') {
                $values[$role->getUrl()] = $role->getTitle();
              }
            }
            $form['new_role'] = array(
              '#title' => t('New Role'),
              '#type' => 'radios',
              '#description' => t("Select the new role for this member."),
              '#options' => $values
            );

            $form['actions']['#type'] = 'actions';
            $form['actions']['submit'] = array(
              '#type' => 'submit',
              '#value' => t('Submit'),
            );
            $themeHandler = \Drupal::service('theme_handler');
            if ($themeHandler->themeExists('bootstrap')) {
              $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('ok');
            }
            $form['actions']['cancel'] = array(
              '#type' => 'link',
              '#title' => t('Cancel'),
              '#href' => 'myorg',
              '#attributes' => array('class' => ['button', 'apicSecondary'])
            );
            if ($themeHandler->themeExists('bootstrap')) {
              $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
            }
          }
          else {
            drupal_set_message(t('Cannot change role: could not find more than 1 role for developer organization %org', array("%org" => $this->orgNode->getTitle())), 'error');
          }
        }
      }
      else {
        drupal_set_message(t('Failed to retrieve member list for developer organization %org', array("%org" => $this->orgNode->getTitle())), 'error');

        $form = array();
        $form['description'] = array('#markup' => '<p>' . t('Could not get member list for this organization.') . '</p>');

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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $new_role = $form_state->getValue('new_role');
    if (is_array($new_role) && isset($new_role[0]['value'])) {
      $new_role = $new_role[0]['value'];
    }
    if (!isset($new_role) || empty($new_role)) {
      $form_state->setErrorByName('New Role', $this->t('New role name is a required field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $new_role = $form_state->getValue('new_role');
    $member = $this->member;

    if (!isset($new_role) || empty($new_role)) {
      drupal_set_message(t('A new role is required.'), 'error');
    }
    elseif (!isset($member)) {
      drupal_set_message(t('Member is not set.'), 'error');
    }
    else {
      $selected_role_url = \Drupal::service('ibm_apim.apim_utils')->createFullyQualifiedUrl($new_role);
      $response = $this->consumerOrgService->changeMemberRole($member, $selected_role_url);
      if ($response->success()) {
        drupal_set_message(t('Member role updated.'));
      }
      else {
        drupal_set_message(t('Error during member role update. Contact the system administrator.'), 'error');
      }

    }
    $form_state->setRedirectUrl(Url::fromRoute('ibm_apim.myorg'));
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
