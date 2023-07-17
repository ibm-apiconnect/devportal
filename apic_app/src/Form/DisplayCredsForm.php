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

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use JsonException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\apic_api\Service\ApiUtils;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Form to display the credentials of an application.
 */
class DisplayCredsForm extends FormBase {

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected ModuleHandler $module_handler;

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  protected ApiUtils $api_utils;

  protected $creds;

  protected $app_store;

  /**
   * DisplayCredsForm constructor.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   * @param \Drupal\Core\Extension\ModuleHandler $api_utils
   */
  public function __construct(Messenger $messenger, ModuleHandler $module_handler, ApiUtils $api_utils, PrivateTempStoreFactory $temp_store_factory) {
    $this->messenger = $messenger;
    $this->module_handler = $module_handler;
    $this->api_utils = $api_utils;
    $this->app_store = $temp_store_factory->get('apic_app');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): DisplayCredsForm {
    // Load the service required to construct this class
    return new static(
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('apic_api.utils'),
      $container->get('tempstore.private'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'display_app_credentials_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL): array {
    if ($appId !== NULL) {
      $this->node = $appId;
    }
    $credentials = $this->app_store->get('credentials');
    if (!isset($credentials)) {
      $form = [];
      $form['description'] = ['#markup' => '<p>' . t('This credential cannot be viewed again. You can reset it on the application page.') . '</p>'];

      $form['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => $this->getCancelUrl(),
        '#attributes' => ['class' => ['button']],
      ];
      return $form;
    }
    $this->app_store->delete('credentials');
    try {
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('encrypt') && isset($credentials) ) {
        $ibmApimConfig = \Drupal::config('ibm_apim.settings');
        $encryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
        if (isset($encryptionProfileName)) {
          $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($encryptionProfileName);
          $encryptionService = \Drupal::service('encryption');
          if ($encryptionProfile !== NULL) {
            $this->creds = json_decode($encryptionService->decrypt($credentials, $encryptionProfile),TRUE, 512, JSON_THROW_ON_ERROR);
          }
        } else {
          \Drupal::logger('apic_app')->warning('display_app_credentials_form: No encryption profile set', []);
        }
      } else {
        $this->creds = json_decode(base64_decode($credentials, FALSE), TRUE, 512, JSON_THROW_ON_ERROR);
      }
    } catch (JsonException $e) {

    }
    $form['#attached']['library'][] = 'apic_app/basic';

    if ($this->module_handler->moduleExists('clipboardjs')) {
      $form['#attached']['library'][] = 'clipboardjs/drupal';
      if (isset($this->creds['client_id'])) {
        $form['client_id'] = [
          '#markup' => Markup::create('<div class="clientIDContainer"><label for="client_id" class="field__label">' . t('Key') . '</label><div class="bx--form-item appID js-form-item form-item js-form-type-textfield form-group"><input id="clientIDInput" class="clipboardjs password-field passwordCreds" type="password" aria-labelledby="clientIDInputLabel" value="' . $this->creds['client_id'] . '" />
                <div id="hiddenClientIDInput" class="offscreen-field">' . $this->creds['client_id'] . '</div>
                <button class="clipboardjs-button" type="button" data-clipboard-alert="tooltip" data-clipboard-alert-text="' . t('Copied successfully') . '" data-clipboard-target="#hiddenClientIDInput">
                  ' . file_get_contents(\Drupal::service('extension.list.module')->getPath('apic_app') . "/images/clipboard.svg") . '</button></div></div>'),
          '#weight' => 10,
        ];
      }
      if (isset($this->creds['client_secret'])) {
        $form['client_secret'] = [
          '#markup' => Markup::create('<div class="clientSecretContainer"><label for="client_secret" class="field__label">' . t('Secret') . '</label><div class="bx--form-item appSecret js-form-item form-item js-form-type-textfield form-group"><input id="clientSecretInput" class="clipboardjs password-field passwordCreds" type="password" aria-labelledby="clientSecretInputLabel" value="' . $this->creds['client_secret'] . '" />
                <div id="hiddenClientSecretInput" class="offscreen-field">' . $this->creds['client_secret'] . '</div>
                <button class="clipboardjs-button" type="button" data-clipboard-alert="tooltip" data-clipboard-alert-text="' . t('Copied successfully') . '" data-clipboard-target="#hiddenClientSecretInput">
                  ' . file_get_contents(\Drupal::service('extension.list.module')->getPath('apic_app') . "/images/clipboard.svg") . '</button></div></div>'),
          '#weight' => 20,
        ];
      }

    }
    else {
      if (isset($this->creds['client_id'])) {
        $form['client_id'] = [
          '#markup' => Markup::create('<div class="clientIDContainer"><label for="client_id" class="field__label">' . t('Key') . '</label><div class="bx--form-item appID js-form-item form-item js-form-type-textfield form-group"><input class="form-control" id="client_id" readonly value="' . $this->creds['client_id'] . '"></div></div>'),
          '#weight' => 10,
        ];
      }
      if (isset($this->creds['client_secret'])) {
        $form['client_secret'] = [
          '#markup' => Markup::create('<div class="clientSecretContainer"><label for="client_secret" class="field__label">' . t('Secret') . '</label><div class="bx--form-item appSecret js-form-item form-item js-form-type-textfield form-group"><input class="form-control" id="client_secret" readonly value="' . $this->creds['client_secret'] . '"></div></div>'),
          '#weight' => 20,
        ];
      }
    }
    $form['intro'] = [
      '#markup' => '<span>' . t('The API Key and Secret have been generated for your application.') . '</span>',
      '#weight' => 0,
    ];
    $outro = '<p>' . t('The Secret will only be displayed here one time. Please copy your API Secret and keep it for your records.') . '</p>';
    if ($this->api_utils->areEventAPIsPresent()) {
      $outro = $outro . '<p>' . t('Application credentials are used when an API requires authentication. Depending on its use, it can be referred to as API key and secret in a HTTP authentication header, Client ID and secret in an OAuth flow or Kafka SASL username and password.') . '</p>';
    }
    $form['outro'] = [
      '#markup' => $outro,
      '#weight' => 30,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('OK'),
      '#url' => $this->getCancelUrl(),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    return $form;
  }

  /**
   * @return \Drupal\Core\Url
   */
  public function getCancelUrl(): Url {
    $analytics_service = \Drupal::service('ibm_apim.analytics')->getDefaultService();
    $destination = \Drupal::request()->get('redirectto');
    if (isset($destination) && !empty($destination)) {
      if ($destination[0] !== '/' && $destination[0] !== '?' && $destination[0] !== '#') {
        $destination = '/' . $destination;
      }
      $url = Url::fromUserInput($destination);
    } else if (isset($analytics_service) && $analytics_service->getClientEndpoint() !== NULL) {
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
    // no op since this form doesnt do anything
  }

}
