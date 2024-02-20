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

namespace Drupal\consumerorg\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Html;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to invite other users to a consumer org.
 */
class InviteUserForm extends FormBase {

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected ConsumerOrgService $consumerOrgService;

  /**
   * @var \Drupal\consumerorg\ApicType\ConsumerOrg|NULL
   */
  protected ?ConsumerOrg $currentOrg;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected SiteConfig $siteConfig;

  /**
   * InviteUserForm constructor.
   *
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumer_org_service
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\Service\UserUtils $user_utils
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\ibm_apim\Service\SiteConfig $siteConfig
   */
  public function __construct(
    ConsumerOrgService $consumer_org_service,
    AccountInterface $account,
    LoggerInterface $logger,
    UserUtils $user_utils,
    StateInterface $state,
    Messenger $messenger,
    SiteConfig $siteConfig
  ) {
    $this->consumerOrgService = $consumer_org_service;
    $this->currentUser = $account;
    $this->logger = $logger;
    $this->userUtils = $user_utils;
    $this->state = $state;
    $this->messenger = $messenger;
    $this->siteConfig = $siteConfig;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): InviteUserForm {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('current_user'),
      $container->get('logger.channel.consumerorg'),
      $container->get('ibm_apim.user_utils'),
      $container->get('state'),
      $container->get('messenger'),
      $container->get('ibm_apim.site_config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'consumerorg_invite_user_form';
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \JsonException
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $roles = NULL;
    $permittedRoles = [];
    $org = $this->userUtils->getCurrentConsumerorg();
    $this->currentOrg = $this->consumerOrgService->get($org['url']);
    if ($this->currentOrg !== NULL) {
      $roles = $this->currentOrg->getRoles();
      // this check is to handle inviting someone to an org having only just created it
      // if just been created and not had a webhook yet then we will only have the owner role and so cant invite anyone
      // refresh the data from apim to solve the problem
      if ($roles === NULL || count($roles) < 2) {
        try {
          $updatedOrg = $this->consumerOrgService->getFromApim($this->currentOrg);
          if ($updatedOrg !== NULL) {
            $this->currentOrg = $updatedOrg;
          }
        } catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException | \JsonException $e) {
          $this->messenger->addError(t('An error occurred retrieving organization information from API Manager.'));
        }
      }
    }

    $configRoles = $this->siteConfig->getConsumerOrgInvitationRoles();

    foreach ($roles as $role) {
      $roleName = $role->getName();
      if ($roleName !== 'owner' && $roleName !== 'member' && in_array($roleName, $configRoles, TRUE)) {
        $permittedRoles[] = $role;
      }
    }

    if (!$this->userUtils->checkHasPermission('member:manage') || !$this->siteConfig->isConsumerOrgInvitationEnabled()) {
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
    elseif ($roles === NULL || count($roles) < 2) {
      // this check uses $roles not $permittedRoles since its checking for invalid config - e.g. owner only role
      $this->messenger->addError(t('Permission denied.'));

      $form = [];
      $form['description'] = ['#markup' => '<p>' . t('Only one role found for this consumer organization. Inviting other members is not possible. Please contact your administrator if you think this is an error.') . '</p>'];

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => $this->getCancelUrl(),
        '#attributes' => ['class' => ['button']],
      ];

    }
    elseif ($permittedRoles === NULL || count($permittedRoles) < 1) {
      // this check makes sure there is at least one permitted role
      $this->messenger->addError(t('Permission denied.'));

      $form = [];
      $form['description'] = ['#markup' => '<p>' . t('No permitted roles for invitation. Inviting other members is not possible. Please contact your administrator if you think this is an error.') . '</p>'];

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => $this->getCancelUrl(),
        '#attributes' => ['class' => ['button']],
      ];
    }
    else {
      $form['new_email'] = [
        '#type' => 'email',
        '#title' => t('Email'),
        '#size' => 25,
        '#maxlength' => 100,
        '#required' => TRUE,
      ];

      if (count($permittedRoles) > 1) {
        $roles_array = [];
        $default_role = NULL;
        foreach ($permittedRoles as $role) {
          $roleName = $role->getName();
          if ($roleName !== 'owner' && $roleName !== 'member') {
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
          '#title' => t('Assign Roles'),
          '#default_value' => $default_role,
          '#options' => $roles_array,
          '#description' => t('Select which role the new user will have in your organization.'),
        ];
      }
      else {
        // only one permitted role means we can just default to using it
        $role = reset($permittedRoles);
        $form['role'] = [
          '#type' => 'hidden',
          '#value' => $role->getUrl(),
        ];
      }

      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('honeypot')) {
        // add honeypot to this form to prevent it being used to spam user's mailboxes
        \Drupal::service('honeypot')->addFormProtection($form, $form_state, ['honeypot', 'time_restriction']);
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
   * If check_dns is enabled then validate the email address now
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Core\TempStore\TempStoreException|\JsonException
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    parent::validateForm($form, $form_state);
    $mail = $form_state->getValue('new_email');
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('check_dns') && $mail !== NULL && 2 < \strlen($mail)) {
      // Get the email.
      $mail2 = Html::escape($mail);
      $mail2 = explode('@', $mail2);
      // Fetch DNS Resource Records associated with a hostname.
      $result = checkdnsrr(end($mail2));

      if (empty($result) || $result !== TRUE) {
        // If no record is found.
        $form_state->setErrorByName('new_email', t('Your email domain is not recognised. Please enter a valid email id.'));
      }
    }
    // check not inviting the org owner
    $org = $this->userUtils->getCurrentConsumerorg();
    $members = $this->consumerOrgService->getMembers($org['url']);
    $consumerorgOwnerUrl = $this->consumerOrgService->getConsumerOrgAsObject($org['url'])->getOwnerUrl();
    $consumerorgOwnerAccount = \Drupal::service('ibm_apim.user_storage')
      ->loadUserByUrl($consumerorgOwnerUrl);
    if ($consumerorgOwnerAccount !== NULL && $consumerorgOwnerAccount->getEmail() === $mail) {
      $form_state->setErrorByName('new_email', t('That email address is already a member of this consumer organization. Please enter a valid email id.'));
    }

    // check not inviting an existing member
    foreach ($members as $member) {
      // Don't include the current owner in the list
      if ($member->getUser()->getMail() === $mail) {
        $form_state->setErrorByName('new_email', t('That email address is already a member of this consumer organization. Please enter a valid email id.'));
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $email = $form_state->getValue('new_email');
    $role = $form_state->getValue('role');

    if (!empty($email)) {
      $response = $this->consumerOrgService->inviteMember($this->currentOrg, $email, $role);
      if ($response->success()) {
        $this->messenger->addMessage(t('Invitation sent successfully.'));
      }
      else {
        $this->messenger->addError(t('Error sending invitation. Contact the system administrator.'));
      }
    }
    else {
      $this->messenger->addError(t('No user specified.'));
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
