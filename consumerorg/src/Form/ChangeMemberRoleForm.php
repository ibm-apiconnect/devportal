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
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\ApimUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ThemeHandler;

/**
 * Form to change the role of a consumerorg member.
 */
class ChangeMemberRoleForm extends FormBase {

  protected $consumerOrgService;

  protected $userUtils;

  protected $apimUtils;

  protected $themeHandler;

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

  protected $currentOrg;

  /**
   * ChangeMemberRoleForm constructor.
   *
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumer_org_service
   * @param \Drupal\ibm_apim\Service\UserUtils $user_utils
   * @param \Drupal\ibm_apim\Service\ApimUtils $apim_utils
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   */
  public function __construct(
    ConsumerOrgService $consumer_org_service,
    UserUtils $user_utils,
    ApimUtils $apim_utils,
    ThemeHandler $themeHandler
  ) {
    $this->consumerOrgService = $consumer_org_service;
    $this->userUtils = $user_utils;
    $this->apimUtils = $apim_utils;
    $this->themeHandler = $themeHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.apim_utils'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'consumerorg_change_member_role_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $memberId = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (!$this->userUtils->checkHasPermission('settings:manage')) {
      $message = t('Permission denied.');
      drupal_set_message($message, 'error');

      $form = [];
      $form['description'] = ['#markup' => '<p>' . t('You do not have sufficient access to perform this action.') . '</p>'];

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#href' => 'myorg',
        '#attributes' => ['class' => ['button']],
      ];
      if ($this->themeHandler->themeExists('bootstrap')) {
        $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }
    }
    else {
      $org = $this->userUtils->getCurrentConsumerorg();
      $this->currentOrg = $this->consumerOrgService->get($org['url']);

      $members = $this->currentOrg->getMembers();
      if ($members) {
        $values = [];
        // If there is only one member, do not allow change
        if (count($members) === 1) {
          drupal_set_message(t('Cannot change role: no other members in developer organization %org', ['%org' => $this->currentOrg->getTitle()]), 'error');
        }
        else {
          foreach ($members as $member) {
            if ($member->getId() === $memberId) {
              $this->memberId = $memberId;
              $this->member = $member;
            }
          }

          $roles = $this->currentOrg->getRoles();
          if (count($roles) > 1) {
            foreach ($roles as $role) {
              // owner and member are special cases - ignore them
              if ($role->getName() !== 'owner' && $role->getName() !== 'member') {
                $values[$role->getUrl()] = $role->getTitle();
              }
            }
            $form['new_role'] = [
              '#title' => t('New Role'),
              '#type' => 'radios',
              '#description' => t('Select the new role for this member.'),
              '#options' => $values,
            ];

            $form['actions']['#type'] = 'actions';
            $form['actions']['submit'] = [
              '#type' => 'submit',
              '#value' => t('Submit'),
            ];
            if ($this->themeHandler->themeExists('bootstrap')) {
              $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('ok');
            }
            $form['actions']['cancel'] = [
              '#type' => 'link',
              '#title' => t('Cancel'),
              '#href' => 'myorg',
              '#attributes' => ['class' => ['button', 'apicSecondary']],
            ];
            if ($this->themeHandler->themeExists('bootstrap')) {
              $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
            }
          }
          else {
            drupal_set_message(t('Cannot change role: could not find more than 1 role for developer organization %org', ['%org' => $this->currentOrg->getTitle()]), 'error');
          }
        }
      }
      else {
        drupal_set_message(t('Failed to retrieve member list for developer organization %org', ['%org' => $this->currentOrg->getTitle()]), 'error');

        $form = [];
        $form['description'] = ['#markup' => '<p>' . t('Could not get member list for this organization.') . '</p>'];

        $form['cancel'] = [
          '#type' => 'link',
          '#title' => t('Cancel'),
          '#url' => Url::fromRoute('ibm_apim.myorg'),
          '#attributes' => ['class' => ['button']],
        ];
        if ($this->themeHandler->themeExists('bootstrap')) {
          $form['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
        }
      }

    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('ibm_apim.myorg');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $new_role = $form_state->getValue('new_role');
    if (\is_array($new_role) && isset($new_role[0]['value'])) {
      $new_role = $new_role[0]['value'];
    }
    if ($new_role === NULL || empty($new_role)) {
      $form_state->setErrorByName('New Role', $this->t('New role name is a required field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $new_role = $form_state->getValue('new_role');
    $member = $this->member;

    if ($new_role === NULL || empty($new_role)) {
      drupal_set_message(t('A new role is required.'), 'error');
    }
    elseif ($member === NULL) {
      drupal_set_message(t('Member is not set.'), 'error');
    }
    else {
      $selected_role_url = $this->apimUtils->createFullyQualifiedUrl($new_role);
      $response = $this->consumerOrgService->changeMemberRole($member, $selected_role_url);
      if ($response->success()) {
        drupal_set_message(t('Member role updated.'));
      }
      else {
        drupal_set_message(t('Error during member role update. Contact the system administrator.'), 'error');
      }

    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
