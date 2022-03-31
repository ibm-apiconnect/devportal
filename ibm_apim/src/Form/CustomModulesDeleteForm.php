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

namespace Drupal\ibm_apim\Form;


use DirectoryIterator;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\ibm_apim\Service\Utils;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomModulesDeleteForm
 *
 * @package Drupal\ibm_apim\Form
 */
class CustomModulesDeleteForm extends FormBase {

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected KeyValueStoreExpirableInterface $keyValueExpirable;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected Utils $utils;

  /**
   * CustomModulesDeleteForm constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   * @param \Drupal\ibm_apim\Service\Utils $utils
   */
  public function __construct(ModuleHandlerInterface          $module_handler,
                              LoggerInterface                 $logger,
                              KeyValueStoreExpirableInterface $key_value_expirable, Utils $utils) {
    $this->moduleHandler = $module_handler;
    $this->logger = $logger;
    $this->keyValueExpirable = $key_value_expirable;
    $this->utils = $utils;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\ibm_apim\Form\CustomModulesDeleteForm|static
   */
  public static function create(ContainerInterface $container): CustomModulesDeleteForm {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('module_handler'),
      $container->get('logger.channel.ibm_apim'),
      $container->get('keyvalue.expirable')->get('ibm_apim_custommodule_delete'),
      $container->get('ibm_apim.utils')
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId(): string {
    return 'ibm_apim_custommodule_delete_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $form['preamble'] = [
      '#type' => 'item',
      '#description' => 'This form allows you to delete any modules you have installed. Any modules shipped with IBM API Developer Portal cannot be deleted. For a module to appear in the list it, and all of its sub-modules, need to be disabled.',
    ];
    $header = [
      'module' => $this->t('Module'),
      'info' => $this->t('Information'),
    ];
    $options = $this->utils->getDisabledCustomModules();
    $form['table'] = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#empty' => $this->t('No deletable modules found.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Delete'),
    ];
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if (empty(array_filter($form_state->getValue('table')))) {
      $form_state->setErrorByName('', $this->t('No modules selected.'));
      $form_state->setRedirect('ibm_apim.custommodules_delete');
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // Save all the values in an expirable key value store.
    $modules = $form_state->getValue('table');
    $uninstall = array_keys(array_filter($modules));
    $account = $this->currentUser()->id();

    $this->keyValueExpirable->setWithExpire($account, $uninstall, 6 * 60 * 60);

    // Redirect to the confirm form.
    $form_state->setRedirect('ibm_apim.custommodules_delete_confirm');
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
