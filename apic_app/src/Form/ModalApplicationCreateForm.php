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

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\apic_app\Service\CertificateService;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to create an application.
 */
class ModalApplicationCreateForm extends FormBase {

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
   * @var \Drupal\apic_app\Service\CertificateService
   */
  protected CertificateService $certService;

  /**
   * ModalApplicationCreateForm constructor.
   *
   * @param \Drupal\apic_app\Service\ApplicationRestInterface $restService
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\apic_app\Service\CertificateService $certService
   */
  public function __construct(
    ApplicationRestInterface $restService, UserUtils $userUtils, Messenger $messenger, CertificateService $certService) {
    $this->restService = $restService;
    $this->userUtils = $userUtils;
    $this->messenger = $messenger;
    $this->certService = $certService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ModalApplicationCreateForm {
    // Load the service required to construct this class
    return new static(
      $container->get('apic_app.rest_service'),
      $container->get('ibm_apim.user_utils'),
      $container->get('messenger'),
      $container->get('apic_app.certificate')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'modal_application_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $form['#parents'] = [];
    $max_weight = 500;

    $moduleHandler = \Drupal::service('module_handler');

    $entity = \Drupal::entityTypeManager()->getStorage('node')->create([
      'type' => 'application',
      'uid' => 1,
    ]);
    $entity_form = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.application.default');

    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'application');

    if ($entity_form !== NULL) {
      foreach ($entity_form->getComponents() as $name => $options) {

        if (($configuration = $entity_form->getComponent($name)) && isset($configuration['type'], $definitions[$name]) && ($definition = $definitions[$name])) {
          $widget = \Drupal::service('plugin.manager.field.widget')->getInstance([
            'field_definition' => $definition,
            'form_mode' => 'default',
            // No need to prepare, defaults have been merged in setComponent().
            'prepare' => FALSE,
            'configuration' => $configuration,
          ]);
        } else {
          unset($widget);
        }

        if (isset($widget)) {
          $items = $entity->get($name);
          $items->filterEmptyItems();
          $form[$name] = $widget->form($items, $form, $form_state);
          $form[$name]['#access'] = $items->access('edit');

          // Assign the correct weight.
          $form[$name]['#weight'] = $options['weight'];
          if ($options['weight'] > $max_weight) {
            $max_weight = $options['weight'];
          }
        }
      }
    }

    if (isset($form['application_image'])) {
      unset($form['application_image']);
    }

    $ibm_apim_application_certificates = \Drupal::state()->get('ibm_apim.application_certificates');
    if ($ibm_apim_application_certificates) {

      $form['certificate'] = [
        '#type' => 'textarea',
        '#title' => t('Certificate'),
        '#description' => t('Paste the content of your application\'s x509 certificate.'),
        '#required' => FALSE,
        '#wysiwyg' => FALSE,
      ];
    }

    $form['#prefix'] = '<div id="modal_application_create_form">';
    $form['#suffix'] = '</div>';
    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -20,
    ];

    $form['title']['#required'] = TRUE;

    $form['actions']['#type'] = 'actions';
    $form['actions']['#weight'] = $max_weight + 1;
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => '::submitModalFormAjax',
        'event' => 'click',
        'method' => 'append',
      ],
    ];

    $form['#attached']['library'][] = 'ibm_apim/modal';
    $form['#attached']['library'][] = 'apic_app/basic';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    if ($moduleHandler->moduleExists('clipboardjs')) {
      $form['#attached']['library'][] = 'clipboardjs/drupal';
    }
    if ($moduleHandler->moduleExists('view_password')) {
      // Adding js for the view_password lib since it only attaches to forms by default.
      $form['#attached']['library'][] = 'view_password/pwd_lb';
      $form['#attributes']['class'][] = 'pwd-see';
      $form['#cache'] = [
        'tags' => [
            'config:view_password.settings',
        ],
      ];
      $span_classes = \Drupal::config('view_password.settings')->get('span_classes');
      $form['#attached']['drupalSettings']['view_password'] = [
        'showPasswordLabel' => t("Show password"),
        'hidePasswordLabel' => t("Hide password"),
        'span_classes' => $span_classes
      ];
    }

    // remove any admin fields if they exist
    if (isset($form['revision_log'])) {
      unset($form['revision_log']);
    }
    if (isset($form['status'])) {
      unset($form['status']);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $name = $form_state->getValue('title');
    if (is_array($name) && isset($name[0]['value'])) {
      $name = $name[0]['value'];
    }
    $name = trim($name);
    if (!isset($name) || empty($name)) {
      $form_state->setErrorByName('Name', $this->t('Application name is a required field.'));
    }
  }

  /**
   * @return \Drupal\Core\Url
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('view.applications.page_1');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function submitModalFormAjax(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      // Remember the previous id ? Here it is
      $response->addCommand(new ReplaceCommand('#modal_application_create_form', $form));
    }
    else {
      $certificate = NULL;

      // Get form inputs
      $name = $form_state->getValue('title');
      if (is_array($name) && isset($name[0]['value'])) {
        $name = $name[0]['value'];
      }
      $name = trim($name);
      $summary = $form_state->getValue('apic_summary');
      if (is_array($summary) && isset($summary[0]['value'])) {
        $summary = $summary[0]['value'];
      }
      $oauth_endpoints = [];
      $oauth = $form_state->getValue('application_redirect_endpoints');
      foreach ($oauth as $oauth_value) {
        if (is_array($oauth_value) && !empty($oauth_value['value'])) {
          $oauth_endpoints[] = trim($oauth_value['value']);
        }
      }
      $ibm_apim_application_certificates = \Drupal::state()->get('ibm_apim.application_certificates');
      if ($ibm_apim_application_certificates) {
        $certificate = $this->certService->cleanup($form_state->getValue('certificate'));
      }

      // Create the application
      $restService = \Drupal::service('apic_app.rest_service');
      $result = $restService->createApplication($name, $summary, $oauth_endpoints, $certificate, $form_state);

      // Response is a set of Ajax commands to update the DOM or reload the page etc

      if (isset($result->data['errors'])) {
        $response->addCommand(new RedirectCommand(Url::fromRoute('ibm_apim.subscription_wizard.step', ['step' => 'chooseapp'])
          ->toString()));
      }
      else {

        // Swallow the create app success drupal status message
        $this->messenger->deleteAll();

        $data = $result->data;

        $clientId = $data['client_id'];
        $clientSecret = $data['client_secret'];
        $nid = $data['nid'];

        // Add the new app to the app list
        $node = Node::load($nid);

        if ($node !== NULL) {
          $renderArray = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, 'subscribewizard');
          $renderer = \Drupal::service('renderer');
          $html = $renderer->render($renderArray);
          $response->addCommand(new InsertCommand('div.apicNewAppsList', $html, []));
        }

        // Pop up a new modal dialog to display the client id and secret
        $credsForm = [];
        $moduleHandler = \Drupal::service('module_handler');

        $modalHeaderHTML = '<div class="modal-header ui-dialog-titlebar ui-draggable-handle" id="drupal-modal--header"><button class="close ui-dialog-titlebar-close" aria-label="Close" data-dismiss="modal" type="button"><span aria-hidden="true">×</span></button><h4 class="modal-title ui-dialog-title">' . t('Credentials for your new application') . '</h4></div>';
        $credsForm['intro'] = [
          '#markup' => '<div class="modalAppResultContainer modal-dialog"><div class="modal-content">' . $modalHeaderHTML . '<div class="modal-body pwd-see"><p>' . t('The API Key and Secret have been generated for your application.') . '</p>',
          '#weight' => 0,
          '#allowed_tags' => ['button', 'div', 'span', 'p', 'h4'],
        ];
        if ($moduleHandler->moduleExists('clipboardjs')) {
          $credsForm['client_id'] = [
            '#markup' => Markup::create('<div class="clientIDContainer"><label for="client_id" class="field__label">' . t('Key') . '</label><div class="bx--form-item appID js-form-item form-item js-form-type-textfield form-group"><input id="clientIDInput" class="clipboardjs password-field passwordCreds" type="password" aria-labelledby="clientIDInputLabel" readonly aria-readonly value="' . $clientId . '" />
                <div id="hiddenClientIDInput" class="offscreen-field">' . $clientId . '</div>
                  <span class="clipboardjs clipboardjs-btn" data-toggle="tooltip" data-placement="auto" title="' . t('Copy Key') . '">
                    <button class="clipboardjs-button" data-clipboard-alert="tooltip" data-clipboard-alert-text="' . t('Copied successfully') . '" data-clipboard-target="#hiddenClientIDInput">
                    <div class="clipboardjs-tooltip">
                        ' . file_get_contents(\Drupal::service('extension.list.module')->getPath('apic_app') . "/images/clipboard.svg") . '
                        <span class="tooltiptext clipboardjs-tooltip"></span>
                      </div>
                    </button>
                  </span>
                  </div>'),
            '#weight' => 10,
          ];

          $credsForm['client_secret'] = [
            '#markup' => Markup::create('<div class="clientSecretContainer"><label for="client_secret" class="field__label">' . t('Secret') . '</label><div class="bx--form-item appSecret js-form-item form-item js-form-type-textfield form-group"><input id="clientSecretInput" class="clipboardjs password-field passwordCreds" type="password" aria-labelledby="clientSecretInputLabel" readonly aria-readonly value="' . $clientSecret . '" />
                <div id="hiddenClientSecretInput" class="offscreen-field">' . $clientSecret . '</div>
                  <span class="clipboardjs clipboardjs-btn" data-toggle="tooltip" data-placement="auto" title="' . t('Copy Secret') . '">
                    <button class="clipboardjs-button" data-clipboard-alert="tooltip" data-clipboard-alert-text="' . t('Copied successfully') . '" data-clipboard-target="#hiddenClientSecretInput">
                    <div class="clipboardjs-tooltip">
                        ' . file_get_contents(\Drupal::service('extension.list.module')->getPath('apic_app') . "/images/clipboard.svg") . '
                        <span class="tooltiptext clipboardjs-tooltip""></span>
                      </div>
                    </button>
                  </span>
                  </div>'),
            '#weight' => 20,
          ];
        }
        else {
          $credsForm['client_id'] = [
            '#markup' => Markup::create('<div class="clientIDContainer"><label for="client_id" class="field__label">' . t('Key') . '</label><div class="bx--form-item appID js-form-item form-item js-form-type-textfield form-group"><input class="form-control password-field passwordCreds" type="password" id="client_id" value="' . $clientId . '"></div></div>'),
            '#weight' => 10,
          ];

          $credsForm['client_secret'] = [
            '#markup' => Markup::create('<div class="clientSecretContainer"><label for="client_secret" class="field__label">' . t('Secret') . '</label><div class="bx--form-item appSecret js-form-item form-item js-form-type-textfield form-group"><input class="form-control password-field passwordCreds" type="password" id="client_secret" value="' . $clientSecret . '"></div></div>'),
            '#weight' => 20,
          ];
        }

        $credsForm['outro'] = [
          '#markup' => '<p>' . t('The Secret will only be displayed here one time. Please copy your API Secret and keep it for your records.') . '</p></div></div></div>',
          '#weight' => 30,
        ];
        if ($moduleHandler->moduleExists('clipboardjs')) {
          $credsForm['#attached']['library'][] = 'clipboardjs/drupal';
        }
        if ($moduleHandler->moduleExists('view_password')) {
          // Adding js for the view_password lib since it only attaches to forms by default.
          $credsForm['#attached']['library'][] = 'view_password/pwd_lb';
          $credsForm['#attributes']['class'][] = 'pwd-see';
          $credsForm['#cache'] = [
            'tags' => [
                'config:view_password.settings',
            ],
          ];
          $span_classes = \Drupal::config('view_password.settings')->get('span_classes');
          $credsForm['#attached']['drupalSettings']['view_password'] = [
            'showPasswordLabel' => t("Show password"),
            'hidePasswordLabel' => t("Hide password"),
            'span_classes' => $span_classes
          ];
        }

        $response->addCommand(new OpenModalDialogCommand(t('Credentials for your new application'), $credsForm));
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    return $response;
  }

}
