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

use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_event_log\ApicType\ApicEvent;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to edit the consumerorg owner.
 */
class ChangeOrgOwnerForm extends FormBase {

  protected $currentOrg;

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
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * ChangeOrgOwnerForm constructor.
   *
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumer_org_service
   * @param \Drupal\ibm_apim\Service\UserUtils $user_utils
   * @param \Drupal\ibm_apim\Service\ApimUtils $apim_utils
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(ConsumerOrgService $consumer_org_service, UserUtils $user_utils, ApimUtils $apim_utils, ThemeHandler $themeHandler, AccountProxyInterface $current_user, Messenger $messenger) {
    $this->consumerOrgService = $consumer_org_service;
    $this->userUtils = $user_utils;
    $this->apimUtils = $apim_utils;
    $this->themeHandler = $themeHandler;
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ChangeOrgOwnerForm {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.apim_utils'),
      $container->get('theme_handler'),
      $container->get('current_user'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'consumerorg_change_owner_form';
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $org = $this->userUtils->getCurrentConsumerorg();
    $this->currentOrg = $this->consumerOrgService->get($org['url']);

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
      $members = $this->consumerOrgService->getMembers($org['url']);
      if ($members) {
        $values = [];
        // If there is only one member, do not allow change
        if (count($members) === 1) {
          $this->messenger->addError(t('Cannot change ownership: only one user in developer organization %org', ['%org' => $this->currentOrg->getTitle()]));
        }
        else {

          foreach ($members as $member) {
            // Don't include the current owner in the list
            if ($member->getUser()->getUrl() !== $this->currentOrg->getOwnerUrl()) {
              $values[$member->getUrl()] = $member->getUser()->getDisplayName();
            }
          }
          $form['new_owner'] = [
            '#title' => t('New Owner'),
            '#type' => 'select',
            '#description' => t('Select the user to take ownership.'),
            '#options' => $values,
          ];

          // These are the roles to assign to the old owner now that he isn't the owner any more
          $roles = $this->currentOrg->getRoles();
          if ($roles !== NULL && count($roles) > 1) {
            $roles_array = [];
            $default_role = NULL;
            foreach ($roles as $role) {
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
              if ($role->getName() === 'developer') {
                $default_role = $role->getUrl();
              }
            }

            $form['role'] = [
              '#type' => 'radios',
              '#title' => t('Assign Previous Owner\'s Role'),
              '#default_value' => $default_role,
              '#options' => $roles_array,
              '#description' => t('Select the new role for the previous owner.'),
            ];
          }

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
      }
      else {
        $this->messenger->addError(t('Failed to retrieve member list for developer organization %org', ['%org' => $this->currentOrg->getTitle()]));

        $form = [];
        $form['description'] = ['#markup' => '<p>' . t('Could not get member list for this organization so can not transfer ownership.') . '</p>'];

        $form['cancel'] = [
          '#type' => 'link',
          '#title' => t('Cancel'),
          '#url' => Url::fromRoute('ibm_apim.myorg'),
          '#attributes' => ['class' => ['button']],
        ];
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * @return \Drupal\Core\Url
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('ibm_apim.myorg');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $new_owner = $form_state->getValue('new_owner');
    $role = $form_state->getValue('role');

    if (empty($new_owner)) {
      $this->messenger->addError(t('A new owner is required.'));
    }
    else {
      // update APIm
      $newUserUrl = $this->apimUtils->createFullyQualifiedUrl($new_owner);
      $roleUrl = $this->apimUtils->createFullyQualifiedUrl($role);
      $response = $this->consumerOrgService->changeOrgOwner($this->currentOrg, $newUserUrl, $roleUrl);
      if ($response->success()) {
        $this->messenger->addMessage(t('Organization owner updated.'));
        \Drupal::logger('consumerorg')->notice('Consumer organization owner for @orgname changed by @username', [
          '@orgname' => $this->currentOrg->getTitle(),
          '@username' => $this->currentUser->getAccountName(),
        ]);
        // Add Activity Feed Event Log
        $eventEntity = new ApicEvent();
        $eventEntity->setArtifactType('consumer_org');
        if (\Drupal::currentUser()->isAuthenticated() && (int) \Drupal::currentUser()->id() !== 1) {
          $current_user = User::load(\Drupal::currentUser()->id());
          if ($current_user !== NULL) {
            // we only set the user if we're running as someone other than admin
            // if running as admin then we're likely doing things on behalf of the admin
            // TODO we might want to check if there is a passed in user_url and use that too
            $eventEntity->setUserUrl($current_user->get('apic_url')->value);
          }
        }
        $members = $this->consumerOrgService->getMembers($this->currentOrg->apic_url->value);
        $owner = $members[$new_owner];
        if ($owner === NULL) {
          $owner = $new_owner;
        }
        $eventEntity->setTimestamp(time());
        $eventEntity->setEvent('change_owner');
        $eventEntity->setArtifactUrl($this->currentOrg->apic_url->value);
        $eventEntity->setConsumerOrgUrl($this->currentOrg->apic_url->value);
        $eventEntity->setData(['owner' => $owner, 'orgName' => $this->currentOrg->getTitle()]);
        $eventLogService = \Drupal::service('ibm_apim.event_log');
        $eventLogService->createIfNotExist($eventEntity);
      }
      else {
        $this->messenger->addError(t('Error updating the organization owner. Contact the system administrator.'));
      }
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
