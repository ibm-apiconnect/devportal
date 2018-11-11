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

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\apic_app\Subscription;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrate an application subscription.
 */
class MigrateSubscriptionForm extends ConfirmFormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Subscription ID
   *
   * @var string
   */
  protected $subId;

  /**
   * Plan reference
   * productname:version:planname
   *
   * @var string
   */
  protected $planRef;

  protected $restService;

  protected $userUtils;
  protected $apimUtils;
  protected $utils;

  /**
   * ApplicationCreateForm constructor.
   *
   * @param ApplicationRestInterface $restService
   * @param UserUtils $userUtils
   */
  public function __construct(
                              ApplicationRestInterface $restService,
                              UserUtils $userUtils,
                              ApimUtils $apimUtils,
                              Utils $utils) {
    $this->restService = $restService;
    $this->userUtils = $userUtils;
    $this->apimUtils = $apimUtils;
    $this->utils = $utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Load the service required to construct this class
    return new static(
      $container->get('apic_app.rest_service'),
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.apim_utils'),
      $container->get('ibm_apim.utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'application_migrate_subscription_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL, $subId = NULL, $planRef = NULL) {
    $this->node = $appId;
    $this->subId = Html::escape($subId);
    $this->planRef = Html::escape($planRef);
    $form =  parent::buildForm($form, $form_state);
    $themeHandler = \Drupal::service('theme_handler');
    if ($themeHandler->themeExists('bootstrap')) {
      if (isset($form['actions']['submit'])) {
        $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('ok');
      }
      if (isset($form['actions']['cancel'])) {
        $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }
    }
    $form['#attached']['library'][] = 'apic_app/basic';

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to migrate this subscription? This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Migrate subscription');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Migrate the subscription for %title?', ['%title' => $this->node->title->value]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $analytics_service = \Drupal::service('ibm_apim.analytics')->getDefaultService();
    if(isset($analytics_service) && $analytics_service->getClientEndpoint() !== NULL) {
      return Url::fromRoute('apic_app.subscriptions', ['node' => $this->node->id()]);
    } else {
      return Url::fromRoute('entity.node.canonical', ['node' => $this->node->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $appId = $this->node->application_id->value;
    $planref = Html::escape($this->utils->base64_url_decode($this->planRef));

    $url = $this->node->apic_url->value . '/subscriptions/' . $this->subId;
    $parts = explode(':', $planref);
    $product_url = $parts[0];
    $planname = $parts[1];

    // 'adjust' the product url if it isn't in the format that the consumer-api expects
    $full_product_url = $this->apimUtils->createFullyQualifiedUrl($product_url);

    $data = array(
      "product_url" => $full_product_url,
      'plan' => $planname
    );

    $result = $this->restService->patchSubscription($url, json_encode($data));
    if (isset($result) && $result->code >= 200 && $result->code < 300) {
      drupal_set_message(t('Application subscription migrated successfully.'));
      // Calling all modules implementing 'hook_apic_app_migrate':
      \Drupal::moduleHandler()->invokeAll('apic_app_migrate', [
        'node' => $this->node,
        'data' => $result->data,
        'appId' => $appId,
        'planId' => $this->planRef,
        'subId' => $this->subId,
      ]);

      $current_user = \Drupal::currentUser();
      \Drupal::logger('apic_app')->notice('Subscription migrated for application @appname by @username', [
        '@appname' => $this->node->getTitle(),
        '@username' => $current_user->getAccountName(),
      ]);

      // Update the subscription
      Subscription::createOrUpdate($result->data);

    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
