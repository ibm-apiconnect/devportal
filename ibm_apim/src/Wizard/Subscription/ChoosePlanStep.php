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

class ChoosePlanStep extends IbmWizardStepBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_wizard_choose_plan';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // If a non-developer user somehow gets in to the wizard, validateAccess will send them away again
    if($this->validateAccess()) {
      $cached_values = $form_state->getTemporaryValue('wizard');

      // if refering page was not another part of the subscription wizard, store a reference to it in the drupal session
      if(strpos($_SERVER['HTTP_REFERER'], '/subscription') === FALSE && strpos($_SERVER['HTTP_REFERER'], '/login') === FALSE){
        \Drupal::service('tempstore.private')->get('ibm_apim')->set('subscription_wizard_referer', $_SERVER['HTTP_REFERER']);
      }

      // First time through, the productId comes from the url
      $product_id = \Drupal::request()->query->get('productId');

      if(empty($product_id)) {
        // If someone pushed "previous" from the choose app page, we need the productId out of the wizard context
        $product_id = $cached_values['productId'];
        if(empty($product_id)) {
          $products_url = \Drupal::l(t('API Products'), \Drupal\Core\Url::fromRoute('view.products.page_1'));
          drupal_set_message(t("Subscription wizard was invoked with no productId. Start the wizard again from the %apiproducts page.", array('%apiproducts' => $products_url)), 'error');
          $this->redirect("<front>")->send();
          return;
        }
      }

      $product_node = Node::load($product_id);
      $product_node_build = node_view($product_node, 'subscribewizard');

      $form['product'] = $product_node_build;

      $cached_values['productId'] = $product_id;
      $cached_values['productName'] = $product_node->getTitle();

      $form_state->setTemporaryValue('wizard', $cached_values);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if(empty($form_state->getUserInput()['selectedPlan'])) {
      $form_state->setErrorByName('selectedPlan', t('You must select a plan that you want to subscribe to.'));
      return FALSE;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');

    $plan_bits = explode(":", $form_state->getUserInput()['selectedPlan']);

    $cached_values['planName'] = $plan_bits[0];
    $cached_values['productUrl'] = $plan_bits[1];
    $cached_values['planId'] = $plan_bits[1] . ':' . $plan_bits[2];

    $form_state->setTemporaryValue('wizard', $cached_values);
  }

}
