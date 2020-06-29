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

use Drupal\apic_app\Entity\ApplicationCredentials;
use Drupal\apic_app\Application;
use Drupal\apic_app\Event\CredentialDeleteEvent;
use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\apic_app\Service\CredentialsService;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\NodeInterface;
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
   * This represents the credential ID
   *
   * @var string
   */
  protected $credId;

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
    return 'application_delete_credentials_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL, $credId = NULL): array {
    $this->node = $appId;
    $this->credId = Html::escape($credId);
    $form = parent::buildForm($form, $form_state);
    $themeHandler = \Drupal::service('theme_handler');
    if ($themeHandler->themeExists('bootstrap')) {
      if (isset($form['actions']['submit'])) {
        $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('trash');
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
    $query = \Drupal::entityQuery('apic_app_application_creds');
    $query->condition('id', $this->credId);
    $entityIds = $query->execute();
    if (isset($entityIds) && !empty($entityIds)) {
      $cred = ApplicationCredentials::load(array_pop($entityIds));
      if (isset($cred)) {
        $question =  $this->t('Delete credentials %credentials for %title?', ['%credentials' => $cred->name(),'%title' => $this->node->title->value]);
      }
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
    $url = $appUrl . '/credentials/' . $this->credId;
    $result = $this->restService->deleteCredentials($url);
    if (isset($result) && $result->code >= 200 && $result->code < 300) {
      // update the stored app
      $this->node = $this->credsService->deleteCredentials($this->node, $this->credId);

      // Calling all modules implementing 'hook_apic_app_creds_delete':
      $moduleHandler = \Drupal::moduleHandler();
      $moduleHandler->invokeAll('apic_app_creds_delete', [
        'node' => $this->node,
        'data' => $result->data,
        'credId' => $this->credId,
      ]);
      if ($moduleHandler->moduleExists('rules')) {
        // Set the args twice on the event: as the main subject but also in the
        // list of arguments.
        $event = new CredentialDeleteEvent($this->node, $result->data, $this->credId, [
          'application' => $this->node,
          'data' => $result->data,
          'credId' => $this->credId,
        ]);
        $eventDispatcher = \Drupal::service('event_dispatcher');
        $eventDispatcher->dispatch(CredentialDeleteEvent::EVENT_NAME, $event);
      }

      $this->messenger->addMessage($this->t('Credentials deleted successfully.'));
      $currentUser = \Drupal::currentUser();
      \Drupal::logger('apic_app')->notice('Application @appName credentials deleted by @username', [
        '@appName' => $this->node->getTitle(),
        '@username' => $currentUser->getAccountName(),
      ]);
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
