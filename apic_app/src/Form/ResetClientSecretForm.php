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

use Drupal\apic_app\Entity\ApplicationCredentials;
use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_event_log\ApicType\ApicEvent;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to reset an application client secret.
 */
class ResetClientSecretForm extends ConfirmFormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * This represents the credential object
   *
   * @var \Drupal\apic_app\Entity\ApplicationCredentials
   */
  protected ApplicationCredentials $cred;

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
   * ResetClientSecretForm constructor.
   *
   * @param \Drupal\apic_app\Service\ApplicationRestInterface $restService
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(ApplicationRestInterface $restService, UserUtils $userUtils, Messenger $messenger) {
    $this->restService = $restService;
    $this->userUtils = $userUtils;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ResetClientSecretForm {
    // Load the service required to construct this class
    return new static($container->get('apic_app.rest_service'), $container->get('ibm_apim.user_utils'), $container->get('messenger'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'application_reset_clientsecret_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL, $credId = NULL): array {
    if ($appId !== NULL) {
      $this->node = $appId;
    }
    if ($credId !== NULL) {
      $this->cred = $credId;
    }

    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'apic_app/basic';

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Are you sure you want to reset the client secret? This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Reset');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Reset the client secret for %title?', ['%title' => $this->node->title->value]);
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
    $appId = $this->node->application_id->value;
    $url = $this->node->apic_url->value . '/credentials/' . $this->cred->uuid() . '/reset-client-secret';
    $result = $this->restService->postClientSecret($url, '');
    if (isset($result) && $result->code >= 200 && $result->code < 300) {
      $resultData = $result->data;
      $currentUser = \Drupal::currentUser();
      \Drupal::logger('apic_app')->notice('Application @appName client secret reset by @username', [
        '@appName' => $this->node->getTitle(),
        '@username' => $currentUser->getAccountName(),
      ]);

      $data = $result->data;
      // alter hook (pre-invoke)
      \Drupal::moduleHandler()->alter('apic_app_modify_client_secret_reset', $data, $appId);

      // Add Activity Feed Event Log
      $eventEntity = new ApicEvent();
      $eventEntity->setArtifactType('credential');
      if ($currentUser->isAuthenticated() && (int) $currentUser->id() !== 1) {
        $current_user = User::load($currentUser->id());
        if ($current_user !== NULL) {
          // we only set the user if we're running as someone other than admin
          // if running as admin then we're likely doing things on behalf of the admin
          // TODO we might want to check if there is a passed in user_url and use that too
          $eventEntity->setUserUrl($current_user->get('apic_url')->value);
        }
      }
      $timestamp = $resultData['updated_at'];
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
      $eventEntity->setEvent('resetSecret');
      $eventEntity->setArtifactUrl($this->node->apic_url->value . '/credentials/' . $this->cred->uuid());
      $eventEntity->setAppUrl($this->node->apic_url->value);
      $eventEntity->setConsumerOrgUrl($this->node->application_consumer_org_url->value);
      $utils = \Drupal::service('ibm_apim.utils');
      $appTitle = $utils->truncate_string($this->node->getTitle());
      $eventEntity->setData(['name' => $this->cred->name(), 'appName' => $appTitle]);
      $eventLogService = \Drupal::service('ibm_apim.event_log');
      $eventLogService->createIfNotExist($eventEntity);

      // Calling all modules implementing 'hook_apic_app_clientsecret_reset':
      $moduleHandler = \Drupal::service('module_handler');
      $moduleHandler->invokeAll('apic_app_clientsecret_reset', [
        'node' => $this->node,
        'data' => $data,
        'appId' => $appId,
        'credId' => $this->cred->uuid(),
      ]);

      $credsString = base64_encode(json_encode($data, JSON_THROW_ON_ERROR));
      $displayCredsUrl = Url::fromRoute('apic_app.display_creds', ['appId' => $appId, 'credentials' => $credsString]);
    }
    else {
      $displayCredsUrl = $this->getCancelUrl();
      $this->messenger->addError($this->t('Failed to reset client secret.'));
      \Drupal::logger('apic_app')->notice('Received @code when trying to delete credential.', [
        '@code' => $result->code,
      ]);
    }
    $form_state->setRedirectUrl($displayCredsUrl);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
