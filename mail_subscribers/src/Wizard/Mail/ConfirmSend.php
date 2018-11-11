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

namespace Drupal\mail_subscribers\Wizard\Mail;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class ConfirmSend extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_subscribers_wizard_confirm_send';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $cached_values = $form_state->getTemporaryValue('wizard');
    $objectType = $cached_values['objectType'];
    $subject = $cached_values['subject'];
    $message = $cached_values['message'];
    $role = $cached_values['role'];
    $direct = $cached_values['direct'];
    $carbon_copy = $cached_values['carbon_copy'];
    $priority = $cached_values['priority'];
    $receipt = $cached_values['receipt'];
    $headers = $cached_values['headers'];

    $form['intro'] = array(
      '#markup' => '<p>' . $this->t('Please confirm the below settings are correct and then click Next to send the message.') . '</p>',
      '#weight' => -100
    );

    if ($objectType === 'product') {
      $node = Node::load($cached_values['product']);
      if ($role === 'owners') {
        $form['product'] = array(
          '#markup' => '<p>' . $this->t('Email all owners of consumer organizations subscribing to any %product_title plan the following message:', array('%product_title'=>$node->getTitle())) . '</p>',
          '#weight' => -80
        );
      }
      else {
        $form['product'] = array(
          '#markup' => '<p>' . $this->t('Email all members of consumer organizations subscribing to any %product_title plan the following message:', array('%product_title'=>$node->getTitle())) . '</p>',
          '#weight' => -80
        );
      }
    }
    elseif ($objectType === 'plan') {
      $plan = $cached_values['plan'];
      $node = Node::load($cached_values['product']);
      if ($role === 'owners') {
        $form['plan'] = array(
          '#markup' => '<p>' . $this->t('Email all owners of consumer organizations subscribing to the %product_title %plan plan the following message:', array('%product_title'=>$node->getTitle(), '%plan'=>$plan['title'])) . '</p>',
          '#weight' => -80
        );
      }
      else {
        $form['plan'] = array(
          '#markup' => '<p>' . $this->t('Email all members of consumer organizations subscribing to the %product_title %plan plan the following message:', array('%product_title'=>$node->getTitle(), '%plan'=>$plan['title'])) . '</p>',
          '#weight' => -80
        );
      }
    }
    elseif ($objectType === 'api') {
      $node = Node::load($cached_values['api']);
      if ($role === 'owners') {
        $form['api'] = array(
          '#markup' => '<p>' . $this->t('Email all owners of consumer organizations subscribing to plans containing API: %api_title the following message:', array('%api_title'=>$node->getTitle())) . '</p>',
          '#weight' => -80
        );
      }
      else {
        $form['api'] = array(
          '#markup' => '<p>' . $this->t('Email all members of consumer organizations subscribing to plans containing API: %api_title the following message:', array('%api_title'=>$node->getTitle())) . '</p>',
          '#weight' => -80
        );
      }
    }
    elseif ($objectType === 'all') {
      if ($role === 'owners') {
        $form['all'] = array(
          '#markup' => '<p>' . $this->t('Email all owners of all consumer organization the following message:') . '</p>',
          '#weight' => -80
        );
      }
      else {
        $form['all'] = array(
          '#markup' => '<p>' . $this->t('Email all members of all consumer organization the following message:') . '</p>',
          '#weight' => -80
        );
      }
    }
    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => $subject,
      '#disabled' => TRUE,
      '#weight' => -70
    );
    $form['message'] = array(
      '#title' => $this->t('Message'),
      '#type' => 'textarea',
      '#default_value' => $message['value'],
      '#wysiwyg' => FALSE,
      '#disabled' => TRUE,
      '#weight' => -60
    );

    $form['priority'] = array(
      '#type' => 'select',
      '#title' => $this->t('Priority'),
      '#options' => array(
        0 => $this->t('none'),
        1 => $this->t('highest'),
        2 => $this->t('high'),
        3 => $this->t('normal'),
        4 => $this->t('low'),
        5 => $this->t('lowest')
      ),
      '#default_value' => $priority,
      '#disabled' => TRUE,
      '#weight' => -50
    );
    $form['receipt'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Request receipt'),
      '#default_value' => $receipt,
      '#disabled' => TRUE,
      '#weight' => -40
    );
    $form['headers'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Additional headers'),
      '#rows' => 4,
      '#default_value' => $headers,
      '#disabled' => TRUE,
      '#weight' => -30
    );

    $form['direct'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Send the message directly using the Batch API.'),
      '#default_value' => $direct,
      '#disabled' => TRUE,
      '#weight' => -20
    );
    $form['carbon_copy'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Send a copy of the message to the sender.'),
      '#default_value' => $carbon_copy,
      '#disabled' => TRUE,
      '#weight' => -10
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $cached_values = $form_state->getTemporaryValue('wizard');

    $mailService = \Drupal::service('mail_subscribers.mail_service');

    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $config = \Drupal::config('mail_subscribers.settings');
    $site_config = \Drupal::config('system.site');
    $from = array();
    $from_name = $config->get('from_name');
    $from_mail = $config->get('from_mail');
    $from['name'] = (isset($from_name) && !empty($from_name)) ? $from_name : $site_config->get('name');
    $from['mail'] = (isset($from_mail) && !empty($from_mail)) ? $from_mail : $site_config->get('mail');

    $rc = NULL;

    if ($cached_values['objectType'] === 'product' && $cached_values['role'] === 'owners') {
      $rc = $mailService->mailProductOwners($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'product' && $cached_values['role'] === 'members') {
      $rc = $mailService->mailProductMembers($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'plan' && $cached_values['role'] === 'owners') {
      $rc = $mailService->mailProductOwners($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'plan' && $cached_values['role'] === 'members') {
      $rc = $mailService->mailProductMembers($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'api' && $cached_values['role'] === 'owners') {
      $rc = $mailService->mailApiOwners($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'api' && $cached_values['role'] === 'members') {
      $rc = $mailService->mailApiMembers($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'all' && $cached_values['role'] === 'owners') {
      $rc = $mailService->mailAllOwners($cached_values, $from, $langcode);
    }
    elseif ($cached_values['objectType'] === 'all' && $cached_values['role'] === 'members') {
      $rc = $mailService->mailAllMembers($cached_values, $from, $langcode);
    }
    else {
      // invalid combination
      // TODO something here
    }

    $cached_values['result'] = $rc;

    $result_state = \Drupal::state()->get('mail_subscribers.result');
    if ($result_state === null) {
      $result_state = [];
    }
    if (isset($cached_values['instance'])) {
      $result_state[$cached_values['instance']] = $rc;
      // clear out any old results
      $TTL = 86400;
      $now = time();
      foreach ($result_state as $key => $value) {
        if ($now > ((int)$key + $TTL)) {
          unset($result_state[$key]);
        }
      }
      \Drupal::state()->set('mail_subscribers.result', $result_state);
    }
    $form_state->setTemporaryValue('wizard', $cached_values);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
