<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\consumerorg\Form;

use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ThemeHandler;

/**
 * Form to create a new consumerorg.
 */
class OrgCreateForm extends FormBase {

  protected $consumerOrgService;

  protected $currentUser;

  protected $logger;

  protected $themeHandler;


  /**
   * Constructs an Org Creation Form.
   *
   * {@inheritdoc}
   *
   * @param AccountInterface $account
   *   Current user.
   * @param LoggerInterface $logger
   *   Logger.
   */
  public function __construct(
    ConsumerOrgService $consumer_org_service,
    AccountInterface $account,
    LoggerInterface $logger,
    ThemeHandler $themeHandler
  ) {
    $this->consumerOrgService = $consumer_org_service;
    $this->currentUser = $account;
    $this->logger = $logger;
    $this->themeHandler = $themeHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('current_user'),
      $container->get('logger.channel.auth_apic'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'consumerorg_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $form['intro'] = [
      '#markup' => '<p>' . t('A consumer organization can own one or more applications and have multiple members. It is possible to own multiple consumer organizations, use this form to create a new one.') . '</p>',
    ];
    $form['orgname'] = [
      '#type' => 'textfield',
      '#title' => t('Organization name'),
      '#size' => 25,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => $this->getCancelUrl(),
      '#attributes' => ['class' => ['button', 'apicSecondary']],
    ];
    if ($this->themeHandler->themeExists('bootstrap')) {
      $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('ok');
      $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $orgname = $form_state->getValue('orgname');

    $response = $this->consumerOrgService->create($orgname);

    if ($response->getMessage() !== NULL) {
      $error_arg = $response->success() ? 'status' : 'error';
      drupal_set_message($response->getMessage(), $error_arg);
    }

    if ($response->getRedirect() !== NULL) {
      $form_state->setRedirectUrl(Url::fromRoute($response->getRedirect()));
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
