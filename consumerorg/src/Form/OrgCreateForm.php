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
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to create a new consumerorg.
 */
class OrgCreateForm extends FormBase {

  protected $consumerOrgService;
  protected $currentUser;
  protected $logger;


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
                              LoggerInterface $logger
                             ) {
    $this->consumerOrgService = $consumer_org_service;
    $this->currentUser = $account;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('current_user'),
      $container->get('logger.channel.auth_apic')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'consumerorg_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $form['intro'] = array(
      '#markup' => '<p>' . t('A consumer organization can own one or more applications and have multiple members. It is possible to own multiple consumer organizations, use this form to create a new one.') . '</p>'
    );
    $form['orgname'] = array(
      '#type' => 'textfield',
      '#title' => t('Organization name'),
      '#size' => 25,
      '#maxlength' => 128,
      '#required' => TRUE
    );

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
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
