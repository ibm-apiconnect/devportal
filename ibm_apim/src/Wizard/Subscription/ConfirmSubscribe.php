<?php

namespace Drupal\ibm_apim\Wizard\Subscription;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\ibm_apim\Wizard\IbmWizardStepBase;
use Drupal\node\Entity\Node;
use Drupal\product\Product;
use Drupal\ibm_apim\External\SerializationUtility;

/**
 * Class ConfirmSubscribe
 *
 * @package Drupal\ibm_apim\Wizard\Subscription
 */
class ConfirmSubscribe extends IbmWizardStepBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'subscription_wizard_confirm_subscribe';
  }

  /**
   * @inheritDoc
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
      if ($temp_store->get('productId') !== null) {
        $product_node = Node::load($temp_store->get('productId'));
        if (isset($product_node)) {
          //Get selected plan
          $plans = $product_node->get("product_plans")->getValue();
          $selectedPlan = null;
          foreach ($plans as $plan) {
            $plan = SerializationUtility::decodeData($plan['value'], TRUE, FALSE);
            if (isset($plan['title']) && $plan['title'] === $plan_name) {
              $selectedPlan = $plan;
            }
          }
        }
      }

      if (isset($selectedPlan) && $product_node !== NULL) {
        //Get the product image
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
        $form['#termsOfService'] = NULL;
        $config = \Drupal::config('ibm_apim.settings');
        $show_subscription_tos = $config->get('show_subscription_tos') ?? TRUE;
        
        if ($show_subscription_tos && $product_node->hasField('product_terms_of_service')) {
          $tos_field = $product_node->get('product_terms_of_service');
          if (!$tos_field->isEmpty()) {
            $form['#termsOfService'] = $tos_field->processed;
          }
        }

      //Set the rate limit
      $form['#planInfo']['name'] = $plan_name;
      $planService = \Drupal::service('ibm_apim.product_plan');
      if (isset($selectedPlan['rate-limits']) && !empty($selectedPlan['rate-limits'])) {
        if (count($selectedPlan['rate-limits']) > 1) {
          $form['#planInfo']['rateLimit'] = 'multiple';
        } else {
          $rateLimitData = array_shift($selectedPlan['rate-limits']);
          if ($rateLimitData && isset($rateLimitData['value'])) {
            $rateLimit = $rateLimitData['value'];
            $form['#planInfo']['rateLimit'] = $planService->parseRateLimit($rateLimit);
          }
        }
      }

      $form['#billing'] = false;
      //Set the billing info
      $billingAccessCheck = \Drupal::service('ibm_apim.billing_access_checker')->access();
      if (isset($billingAccessCheck) && $billingAccessCheck->isAllowed()) {
        $form['#billing'] = true;
        if (isset($product_node)) {
          $org_url = \Drupal::service('ibm_apim.user_utils')->getCurrentConsumerOrg();
          $corgService = \Drupal::service('ibm_apim.consumerorg');
          $org = $corgService->get($org_url['url']);

          if (isset($selectedPlan['billing'])) {
            $billingInfo = $planService->parseBilling($selectedPlan['billing']);

            if(isset($billingInfo, $billingInfo['billingText'], $billingInfo['trialPeriodText'])) {
              $form['#planInfo']['billingInfo'] = $billingInfo['billingText'] . ' ' . $billingInfo['trialPeriodText'];

              $form['#paymentMethod'] = $org->getDefaultPaymentMethod();
              if (is_array($form['#paymentMethod']) && sizeof($form['#paymentMethod']) == 1) {
                $form['#paymentMethod'] = array_shift($form['#paymentMethod']);
              }
            }
          }
        }
      }

      $form['#productImage'] = $product_image;
      $form['#productName'] = $product_name;      
      if (isset($form['#termsOfService']) && !empty($form['#termsOfService'])) {
        $form['tos_accepted'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('I have read and agree to the Terms of Service'),
          '#required' => TRUE,
          '#default_value' => FALSE,
        ];
        
        $form['#attached']['library'][] = 'ibm_apim/subscription_tos';
      }
      $form['#applicationName'] = $application_name;
    } else {
      $form['#error'] = TRUE;
    }

    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    if (isset($form['#termsOfService']) && !empty($form['#termsOfService'])) {
      $tos_accepted = $form_state->getValue('tos_accepted');
      if (empty($tos_accepted)) {
        $form_state->setErrorByName('tos_accepted',
          $this->t('You must accept the Terms of Service to continue.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $temp_store_factory */
    $temp_store_factory = \Drupal::service('session_based_temp_store');
    $temp_store = $temp_store_factory->get('ibm_apim.wizard');

    $applicationUrl = $temp_store->get('applicationUrl');
    $planId = $temp_store->get('planId');

    $restService = \Drupal::service('apic_app.rest_service');
    $result = $restService->subscribeToPlan($applicationUrl, $planId);
    $temp_store->set('result', $result);
    if (isset($form['#planInfo']['billingInfo'])) {
      $temp_store->set('price', $form['#planInfo']['billingInfo']);
    }
  }

}
