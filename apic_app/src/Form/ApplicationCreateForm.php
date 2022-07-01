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

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ApplicationCreateForm extends FormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId(): string {
    return 'application_create_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $form['#parents'] = [];
    $max_weight = 500;

    $entity = \Drupal::entityTypeManager()->getStorage('node')->create([
      'type' => 'application',
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
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => $this->getCancelUrl(),
      '#attributes' => ['class' => ['button', 'apicSecondary']],
    ];
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
   * @return \Drupal\Core\Url
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('view.applications.page_1');
  }

  /**
   * {@inheritdoc}
   * @throws \JsonException
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
    $options = [];
    $destination = \Drupal::request()->get('redirectto');
    if (isset($destination) && !empty($destination)) {
      $options['query']['redirectto'] = $destination;
    }
    if (empty($result->data['errors']) && $result->code < 300) {
      if (isset($result->data['id'])) {
        if (!isset($result->data['client_id'], $result->data['client_secret'])) {
          \Drupal::messenger()->addMessage(t('Error: No application credentials were returned.', []));
        }

        $credsJson = json_encode([
          'client_id' => $result->data['client_id'],
          'client_secret' => $result->data['client_secret'],
        ], JSON_THROW_ON_ERROR);
        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('encrypt')) {
          $ibmApimConfig = \Drupal::config('ibm_apim.settings');
          $encryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
          if (isset($encryptionProfileName)) {
            $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($encryptionProfileName);
            if ($encryptionProfile !== NULL) {
              $encryptionService = \Drupal::service('encryption');
              $credsString = $encryptionService->encrypt($credsJson, $encryptionProfile);
            }
          } else {
            \Drupal::logger('apic_app')->warning('createApp: No encryption profile set', []);
            $credsString = base64_encode($credsJson);
          }
        } else {
          $credsString = base64_encode($credsJson);
        }
        $displayCredsUrl = Url::fromRoute('apic_app.display_creds', ['appId' => $result->data['id'], 'credentials' => $credsString], $options);
        $form_state->setRedirectUrl($displayCredsUrl);
      }
      else {
        \Drupal::logger('apic_app')->notice('Application ID missing in response to application create request for @appName by @username', [
          '@appName' => $name,
          '@username' => \Drupal::currentUser()->getAccountName(),
        ]);
        \Drupal::service('messenger')->addError($this->t('Application creation failed.'));
        $form_state->setRedirectUrl(Url::fromRoute('apic_app.create', [], $options));
      }
    }
    else {
      \Drupal::logger('apic_app')->notice('Error in response to application create request for @appName by @username', [
        '@appName' => $name,
        '@username' => \Drupal::currentUser()->getAccountName(),
      ]);
      \Drupal::service('messenger')->addError($this->t('Application creation failed.'));
      $form_state->setRedirectUrl(Url::fromRoute('apic_app.create', [], $options));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
