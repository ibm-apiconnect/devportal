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

namespace Drupal\consumerorg\Form;

use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ThemeHandler;

/**
 * Remove form for consumerorgs.
 */
class OrgDeleteForm extends ConfirmFormBase {

  protected $consumerOrgService;

  protected $currentOrg;

  protected $userUtils;

  protected $currentUser;

  protected $themeHandler;

  /**
   * OrgDeleteForm constructor.
   *
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumer_org_service
   * @param \Drupal\ibm_apim\Service\UserUtils $user_utils
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   */
  public function __construct(ConsumerOrgService $consumer_org_service, UserUtils $user_utils, AccountProxyInterface $current_user, ThemeHandler $themeHandler) {
    $this->consumerOrgService = $consumer_org_service;
    $this->userUtils = $user_utils;
    $this->currentUser = $current_user;
    $this->themeHandler = $themeHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('ibm_apim.user_utils'),
      $container->get('current_user'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'consumerorg_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if (!$this->userUtils->checkHasPermission('settings:manage')) {
      $message = t('Permission denied.');
      drupal_set_message($message, 'error');

      $form = [];
      $form['description'] = ['#markup' => '<p>' . t('You do not have sufficient access to perform this action.') . '</p>'];

      $form['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => Url::fromRoute('ibm_apim.myorg'),
        '#attributes' => ['class' => ['button']],
      ];
      if ($this->themeHandler->themeExists('bootstrap')) {
        $form['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }

    }
    elseif (sizeof($this->userUtils->loadConsumerorgs()) === 1) {
      $message = t('You cannot delete your organization because you are not a member of any other organizations.');
      drupal_set_message($message, 'error');

      $form = [];
      $form['description'] = ['#markup' => '<p>' . t('You cannot delete your organization because this is your only organization and you must be a member of at least one organization.') . '</p>'];

      $form['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => Url::fromRoute('ibm_apim.myorg'),
        '#attributes' => ['class' => ['button']],
      ];
      if ($this->themeHandler->themeExists('bootstrap')) {
        $form['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }
    }
    else {
      $org = $this->userUtils->getCurrentConsumerorg();
      $this->currentOrg = $this->consumerOrgService->get($org['url']);

      $current_user_node = User::load($this->currentUser->id());
      if ($current_user_node === NULL || $current_user_node->apic_url->value !== $this->currentOrg->getOwnerUrl()) {
        $message = t('You cannot delete this organization as you are not the owner.');
        drupal_set_message($message, 'error');

        $form = [];
        $form['description'] = ['#markup' => '<p>' . t('You cannot delete an organization that you do not own.') . '</p>'];

        $form['cancel'] = [
          '#type' => 'link',
          '#title' => t('Cancel'),
          '#url' => Url::fromRoute('ibm_apim.myorg'),
          '#attributes' => ['class' => ['button']],
        ];
        if ($this->themeHandler->themeExists('bootstrap')) {
          $form['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
        }
      }
      else {
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
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action will permanently remove access to the organization, and all of its applications and subscriptions, for all members of the organization. Please note that once an organization has been deleted, it cannot be reactivated.');
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
    return $this->t('Are you sure you want to delete this organization?');
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

    $apim_response = $this->consumerOrgService->delete($this->currentOrg);
    if ($apim_response->success()) {
      drupal_set_message(t('Organization deleted successfully.'));
    }
    else {
      drupal_set_message(t('Error deleting organization. Please contact the system administrator'), 'error');
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
