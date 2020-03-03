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

namespace Drupal\apic_app\Form\Multistep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ApplicationCreateStep1Form extends MultistepFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId(): string {
    return 'application_create_form_one';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $form = parent::buildForm($form, $form_state);

    $form['#parents'] = [];
    $max_weight = 500;

    $entity = \Drupal::entityTypeManager()->getStorage('node')->create([
      'type' => 'application',
    ]);
    $entity_form = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.application.default');

    $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'application');

    if ($entity_form !== NULL) {
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

    $ibmApimApplicationCertificates = \Drupal::state()->get('ibm_apim.application_certificates');
    if ($ibmApimApplicationCertificates) {

      $form['certificate'] = [
        '#type' => 'textarea',
        '#title' => t('Certificate'),
        '#description' => t('Paste the content of your application\'s x509 certificate.'),
        '#required' => FALSE,
        '#wysiwyg' => FALSE,
      ];
    }

    $form['title']['#required'] = TRUE;

    $form['actions']['#type'] = 'actions';
    $form['actions']['#weight'] = $max_weight + 1;
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => $this->getCancelUrl(),
      '#attributes' => ['class' => ['button', 'apicSecondary']],
    ];
    $themeHandler = \Drupal::service('theme_handler');
    if ($themeHandler->themeExists('bootstrap')) {
      $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('ok');
      $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
    }
    $form['#attached']['library'][] = 'apic_app/basic';

    // remove any admin fields if they exist
    if (isset($form['revision_log'])) {
      unset($form['revision_log']);
    }
    if (isset($form['status'])) {
      unset($form['status']);
    }

    // If we were invoked from somewhere else and given a redirect location, we want to go back there later so store it
    $redirect_to = \Drupal::request()->query->get('redirectTo');
    if (empty($redirect_to)) {
      $this->store->set('redirect_to', NULL);
    }
    else {
      $this->store->set('redirect_to', $redirect_to);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
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
    if (!isset($name) || empty($name)) {
      $form_state->setErrorByName('Name', $this->t('Application name is a required field.'));
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('view.applications.page_1');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $certificate = NULL;

    $name = $form_state->getValue('title');
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
    $ibmApimApplicationCertificates = (boolean) \Drupal::state()->get('ibm_apim.application_certificates');
    if ($ibmApimApplicationCertificates) {
      $certificate = \Drupal::service('apic_app.certificate')->cleanup($form_state->getValue('certificate'));
    }

    $restService = \Drupal::service('apic_app.rest_service');
    $result = $restService->createApplication($name, $summary, $oauthEndpoints, $certificate, $form_state);

    if (empty($result->data['errors']) && $result->code < 300) {
      if (isset($result->data['id'])) {
        if (isset($result->data['client_id'], $result->data['client_secret'])) {
          $this->store->set('creds', serialize(['client_id' => $result->data['client_id'], 'client_secret' => $result->data['client_secret']]));
        }
        else {
          \Drupal::messenger()->addMessage(t('Error: No application credentials were returned.', []));
          $this->store->set('creds', serialize(['client_id' => NULL, 'client_secret' => NULL]));
        }
        $this->store->set('appId', $result->data['id']);
        $this->store->set('nid', $result->data['nid']);

        $form_state->setRedirect('apic_app.create_step_two');
      }
      else {
        \Drupal::logger('apic_app')->notice('Application ID missing in response to application create request for @appName by @username', [
          '@appName' => $name,
          '@username' => \Drupal::currentUser()->getAccountName(),
        ]);
        \Drupal::service('messenger')->addError($this->t('Application creation failed.'));
        $form_state->setRedirectUrl(Url::fromRoute('apic_app.create'));
      }
    }
    else {
      \Drupal::logger('apic_app')->notice('Error in response to application create request for @appName by @username', [
        '@appName' => $name,
        '@username' => \Drupal::currentUser()->getAccountName(),
      ]);
      \Drupal::service('messenger')->addError($this->t('Application creation failed.'));
      $form_state->setRedirectUrl(Url::fromRoute('apic_app.create'));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
