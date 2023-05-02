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

namespace Drupal\auth_apic\Form;

use Drupal\auth_apic\UserManagement\ApicUserDeleteInterface;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Delete form for users.
 */
class ApicUserDeleteForm extends ConfirmFormBase {

  /**
   * @var array
   */
  protected array $ownedOrgs;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected ConsumerOrgService $orgService;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicUserDeleteInterface
   */
  protected ApicUserDeleteInterface $deleteService;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  public function __construct(UserUtils $user_utils,
                              ConsumerOrgService $org_service,
                              ApicUserDeleteInterface $delete_service,
                              LoggerInterface $logger,
                              Messenger $messenger) {
    $this->userUtils = $user_utils;
    $this->orgService = $org_service;
    $this->deleteService = $delete_service;
    $this->logger = $logger;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ApicUserDeleteForm {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.consumerorg'),
      $container->get('auth_apic.delete_user'),
      $container->get('logger.channel.auth_apic'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'user_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $this->ownedOrgs = $this->userUtils->loadOwnedConsumerorgs();

    if (sizeof($this->ownedOrgs) > 1) {
      $this->logger->warning('Attempt to load ApicUserDeleteForm while owning more than 1 org.');
      $this->messenger->addError(t('You cannot delete your account because you own more than 1 organization.'));

      $form = [];
      $form['description'] = [
        '#markup' => t('You are the owner of multiple consumer organizations.
                        You can delete your account only when you are the owner of a single organization.
                        Please transfer the ownership of, or delete, the other organizations before you delete your account.'),
      ];

      $form['actions'] = [
        '#type' => 'actions',
      ];

      $form['actions']['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => Url::fromRoute('<front>'),
        '#attributes' => [
          'class' => [
            'button',
          ],
        ],
      ];

      $message = 'own >1 org(not allowed)';
      $returnForm = $form;
    }
    elseif (sizeof($this->ownedOrgs) === 1) {
      // at this point which org the user in is irrelevant, if they own one org we need to delete it.
      $form['org_to_delete'] = [
        '#type' => 'value',
        '#value' => array_shift($this->ownedOrgs),
      ];

      $message = 'own 1 org';
      $returnForm = parent::buildForm($form, $form_state);
    }
    else {
      // delete user is ok, no need to delete any org.
      $message = 'own 0 orgs';
      $returnForm = parent::buildForm($form, $form_state);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $message);
    return $returnForm;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    if (sizeof($this->ownedOrgs) === 1) {
      $description = $this->t('Are you sure you want to delete your account? This action cannot be undone. This action will also remove the organization you own. This permanently removes access to the organization, and all of its applications and subscriptions, for all members of the organization. Please note that once an organization has been deleted, it cannot be reactivated. You might want to consider changing ownership of your Developer organizations, before deleting your account.');
    }
    else {
      $description = $this->t('Are you sure you want to delete your account? This action cannot be undone.');
    }
    return $description;
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
    return $this->t('Are you sure you want to delete your account?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('<front>');
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Core\Entity\EntityStorageException|\Drupal\Core\TempStore\TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($org_url = $form_state->getValue('org_to_delete')) {
      $this->logger->info('Deleting %org_url as part of user deletion', ['%org_url' => $org_url]);
      $org_delete_response = $this->orgService->delete($this->orgService->get($org_url), \Drupal::currentUser()->id()); // TODO - delete by url

      if ($org_delete_response->success()) {
        $this->logger->debug('Organization successfully deleted from ApicUserDeleteForm.');
        $this->messenger->addStatus(t('Organization successfully deleted.'));
      }
      else {
        $this->logger->error('Error deleting organization (%org_url). Please contact your system administrator for assistance.', ['%org_url' => $org_url]);
        $this->messenger->addError(t('Error deleting organization (%org_url). Please contact your system administrator for assistance.', ['%org_url' => $org_url]));
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'error deleting org');
        return;
      }

    }
    else {
      $this->logger->debug('No org to delete as part of user deletion');
    }

    $delete_me_response = $this->deleteService->deleteUser();

    if ($delete_me_response->success()) {
      $this->messenger->addStatus(t('Account successfully deleted.'));
      $this->logger->notice('Account deleted successfully from ApicUserDeleteForm.');
    }
    else {
      $this->logger->error('Error deleting account from ApicUserDeleteForm.');
      $this->messenger->addError(t('Error deleting user. Please contact your system administrator for assistance.'));
    }

    $form_state->setRedirectUrl($this->getCancelUrl());

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Provides a submit handler for the 'Cancel' button.
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function deleteCancelSubmit($form, FormStateInterface $form_state) : void{
    $form_state->setRedirect('<front>');
  }

}
