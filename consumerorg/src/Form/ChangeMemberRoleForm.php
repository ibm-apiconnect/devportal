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

namespace Drupal\consumerorg\Form;

use Drupal\consumerorg\ApicType\Member;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to change the role of a consumerorg member.
 */
class ChangeMemberRoleForm extends FormBase {

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected ConsumerOrgService $consumerOrgService;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected ApimUtils $apimUtils;

  /**
   * @var \Drupal\Core\Extension\ThemeHandler
   */
  protected ThemeHandler $themeHandler;

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $orgNode;

  /**
   * The id of the member to change role
   *
   * @var string
   */
  protected string $memberId;

  /**
   * The member to change role
   *
   * @var \Drupal\consumerorg\ApicType\Member
   */
  protected Member $member;

  protected $currentOrg;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * ChangeMemberRoleForm constructor.
   *
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumer_org_service
   * @param \Drupal\ibm_apim\Service\UserUtils $user_utils
   * @param \Drupal\ibm_apim\Service\ApimUtils $apim_utils
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(
    ConsumerOrgService $consumer_org_service,
    UserUtils $user_utils,
    ApimUtils $apim_utils,
    ThemeHandler $themeHandler,
    Messenger $messenger
  ) {
    $this->consumerOrgService = $consumer_org_service;
    $this->userUtils = $user_utils;
    $this->apimUtils = $apim_utils;
    $this->themeHandler = $themeHandler;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ChangeMemberRoleForm {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.apim_utils'),
      $container->get('theme_handler'),
      $container->get('messenger')
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
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function buildForm(array $form, FormStateInterface $form_state, $memberId = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (!$this->userUtils->checkHasPermission('settings:manage')) {
      $this->messenger->addError(t('Permission denied.'));

      $form = [];
      $form['description'] = ['#markup' => '<p>' . t('You do not have sufficient access to perform this action.') . '</p>'];

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => $this->getCancelUrl(),
        '#attributes' => ['class' => ['button']],
      ];
    }
    else {
      $org = $this->userUtils->getCurrentConsumerorg();
      $this->currentOrg = $this->consumerOrgService->get($org['url']);

      $members = $this->currentOrg->getMembers();
      if ($members && $memberId !== NULL) {
        $roles_array = [];
        // If there is only one member, do not allow change
        if (count($members) === 1) {
          $this->messenger->addError(t('Cannot change role: no other members in developer organization %org', ['%org' => $this->currentOrg->getTitle()]));
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
                // use translated role names if possible
                switch ($role->getTitle()) {
                  case 'Administrator':
                    $roles_array[$role->getUrl()] = t('Administrator');
                    break;
                  case 'Developer':
                    $roles_array[$role->getUrl()] = t('Developer');
                    break;
                  case 'Viewer':
                    $roles_array[$role->getUrl()] = t('Viewer');
                    break;
                  default:
                    $roles_array[$role->getUrl()] = $role->getTitle();
                    break;
                }
              }
            }
            $form['new_role'] = [
              '#title' => t('New Role'),
              '#type' => 'radios',
              '#description' => t('Select the new role for this member.'),
              '#options' => $roles_array,
            ];

            $form['actions']['#type'] = 'actions';
            $form['actions']['submit'] = [
              '#type' => 'submit',
              '#value' => t('Save'),
            ];
            $form['actions']['cancel'] = [
              '#type' => 'link',
              '#title' => t('Cancel'),
              '#url' => $this->getCancelUrl(),
              '#attributes' => ['class' => ['button', 'apicSecondary']],
            ];
          }
          else {
            $this->messenger->addError(t('Cannot change role: could not find more than 1 role for developer organization %org', ['%org' => $this->currentOrg->getTitle()]));
          }
        }
      }
      else {
        $this->messenger->addError(t('Failed to retrieve member list for developer organization %org', ['%org' => $this->currentOrg->getTitle()]));

        $form = [];
        $form['description'] = ['#markup' => '<p>' . t('Could not get member list for this organization.') . '</p>'];

        $form['cancel'] = [
          '#type' => 'link',
          '#title' => t('Cancel'),
          '#url' => $this->getCancelUrl(),
          '#attributes' => ['class' => ['button']],
        ];
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
      $this->messenger->addError(t('A new role is required.'));
    }
    elseif ($member === NULL) {
      $this->messenger->addError(t('Member is not set.'));
    }
    else {
      $selected_role_url = $this->apimUtils->createFullyQualifiedUrl($new_role);
      $response = $this->consumerOrgService->changeMemberRole($member, $selected_role_url);
      if ($response->success()) {
        $this->messenger->addMessage(t('Member role updated.'));
      }
      else {
        $this->messenger->addError(t('Error during member role update. Contact the system administrator.'));
      }

    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
