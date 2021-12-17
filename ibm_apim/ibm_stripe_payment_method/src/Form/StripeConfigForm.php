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

namespace Drupal\ibm_stripe_payment_method\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * APIC Stripe monetization settings form.
 */
class StripeConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID(): string {
    return 'ibm_stripe_payment_method_config_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['ibm_stripe_payment_method.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('ibm_stripe_payment_method.settings');

    $billingProviderConfig = unserialize(\Drupal::config('ibm_apim.settings')->get('billing_providers'), ['allowed_classes' => FALSE]);
    if (!isset($billingProviderConfig) || empty($billingProviderConfig)) {
      \Drupal::messenger()->addWarning(t('No billing provider is currently configured in the portal. Go to /admin/config/system/apic_billing to set the billing provider mapping.'));
    }

    $publishableKey = $config->get('publishable_key');
    $secretKey = $config->get('secret_key');
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('encrypt')) {
      $ibmApimConfig = \Drupal::config('ibm_apim.settings');
      $encryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
      $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($encryptionProfileName);
      $encryptionService = \Drupal::service('encryption');
      $publishableKey = $encryptionService->decrypt($publishableKey, $encryptionProfile);
      $secretKey = $encryptionService->decrypt($secretKey, $encryptionProfile);
    }

    $form['intro'] = [
      '#markup' => t('IBM API Developer Portal Stripe Integration Settings'),
      '#weight' => -20,
    ];

    $form['stripeapi'] = [
      '#type' => 'fieldset',
      '#title' => t('Stripe API'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['stripeapi']['publishable_key'] = [
      '#type' => 'textfield',
      '#title' => t('Publishable Key'),
      '#description' => t('Provide the publishable key for your Stripe account.'),
      '#size' => 25,
      '#maxlength' => 256,
      '#default_value' => $publishableKey,
      '#required' => TRUE,
      '#weight' => 40,
    ];

    $form['stripeapi']['secret_key'] = [
      '#type' => 'textfield',
      '#title' => t('Secret Key'),
      '#description' => t('Provide the secret key for your Stripe account.'),
      '#size' => 25,
      '#maxlength' => 256,
      '#default_value' => $secretKey,
      '#required' => TRUE,
      '#weight' => 50,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {

    // Validate the secret key.
    if (!empty($form_state->getValue('secret_key'))) {
      try {
        \Stripe\Stripe::setApiKey($form_state->getValue('secret_key'));
      } catch (\Stripe\Exception\ApiErrorException $e) {
        $form_state->setError($form['secret_key'], $this->t('Invalid secret key.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Set the submitted configuration settings
    $publishableKey = $form_state->getValue('publishable_key');
    $secretKey = $form_state->getValue('secret_key');

    // store encrypted if possible
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('encrypt')) {
      $ibmApimConfig = \Drupal::config('ibm_apim.settings');
      $encryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
      $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($encryptionProfileName);
      $encryptionService = \Drupal::service('encryption');
      $publishableKey = $encryptionService->encrypt($publishableKey, $encryptionProfile);
      $secretKey = $encryptionService->encrypt($secretKey, $encryptionProfile);
    }
    $this->config('ibm_stripe_payment_method.settings')
      ->set('publishable_key', $publishableKey)
      ->set('secret_key', $secretKey)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
