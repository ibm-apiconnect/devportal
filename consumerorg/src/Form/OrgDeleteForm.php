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
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Remove form for consumerorgs.
 */
class OrgDeleteForm extends ConfirmFormBase {

  protected $consumerOrgService;
  protected $currentOrg;
  protected $userUtils;
  protected $currentUser;

  /**
   * Constructs an Org User Invitation Form.
   *
   * {@inheritdoc}
   *
   * @param ConsumerOrgService $consumerOrgService

   */
  public function __construct(ConsumerOrgService $consumer_org_service, UserUtils $user_utils, AccountProxyInterface $current_user) {
    $this->consumerOrgService = $consumer_org_service;
    $this->userUtils = $user_utils;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('ibm_apim.user_utils'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'consumerorg_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if (!$this->userUtils->checkHasPermission('settings:manage')) {
      $message = t('Permission denied.');
      drupal_set_message($message, 'error');

      $form = array();
      $form['description'] = array('#markup' => t('You do not have sufficient access to perform this action.'));

      $form['cancel'] = array(
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => Url::fromRoute('ibm_apim.myorg'),
      );

      return $form;
    } else if (sizeof($this->userUtils->loadConsumerorgs()) == 1) {
      $message = t('You cannot delete your organization because you are not a member of any other organizations.');
      drupal_set_message($message, 'error');

      $form = array();
      $form['description'] = array('#markup' => t('You cannot delete your organization because this is your only organization and you must be a member of at least one organization.'));

      $form['cancel'] = array(
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => Url::fromRoute('ibm_apim.myorg'),
      );
      return $form;
    }
    else {
      $org = $this->userUtils->getCurrentConsumerOrg();
      $this->currentOrg = $this->consumerOrgService->get($org['url']);

      $current_user_node = User::load($this->currentUser->id());
      if($current_user_node->apic_url->value !== $this->currentOrg->getOwnerUrl()) {
        $message = t('You cannot delete this organization as you are not the owner.');
        drupal_set_message($message, 'error');

        $form = array();
        $form['description'] = array('#markup' => t('You cannot delete an organization that you do not own.'));

        $form['cancel'] = array(
          '#type' => 'link',
          '#title' => t('Cancel'),
          '#url' => Url::fromRoute('ibm_apim.myorg'),
        );
        return $form;
      }

      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return parent::buildForm($form, $form_state);
    }
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
  public function getCancelUrl() {
    return Url::fromRoute('ibm_apim.myorg');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
