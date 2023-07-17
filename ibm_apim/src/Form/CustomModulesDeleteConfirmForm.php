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

namespace Drupal\ibm_apim\Form;


use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\Interfaces\ApicModuleInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomModulesDeleteConfirmForm
 *
 * @package Drupal\ibm_apim\Form
 */
class CustomModulesDeleteConfirmForm extends ConfirmFormBase {

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected KeyValueStoreExpirableInterface $keyValueExpirable;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ApicModuleInterface
   */
  protected ApicModuleInterface $moduleService;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * An array of modules to delete.
   *
   * @var array
   */
  protected array $modules = [];

  /**
   * CustomModulesDeleteConfirmForm constructor.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\Service\Interfaces\ApicModuleInterface $module_service
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(KeyValueStoreExpirableInterface $key_value_expirable,
                              LoggerInterface $logger,
                              ApicModuleInterface $module_service,
                              Messenger $messenger) {
    $this->keyValueExpirable = $key_value_expirable;
    $this->logger = $logger;
    $this->moduleService = $module_service;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): CustomModulesDeleteConfirmForm {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('keyvalue.expirable')->get('ibm_apim_custommodule_delete'),
      $container->get('logger.channel.ibm_apim'),
      $container->get('ibm_apim.module'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Confirm delete');
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
  public function getCancelUrl(): Url {
    return new Url('ibm_apim.custommodules_delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Would you like to continue with deleting these modules?');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ibm_apim_custommodules_delete_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    // Retrieve the list of modules from the key value store.
    $account = $this->currentUser()->id();
    $this->modules = $this->keyValueExpirable->get($account) ?? [];

    // Prevent this page from showing when the module list is empty.
    if (empty($this->modules)) {
      $this->messenger->addError($this->t('The selected modules could not be deleted, either due to a website problem or due to the delete confirmation form timing out. Please try again.'));
      $this->logger->error('The selected modules could not be deleted, either due to a website problem or due to the delete confirmation form timing out.');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'empty modules');
      }
      return $this->redirect('ibm_apim.custommodules_delete');
    }

    $form['text']['#markup'] = '<p>' . $this->t('The following modules will be completely deleted from your system, and <em>all data from these modules will be lost</em>!') . '</p>';
    // TODO: pass through the module name from the info.yml to list on the page
    $form['modules'] = [
      '#theme' => 'item_list',
      '#items' => $this->modules,
    ];

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    // Clear the key value store entry.
    $account = $this->currentUser()->id();
    $this->keyValueExpirable->delete($account);

    // Uninstall the modules.
    $result = $this->moduleService->deleteExtensionOnFileSystem('module', $this->modules);
    if ($result) {
      $this->messenger->addMessage($this->t('The selected modules have been deleted.'));
      $this->logger->notice('CustomModuleDeleteConfirmForm: modules deleted successfully');
    }
    else {
      $this->messenger->addError($this->t('There was a problem deleting the specified modules.'));
      $this->logger->error('CustomModuleDeleteConfirmForm: error deleting modules');
    }
    $form_state->setRedirectUrl($this->getCancelUrl());

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

}
