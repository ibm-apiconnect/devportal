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

namespace Drupal\apic_app\Form;

use Drupal\apic_app\Service\ApplicationRestInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remove form for and application image.
 */
class RemoveImageForm extends ConfirmFormBase {

  /**
   * The node representing the application.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  protected $userUtils;

  /**
   * ApplicationCreateForm constructor.
   *
   * @param ApplicationRestInterface $restService
   * @param UserUtils $userUtils
   */
  public function __construct(ApplicationRestInterface $restService, UserUtils $userUtils) {
    $this->restService = $restService;
    $this->userUtils = $userUtils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Load the service required to construct this class
    return new static($container->get('apic_app.rest_service'), $container->get('ibm_apim.user_utils'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'application_remove_image_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $appId = NULL) {
    $this->node = $appId;
    $form =  parent::buildForm($form, $form_state);
    $themeHandler = \Drupal::service('theme_handler');
    if ($themeHandler->themeExists('bootstrap')) {
      if (isset($form['actions']['submit'])) {
        $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('ok');
      }
      if (isset($form['actions']['cancel'])) {
        $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }
    }
    $form['#attached']['library'][] = 'apic_app/basic';

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Are you sure you want to delete the image for this application? This action cannot be undone.');
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
    return $this->t('Delete the image for %title?', ['%title' => $this->node->title->value]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->node->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $appId = $this->node->application_id->value;
    // delete the file from drupal fs and db
    $fid = $this->node->application_image->value;
    file_delete($fid);

    // Calling all modules implementing 'hook_apic_app_image_delete':
    \Drupal::moduleHandler()->invokeAll('apic_app_image_delete', ['node' => $this->node, 'appId' => $appId]);

    drupal_set_message(t('Application image removed.'));
    $current_user = \Drupal::currentUser();
    \Drupal::logger('apic_app')->notice('Image for application @appname removed by @username', [
      '@appname' => $this->node->getTitle(),
      '@username' => $current_user->getAccountName(),
    ]);

    $form_state->setRedirectUrl($this->getCancelUrl());
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
