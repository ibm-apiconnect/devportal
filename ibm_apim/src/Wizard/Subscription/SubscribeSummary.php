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

use Drupal\apic_app\Application;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\ibm_apim\Wizard\IbmWizardStepBase;
use Drupal\node\Entity\Node;
use Drupal\product\Product;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SubscribeSummary extends IbmWizardStepBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'subscription_wizard_subscribe_result';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // If a non-developer user somehow gets in to the wizard, validateAccess will send them away again
    if($this->validateAccess()) {
      $cached_values = $form_state->getTemporaryValue('wizard');
      $product_name = $cached_values['productName'];
      $plan_name = $cached_values['planName'];
      $application_name = $cached_values['applicationName'];
      $application_node_id = $cached_values['applicationNodeId'];
      $result = $cached_values['result'];
      $product_node_id = $cached_values['productId'];

      // read referer and clear it again
      $referer = \Drupal::service('tempstore.private')->get('ibm_apim')->get('subscription_wizard_referer', NULL);
      \Drupal::service('tempstore.private')->get('ibm_apim')->set('subscription_wizard_referer', NULL);

      if($result->code == 201) {
        // For all data we want to display, just pass the data to the twig template which then handles how it gets rendered.
        if($result->data['state'] !== 'enabled') {
          $form['#messages']['statusText'] = t('Your subscription request has been created and is now pending approval. You will receive an email once your subscription request is approved.');
        }
        else {
          $form['#messages']['statusText'] = t('Your application is now subscribed to the selected plan.');
        }
        $moduleHandler = \Drupal::service('module_handler');
        $ibm_apim_show_placeholder_images = \Drupal::config('ibm_apim.settings')->get('show_placeholder_images');

        // Dig out the application icon
        $application_node = Node::load($application_node_id);
        if(isset($application_node)) {
          $fid = $application_node->application_image->getValue();
          $application_image = NULL;
          if (isset($fid) && !empty($fid) && isset($fid[0]['target_id'])) {
            $file = File::load($fid[0]['target_id']);
            $application_image = $file->toUrl();
          }
          else {
            if ($ibm_apim_show_placeholder_images && $moduleHandler->moduleExists('apic_app')) {
              $rawImage = Application::getRandomImageName($application_node->getTitle());
              $application_image = base_path() . drupal_get_path('module', 'apic_app') . '/images/' . $rawImage;
            }
          }
        }

        // Dig out the product icon
        $product_node = Node::load($product_node_id);
        if(isset($product_node)) {
          $fid = $product_node->apic_image->getValue();
          $product_image = NULL;
          if (isset($fid) && !empty($fid) && isset($fid[0]['target_id'])) {
            $file = File::load($fid[0]['target_id']);
            $product_image = $file->toUrl();
          }
          else {
            if ($ibm_apim_show_placeholder_images && $moduleHandler->moduleExists('product')) {
              $rawImage = Product::getRandomImageName($product_node->getTitle());
              $product_image = base_path() . drupal_get_path('module', 'product') . '/images/' . $rawImage;
            }
          }
        }

        $form['#subscriptionDetails'] = array(
          'planName' => $plan_name,
          'productName' => $product_name,
          'productNodeId' => $product_node_id,
          'applicationName' => $application_name,
          'applicationNodeId' => $application_node_id
        );

        if(isset($referer)) {
          $form['#subscriptionDetails']['referer'] = $referer;
        }

        if(isset($application_image) && !empty($application_image)) {
          $form['#subscriptionDetails']['applicationIcon'] = $application_image;
        }
        if(isset($product_image) && !empty($product_image)) {
          $form['#subscriptionDetails']['productIcon'] = $product_image;
        }
      }
      else {
        // Failed to subscribe for some reason
        $form['#messages']['statusText'] = t("There was a problem with your subscription request. Review any error messages, correct the problem and try again.");
      }

      // blank out the form state - we've finished now
      $form_state->setTemporaryValue('wizard', array());
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setTemporaryValue('wizard', array());

    $form_state->setRedirect('<front>');
  }

}
