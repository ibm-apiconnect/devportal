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

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

use Symfony\Component\HttpFoundation\RedirectResponse;

class EnterContentStep extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_subscribers_wizard_enter_content';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // load any saved values in case have gone backwards in the wizard
    $cached_values = $form_state->getTemporaryValue('wizard');
    if(!empty($cached_values) && isset($cached_values['subject'])) {
      $subject = $cached_values['subject'];
    }
    if(!empty($cached_values) && isset($cached_values['message'])) {
      $message = $cached_values['message'];
    }
    if(!empty($cached_values) && isset($cached_values['direct'])) {
      $direct = $cached_values['direct'];
    }
    if(!empty($cached_values) && isset($cached_values['carbon_copy'])) {
      $carbon_copy = $cached_values['carbon_copy'];
    }
    if(!empty($cached_values) && isset($cached_values['priority'])) {
      $priority = $cached_values['priority'];
    }
    if(!empty($cached_values) && isset($cached_values['receipt'])) {
      $receipt = $cached_values['receipt'];
    }
    if(!empty($cached_values) && isset($cached_values['headers'])) {
      $headers = $cached_values['headers'];
    }
    if (!isset($message)) {
      $message = array();
    }
    if (!isset($message['value'])) {
      $message['value'] = '';
    }
    if (!isset($message['format'])) {
      $message['format'] = 'basic_html';
    }
    if (!isset($headers)) {
      $headers = '';
    }
    if (!isset($priority)) {
      $priority = 0;
    }
    if (!isset($receipt)) {
      $receipt = 0;
    }
    if (!isset($direct)) {
      $direct = 1;
    }
    if (!isset($carbon_copy)) {
      $carbon_copy = 1;
    }

    $form['intro'] = array(
      '#markup' => '<p>' . t('Now provide the content of the message to send.') . '</p>',
      '#weight' => 0
    );

    $form['subject'] = array(
      '#type' => 'textfield',
      '#default_value' => !empty($subject) ? $subject : "" ,
      '#title' => $this->t('Subject')
    );

    $form['message'] = array(
      '#title' => t('Message'),
      '#type' => 'text_format',
      '#format' => $message['format'],
      '#wysiwyg' => TRUE,
      '#default_value' => $message['value'],
      '#allowed_formats' => ['basic_html', 'plain_text'],
      '#rows' => 10,
      '#description' => t('Enter the body of the message. You can use tokens in the message.'),
    );

    if (!\Drupal::moduleHandler()->moduleExists('token')) {
      // TODO : implement mail subscriber specific tokens
      // $form['token']['tokens'] = array(
      //   '#markup' => mail_subscribers_token_help($fields_name_text)
      // );
    }
    else {

      $form['token'] = array(
        '#type' => 'details',
        '#title' => t('Replacements'),
        '#description' => t('You can use the following tokens in the subject or message.'),
      );

      // standard tokens from drupal
//      $form['token']['general'] = array(
//        '#type' => 'details',
//        '#title' => t('General tokens'),
//      );

      $token_types = array('site', 'user', 'node', 'current-date');
      $form['token']['tokens'] = array(
        '#theme' => 'token_tree_link',
        '#token_types' => $token_types,
      );

      // TODO : implement mail subscriber specific tokens
      //      $form['token']['mail_subscribers'] = array(
      //        '#type' => 'details',
      //        '#title' => t('Mail Subscribers specific tokens'),
      //      );
      //      $form['token']['mail_subscribers']['tokens'] = array(
      //        '#markup' => mail_subscribers_token_help($fields_name_text)
      //      );
      //      $form['token']['general'] = array(
      //        '#type' => 'details',
      //        '#title' => t('General tokens'),
      //      );
    }

    $form['additional'] = array(
      '#type' => 'details',
      '#title' => t('Additional email options'),
    );
    $form['additional']['priority'] = array(
      '#type' => 'select',
      '#title' => t('Priority'),
      '#options' => array(
        0 => t('none'),
        1 => t('highest'),
        2 => t('high'),
        3 => t('normal'),
        4 => t('low'),
        5 => t('lowest')
      ),
      '#description' => t('Note that email priority is ignored by a lot of email programs.'),
      '#default_value' => $priority,
    );
    $form['additional']['receipt'] = array(
      '#type' => 'checkbox',
      '#title' => t('Request receipt'),
      '#default_value' => $receipt,
      '#description' => t('Request a Read Receipt from your emails. A lot of e-mail programs ignore these so it is not a definitive indication of how many people have read your message.'),
    );
    $form['additional']['headers'] = array(
      '#type' => 'textarea',
      '#title' => t('Additional headers'),
      '#description' => t("Additional headers to be sent with the message. Enter one per line. Example:<pre>Reply-To: noreply@example.com\nX-MyCustomHeader: Value</pre>"),
      '#rows' => 4,
      '#default_value' => $headers,
    );

    $form['direct'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send the message directly using the Batch API.'),
      '#default_value' => $direct,
    );
    $form['carbon_copy'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send a copy of the message to the sender.'),
      '#default_value' => $carbon_copy,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (empty($form_state->getUserInput()['subject'])) {
      $form_state->setErrorByName('subject', t('You must enter a Subject.'));
      return FALSE;
    }
    if (empty($form_state->getUserInput()['message'])) {
      $form_state->setErrorByName('message', t('You must enter some content for the message.'));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');

    $subject = $form_state->getUserInput()['subject'];
    $message = $form_state->getValue(['message']);
    $priority = $form_state->getUserInput()['priority'];
    $receipt = $form_state->getUserInput()['receipt'];
    $headers = $form_state->getUserInput()['headers'];
    $direct = $form_state->getUserInput()['direct'];
    $carbon_copy = $form_state->getUserInput()['carbon_copy'];

    $cached_values['subject'] = $subject;
    $cached_values['message'] = $message;
    $cached_values['priority'] = $priority;
    $cached_values['receipt'] = $receipt;
    $cached_values['headers'] = $headers;
    $cached_values['direct'] = $direct;
    $cached_values['carbon_copy'] = $carbon_copy;

    $form_state->setTemporaryValue('wizard', $cached_values);

  }

}
