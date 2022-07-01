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

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Entity\ApplicationCredentials;
use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to verify an application client secret.
 */
class VerifyClientSecretForm extends FormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * This represents the credential object
   *
   * @var \Drupal\apic_app\Entity\ApplicationCredentials
   */
  protected ApplicationCredentials $cred;

  /**
   * @var \Drupal\apic_app\Service\ApplicationRestInterface
   */
  protected ApplicationRestInterface $restService;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * VerifyClientSecretForm constructor.
   *
   * @param \Drupal\apic_app\Service\ApplicationRestInterface $restService
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(ApplicationRestInterface $restService, UserUtils $userUtils, Messenger $messenger) {
    $this->restService = $restService;
    $this->userUtils = $userUtils;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): VerifyClientSecretForm {
    // Load the service required to construct this class
    return new static($container->get('apic_app.rest_service'), $container->get('ibm_apim.user_utils'), $container->get('messenger'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'application_verify_clientsecret_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL, $credId = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($appId !== NULL) {
      $this->node = $appId;
    }
    if ($credId !== NULL) {
      $this->cred = $credId;
    }

    $form['intro'] = ['#markup' => '<p>' . t('Use this form to verify you have the correct client secret for this application.') . '</p>'];

    $form['secret'] = [
      '#type' => 'password',
      '#title' => t('Secret'),
      '#size' => 50,
      '#maxlength' => 50,
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Verify'),
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
   * @return \Drupal\Core\Url
   */
  public function getCancelUrl(): Url {
    $url = Url::fromRoute('entity.node.canonical', ['node' => $this->node->id()]);
    $analytics_service = \Drupal::service('ibm_apim.analytics')->getDefaultService();
    if (isset($analytics_service) && $analytics_service->getClientEndpoint() !== NULL) {
      $url = Url::fromRoute('apic_app.subscriptions', ['node' => $this->node->id()]);
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   * @throws \JsonException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $secret = $form_state->getValue('secret');

    $url = $this->node->apic_url->value . '/credentials/' . $this->cred->uuid() . '/verify-client-secret';

    $data = ['client_secret' => $secret, 'client_id' => $this->cred->client_id()];
    $result = $this->restService->postClientSecret($url, json_encode($data, JSON_THROW_ON_ERROR));
    if (isset($result) && $result->code >= 200 && $result->code < 300) {
      $this->messenger->addMessage(t('Application secret verified successfully.'));
      $current_user = \Drupal::currentUser();
      \Drupal::logger('apic_app')->notice('Application @appname client secret verified by @username', [
        '@appname' => $this->node->getTitle(),
        '@username' => $current_user->getAccountName(),
      ]);
    }
    else {
      \Drupal::logger('apic_app')->notice('Received @code when trying to verify credentials.', [
        '@code' => $result->code,
      ]);
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
