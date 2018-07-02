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

use Drupal\apic_app\Event\CredentialUpdateEvent;
use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to update an application credential's description.
 */
class CredentialsUpdateForm extends FormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * This represents the credential ID
   *
   * @var string
   */
  protected $credId;

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
    return 'application_update_credentials_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL, $credId = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $this->node = $appId;
    $this->credId = Html::escape($credId);

    $form['intro'] = ['#markup' => '<p>' . t('Use this form to update an existing set of credentials for this application.') . '</p>'];

    $description = '';
    $creds = array();
    foreach($this->node->application_credentials->getValue() as $arrayValue){
      $creds[] = unserialize($arrayValue['value']);
    }
    foreach ($creds as $key => $existingcred) {
      if (isset($existingcred['id']) && $existingcred['id'] == $this->credId) {
        $summary = $existingcred['summary'];
        $title = $existingcred['title'];
      }
    }

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#required' => FALSE,
      '#default_value' => $title,
    ];

    $form['summary'] = [
      '#type' => 'textfield',
      '#title' => t('Summary'),
      '#required' => FALSE,
      '#default_value' => $summary,
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
    $org = $this->userUtils->getCurrentConsumerOrg();
    $appUrl = $this->node->apic_url->value;
    $title = $form_state->getValue('title');
    $summary = $form_state->getValue('summary');
    $url = $appUrl. '/credentials/' . $this->credId;
    $data = ["title"=> $title, "summary" => $summary];
    $result = $this->restService->patchCredentials($url, json_encode($data));
    if (isset($result) && $result->code >= 200 && $result->code < 300) {
      drupal_set_message(t('Application credentials updated.'));
      // update the stored app with the new creds
      if (!empty($this->node->application_credentials->getValue())) {
        $existingcreds = array();
        foreach($this->node->application_credentials->getValue() as $arrayValue){
          $existingcreds[] = unserialize($arrayValue['value']);
        }
        $this->node->set('application_credentials', array());
        foreach ($existingcreds as $existingcred) {
          if (isset($existingcred['id']) && $existingcred['id'] == $this->credId) {
            $existingcred['summary'] = $summary;
            $existingcred['title'] = $title;
          }
          $this->node->application_credentials[] = serialize($existingcred);
        }
        if (!empty($existingcreds)) {
          $this->node->save();
        }
      }
      $current_user = \Drupal::currentUser();
      \Drupal::logger('apic_app')->notice('Application @appname credentials updated by @username', [
        '@appname' => $this->node->getTitle(),
        '@username' => $current_user->getAccountName(),
      ]);

      // Calling all modules implementing 'hook_apic_app_creds_update':
      $moduleHandler = \Drupal::moduleHandler();
      $moduleHandler->invokeAll('apic_app_creds_update', [
        'node' => $this->node,
        'data' => $result->data,
        'credId' => $this->credId
      ]);

      if ($moduleHandler->moduleExists('rules')) {
        // Set the args twice on the event: as the main subject but also in the
        // list of arguments.
        $event = new CredentialUpdateEvent($this->node, ['application' => $this->node]);
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch(CredentialUpdateEvent::EVENT_NAME, $event);
      }
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
