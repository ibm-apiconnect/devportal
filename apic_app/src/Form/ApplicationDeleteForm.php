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

use Drupal\apic_app\Service\ApplicationService;
use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_event_log\ApicType\ApicEvent;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remove form for applications.
 */
class ApplicationDeleteForm extends ConfirmFormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\apic_app\Service\ApplicationRestInterface
   */
  protected ApplicationRestInterface $restService;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * @var \Drupal\Core\Extension\ThemeHandler
   */
  protected ThemeHandler $themeHandler;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected ModuleHandler $moduleHandler;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\apic_app\Service\ApplicationService
   */
  protected ApplicationService $applicationService;

  /**
   * ApplicationDeleteForm constructor.
   *
   * @param \Drupal\apic_app\Service\ApplicationRestInterface $restService
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\apic_app\Service\ApplicationService $applicationService
   */
  public function __construct(ApplicationRestInterface $restService, UserUtils $userUtils, AccountProxyInterface $current_user, ThemeHandler $themeHandler, ModuleHandler $moduleHandler, Messenger $messenger, ApplicationService $applicationService) {
    $this->restService = $restService;
    $this->userUtils = $userUtils;
    $this->currentUser = $current_user;
    $this->themeHandler = $themeHandler;
    $this->moduleHandler = $moduleHandler;
    $this->messenger = $messenger;
    $this->applicationService = $applicationService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ApplicationDeleteForm {
    // Load the service required to construct this class
    return new static(
      $container->get('apic_app.rest_service'),
      $container->get('ibm_apim.user_utils'),
      $container->get('current_user'),
      $container->get('theme_handler'),
      $container->get('module_handler'),
      $container->get('messenger'),
      $container->get('apic_app.application')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'application_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL): array {
    if ($appId !== NULL) {
      $this->node = $appId;
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
    return $this->t('Are you sure you want to delete this application? This action cannot be undone.');
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
    return $this->t('Delete application %title?', ['%title' => $this->node->title->value]);
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getCancelUrl(): Url {
    return $this->node->toUrl();
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $appId = $this->node->application_id->value;
    $url = $this->node->apic_url->value;
    $result = $this->restService->deleteApplication($url);
    if ($result !== NULL && $result->code >= 200 && $result->code < 300) {
      // create copy of the node we're deleting for use in hooks later
      $node = $this->node;

      \Drupal::logger('apic_app')->notice('Application @appName deleted by @username', [
        '@appName' => $node->getTitle(),
        '@username' => $this->currentUser->getAccountName(),
      ]);

      // also delete the node from the drupal DB too
      $this->applicationService->deleteNode($this->node->id(), 'delete');
      // Calling all modules implementing 'hook_apic_app_delete':
      // NOTE: This hook is being deprecated in favour of apic_app_pre_delete and
      //       apic_app_post_delete. This is being done because this happens too late
      //       and is not called consistently, i.e. not from webhooks only the ui.
      $description = 'The apic_app_delete hook is deprecated and will be removed. Please use the apic_app_pre_delete or apic_app_post_delete hook instead.';
      $this->moduleHandler->invokeAllDeprecated($description, 'apic_app_delete', [
        'node' => $node,
        'data' => $result->data,
        'appId' => $appId,
      ]);

      $eventEntity = new ApicEvent();
      $eventEntity->setArtifactType('application');
      if (\Drupal::currentUser()->isAuthenticated() && (int) \Drupal::currentUser()->id() !== 1) {
        $current_user = User::load(\Drupal::currentUser()->id());
        if ($current_user !== NULL) {
          $eventEntity->setUserUrl($current_user->get('apic_url')->value);
        }
      }
      $eventEntity->setTimestamp(time());
      $eventEntity->setEvent('delete');
      $eventEntity->setArtifactUrl($url);
      $eventEntity->setAppUrl($url);
      $eventEntity->setConsumerOrgUrl($this->node->application_consumer_org_url->value);
      $utils = \Drupal::service('ibm_apim.utils');
      $appTitle = $utils->truncate_string($this->node->getTitle());
      $eventEntity->setData(['name' => $appTitle]);
      $eventLogService = \Drupal::service('ibm_apim.event_log');
      $eventLogService->createIfNotExist($eventEntity);

      \Drupal::service('apic_app.application')->invalidateCaches();

      $this->messenger->addMessage($this->t('Application deleted successfully.'));
      $form_state->setRedirectUrl(Url::fromRoute('view.applications.page_1'));
    }
    else {
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
