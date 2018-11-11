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

namespace Drupal\auth_apic\Form;

use Drupal\auth_apic\Service\Interfaces\UserManagerInterface;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete form for users.
 */
class ApicUserDeleteForm extends ConfirmFormBase {

  protected $ownedOrgs;
  protected $userUtils;
  protected $orgService;
  protected $userManager;
  protected $logger;

  public function __construct(UserUtils $user_utils,
                              ConsumerOrgService $org_service,
                              UserManagerInterface $user_manager,
                              LoggerInterface $logger) {
    $this->userUtils = $user_utils;
    $this->orgService = $org_service;
    $this->userManager = $user_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.consumerorg'),
      $container->get('auth_apic.usermanager'),
      $container->get('logger.channel.auth_apic')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    
    $this->ownedOrgs = $this->userUtils->loadOwnedConsumerorgs();

    if (sizeof($this->ownedOrgs) > 1) {
      $this->logger->warning('Attempt to load ApicUserDeleteForm while owning more than 1 org.');
      drupal_set_message(t('You cannot delete your account because you own more than 1 organization.'), 'error');

      $form = array();
      $form['description'] = array(
        '#markup' => t('You are the owner of multiple consumer organizations. 
                        You can delete your account only when you are the owner of a single organization. 
                        Please transfer the ownership of, or delete, the other organizations before you delete your account.')
      );

      $form['actions'] = array(
        '#type' => 'actions'
      );

      $form['actions']['cancel'] = array(
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => Url::fromRoute('<front>'),
        '#attributes' => array(
          'class' => array(
            'button'
          )
        )
      );

      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'own >1 org(not allowed)');
      return $form;
    }
    else if (sizeof($this->ownedOrgs) === 1){
      // at this point which org the user in is irrelevant, if they own one org we need to delete it.
      $form['org_to_delete']  = array(
        '#type' => 'value',
        '#value' => array_shift($this->ownedOrgs),
      );

      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'own 1 org');
      return parent::buildForm($form, $form_state);
    }
    else {
      // delete user is ok, no need to delete any org.
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'own 0 orgs');
      return parent::buildForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if (sizeof($this->ownedOrgs) === 1) {
      return $this->t('Are you sure you want to delete your account? This action cannot be undone. This action will also remove the organization you own. This permanently removes access to the organization, and all of its applications and subscriptions, for all members of the organization. Please note that once an organization has been deleted, it cannot be reactivated. You might want to consider changing ownership of your Developer organizations, before deleting your account.');
    }
    else {
      return $this->t('Are you sure you want to delete your account? This action cannot be undone.');
    }
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
    return $this->t('Are you sure you want to delete your account?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if ($org_url = $form_state->getValue('org_to_delete')) {
      $this->logger->info(t('Deleting %org_url as part of user deletion', array('%org_url' => $org_url)));
      $org_delete_response = $this->orgService->delete($this->orgService->get($org_url)); // TODO - delete by url

      if ($org_delete_response->success()) {
        $this->logger->debug(t('Organization successfully deleted from ApicUserDeleteForm.'));
        drupal_set_message(t('Organization successfully deleted.'));
      }
      else {
        $msg = t('Error deleting organization (%org_url). Please contact your system administrator for assistance.', array('%org_url' => $org_url));
        $this->logger->debug($msg);
        drupal_set_message($msg, 'error');
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'error deleting org');
        return;
      }

    }
    else {
      $this->logger->debug(t('No org to delete as part of user deletion'));
    }

    $delete_me_response = $this->userManager->deleteUser();

    if ($delete_me_response->success()) {
      $this->logger->notice('Account deleted successfully from ApicUserDeleteForm.');
    }
    else {
      $this->logger->warning('Error deleting account from ApicUserDeleteForm.');
      drupal_set_message(t('Error deleting user. Please contact your system administrator for assistance.'), 'error');
    }

    $form_state->setRedirectUrl($this->getCancelUrl());

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Provides a submit handler for the 'Cancel' button.
   */
  public function deleteCancelSubmit($form, FormStateInterface $form_state) {
    $form_state->setRedirect('<front>');
  }

}
