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
use Drupal\Component\Utility\Html;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to invite other users to a consumer org.
 */
class InviteUserForm extends FormBase {

  protected $consumerOrgService;
  protected $currentOrg;
  protected $currentUser;
  protected $logger;
  protected $userUtils;
  protected $state;

  /**
   * Constructs an Org User Invitation Form.
   *
   * {@inheritdoc}
   *
   * @param ConsumerOrgService $consumer_org_service
   * @param AccountInterface $account
   *   Current user.
   * @param LoggerInterface $logger
   *   Logger.
   */
  public function __construct(
    ConsumerOrgService $consumer_org_service,
    AccountInterface $account,
    LoggerInterface $logger,
    UserUtils $user_utils,
    StateInterface $state
  ) {
    $this->consumerOrgService = $consumer_org_service;
    $this->currentUser = $account;
    $this->logger = $logger;
    $this->userUtils = $user_utils;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('current_user'),
      $container->get('logger.channel.auth_apic'),
      $container->get('ibm_apim.user_utils'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'consumerorg_invite_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (!$this->userUtils->checkHasPermission('member:manage')) {
      $message = t('Permission denied.');
      drupal_set_message($message, 'error');

      $form = array();
      $form['description'] = array('#markup' => t('You do not have sufficient access to perform this action.'));

      $form['actions'] = array('#type' => 'actions');
      $form['actions']['cancel'] = array(
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#href' => 'myorg',
      );

      return $form;
    } else {
      $org = $this->userUtils->getCurrentConsumerOrg();
      // TODO move getCurrentConsumerOrg to correct service.
      $this->currentOrg = $this->consumerOrgService->get($org['url']);

      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $this->currentOrg->getUrl());
      $nids = $query->execute();
      $this->orgNode = NULL;
      if (isset($nids) && !empty($nids)) {
        $productnid = array_shift($nids);
        $this->orgNode = Node::load($productnid);
      }

      $form['new_email'] = array(
        '#type' => 'email',
        '#title' => t('Email'),
        '#size' => 25,
        '#maxlength' => 100,
        '#required' => TRUE
      );

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
          '#title' => t('Assign Roles'),
          '#default_value' => $default_role,
          '#options' => $roles_array,
          '#description' => t('Select which role the new user will have in your organization.')
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
    $email = Html::escape($form_state->getValue('new_email'));
    $role = Html::escape($form_state->getValue('role'));

    if (!empty($email)) {
      $response = $this->consumerOrgService->inviteMember($this->currentOrg, $email, $role);
      if ($response->success()) {
        drupal_set_message(t('Invitation sent successfully.'));
      }
      else {
        drupal_set_message(t('Error sending invitation. Contact the system administrator.'), 'error');
      }
    }
    else {
      drupal_set_message(t('No user specified.'), 'error');
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
