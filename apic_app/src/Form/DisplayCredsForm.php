<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
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
use Symfony\Component\DependencyInjection\ContainerInterface;

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
  protected $module_handler;

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  protected $creds;

  /**
   * DisplayCredsForm constructor.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   */
  public function __construct(Messenger $messenger, ModuleHandler $module_handler) {
    $this->messenger = $messenger;
    $this->module_handler = $module_handler;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Load the service required to construct this class
    return new static(
      $container->get('messenger'),
      $container->get('module_handler')
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
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL, $credentials = NULL): array {
    $this->node = $appId;
    try {
      $this->creds = json_decode(base64_decode($credentials, FALSE), TRUE, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $e) {

    }
    $form['#attached']['library'][] = 'apic_app/basic';

    if ($this->module_handler->moduleExists('clipboardjs')) {
      $form['#attached']['library'][] = 'clipboardjs/drupal';
      $form['intro'] = [
        '#markup' => '<span>' . t('The API Key and Secret have been generated for your application.') . '</span>',
        '#weight' => 0,
      ];
      if (isset($this->creds['client_id'])) {
        $form['client_id'] = [
          '#markup' => Markup::create('<div class="clientIDContainer"><label for="client_id" class="field__label">' . t('Key') . '</label><div class="bx--form-item appID js-form-item form-item js-form-type-textfield form-group"><input id="clientIDInput" class="clipboardjs password-field passwordCreds" type="password" aria-labelledby="clientIDInputLabel" value="' . $this->creds['client_id'] . '" />
                <div id="hiddenClientIDInput" class="offscreen-field">' . $this->creds['client_id'] . '</div>
                <button class="clipboardjs-button" data-clipboard-alert="tooltip" data-clipboard-alert-text="' . t('Copied successfully') . '" data-clipboard-target="#hiddenClientIDInput">
                  ' . file_get_contents(drupal_get_path('module', 'apic_app') . "/images/clipboard.svg") . '</button></div></div>'),
          '#weight' => 10,
        ];
      }
      if (isset($this->creds['client_secret'])) {
        $form['client_secret'] = [
          '#markup' => Markup::create('<div class="clientSecretContainer"><label for="client_secret" class="field__label">' . t('Secret') . '</label><div class="bx--form-item appSecret js-form-item form-item js-form-type-textfield form-group"><input id="clientSecretInput" class="clipboardjs password-field passwordCreds" type="password" aria-labelledby="clientSecretInputLabel" value="' . $this->creds['client_secret'] . '" />
                <div id="hiddenClientSecretInput" class="offscreen-field">' . $this->creds['client_secret'] . '</div>
                <button class="clipboardjs-button" data-clipboard-alert="tooltip" data-clipboard-alert-text="' . t('Copied successfully') . '" data-clipboard-target="#hiddenClientSecretInput">
                  ' . file_get_contents(drupal_get_path('module', 'apic_app') . "/images/clipboard.svg") . '</button></div></div>'),
          '#weight' => 20,
        ];
      }

    } else {
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
    $form['outro'] = [
      '#markup' => '<span>' . t('The Secret will only be displayed here one time. Please copy your API Secret and keep it for your records.') . '</span>',
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
    // no op since this form doesnt do anything
  }

}