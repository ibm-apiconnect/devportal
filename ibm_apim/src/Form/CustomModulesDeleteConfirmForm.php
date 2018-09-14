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

namespace Drupal\ibm_apim\Form;


use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\Utils;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CustomModulesDeleteConfirmForm extends ConfirmFormBase {

  protected $keyValueExpirable;
  protected $logger;
  protected $sitePath;
  protected $utils;

  /**
   * An array of modules to delete.
   *
   * @var array
   */
  protected $modules = array();

  public function __construct(KeyValueStoreExpirableInterface $key_value_expirable,
                              LoggerInterface $logger,
                              string $site_path,
                              Utils $utils) {
    $this->keyValueExpirable = $key_value_expirable;
    $this->logger = $logger;
    $this->sitePath = $site_path;
    $this->utils = $utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('keyvalue.expirable')->get('ibm_apim_custommodule_delete'),
      $container->get('logger.channel.ibm_apim'),
      $container->get('site.path'),
      $container->get('ibm_apim.utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Confirm delete');
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
  public function getCancelUrl() {
    return new Url('ibm_apim.custommodules_delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Would you like to continue with deleting these modules?');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ibm_apim_custommodules_delete_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Retrieve the list of modules from the key value store.
    $account = $this->currentUser()->id();
    $this->modules = $this->keyValueExpirable->get($account);

    // Prevent this page from showing when the module list is empty.
    if (empty($this->modules)) {
      drupal_set_message($this->t('The selected modules could not be deleted, either due to a website problem or due to the delete confirmation form timing out. Please try again.'), 'error');
      return $this->redirect('ibm_apim.custommodules_delete');
    }

    $form['text']['#markup'] = '<p>' . $this->t('The following modules will be completely deleted from your system, and <em>all data from these modules will be lost</em>!') . '</p>';
    // TODO: pass through the module name from the info.yml to list on the page
    $form['modules'] = array(
      '#theme' => 'item_list',
      '#items' => $this->modules,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Clear the key value store entry.
    $account = $this->currentUser()->id();
    $this->keyValueExpirable->delete($account);

    // Uninstall the modules.
    $result = $this->deleteModulesOnFileSystem($this->modules);
    if ($result) {
      drupal_set_message($this->t('The selected modules have been deleted.'));
    }
    else {
      drupal_set_message($this->t('There was a problem deleting the specified modules.'), 'error');
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  private function deleteModulesOnFileSystem(array $modules) {
    $paths = array();
    foreach ($modules as $module) {
      $path = \DRUPAL_ROOT . '/' . $this->sitePath . '/modules/' . $module;
      $error_found = FALSE;
      $this->logger->debug('Delete modules: checking existence of %path', array('%path' => $path));
      if (is_dir($path)) {
        $paths[] = $path;
      }
      else {
        $this->logger->error('%path is not a directory, exitting.', array('%path' => $path));
        $error_found = TRUE;
      }
    }

    if ($error_found) {
      $this->logger->error('Errors found while checking module directories to delete, so cancelling processing.');
      $return = FALSE;
    }
    else {
      if (!empty($paths)) {
        foreach ($paths as $path) {
          $this->logger->debug('Delete modules: recursively deleting %path', ['%path' => $path]);
          $this->utils->file_delete_recursive($path);
        }
        $return = TRUE;
      }
      else {
        $this->logger->error('Empty list of paths to delete.');
        $return = FALSE;
      }

    }

    return $return;
  }

}
