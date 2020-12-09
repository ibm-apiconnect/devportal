<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\apic_app\Service\CredentialsService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to update an application credential's description.
 */
class CredentialsUpdateForm extends FormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * This represents the credential object
   *
   * @var \Drupal\apic_app\Entity\ApplicationCredentials
   */
  protected $cred;

  /**
   * @var \Drupal\apic_app\Service\ApplicationRestInterface
   */
  protected $restService;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected $userUtils;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\apic_app\Service\CredentialsService
   */
  protected $credsService;

  /**
   * CredentialsUpdateForm constructor.
   *
   * @param \Drupal\apic_app\Service\ApplicationRestInterface $restService
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\apic_app\Service\CredentialsService $credsService
   */
  public function __construct(ApplicationRestInterface $restService, UserUtils $userUtils, Messenger $messenger, CredentialsService $credsService) {
    $this->restService = $restService;
    $this->userUtils = $userUtils;
    $this->messenger = $messenger;
    $this->credsService = $credsService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Load the service required to construct this class
    return new static($container->get('apic_app.rest_service'),
      $container->get('ibm_apim.user_utils'),
      $container->get('messenger'),
      $container->get('apic_app.credentials'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'application_update_credentials_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL, $credId = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $this->node = $appId;
    $this->cred = $credId;

    $form['intro'] = ['#markup' => '<p>' . t('Use this form to update an existing set of credentials for this application.') . '</p>'];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#required' => FALSE,
      '#default_value' => $this->cred->title(),
    ];

    $form['summary'] = [
      '#type' => 'textfield',
      '#title' => t('Summary'),
      '#required' => FALSE,
      '#default_value' => $this->cred->summary(),
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
    $form['#attached']['library'][] = 'apic_app/basic';
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    $analytics_service = \Drupal::service('ibm_apim.analytics')->getDefaultService();
    if (isset($analytics_service) && $analytics_service->getClientEndpoint() !== NULL) {
      $url = Url::fromRoute('apic_app.subscriptions', ['node' => $this->node->id()]);
    }
    else {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $this->node->id()]);
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $appUrl = $this->node->apic_url->value;
    $title = $form_state->getValue('title');
    $summary = $form_state->getValue('summary');
    $url = $appUrl . '/credentials/' . $this->cred->uuid();
    $data = ['title' => $title, 'summary' => $summary];
    $result = $this->restService->patchCredentials($url, json_encode($data));
    if (isset($result) && $result->code >= 200 && $result->code < 300) {
      $this->messenger->addMessage(t('Application credentials updated.'));
      // update the stored app with the new creds
      $existingCred = $this->cred->toArray();
      $existingCred['summary'] = $summary;
      $existingCred['title'] = $title;
      $this->node = $this->credsService->updateCredentials($this->node, $existingCred);

      $currentUser = \Drupal::currentUser();
      \Drupal::logger('apic_app')->notice('Application @appName credentials updated by @username', [
        '@appName' => $this->node->getTitle(),
        '@username' => $currentUser->getAccountName(),
      ]);

      // Calling all modules implementing 'hook_apic_app_creds_update':
      $moduleHandler = \Drupal::moduleHandler();
      $moduleHandler->invokeAll('apic_app_creds_update', [
        'node' => $this->node,
        'data' => $result->data,
        'credId' => $this->cred->uuid(),
      ]);

    }
    else {
      $this->messenger->addError($this->t('Failed to update credentials.'));
      \Drupal::logger('apic_app')->notice('Received @code when trying to update credential.', [
        '@code' => $result->code,
      ]);
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
