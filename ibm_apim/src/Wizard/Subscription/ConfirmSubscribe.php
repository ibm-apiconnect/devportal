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

namespace Drupal\ibm_apim\Wizard\Subscription;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\ibm_apim\Wizard\IbmWizardStepBase;

class ConfirmSubscribe extends IbmWizardStepBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_wizard_confirm_subscribe';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // If a non-developer user somehow gets in to the wizard, validateAccess will send them away again
    if($this->validateAccess()) {
      /** @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $temp_store_factory */
      $temp_store_factory = \Drupal::service('session_based_temp_store');
      /** @var \Drupal\session_based_temp_store\SessionBasedTempStore $temp_store */
      $temp_store = $temp_store_factory->get('ibm_apim.wizard');
      $product_name = $temp_store->get('productName');
      $plan_name = $temp_store->get('planName');
      $application_name = $temp_store->get('applicationName');

      $form['productName'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Product'),
        '#default_value' => $product_name,
        '#disabled' => TRUE,
      );

      $form['planName'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Plan'),
        '#default_value' => $plan_name,
        '#disabled' => TRUE,
      );

      $form['applicationName'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Application'),
        '#default_value' => $application_name,
        '#disabled' => TRUE,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $temp_store_factory */
    $temp_store_factory = \Drupal::service('session_based_temp_store');
    /** @var \Drupal\session_based_temp_store\SessionBasedTempStore $temp_store */
    $temp_store = $temp_store_factory->get('ibm_apim.wizard');

    $applicationUrl = $temp_store->get('applicationUrl');
    $planId = $temp_store->get('planId');

    $restService = \Drupal::service('apic_app.rest_service');
    $result = $restService->subscribeToPlan($applicationUrl, $planId);
    $temp_store->set('result', $result);
  }

}
