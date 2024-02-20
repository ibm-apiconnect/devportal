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

namespace Drupal\consumerorg\Form;

use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to create a new payment method.
 */
class PaymentMethodCreateForm extends FormBase {

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected ConsumerOrgService $consumerOrgService;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\Core\Extension\ThemeHandler
   */
  protected ThemeHandler $themeHandler;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected ApimUtils $apimUtils;

  protected int $step = 1;

  protected $chosen_integration;

  protected $billingUrl;

  /**
   * OrgCreateForm constructor.
   *
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumer_org_service
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\ibm_apim\Service\ApimUtils $apimUtils
   */
  public function __construct(
    ConsumerOrgService $consumer_org_service,
    AccountInterface $account,
    LoggerInterface $logger,
    ThemeHandler $themeHandler,
    EntityTypeManagerInterface $entityTypeManager,
    EntityFieldManagerInterface $entityFieldManager,
    Messenger $messenger,
    ApimUtils $apimUtils
  ) {
    $this->consumerOrgService = $consumer_org_service;
    $this->currentUser = $account;
    $this->logger = $logger;
    $this->themeHandler = $themeHandler;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->messenger = $messenger;
    $this->apimUtils = $apimUtils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): PaymentMethodCreateForm {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('current_user'),
      $container->get('logger.channel.consumerorg'),
      $container->get('theme_handler'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('messenger'),
      $container->get('ibm_apim.apim_utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'payment_method_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $max_weight = 500;
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'core/jquery';
    $form['#attached']['library'][] = 'core/once';
    $integrationService = \Drupal::service('ibm_apim.payment_method_schema');
    $billingProviders = \Drupal::service('ibm_apim.billing')->getAll();
    $billingProvider = NULL;
    $integrations = [];

    if (!empty($billingProviders)) {
      foreach ($billingProviders as $billing_url => $billingProvider) {
        $billingProvider = \Drupal::service('ibm_apim.billing')->decrypt($billing_url);
        // get the integrations for this billing provider
        if (isset($billingProvider['payment_method_integration_urls'])) {
          foreach ($billingProvider['payment_method_integration_urls'] as $payment_method_integration_url) {
            // this uses a key based on the integration URL to prevent there being the same integration type listed twice
            $urlIdParts = explode('/', $payment_method_integration_url);
            $urlId = end($urlIdParts);
            $integration = $integrationService->getById($urlId);
            if (isset($integration)) {
              $integrations[$payment_method_integration_url] = $integration;
            }
          }
        }
      }
    }
    // Add a wrapper div that will be used by the Form API to update the form using AJAX
    $form['#prefix'] = '<div id="ajax_form_multistep_form">';
    $form['#suffix'] = '</div>';

    //TODO Re-enable this and below when support for multiple payment methods is added
    // $form['is_default'] = array(
    //   '#type' => 'checkbox',
    //   '#title' => t('Make this the default payment method?'),
    // );

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    if ($integrations === NULL || empty($integrations)) {
      // no integrations found - abort
      $this->messenger->addError(t('No billing integrations found.'));
      $form['description'] = ['#markup' => '<p>' . t('No billing integrations have been found. Please contact your system administrator.') . '</p>'];
      unset($form['actions']['submit']);
      $this->step++;
    }
    elseif ($this->step === 2) {
      // second pass through the form so use the chosen integration type
      $populatedIntegration = $integrationService->getByName($this->chosen_integration);
      $billingProvider = \Drupal::service('ibm_apim.billing')->decrypt($this->billingUrl);
      $form['title'] = [
        '#type' => 'textfield',
        '#title' => t('Payment Method Title'),
        '#required' => TRUE,
        '#description' => t('Provide a title to identify this payment method.'),
      ];

      \Drupal::moduleHandler()->alter('consumerorg_payment_method_create', $form, $populatedIntegration, $billingProvider);
    }
    elseif (count($integrations) === 1) {
      $populatedIntegration = array_shift($integrations);

      // only one integration type so just use it
      $this->step++;
      $this->chosen_integration = $populatedIntegration['name'];
      $this->getBillingProviderForIntegration();
      $billingProvider = isset($this->billingUrl) ? \Drupal::service('ibm_apim.billing')->decrypt($this->billingUrl) : '';
      $form['title'] = [
        '#type' => 'textfield',
        '#title' => t('Payment Method Title'),
        '#required' => TRUE,
        '#description' => t('Provide a title to identify this payment method.'),
      ];

      \Drupal::moduleHandler()->alter('consumerorg_payment_method_create', $form, $populatedIntegration, $billingProvider);
    }
    else {
      // more than one integration so need to prompt to allow the user to select
      unset($form['actions']['submit']);
      $form['integration_type'] = [
        '#type' => 'select',
        '#title' => t('Payment type'),
        '#description' => t('Select what type of payment method to use.'),
        '#required' => TRUE,
        '#weight' => 30,
      ];
      $form['actions']['next'] = [
        '#type' => 'submit',
        '#value' => t('Next'),
        '#attributes' => ['class' => ['button', 'btn-primary']],
        '#ajax' => [
          // We pass in the wrapper we created at the start of the form
          'wrapper' => 'ajax_form_multistep_form',
          // We pass a callback function we will use later to render the form for the user
          'callback' => '::ajax_form_multistep_form_ajax_callback',
          'event' => 'click',
        ],
      ];
      $options = [];
      foreach ($integrations as $possible_int) {
        $options[$possible_int['name']] = $possible_int['title'];
      }
      $form['integration_type']['#options'] = $options;
    }

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => $this->getCancelUrl(),
      '#attributes' => ['class' => ['button', 'apicSecondary']],
    ];
    if (!isset($form['actions']['next'], $form['actions']['submit'])) {
      $form['actions']['cancel']['#attributes']['style'] = ["margin-right:auto"];
    }

    $form['actions']['#weight'] = $max_weight + 1;
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * @return \Drupal\Core\Url
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('ibm_apim.billing');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function ajax_form_multistep_form_ajax_callback(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $integrationService = \Drupal::service('ibm_apim.payment_method_schema');
    $integration_type = $form_state->getValue('integration_type');;
    if ($this->step !== 2) {
      $this->chosen_integration = $integration_type;
      $this->step++;

      // set first billing provider that uses this integration schema
      $this->getBillingProviderForIntegration();

      $form_state->setRebuild();
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return;
    }

    // this is a generic submit handler that just takes whats in the form_state and sends it back to apim
    $userUtils = \Drupal::service('ibm_apim.user_utils');

    $org = $userUtils->getCurrentConsumerorg();

    $requestBody = ['configuration' => []];
    foreach ($form_state->getValues() as $key => $value) {
      if (!in_array($key, [
        'title',
        'billing_url',
        'integration_type',
        'is_default',
        'submit',
        'cancel',
        'form_build_id',
        'form_token',
        'form_id',
        'op',
      ])) {
        if (is_array($value) && isset($value[0]['value'])) {
          $value = $value[0]['value'];
        }
        elseif (isset($value[0]) && is_array($value[0])) {
          $value = array_values($value[0]);
        }
        $requestBody['configuration'][$key] = $value;
      }
    }

    $isDefault = (boolean) $form_state->getValue('is_default') === TRUE;
    $title = $form_state->getValue('title');
    $requestBody['title'] = $title;
    $consumerOrgUrl = $org['url'];
    $configuration = $requestBody['configuration'];
    $integration = $integrationService->getByName($this->chosen_integration);

    $requestBody['billing_url'] = $this->apimUtils->createFullyQualifiedUrl($this->billingUrl);
    $requestBody['payment_method_type_url'] = $this->apimUtils->createFullyQualifiedUrl($integration['url']);

    $response = \Drupal::service('ibm_apim.mgmtserver')->postPaymentMethod($org, $requestBody);
    if ($response !== NULL) {
      if ((int) $response->getCode() === 200 || (int) $response->getCode() === 201) {
        $paymentMethodId = $response->getData()['id'];
        $paymentMethodObject = [
          'id' => $paymentMethodId,
          'title' => $title,
          'billing_url' => $this->billingUrl,
          'payment_method_type_url' => $integration['url'],
          'org_url' => $consumerOrgUrl,
          'configuration' => $configuration,
          'url' => $response->getData()['url'],
          'updated_at' => $response->getData()['updated_at'],
          'created_at' => $response->getData()['created_at'],
        ];
        $current_user = User::load(\Drupal::currentUser()->id());
        if ($current_user !== NULL && (int)$current_user->id() !== 1) {
          // we only set the user if we're running as someone other than admin
          // if running as admin then we're likely doing things on behalf of the admin
          $paymentMethodObject['created_by'] = $current_user->get('apic_url')->value;
          $paymentMethodObject['updated_by'] = $current_user->get('apic_url')->value;
        }
        \Drupal::service('consumerorg.paymentmethod')->createOrUpdate($paymentMethodObject);
        $this->messenger->addMessage(t('Successfully added your payment method'));
      }
      else {
        \Drupal::logger('consumerorg')
          ->error('Received @code code while creating payment method.', ['@code' => (int) $response->getCode()]);
        $this->messenger->addError(t('Failed to add your payment method.'));
      }
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  private function getBillingProviderForIntegration(): void {
    $integrationService = \Drupal::service('ibm_apim.payment_method_schema');
    $billingProviders = \Drupal::service('ibm_apim.billing')->getAll();
    foreach ($billingProviders as $billing_url => $billingProvider) {
      $billingProvider = \Drupal::service('ibm_apim.billing')->decrypt($billing_url);
      // get the integrations for this billing provider, stop once found first one with this integration
      if (isset($billingProvider['payment_method_integration_urls']) && $this->billingUrl === NULL) {
        foreach ($billingProvider['payment_method_integration_urls'] as $payment_method_integration_url) {
          $urlIdParts = explode('/', $payment_method_integration_url);
          $urlId = end($urlIdParts);
          $possible_int = $integrationService->getById($urlId);
          if (isset($possible_int['name']) && $possible_int['name'] === $this->chosen_integration) {
            // found the billing provider we're looking for
            $this->billingUrl = $billing_url;
          }
        }
      }
    }
  }

}
