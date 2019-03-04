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

namespace Drupal\consumerorg\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete user invitation form for consumerorg members.
 */
class DeleteInviteForm extends ConfirmFormBase {

  /**
   * The invitation to delete
   *
   * @var object
   */
  protected $invite;

  protected $consumerOrgService;

  protected $currentOrg;

  protected $userUtils;

  protected $themeHandler;

  /**
   * DeleteInviteForm constructor.
   *
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumer_org_service
   * @param \Drupal\ibm_apim\Service\UserUtils; $user_utils
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   */
  public function __construct(
    ConsumerOrgService $consumer_org_service,
    UserUtils $user_utils,
    ThemeHandler $themeHandler
  ) {
    $this->consumerOrgService = $consumer_org_service;
    $this->userUtils = $user_utils;
    $this->themeHandler = $themeHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('ibm_apim.user_utils'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'consumerorg_delete_invitation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $inviteId = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (!$this->userUtils->checkHasPermission('member:manage')) {
      $message = t('Permission denied.');
      drupal_set_message($message, 'error');

      $form = [];
      $form['description'] = ['#markup' => '<p>' . t('You do not have sufficient access to perform this action.') . '</p>'];

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#href' => 'myorg',
        '#attributes' => ['class' => ['button']],
      ];
      if ($this->themeHandler->themeExists('bootstrap')) {
        $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }
    }
    else {
      $org = $this->userUtils->getCurrentConsumerorg();
      $this->currentOrg = $this->consumerOrgService->get($org['url']);

      $found = FALSE;
      $escapedId = Html::escape($inviteId);
      foreach ($this->currentOrg->getInvites() as $invite) {
        if ($invite['id'] === $escapedId) {
          $this->invite = $invite;
          $found = TRUE;
        }
      }
      if ($found !== TRUE) {
        // return error as inviteId not in this consumerorg
        throw new NotFoundHttpException(t('Specified invite not found in this consumer organization.'));
      }
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
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to delete the invitation to this user?');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the invitation to this user?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('ibm_apim.myorg');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if ($this->invite !== NULL) {
      $response = $this->consumerOrgService->cancelInvitation($this->currentOrg, $this->invite);
      if ($response->success()) {
        drupal_set_message(t('Invitation deleted.'));
      }
      else {
        drupal_set_message(t('Error deleting invitation. Contact the system administrator.'), 'error');
      }
    }
    else {
      drupal_set_message(t('No invitation specified.'), 'error');
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
