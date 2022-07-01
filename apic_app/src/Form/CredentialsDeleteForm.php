<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Entity\ApplicationCredentials;
use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\apic_app\Service\CredentialsService;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_event_log\ApicType\ApicEvent;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remove form for application credentials.
 */
class CredentialsDeleteForm extends ConfirmFormBase {

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
   * This represents the credential object
   *
   * @var \Drupal\apic_app\Entity\ApplicationCredentials
   */
  protected ApplicationCredentials $cred;

  /**
   * CredentialsDeleteForm constructor.
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
  public static function create(ContainerInterface $container): CredentialsDeleteForm {
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
    return 'application_delete_credentials_form';
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
    return $this->t('Are you sure you want to delete these credentials? This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    $question = $this->t('Delete credentials for %title?', ['%title' => $this->node->title->value]);
    if (isset($this->cred)) {
      $question = $this->t('Delete credentials %credentials for %title?', [
        '%credentials' => $this->cred->name(),
        '%title' => $this->node->title->value,
      ]);
    }
    return $question;
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
    $url = $appUrl . '/credentials/' . $this->cred->uuid();
    $result = $this->restService->deleteCredentials($url);
    if (isset($result) && $result->code >= 200 && $result->code < 300) {
      // update the stored app
      $this->credsService->deleteCredentials($this->cred->uuid());
      // Reload the node to pick up the updated list of credentials
      $this->node = Node::load($this->node->id());

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
      $eventEntity->setTimestamp(time());
      $eventEntity->setEvent('delete');
      $eventEntity->setArtifactUrl($url);
      $eventEntity->setAppUrl($appUrl);
      $eventEntity->setConsumerOrgUrl($this->node->application_consumer_org_url->value);
      $utils = \Drupal::service('ibm_apim.utils');
      $appTitle = $utils->truncate_string($this->node->getTitle());
      $eventEntity->setData(['name' => $this->cred->name(), 'appName' => $appTitle]);
      $eventLogService = \Drupal::service('ibm_apim.event_log');
      $eventLogService->createIfNotExist($eventEntity);

      // Calling all modules implementing 'hook_apic_app_creds_delete':
      $moduleHandler = \Drupal::moduleHandler();
      $moduleHandler->invokeAll('apic_app_creds_delete', [
        'node' => $this->node,
        'data' => $result->data,
        'credId' => $this->cred->uuid(),
      ]);

      \Drupal::service('apic_app.application')->invalidateCaches();

      $this->messenger->addMessage($this->t('Credentials deleted successfully.'));
      $currentUser = \Drupal::currentUser();
      \Drupal::logger('apic_app')->notice('Application @appName credentials deleted by @username', [
        '@appName' => $this->node->getTitle(),
        '@username' => $currentUser->getAccountName(),
      ]);
    }
    else {
      $this->messenger->addError($this->t('Failed to delete credentials.'));
      \Drupal::logger('apic_app')->notice('Received @code when trying to delete credential.', [
        '@code' => $result->code,
      ]);
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
