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

class CustomModulesDeleteForm extends FormBase {

  protected $sitePath;

  protected $moduleHandler;

  protected $logger;

  protected $keyValueExpirable;

  protected $utils;

  public function __construct(string $site_path,
                              ModuleHandlerInterface $module_handler,
                              LoggerInterface $logger,
                              KeyValueStoreExpirableInterface $key_value_expirable, Utils $utils) {
    $this->sitePath = $site_path;
    $this->moduleHandler = $module_handler;
    $this->logger = $logger;
    $this->keyValueExpirable = $key_value_expirable;
    $this->utils = $utils;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('site.path'),
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
      '#description' => 'This form allows you to delete any modules you have installed. Any modules shipped with IBM API Connect cannot be deleted. For a module to appear in the list it, and all of its sub-modules, need to be disabled.',
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
