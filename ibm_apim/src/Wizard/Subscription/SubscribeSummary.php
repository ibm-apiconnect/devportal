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

namespace Drupal\ibm_apim\Wizard\Subscription;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\ibm_apim\Wizard\IbmWizardStepBase;
use Drupal\node\Entity\Node;
use Drupal\product\Product;

/**
 * Class SubscribeSummary
 *
 * @package Drupal\ibm_apim\Wizard\Subscription
 */
class SubscribeSummary extends IbmWizardStepBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'subscription_wizard_subscribe_result';
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

      $product_name = $temp_store->get('productName');
      $plan_name = $temp_store->get('planName');
      $application_name = $temp_store->get('applicationName');
      $application_node_id = $temp_store->get('applicationNodeId');
      $result = $temp_store->get('result');
      $product_node_id = $temp_store->get('productId');
      $price = $temp_store->get('price');

      // read referer and clear it again
      $referer = \Drupal::service('tempstore.private')->get('ibm_apim')->get('subscription_wizard_referer', NULL);
      \Drupal::service('tempstore.private')->get('ibm_apim')->set('subscription_wizard_referer', NULL);

      if (isset($result) && (int) $result->code === 201) {
        // For all data we want to display, just pass the data to the twig template which then handles how it gets rendered.
        if ($result->data['state'] !== 'enabled') {
          $form['#messages']['statusText'] = t('Your subscription request has been created and is now pending approval. You will receive an email once your subscription request is approved.');
        }
        else {
          $form['#messages']['statusText'] = t('Your application is now subscribed to the selected plan.');
        }
        $moduleHandler = \Drupal::service('module_handler');
        $ibm_apim_show_placeholder_images = (boolean) \Drupal::config('ibm_apim.settings')->get('show_placeholder_images');

        // Dig out the application icon
        $application_node = Node::load($application_node_id);
        if (isset($application_node)) {
          $fid = $application_node->application_image->getValue();
          $application_image = NULL;
          if (isset($fid[0]['target_id'])) {
            $file = File::load($fid[0]['target_id']);
            if ($file !== NULL) {
              $application_image = $file->createFileUrl();
            }
          }
          elseif ($ibm_apim_show_placeholder_images === TRUE && $moduleHandler->moduleExists('apic_app')) {
            $rawImage = \Drupal::service('apic_app.application')->getRandomImageName($application_node->getTitle());
            $application_image = base_path() . \Drupal::service('extension.list.module')->getPath('apic_app') . '/images/' . $rawImage;
          }
        }

        // Dig out the product icon
        $product_node = Node::load($product_node_id);
        if (isset($product_node)) {
          $product_image = $this->getProductImage($product_node);
        }

        $form['#subscriptionDetails'] = [
          'planName' => $plan_name,
          'productName' => $product_name,
          'productNodeId' => $product_node_id,
          'applicationName' => $application_name,
          'applicationNodeId' => $application_node_id,
        ];

        if (isset($referer)) {
          $form['#subscriptionDetails']['referer'] = $referer;
        }
        if (isset($price)) {
          $form['#subscriptionDetails']['price'] = $price;
        }

        if (isset($application_image) && !empty($application_image)) {
          $form['#subscriptionDetails']['applicationIcon'] = $application_image;
        }
        if (isset($product_image) && !empty($product_image)) {
          $form['#subscriptionDetails']['productIcon'] = $product_image;
        }

        $form['#productRecommendations'] = FALSE;
        if((bool) \Drupal::config('ibm_apim.settings')->get('product_recommendations.enabled')) {
          $form['#productRecommendations'] = TRUE;
        }
      }
      else {
        // Failed to subscribe for some reason
        $form['#messages']['statusText'] = t('There was a problem with your subscription request. Review any error messages, correct the problem and try again.');
        $form['#error'] = TRUE;
      }
      // allow other modules to modify the content on the summary panel
      \Drupal::moduleHandler()->alter('ibm_apim_subscription_wizard_summary', $form);

      // cant blank out the temp store quite yet as need it to render the page title
    }

    return $form;
  }

  protected function getProductImage(Node $product_node): ?string {
    $moduleHandler = \Drupal::service('module_handler');
    $ibm_apim_show_placeholder_images = (boolean) \Drupal::config('ibm_apim.settings')->get('show_placeholder_images');
    $fid = $product_node->apic_image->getValue();
    $product_image = NULL;
    if (isset($fid[0]['target_id'])) {
      $file = File::load($fid[0]['target_id']);
      if ($file !== NULL) {
        $product_image = $file->createFileUrl();
      }
    }
    elseif ($ibm_apim_show_placeholder_images && $moduleHandler->moduleExists('product')) {
      $product_image = Product::getPlaceholderImageURL($product_node->getTitle());
    }

    return $product_image;
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $temp_store_factory */
    $temp_store_factory = \Drupal::service('session_based_temp_store');
    $temp_store = $temp_store_factory->get('ibm_apim.wizard');
    $temp_store->deleteAll();

    $form_state->setRedirect('<front>');
  }

}
