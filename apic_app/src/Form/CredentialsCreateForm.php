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

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\apic_app\Service\CredentialsService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_event_log\ApicType\ApicEvent;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to create new application credentials.
 */
class CredentialsCreateForm extends FormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * @var \Drupal\apic_app\Service\ApplicationRestInterface
   */
  protected ApplicationRestInterface $restService;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\apic_app\Service\CredentialsService
   */
  protected CredentialsService $credsService;

  /**
   * CredentialsCreateForm constructor.
   *
   * @param \Drupal\apic_app\Service\ApplicationRestInterface $restService
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\apic_app\Service\CredentialsService $credsService
   */
  public function __construct(ApplicationRestInterface $restService, UserUtils $userUtils, Messenger $messenger, CredentialsService $credsService) {
    $this->restService = $restService;
    $this->userUtils = $userUtils;
    $this->messenger = $messenger;
    $this->credsService = $credsService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): CredentialsCreateForm {
    // Load the service required to construct this class
    return new static($container->get('apic_app.rest_service'),
      $container->get('ibm_apim.user_utils'),
      $container->get('messenger'),
      $container->get('apic_app.credentials'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'application_create_credentials_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($appId !== NULL) {
      $this->node = $appId;
    }
    $moduleHandler = \Drupal::service('module_handler');

    $form['intro'] = [
      '#markup' => '<p>' . t('It is possible to have multiple sets of credentials per Application. For example this could enable the revocation of one set of credentials and migration to a new set in a managed fashion.') . '</p>',
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#required' => TRUE,
    ];
    $form['summary'] = [
      '#type' => 'textfield',
      '#title' => t('Summary'),
      '#required' => FALSE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => $this->getCancelUrl(),
      '#attributes' => ['class' => ['button', 'apicSecondary']],
    ];

    $form['#attached']['library'][] = 'apic_app/basic';
    if ($moduleHandler->moduleExists('clipboardjs')) {
      $form['#attached']['library'][] = 'clipboardjs/drupal';
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    $analytics_service = \Drupal::service('ibm_apim.analytics')->getDefaultService();
    if (isset($analytics_service) && $analytics_service->getClientEndpoint() !== NULL) {
      $url = Url::fromRoute('apic_app.subscriptions', ['node' => $this->node->id()]);
    }
    else {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $this->node->id()]);
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   * @throws \JsonException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $appUrl = $this->node->apic_url->value;

    $title = $form_state->getValue('title');
    $summary = $form_state->getValue('summary');

    $url = $appUrl . '/credentials';
    $data = ['title' => $title, 'summary' => $summary];
    $result = $this->restService->postCredentials($url, json_encode($data, JSON_THROW_ON_ERROR));
    if (isset($result) && $result->code >= 200 && $result->code < 300) {
      $currentUser = \Drupal::currentUser();
      \Drupal::logger('apic_app')->notice('New credentials created for application @appName by @username', [
        '@appName' => $this->node->getTitle(),
        '@username' => $currentUser->getAccountName(),
      ]);

      $data = $result->data;
      // alter hook (pre-invoke)
      \Drupal::moduleHandler()->alter('apic_app_modify_credentials_create', $data, $appId);

      // update the stored app with the additional creds
      $newCred = [
        'id' => $data['id'],
        'client_id' => $data['client_id'],
        'summary' => $data['summary'],
        'name' => $data['name'],
        'title' => $data['title'],
      ];
      if (isset($data['url'])) {
        $newCred['url'] = \Drupal::service('ibm_apim.apim_utils')->removeFullyQualifiedUrl($data['url']);
      }
      if (isset($data['app_url'])) {
        $newCred['app_url'] = \Drupal::service('ibm_apim.apim_utils')->removeFullyQualifiedUrl($data['app_url']);
      }
      if (isset($data['org_url'])) {
        $newCred['consumerorg_url'] = \Drupal::service('ibm_apim.apim_utils')
          ->removeFullyQualifiedUrl($data['org_url']);
      }
      else {
        $org = $this->userUtils->getCurrentConsumerorg();
        $newCred['consumerorg_url'] = $org['url'];
      }
      $this->node = $this->credsService->createOrUpdateSingleCredential($this->node, $newCred);

      // Add Activity Feed Event Log
      $eventEntity = new ApicEvent();
      $eventEntity->setArtifactType('credential');
      if (\Drupal::currentUser()->isAuthenticated() && (int) \Drupal::currentUser()->id() !== 1) {
        $current_user = User::load(\Drupal::currentUser()->id());
        if ($current_user !== NULL) {
          // we only set the user if we're running as someone other than admin
          // if running as admin then we're likely doing things on behalf of the admin
          // TODO we might want to check if there is a passed in user_url and use that too
          $eventEntity->setUserUrl($current_user->get('apic_url')->value);
        }
      }
      $timestamp = $data['created_at'];
      // if timestamp still not set default to current time
      if ($timestamp === NULL) {
        $timestamp = time();
      }
      else {
        // if it is set then ensure its epoch not a string
        // intentionally done this way round since strtotime on null might lead to odd effects
        $timestamp = strtotime($timestamp);
      }
      $eventEntity->setTimestamp((int) $timestamp);
      $eventEntity->setEvent('create');
      $eventEntity->setArtifactUrl($newCred['url']);
      $eventEntity->setAppUrl($newCred['app_url']);
      $eventEntity->setConsumerOrgUrl($newCred['consumerorg_url']);
      $utils = \Drupal::service('ibm_apim.utils');
      $appTitle = $utils->truncate_string($this->node->getTitle());
      $eventEntity->setData(['name' => $newCred['title'], 'appName' => $appTitle]);
      $eventLogService = \Drupal::service('ibm_apim.event_log');
      $eventLogService->createIfNotExist($eventEntity);

      // Calling all modules implementing 'hook_apic_app_creds_create':
      $moduleHandler = \Drupal::moduleHandler();
      $moduleHandler->invokeAll('apic_app_creds_create', [
        'node' => $this->node,
        'data' => $result->data,
        'credId' => $data['id'],
      ]);

      $credsJson = json_encode($data, JSON_THROW_ON_ERROR);
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('encrypt')) {
        $ibmApimConfig = \Drupal::config('ibm_apim.settings');
        $encryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
        if (isset($encryptionProfileName)) {
          $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($encryptionProfileName);
          if ($encryptionProfile !== NULL) {
            $encryptionService = \Drupal::service('encryption');
            $credsString = $encryptionService->encrypt($credsJson, $encryptionProfile);
          }
        } else {
          \Drupal::logger('apic_app')->warning('createCredentials: No encryption profile set', []);
          $credsString = base64_encode($credsJson);
        }
      } else {
        $credsString = base64_encode($credsJson);
      }
      $displayCredsUrl = Url::fromRoute('apic_app.display_creds', [
        'appId' => $this->node->application_id->value,
        'credentials' => $credsString,
      ]);
      \Drupal::service('apic_app.application')->invalidateCaches();
    }
    else {
      $displayCredsUrl = $this->getCancelUrl();
    }
    $form_state->setRedirectUrl($displayCredsUrl);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
