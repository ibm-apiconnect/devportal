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
use Drupal\consumerorg\ApicType\Member;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Extension\ThemeHandler;

/**
 * Remove form for consumerorg members.
 */
class RemoveUserForm extends ConfirmFormBase {

  protected $consumerOrgService;

  protected $userUtils;

  protected $themeHandler;

  /**
   * The node representing the consumerorg.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $orgId;

  protected $currentOrg;

  protected $member;

  /**
   * The id of the member to remove
   *
   * @var string
   */
  protected $memberId;

  /**
   * RemoveUserForm constructor.
   *
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumer_org_service
   * @param \Drupal\ibm_apim\Service\UserUtils $user_utils
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
    return 'consumerorg_remove_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $memberId = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $current_user = \Drupal::currentUser();
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
      $this->currentOrg = $this->consumerOrgService->getConsumerOrgAsObject($org['url']);
      $this->memberId = Html::escape($memberId);
      $found = FALSE;
      foreach ($this->currentOrg->getMembers() as $member) {
        if ($member->getId() === $this->memberId) {
          if ($current_user->getAccountName() !== $member->getUser()->getUsername()) {
            $found = TRUE;
            $this->member = $member;
          }
          else {
            // return error as cannot remove yourself
            throw new BadRequestHttpException(t('Cannot remove yourself from a consumer organization.'));
          }
        }
      }
      if ($found !== TRUE) {
        // return error as memberId not in this consumerorg
        throw new NotFoundHttpException(t('Specified member not found in this consumer organization.'));
      }
      $form = parent::buildForm($form, $form_state);
      if ($this->themeHandler->themeExists('bootstrap')) {
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
    return $this->t('Are you sure you want to remove the user <em>@user?</em>', [
      '@user' => $this->member->getUser()->getUsername(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove the user <em>@user?</em>', [
      '@user' => $this->member->getUser()->getUsername(),
    ]);
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

    $response = $this->consumerOrgService->deleteMember($this->currentOrg, $this->member);
    if ($response->success()) {
      drupal_set_message(t('User removed successfully.'));

      $current_user = \Drupal::currentUser();
      \Drupal::logger('consumerorg')
        ->notice('Organization member @member removed from @orgname by @username', [
          '@orgname' => $this->currentOrg->getTitle(),
          '@member' => basename($this->member->getUrl()),
          '@username' => $current_user->getAccountName(),
        ]);
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
