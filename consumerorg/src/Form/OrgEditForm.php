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
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\UserUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Form to edit the consumerorg name.
 */
class OrgEditForm extends FormBase {

  protected $consumerOrgService;
  protected $currentOrg;
  protected $userUtils;

  /**
   * Constructs an Org User Invitation Form.
   *
   * {@inheritdoc}
   *
   * @param ConsumerOrgService $consumerOrgService

   */
  public function __construct(ConsumerOrgService $consumer_org_service, UserUtils $user_utils) {
    $this->consumerOrgService = $consumer_org_service;
    $this->userUtils = $user_utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.consumerorg'),
      $container->get('ibm_apim.user_utils')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'consumerorg_edit_form';
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
      $form['description'] = array('#markup' => '<p>' . t('You do not have sufficient access to perform this action.') . '</p>');

      $form['actions'] = array('#type' => 'actions');
      $form['actions']['cancel'] = array(
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#href' => 'myorg',
        '#attributes' => array('class' => array('button'))
      );
      $themeHandler = \Drupal::service('theme_handler');
      if ($themeHandler->themeExists('bootstrap')) {
        $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }

      return $form;
    } else {
      $org = $this->userUtils->getCurrentConsumerOrg();
      $this->currentOrg = $this->consumerOrgService->get($org['url']);

      $form['orgname'] = array(
        '#type' => 'textfield',
        '#title' => t('Organization name'),
        '#size' => 25,
        '#maxlength' => 128,
        '#required' => TRUE,
        '#default_value' => $this->currentOrg->getTitle(),
      );

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
      );
      $form['actions']['cancel'] = array(
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => $this->getCancelUrl(),
        '#attributes' => ['class' => ['button', 'apicSecondary']]
      );
      $themeHandler = \Drupal::service('theme_handler');
      if ($themeHandler->themeExists('bootstrap')) {
        $form['actions']['submit']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('ok');
        $form['actions']['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return $form;
    }
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
    $orgname = $form_state->getValue('orgname');

    if (empty($orgname)) {
      drupal_set_message(t('An organization name is required.'), 'error');
    }
    else {

      $response = $this->consumerOrgService->editOrgTitle($this->currentOrg, $orgname);
      if ($response->success()) {
        drupal_set_message(t('Organization name updated.'));
      }
      else {
        drupal_set_message(t('Error during organization update. Contact the system administrator.'), 'error');
      }

    }
    $form_state->setRedirectUrl(Url::fromRoute('ibm_apim.myorg'));
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
