<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Event\CredentialClientSecretResetEvent;
use Drupal\apic_app\Service\ApplicationRestInterface;
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
 * Form to reset an application client secret.
 */
class ResetClientSecretForm extends ConfirmFormBase {

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
  public static function create(ContainerInterface $container) {
    // Load the service required to construct this class
    return new static($container->get('apic_app.rest_service'), $container->get('ibm_apim.user_utils'), $container->get('messenger'));
  }

  /**
   * This represents the credential ID
   *
   * @var string
   */
  protected $credId;

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
    $this->node = $appId;
    $this->credId = Html::escape($credId);
    $form = parent::buildForm($form, $form_state);
    $themeHandler = \Drupal::service('theme_handler');
    if ($themeHandler->themeExists('bootstrap')) {
      if (isset($form['actions']['submit'])) {
        $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('ok');
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
    return $this->t('Are you sure you want to reset the client secret? This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() : TranslatableMarkup{
    return $this->t('Reset');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() : TranslatableMarkup{
    return $this->t('Reset the client secret for %title?', ['%title' => $this->node->title->value]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() : Url{
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
    $appId = $this->node->application_id->value;
    $url = $this->node->apic_url->value . '/credentials/' . $this->credId . '/reset-client-secret';
    $result = $this->restService->postClientSecret($url, NULL);
    if (isset($result) && $result->code >= 200 && $result->code < 300) {
      $currentUser = \Drupal::currentUser();
      \Drupal::logger('apic_app')->notice('Application @appName client secret reset by @username', [
        '@appName' => $this->node->getTitle(),
        '@username' => $currentUser->getAccountName(),
      ]);

      $data = $result->data;
      // alter hook (pre-invoke)
      \Drupal::moduleHandler()->alter('apic_app_modify_client_secret_reset', $data, $appId);

      $clientSecretHtml = \Drupal\Core\Render\Markup::create('<div class="toggleParent"><div id="app_secret" class="appSecretReset bx--form-item js-form-item form-item js-form-type-textfield form-type-password js-form-item-password form-item-password form-group"><input class="form-control toggle" id="client_secret" type="password" readonly value="' . $data['client_secret'] . '"></div>
      <div class="password-toggle bx--form-item js-form-item form-item js-form-type-checkbox form-type-checkbox checkbox"><label title="" data-toggle="tooltip" class="bx--label option" data-original-title=""><input class="form-checkbox bx--checkbox" type="checkbox"><span class="bx--checkbox-appearance"><svg class="bx--checkbox-checkmark" width="12" height="9" viewBox="0 0 12 9" fill-rule="evenodd"><path d="M4.1 6.1L1.4 3.4 0 4.9 4.1 9l7.6-7.6L10.3 0z"></path></svg></span><span class="children"> ' . t('Show') . '</span></label></div></div>');
      $this->messenger->addMessage(t('Your new client secret is: @html', [
        '@html' => $clientSecretHtml,
      ]));

      // Calling all modules implementing 'hook_apic_app_clientsecret_reset':
      $moduleHandler = \Drupal::service('module_handler');
      $moduleHandler->invokeAll('apic_app_clientsecret_reset', [
        'node' => $this->node,
        'data' => $data,
        'appId' => $appId,
        'credId' => $this->credId,
      ]);

      if ($moduleHandler->moduleExists('rules')) {
        // Set the args twice on the event: as the main subject but also in the
        // list of arguments.
        $event = new CredentialClientSecretResetEvent($this->node, ['application' => $this->node]);
        $eventDispatcher = \Drupal::service('event_dispatcher');
        $eventDispatcher->dispatch(CredentialClientSecretResetEvent::EVENT_NAME, $event);
      }
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
