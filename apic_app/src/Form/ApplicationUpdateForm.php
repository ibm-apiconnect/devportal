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

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Application;
use Drupal\apic_app\Event\ApplicationUpdateEvent;
use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to update an application.
 */
class ApplicationUpdateForm extends FormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * @var \Drupal\apic_app\Service\ApplicationRestInterface
   */
  protected $restService;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected $utils;


  /**
   * ApplicationCreateForm constructor.
   *
   * @param ApplicationRestInterface $restService
   * @param UserUtils $userUtils
   * @param Utils $utils
   */
  public function __construct(
    ApplicationRestInterface $restService,
    UserUtils $userUtils,
    Utils $utils) {
    $this->restService = $restService;
    $this->userUtils = $userUtils;
    $this->utils = $utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Load the service required to construct this class
    return new static(
      $container->get('apic_app.rest_service'),
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'application_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $this->node = $appId;

    $form['#parents'] = [];
    $max_weight = 500;

    $entity = \Drupal::entityTypeManager()->getStorage('node')->load($this->node->id());
    $entity_form = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.application.default');

    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'application');
    if ($entity !== NULL && $entity_form !== NULL) {
      foreach ($entity_form->getComponents() as $name => $options) {

        if (($configuration = $entity_form->getComponent($name)) && isset($configuration['type']) && ($definition = $definitions[$name])) {
          $widget = \Drupal::service('plugin.manager.field.widget')->getInstance([
            'field_definition' => $definition,
            'form_mode' => 'default',
            // No need to prepare, defaults have been merged in setComponent().
            'prepare' => FALSE,
            'configuration' => $configuration,
          ]);
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

    $ibmApimApplicationCertificates = (boolean) \Drupal::state()->get('ibm_apim.application_certificates');
    if ($ibmApimApplicationCertificates === TRUE) {
      // we do not store the certificate so have to retrieve it from apim in order to show current value
      $app_data = Application::fetchFromAPIC($this->node->apic_url->value);

      $form['certificate'] = [
        '#type' => 'textarea',
        '#title' => t('Certificate'),
        '#description' => t('Paste the content of your application\'s x509 certificate.'),
        '#required' => FALSE,
        '#wysiwyg' => FALSE,
      ];
      if (isset($app_data['certificate']) && !empty($app_data['certificate'])) {
        $form['certificate']['#default_value'] = $app_data['certificate'];
      }
    }

    $form['title']['#required'] = TRUE;

    $form['actions']['#type'] = 'actions';
    $form['actions']['#weight'] = $max_weight + 1;
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
    $themeHandler = \Drupal::service('theme_handler');
    if ($themeHandler->themeExists('bootstrap')) {
      if (isset($form['actions']['submit'])) {
        $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('ok');
      }
      if (isset($form['actions']['cancel'])) {
        $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }
    }
    $form['#attached']['library'][] = 'apic_app/basic';

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
  public function getCancelUrl(): Url {
    return $this->node->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $name = $form_state->getValue('title');
    if (is_array($name) && isset($name[0]['value'])) {
      $name = $name[0]['value'];
    }
    $name = trim($name);
    if (!isset($name) || empty($name)) {
      $form_state->setErrorByName('title', $this->t('Application name is a required field.'));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $name = $form_state->getValue('title');
    $oauthValues = [];
    if (is_array($name) && isset($name[0]['value'])) {
      $name = $name[0]['value'];
    }
    $name = trim($name);
    $summary = $form_state->getValue('apic_summary');
    if (is_array($summary) && isset($summary[0]['value'])) {
      $summary = $summary[0]['value'];
    }
    $oauthEndpoints = [];
    $oauth = $form_state->getValue('application_redirect_endpoints');
    foreach ($oauth as $oauthValue) {
      if (is_array($oauthValue) && !empty($oauthValue['value'])) {
        $oauthEndpoints[] = trim($oauthValue['value']);
      }
    }

    if (!isset($name) || empty($name)) {
      drupal_set_message(t('ERROR: Title is a required field.'), 'error');
      $form_state->setRedirectUrl(Url::fromRoute('apic_app.create'));
    }
    else {
      $url = $this->node->apic_url->value;
      $data = [
        'title' => $name,
        'summary' => $summary,
      ];
      $data['redirect_endpoints'] = $oauthEndpoints;
      $ibmApimApplicationCertificates = (boolean) \Drupal::state()->get('ibm_apim.application_certificates');
      if ($ibmApimApplicationCertificates === TRUE) {
        $certificate = trim($form_state->getValue('certificate'));
        if (isset($certificate)) {
          $data['application_public_certificate_entry'] = $certificate;
        }
      }
      $imageURL = Application::getImageForApp($this->node, $name);
      $data['image_endpoint'] = $imageURL;

      $result = $this->restService->patchApplication($url, json_encode($data));
      if (isset($result) && $result->code >= 200 && $result->code < 300) {

        $this->node->setTitle($this->utils->truncate_string($name));
        $this->node->set('apic_summary', $summary);
        $this->node->set('application_redirect_endpoints', $oauthValues);
        $customFields = Application::getCustomFields();
        if (isset($customFields) && count($customFields) > 0) {
          foreach ($customFields as $customField) {
            $value = $form_state->getValue($customField);
            if (is_array($value) && isset($value[0]['value'])) {
              $value = $value[0]['value'];
            }
            elseif (isset($value[0])) {
              $value = array_values($value[0]);
            }
            $this->node->set($customField, $value);
          }
        }
        $this->node->save();

        drupal_set_message(t('Application updated successfully.'));
        $currentUser = \Drupal::currentUser();
        \Drupal::logger('apic_app')->notice('Application @appName updated by @username', [
          '@appName' => $this->node->getTitle(),
          '@username' => $currentUser->getAccountName(),
        ]);

        // Calling all modules implementing 'hook_apic_app_update':
        $moduleHandler = \Drupal::service('module_handler');
        $moduleHandler->invokeAll('apic_app_update', [$this->node, $result->data]);

        if ($moduleHandler->moduleExists('rules')) {
          // Set the args twice on the event: as the main subject but also in the
          // list of arguments.
          $event = new ApplicationUpdateEvent($this->node, ['application' => $this->node]);
          $eventDispatcher = \Drupal::service('event_dispatcher');
          $eventDispatcher->dispatch(ApplicationUpdateEvent::EVENT_NAME, $event);
        }
      }
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
