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

namespace Drupal\ibm_apim\Controller;

use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\Billing;
use Drupal\ibm_apim\Service\MyOrgService;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MyOrgController extends ControllerBase {

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\Billing
   */
  protected $billingService;

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected $siteConfig;

  /**
   * @var \Drupal\ibm_apim\Service\MyOrgService
   */
  protected $orgService;

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected $consumerOrgService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(UserUtils $userUtils,
                              Billing $billingService,
                              ConfigFactory $config_factory,
                              SiteConfig $site_config,
                              MyOrgService $org_service,
                              ConsumerOrgService $consumerOrgService,
                              EntityTypeManagerInterface $entityTypeManager) {
    $this->userUtils = $userUtils;
    $this->billingService = $billingService;
    $this->config = $config_factory->get('ibm_apim.settings');
    $this->siteConfig = $site_config;
    $this->orgService = $org_service;
    $this->consumerOrgService = $consumerOrgService;
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.billing'),
      $container->get('config.factory'),
      $container->get('ibm_apim.site_config'),
      $container->get('ibm_apim.myorgsvc'),
      $container->get('ibm_apim.consumerorg'),
      $container->get('entity_type.manager')
    );
  }

  public function content(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $nid = NULL;

    $org = $this->userUtils->getCurrentConsumerorg();
    // load the current consumerorg node to pass through to the twig template
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $org['url']);
    $nids = $query->execute();
    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
    }

    if ($nid === NULL || empty($nid)) {
      // the user is not in any orgs. send them somewhere else.
      // if onboarding is enabled, we can redirect to the create org page
      if ($this->siteConfig->isSelfOnboardingEnabled()) {
        $response = new RedirectResponse(Url::fromRoute('consumerorg.create')->toString());
      }
      else {
        // we can't help the user, they need to talk to an administrator
        $response = new RedirectResponse(Url::fromRoute('ibm_apim.noperms')->toString());
      }
      if (isset($response)) {
        $response->send();
      }
    }
    $nodeArray = ['id' => $nid];

    $tabs = [];
    // tabs should be an array of additional tabs, eg. [{'title' => 'tab title', 'path' => '/tab/path'}, ... ]
    \Drupal::moduleHandler()->alter('consumerorg_myorg_tabs', $tabs, $nodeArray);

    $current_user = \Drupal::currentUser();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return [
      '#cache' => [
        'tags' => ['myorg:url:' . $org['url'], 'user:' . $current_user->id()],
      ],
      '#theme' => 'ibm_apim_myorg',
      '#node' => $nodeArray,
      '#tabs' => $tabs,
    ];
  }

  /**
   * Allow consumerorg owner to set billing info for monetization
   */
  public function billing(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $nid = NULL;
    $node = NULL;

    $org = $this->userUtils->getCurrentConsumerorg();
    // load the current consumerorg node to pass through to the twig template
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $org['url']);
    $nids = $query->execute();
    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
    }

    if ($nid === NULL || empty($nid) || $node === NULL) {
      // the user is not in any orgs. send them somewhere else.
      // if onboarding is enabled, we can redirect to the create org page
      if ($this->siteConfig->isSelfOnboardingEnabled()) {
        $response = new RedirectResponse(Url::fromRoute('consumerorg.create')->toString());
      }
      else {
        // we can't help the user, they need to talk to an administrator
        $response = new RedirectResponse(Url::fromRoute('ibm_apim.noperms')->toString());
      }
      if (isset($response)) {
        $response->send();
      }
    }
    $config = \Drupal::config('ibm_apim.settings');
    $ibmApimShowPlaceholderImages = (boolean) $config->get('show_placeholder_images');
    if ($ibmApimShowPlaceholderImages === NULL) {
      $ibmApimShowPlaceholderImages = TRUE;
    }

    $payment_methods = $node->consumerorg_payment_method_refs->referencedEntities();
    $paymentMethodService = \Drupal::service('consumerorg.paymentmethod');
    $integrationService = \Drupal::service('ibm_apim.payment_method_schema');
    $currentLang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $localeStorage = \Drupal::service('locale.storage');
    $paymentMethodArray = [];
    foreach ($payment_methods as $key => $payment_method) {
      $paymentMethodArray[$key] = $payment_method->toArray();
      $paymentMethodConfiguration = $paymentMethodService->decryptConfiguration($payment_method->configuration());
      // get the integration name and title
      $paymentMethodArray[$key]['configuration'] = [];
      $paymentMethodArray[$key]['raw_configuration'] = $paymentMethodConfiguration;
      $integration = $integrationService->get($payment_method->payment_method_type_url());
      foreach ($integration['configuration_schema'] as $fieldKey => $field) {
        if ($fieldKey !== 'required') {
          if (isset($field['type']) && ((!array_key_exists('x-ibm-display', $field) || $field['x-ibm-display'] === TRUE) ||
              (!array_key_exists('x-ibm-display-card', $field) || $field['x-ibm-display-card'] === TRUE))) {
            $fieldTitle = $field['x-ibm-label'] ?? $fieldKey;
            // have to look up translation manually since not allowed to do t() with variables
            // the form should already be cached per language so if you change language this should be re-evaluated
            $translatedFieldTitle = $localeStorage->findTranslation(['source' => $fieldTitle, 'language' => $currentLang]);
            if ($translatedFieldTitle !== NULL && $translatedFieldTitle->translation !== NULL) {
              $fieldTitle = $translatedFieldTitle->translation;
            }
            if (array_key_exists($fieldKey, $paymentMethodConfiguration)) {
              $paymentMethodArray[$key]['configuration'][$fieldKey] = [
                'title' => $fieldTitle,
                'value' => $paymentMethodConfiguration[$fieldKey],
              ];
            }
          }
        }
      }
      $paymentMethodArray[$key]['payment_type'] = [
        'name' => $integration['name'],
        'title' => $integration['title'],
        'configuration_schema' => $integration['configuration_schema'],
      ];
      if ($ibmApimShowPlaceholderImages) {
        $placeholderUrl = $paymentMethodService->getPlaceholderImageURL($payment_method->title());
        $paymentMethodArray[$key]['placeholderImageUrl'] = $placeholderUrl;
      }
      else {
        $paymentMethodArray[$key]['placeholderImageUrl'] = NULL;
      }
    }

    $nodeArray = [
      'id' => $nid,
      'title' => $node->getTitle(),
      'url' => $node->consumerorg_url->value,
      'payment_methods' => $paymentMethodArray,
    ];

    $current_user = \Drupal::currentUser();

    $tabs = [];
    // tabs should be an array of additional tabs, eg. [{'title' => 'tab title', 'path' => '/tab/path'}, ... ]
    \Drupal::moduleHandler()->alter('consumerorg_myorg_tabs', $tabs, $nodeArray);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return [
      '#cache' => [
        'tags' => ['myorg:url:' . $org['url'], 'user:' . $current_user->id()],
      ],
      '#theme' => 'consumerorg_billing',
      '#node' => $nodeArray,
      '#consumerorgTitle' => $node->getTitle(),
      '#consumerorgId' => $node->consumerorg_id->value,
      '#tabs' => $tabs,
      '#showPlaceholders' => $ibmApimShowPlaceholderImages,
    ];
  }

}
