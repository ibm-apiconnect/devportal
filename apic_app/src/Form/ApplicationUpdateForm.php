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

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Application;
use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\apic_app\Event\ApplicationUpdateEvent;
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

  protected $restService;

  protected $userUtils;
  protected $utils;


  /**
   * ApplicationCreateForm constructor.
   *
   * @param ApplicationRestInterface $restService
   * @param UserUtils $userUtils
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
  public function getFormId() {
    return 'application_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $this->node = $appId;

    $form['#parents'] = [];
    $max_weight = 500;

    $entity = \Drupal::entityTypeManager()->getStorage('node')->load($this->node->id());
    $entity_form = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.application.default');

    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'application');

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

    if (isset($form['application_image'])) {
      unset($form['application_image']);
    }

    $ibm_apim_application_certificates = \Drupal::state()->get('ibm_apim.application_certificates');
    if ($ibm_apim_application_certificates) {
      // we do not store the certificate so have to retrieve it from apim in order to show current value
      $app_data = Application::fetchFromAPIC($this->node->apic_url->value);

      $form['certificate'] = array(
        '#type' => 'textarea',
        '#title' => t('Certificate'),
        '#description' => t('Paste the content of your application\'s x509 certificate.'),
        '#required' => FALSE,
        '#wysiwyg' => FALSE,
      );
      if (isset($app_data) && isset($app_data['certificate']) && !empty($app_data['certificate'])) {
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
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => $this->getCancelUrl(),
      '#attributes' => ['class' => ['button', 'apicSecondary']]
    );
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
  public function getCancelUrl() {
    return $this->node->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $name = $form_state->getValue('title');
    if (is_array($name) && isset($name[0]['value'])) {
      $name = $name[0]['value'];
    }
    if (!isset($name) || empty($name)) {
      $form_state->setErrorByName('title', $this->t('Application name is a required field.'));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $appId = $this->node->application_id->value;
    $name = $form_state->getValue('title');
    $oauth_values = array();
    if (is_array($name) && isset($name[0]['value'])) {
      $name = $name[0]['value'];
    }
    $summary = $form_state->getValue('apic_summary');
    if (is_array($summary) && isset($summary[0]['value'])) {
      $summary = $summary[0]['value'];
    }
    $oauth_endpoints = array();
    $oauth = $form_state->getValue('application_redirect_endpoints');
    foreach($oauth as $oauth_value) {
      if(is_array($oauth_value) && !empty($oauth_value['value'])) {
        $oauth_endpoints[] = trim($oauth_value['value']);
      }
    }

    if (!isset($name) || empty($name)) {
      drupal_set_message(t('ERROR: Title is a required field.'), 'error');
      $form_state->setRedirectUrl(Url::fromRoute('apic_app.create'));
    }
    else {
      $url = $this->node->apic_url->value;
      $data = [
        "title" => $name,
        "summary" => $summary,
      ];
      $data['redirect_endpoints'] = $oauth_endpoints;
      $ibm_apim_application_certificates = \Drupal::state()->get('ibm_apim.application_certificates');
      if ($ibm_apim_application_certificates) {
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
        $this->node->set('application_redirect_endpoints', $oauth_values);
        $customfields = Application::getCustomFields();
        if (isset($customfields) && count($customfields) > 0) {
          foreach ($customfields as $customfield) {
            $value = $form_state->getValue($customfield);
            if (is_array($value) && isset($value[0]['value'])) {
              $value = $value[0]['value'];
            } else if (isset($value[0])) {
              $value = array_values($value[0]);
            }
            $this->node->set($customfield, $value);
          }
        }
        $this->node->save();

        drupal_set_message(t('Application updated successfully.'));
        $current_user = \Drupal::currentUser();
        \Drupal::logger('apic_app')->notice('Application @appname updated by @username', [
          '@appname' => $this->node->getTitle(),
          '@username' => $current_user->getAccountName(),
        ]);

        // apic_app_update hook invoked by Application::update so doesnt need calling here
        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('rules')) {
          // Set the args twice on the event: as the main subject but also in the
          // list of arguments.
          $event = new ApplicationUpdateEvent($this->node, ['application' => $this->node]);
          $event_dispatcher = \Drupal::service('event_dispatcher');
          $event_dispatcher->dispatch(ApplicationUpdateEvent::EVENT_NAME, $event);
        }
      }
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
