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

use Drupal\apic_app\Event\CredentialCreateEvent;
use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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

  protected $restService;

  protected $userUtils;

  /**
   * ApplicationCreateForm constructor.
   *
   * @param ApplicationRestInterface $restService
   * @param UserUtils $userUtils
   */
  public function __construct(ApplicationRestInterface $restService, UserUtils $userUtils) {
    $this->restService = $restService;
    $this->userUtils = $userUtils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Load the service required to construct this class
    return new static($container->get('apic_app.rest_service'), $container->get('ibm_apim.user_utils'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'application_create_credentials_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $this->node = $appId;

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
      '#value' => t('Submit'),
    ];
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => $this->getCancelUrl(),
      '#attributes' => ['class' => ['button', 'apicSecondary']]
    );
    $form['#attached']['library'][] = 'apic_app/basic';
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $analytics_service = \Drupal::service('ibm_apim.analytics')->getDefaultService();
    if (isset($analytics_service) && $analytics_service->getClientEndpoint() !== NULL) {
      return Url::fromRoute('apic_app.subscriptions', ['node' => $this->node->id()]);
    }
    else {
      return Url::fromRoute('entity.node.canonical', ['node' => $this->node->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $appUrl = $this->node->apic_url->value;

    $title = $form_state->getValue('title');
    $summary = $form_state->getValue('summary');

    $url = $appUrl . '/credentials';
    $data = ["title" => $title, "summary" => $summary];
    $result = $this->restService->postCredentials($url, json_encode($data));
    if (isset($result) && $result->code >= 200 && $result->code < 300) {
      $current_user = \Drupal::currentUser();
      \Drupal::logger('apic_app')->notice('New credentials created for application @appname by @username', [
        '@appname' => $this->node->getTitle(),
        '@username' => $current_user->getAccountName(),
      ]);

      $data = $result->data;
      // alter hook (pre-invoke)
      \Drupal::moduleHandler()->alter('apic_app_credentials_create', $appId, $data);

      $clientIDHtml = '<div class="toggleParent"><div id="app_id" class="appID bx--form-item js-form-item form-item js-form-type-textfield form-type-password js-form-item-password form-item-password form-group"><input class="form-control toggle" id="client_id" type="password" readonly value="' . $data['client_id'] . '"></div>
        <div class="password-toggle bx--form-item js-form-item form-item js-form-type-checkbox form-type-checkbox checkbox"><label title="" data-toggle="tooltip" class="bx--label option" data-original-title=""><input class="form-checkbox bx--checkbox" type="checkbox"><span class="bx--checkbox-appearance"><svg class="bx--checkbox-checkmark" width="12" height="9" viewBox="0 0 12 9" fill-rule="evenodd"><path d="M4.1 6.1L1.4 3.4 0 4.9 4.1 9l7.6-7.6L10.3 0z"></path></svg></span><span class="children"> ' . t('Show') . '</span></label></div></div>';
      $clientIDHtml = \Drupal\Core\Render\Markup::create($clientIDHtml);
      drupal_set_message(t('Your client ID is: @html', [
        '@html' => $clientIDHtml,
      ]));

      $clientSecretHtml = '<div class="toggleParent"><div id="app_secret" class="appSecret bx--form-item js-form-item form-item js-form-type-textfield form-type-password js-form-item-password form-item-password form-group"><input class="form-control toggle" id="clientSecret" type="password" readonly value="' . $data['client_secret'] . '"></div>
        <div class="password-toggle bx--form-item js-form-item form-item js-form-type-checkbox form-type-checkbox checkbox"><label title="" data-toggle="tooltip" class="bx--label option" data-original-title=""><input class="form-checkbox bx--checkbox" type="checkbox"><span class="bx--checkbox-appearance"><svg class="bx--checkbox-checkmark" width="12" height="9" viewBox="0 0 12 9" fill-rule="evenodd"><path d="M4.1 6.1L1.4 3.4 0 4.9 4.1 9l7.6-7.6L10.3 0z"></path></svg></span><span class="children"> ' . t('Show') . '</span></label></div></div>';
      $clientSecretHtml = \Drupal\Core\Render\Markup::create($clientSecretHtml);
      drupal_set_message(t('Your client secret is: @html', [
        '@html' => $clientSecretHtml,
      ]));

      // update the stored app with the additional creds
      $existingcreds = [];
      if (!empty($this->node->application_credentials->getValue())) {
        foreach ($this->node->application_credentials->getValue() as $arrayValue) {
          $unserialized = unserialize($arrayValue['value']);
          if (!isset($unserialized['id']) || !isset($data['id']) || $unserialized['id'] != $data['id']) {
            $existingcreds[] = $unserialized;
          }
        }
      }
      $newcred = [
        'id' => $data['id'],
        'client_id' => $data['client_id'],
        'summary' => $data['summary'],
        'name' => $data['name'],
        'title' => $data['title']
      ];
      if (isset($data['url'])) {
        $newcred['url'] = \Drupal::service('ibm_apim.apim_utils')->removeFullyQualifiedUrl($data['url']);
      }
      if (isset($data['app_url'])) {
        $newcred['app_url'] = \Drupal::service('ibm_apim.apim_utils')->removeFullyQualifiedUrl($data['app_url']);
      }
      if (isset($data['consumer_org_url'])) {
        $newcred['consumer_org_url'] = \Drupal::service('ibm_apim.apim_utils')
          ->removeFullyQualifiedUrl($data['consumer_org_url']);
      }
      $existingcreds[] = $newcred;
      $newcreds = [];
      foreach ($existingcreds as $nextCred) {
        $newcreds[] = serialize($nextCred);
      }
      $this->node->set('application_credentials', $newcreds);
      $this->node->save();
      // Calling all modules implementing 'hook_apic_app_creds_create':
      $moduleHandler = \Drupal::moduleHandler();
      $moduleHandler->invokeAll('apic_app_creds_create', [
        'node' => $this->node,
        'data' => $result->data,
        'credId' => $data['id']
      ]);

      if ($moduleHandler->moduleExists('rules')) {
        // Set the args twice on the event: as the main subject but also in the
        // list of arguments.
        $event = new CredentialCreateEvent($this->node, $result->data, $data['id'], [
          'application' => $this->node,
          'data' => $result->data,
          'credId' => $data['id']
        ]);
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch(CredentialCreateEvent::EVENT_NAME, $event);
      }
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
