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

namespace Drupal\ibm_apim\Wizard\Subscription;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ibm_apim\Wizard\IbmWizardStepBase;
use Drupal\node\Entity\Node;
use Drupal\product\Product;

/**
 * Class ChoosePlanStep
 *
 * @package Drupal\ibm_apim\Wizard\Subscription
 */
class ChoosePlanStep extends IbmWizardStepBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'subscription_wizard_choose_plan';
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // If a non-developer user somehow gets in to the wizard, validateAccess will send them away again
    if ($this->validateAccess()) {
      /** @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $temp_store_factory */
      $temp_store_factory = \Drupal::service('session_based_temp_store');
      $temp_store = $temp_store_factory->get('ibm_apim.wizard');

      // if referring page was not another part of the subscription wizard, store a reference to it in the drupal session
      if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/subscription') === FALSE && strpos($_SERVER['HTTP_REFERER'], '/login') === FALSE) {
        \Drupal::service('tempstore.private')->get('ibm_apim')->set('subscription_wizard_referer', $_SERVER['HTTP_REFERER']);
      }

      // First time through, the productId comes from the url
      $product_id = \Drupal::request()->query->get('productId');

      if (empty($product_id)) {
        // If someone pushed "previous" from the choose app page, we need the productId out of the wizard context
        $product_id = $temp_store->get('productId');
        if (empty($product_id)) {
          $products_url = Link::fromTextAndUrl(t('API Products'), Url::fromRoute('view.products.page_1'));
          \Drupal::messenger()
            ->addError(t('Subscription wizard was invoked with no productId. Start the wizard again from the %apiproducts page.', ['%apiproducts' => $products_url]));
          $this->redirect('<front>')->send();
          return $form;
        }
      }

      $product_node = Node::load($product_id);
      if ($product_node !== NULL && $product_node->bundle() === 'product' && Product::checkAccess($product_node)) {
        $product_node_build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($product_node, 'subscribewizard');

        $form['product'] = $product_node_build;

        $temp_store->set('productId', $product_id);
        $temp_store->set('productName', $product_node->getTitle());
      } else {
        \Drupal::messenger()->addWarning(t('The specified arguments were not correct.'));
        $temp_store->delete('productId');
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): ?bool {

    if (empty($form_state->getUserInput()['selectedPlan'])) {
      $form_state->setErrorByName('selectedPlan', t('You must select a plan that you want to subscribe to.'));
      return FALSE;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $temp_store_factory */
    $temp_store_factory = \Drupal::service('session_based_temp_store');
    $temp_store = $temp_store_factory->get('ibm_apim.wizard');

    $plan_bits = explode(':', $form_state->getUserInput()['selectedPlan']);
    $temp_store->set('planName', $plan_bits[0]);
    $temp_store->set('productUrl', $plan_bits[1]);
    $temp_store->set('planId', $plan_bits[1] . ':' . $plan_bits[2]);
  }

}
