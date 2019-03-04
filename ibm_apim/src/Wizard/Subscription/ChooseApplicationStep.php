<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Wizard\Subscription;

use Drupal\apic_app\Application;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Wizard\IbmWizardStepBase;
use Drupal\node\Entity\Node;

class ChooseApplicationStep extends IbmWizardStepBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'subscription_wizard_choose_application';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // If a non-developer user somehow gets in to the wizard, validateAccess will send them away again
    if ($this->validateAccess()) {

      /** @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $temp_store_factory */
      $temp_store_factory = \Drupal::service('session_based_temp_store');
      /** @var \Drupal\session_based_temp_store\SessionBasedTempStore $temp_store */
      $temp_store = $temp_store_factory->get('ibm_apim.wizard');

      // if refering page was not another part of the subscription wizard, store a reference to it in the drupal session
      if (strpos($_SERVER['HTTP_REFERER'], '/subscription') === FALSE && strpos($_SERVER['HTTP_REFERER'], '/login') === FALSE) {
        \Drupal::service('tempstore.private')->get('ibm_apim')->set('subscription_wizard_referer', $_SERVER['HTTP_REFERER']);
      }

      // Check for any query params from where we've started at this step
      $product_id = \Drupal::request()->query->get('productId');
      $plan_title = \Drupal::request()->query->get('planTitle');
      $plan_id = \Drupal::request()->query->get('planId');
      $productName = '';

      if (isset($product_id, $plan_title, $plan_id)) {
        $product_node = Node::load($product_id);
        $temp_store->set('productId', $product_id);
        $temp_store->set('planName', $plan_title);
        $temp_store->set('planId', $plan_id);
        if ($product_node !== NULL) {
          $temp_store->set('productName', $product_node->getTitle());
          $temp_store->set('productUrl', $product_node->get('apic_url')->value);
          $productName = $product_node->getTitle();
        }
      }

      $parts = explode(':', $plan_id);
      $product_url = $parts[0];

      $allApps = Application::listApplications();
      $allApps = Node::loadMultiple($allApps);
      $validApps = [];
      $suspendedApps = [];
      $subscribedApps = [];

      // Do some checks on the apps
      // - if they are suspended don't show them
      // - if they are already subscribed to any plan in this product don't show them
      // - if there are no apps left to display after that, put up a message

      foreach ($allApps as $nid => $nextApp) {
        if (isset($nextApp->apic_state->value) && mb_strtoupper($nextApp->apic_state->value) === 'SUSPENDED') {
          $suspendedApps[] = $nextApp;
        }
        else {
          if (isset($nextApp->application_subscriptions->value)) {
            $subs = unserialize($nextApp->application_subscriptions->value, ['allowed_classes' => FALSE]);
            if (is_array($subs)) {
              $appSubscribedToProduct = FALSE;
              foreach ($subs as $sub) {
                if (isset($sub['product_url']) && $sub['product_url'] === $product_url) {
                  $subscribedApps[] = $nextApp;
                  $appSubscribedToProduct = TRUE;
                  break;
                }
              }
              if (!$appSubscribedToProduct) {
                $validApps[] = $nextApp;
              }
            }
          }
          else {
            $validApps[] = $nextApp;
          }
        }
      }

      if (!empty($suspendedApps)) {
        $form['#messages']['suspendedAppsNotice'] = t('There are %number suspended applications not displayed in this list.', ['%number' => sizeof($suspendedApps)]);
      }

      if (!empty($subscribedApps)) {
        $form['#messages']['subscribedAppsNotice'] = t('There are %number applications that are already subscribed to the %product product. They are not displayed in this list.',
          ['%number' => sizeof($subscribedApps), '%product' => $productName]);
      }

      $form['createNewApp'] = [
        '#type' => 'link',
        '#title' => $this->t('Create New'),
        '#url' => Url::fromRoute('apic_app.create_modal'),
        '#attributes' => [
          'class' => [
            'use-ajax',
            'button',
          ],
        ],
        '#prefix' => '<div class="apicNewAppWrapper">',
        '#suffix' => '</div>',
      ];

      if (!empty($validApps)) {
        $form['apps'] = node_view_multiple($validApps, 'subscribewizard');
        $form['apps']['#prefix'] = "<div class='apicSubscribeAppsList'>";
        $form['apps']['#suffix'] = '</div>';
      }
      else {
        $form['#messages']['noAppsNotice'] = t('There are no applications that can be subscribed to this Plan.');
      }

      // Attach the library for pop-up dialogs/modals.
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $form['#attached']['library'][] = 'apic_app/basic';

    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (empty($form_state->getUserInput()['selectedApplication'])) {
      $form_state->setErrorByName('selectedApplication', t('You must select an application to create a subscription.'));
      return FALSE;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $temp_store_factory */
    $temp_store_factory = \Drupal::service('session_based_temp_store');
    /** @var \Drupal\session_based_temp_store\SessionBasedTempStore $temp_store */
    $temp_store = $temp_store_factory->get('ibm_apim.wizard');

    $application = Node::load($form_state->getUserInput()['selectedApplication']);

    $temp_store->set('applicationUrl', $application->get('apic_url')->value);
    $temp_store->set('applicationName', $application->getTitle());
    $temp_store->set('applicationNodeId', $form_state->getUserInput()['selectedApplication']);
  }

}
