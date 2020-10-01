<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Event\CredentialCreateEvent;
use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\apic_app\Service\CredentialsService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\NodeInterface;
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
  protected $node;

  /**
   * @var \Drupal\apic_app\Service\ApplicationRestInterface
   */
  protected $restService;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected $userUtils;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\apic_app\Service\CredentialsService
   */
  protected $credsService;

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
  public static function create(ContainerInterface $container) {
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
    $this->node = $appId;
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
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $appUrl = $this->node->apic_url->value;

    $title = $form_state->getValue('title');
    $summary = $form_state->getValue('summary');

    $url = $appUrl . '/credentials';
    $data = ['title' => $title, 'summary' => $summary];
    $result = $this->restService->postCredentials($url, json_encode($data));
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

      // Calling all modules implementing 'hook_apic_app_creds_create':
      $moduleHandler = \Drupal::moduleHandler();
      $moduleHandler->invokeAll('apic_app_creds_create', [
        'node' => $this->node,
        'data' => $result->data,
        'credId' => $data['id'],
      ]);

      $credsString = base64_encode(json_encode($data));
      $displayCredsUrl = Url::fromRoute('apic_app.display_creds', ['appId' => $this->node->application_id->value, 'credentials' => $credsString]);
    } else {
      $displayCredsUrl = $this->getCancelUrl();
    }
    $form_state->setRedirectUrl($displayCredsUrl);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
