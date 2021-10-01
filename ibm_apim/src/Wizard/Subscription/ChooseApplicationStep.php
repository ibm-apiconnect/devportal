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

namespace Drupal\ibm_apim\Wizard\Subscription;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Wizard\IbmWizardStepBase;

/**
 * Class ChooseApplicationStep
 *
 * @package Drupal\ibm_apim\Wizard\Subscription
 */
class ChooseApplicationStep extends IbmWizardStepBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'subscription_wizard_choose_application';
  }

  /**
   * {@inheritdoc}
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // If a non-developer user somehow gets in to the wizard, validateAccess will send them away again
    if ($this->validateAccess()) {

      /** @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $temp_store_factory */
      $temp_store_factory = \Drupal::service('session_based_temp_store');
      $temp_store = $temp_store_factory->get('ibm_apim.wizard');

      // if referring page was not another part of the subscription wizard, store a reference to it in the drupal session
      if (strpos($_SERVER['HTTP_REFERER'], '/subscription') === FALSE && strpos($_SERVER['HTTP_REFERER'], '/login') === FALSE) {
        \Drupal::service('tempstore.private')->get('ibm_apim')->set('subscription_wizard_referer', $_SERVER['HTTP_REFERER']);
      }

      // Check for any query params from where we've started at this step
      $product_id = \Drupal::request()->query->get('productId');
      $plan_title = \Drupal::request()->query->get('planTitle');
      $plan_id = \Drupal::request()->query->get('planId');
      $productName = '';

      if (isset($product_id, $plan_title, $plan_id)) { 
        $temp_store->set('productId', $product_id);
        $temp_store->set('planName', $plan_title);
        $temp_store->set('planId', $plan_id);
      } else {
        $temp_store->get('planName');
        $plan_id = $temp_store->get('planId');
        $product_id = $temp_store->get('productId');
      }
      $product_node = \Drupal::entityTypeManager()->getStorage('node')->load($product_id);
      if ($product_node !== NULL) {
        $temp_store->set('productName', $product_node->getTitle());
        $temp_store->set('productUrl', $product_node->get('apic_url')->value);
        $productName = $product_node->getTitle();
      }

      $parts = explode(':', $plan_id);
      $product_url = $parts[0];

      $allApps = \Drupal::service('apic_app.application')->listApplications();
      $allApps = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($allApps);
      $validApps = [];
      $suspendedApps = [];
      $subscribedApps = [];

      // Do some checks on the apps
      // - if they are suspended don't show them
      // - if they are already subscribed to any plan in this product don't show them
      // - if there are no apps left to display after that, put up a message

      foreach ($allApps as $nextApp) {
        if (isset($nextApp->apic_state->value) && mb_strtoupper($nextApp->apic_state->value) === 'SUSPENDED') {
          $suspendedApps[] = $nextApp;
        }
        else {
          $subs = $nextApp->application_subscription_refs->referencedEntities();
          if (is_array($subs) && !empty($subs)) {
            $appSubscribedToProduct = FALSE;
            foreach ($subs as $sub) {
              if ($sub->product_url() === $product_url) {
                $subscribedApps[] = $nextApp;
                $appSubscribedToProduct = TRUE;
                break;
              }
            }
            if (!$appSubscribedToProduct) {
              $validApps[] = $nextApp;
            }
          }
          else {
            $validApps[] = $nextApp;
          }
        }
      }

      if (!empty($suspendedApps)) {
        $form['#messages']['suspendedAppsNotice'] = \Drupal::translation()
          ->formatPlural(sizeof($suspendedApps), 'There is %number suspended application not displayed in this list.', 'There are %number suspended applications not displayed in this list.',
            ['%number' => sizeof($suspendedApps)]);
      }

      if (!empty($subscribedApps)) {
        $form['#messages']['subscribedAppsNotice'] = \Drupal::translation()
          ->formatPlural(sizeof($subscribedApps), 'There is %number application that is already subscribed to the %product product. It is not displayed in this list.', 'There are %number applications that are already subscribed to the %product product. They are not displayed in this list.', [
            '%number' => sizeof($subscribedApps),
            '%product' => $productName,
          ]);
      }

      $config = \Drupal::config('ibm_apim.settings');
      $show_register_app = (boolean) $config->get('show_register_app');
      if ($show_register_app === TRUE) {
        $form['#createNewApp'] = [
          '#type' => 'link',
          '#title' => $this->t('Create Application'),
          '#url' => Url::fromRoute('apic_app.create_modal'),
          '#attributes' => [
            'class' => [
              'use-ajax',
              'button',
              'bx--card',
              'tile-button',
              'apicTertiary',
              'add-app'
            ],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => 560
            ]),
          ],
          '#prefix' => '<div class="apicNewAppButton">',
          '#suffix' => '</div>',
        ];
        $form['#welcome'] = t('Select an existing application or create a new application');
      } else {
        $form['#welcome'] = t('Select an existing application');
      }

      // this empty div is used to put the new apps in
      $form['newApps'] = ['#markup' => "<div class='apicNewAppsList'></div>"];

      if (!empty($validApps)) {
        $form['apps'] = \Drupal::entityTypeManager()->getViewBuilder('node')->viewMultiple($validApps, 'subscribewizard');
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
  public function validateForm(array &$form, FormStateInterface $form_state): ?bool {

    if (empty($form_state->getUserInput()['selectedApplication'])) {
      $form_state->setErrorByName('selectedApplication', t('You must select an application to create a subscription.'));
      return FALSE;
    }
    $application = \Drupal::entityTypeManager()->getStorage('node')->load($form_state->getUserInput()['selectedApplication']);
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $org = $userUtils->getCurrentConsumerOrg();
    if (!isset($application, $org['url']) || $application->application_consumer_org_url->value !== $org['url']) {
      $form_state->setErrorByName('selectedApplication', t('Invalid application: Provide a valid application to create a subscription.'));
      return FALSE;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $temp_store_factory */
    $temp_store_factory = \Drupal::service('session_based_temp_store');
    $temp_store = $temp_store_factory->get('ibm_apim.wizard');
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $org = $userUtils->getCurrentConsumerOrg();

    $application = \Drupal::entityTypeManager()->getStorage('node')->load($form_state->getUserInput()['selectedApplication']);
    if (isset($application, $org['url']) && $application->application_consumer_org_url->value === $org['url']) {
      $temp_store->set('applicationUrl', $application->get('apic_url')->value);
      $temp_store->set('applicationName', $application->getTitle());
      $temp_store->set('applicationNodeId', $form_state->getUserInput()['selectedApplication']);
    }
  }

}
