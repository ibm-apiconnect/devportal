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

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * APIC settings form.
 */
class DevelForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ibm_apim_devel_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('ibm_apim.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ibm_apim.settings');

    $form['intro'] = array(
      '#markup' => t('IBM API Connect Development Settings'),
      '#weight' => -20
    );

    $form['debug'] = array(
      '#type' => 'fieldset',
      '#title' => t('Debug'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE
    );
    $form['debug']['entry_exit_trace'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable method entry / exit trace'),
      '#default_value' => $config->get('entry_exit_trace'),
      '#weight' => 10,
      '#description' => t('WARNING: Not to be used on production servers. It greatly increases the amount of debug information in the logs.')
    );
    $form['debug']['apim_rest_trace'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable API Manager REST interface debug'),
      '#default_value' => $config->get('apim_rest_trace'),
      '#weight' => 15,
      '#description' => t('WARNING: Not to be used on production servers. It greatly increases the amount of debug information in the logs.')
    );
    $form['debug']['webhook_debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable webhook payload debug'),
      '#default_value' => $config->get('webhook_debug'),
      '#weight' => 15,
      '#description' => t('WARNING: Not to be used on production servers. It stores all webhook payloads in the database for debug. This does not scale well!.')
    );
    $form['debug']['acl_debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable node access control debug'),
      '#default_value' => $config->get('acl_debug'),
      '#weight' => 20,
      '#description' => t('WARNING: Not to be used on production servers. It greatly increases the amount of debug information in the logs.')
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Set the submitted configuration setting
    $this->config('ibm_apim.settings')
      ->set('entry_exit_trace', (bool) $form_state->getValue('entry_exit_trace'))
      ->set('apim_rest_trace', (bool) $form_state->getValue('apim_rest_trace'))
      ->set('webhook_debug', (bool) $form_state->getValue('webhook_debug'))
      ->set('acl_debug', (bool) $form_state->getValue('acl_debug'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
