<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\mail_subscribers\Wizard\Mail;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class EnterContentStep extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'mail_subscribers_wizard_enter_content';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $message = NULL;
    $headers = NULL;
    $priority = NULL;
    $receipt = NULL;
    $direct = NULL;
    $carbon_copy = NULL;
    // load any saved values in case have gone backwards in the wizard
    $cached_values = $form_state->getTemporaryValue('wizard');
    if (!empty($cached_values) && isset($cached_values['subject'])) {
      $subject = $cached_values['subject'];
    }
    if (!empty($cached_values) && isset($cached_values['message'])) {
      $message = $cached_values['message'];
    }
    if (!empty($cached_values) && isset($cached_values['direct'])) {
      $direct = $cached_values['direct'];
    }
    if (!empty($cached_values) && isset($cached_values['carbon_copy'])) {
      $carbon_copy = $cached_values['carbon_copy'];
    }
    if (!empty($cached_values) && isset($cached_values['priority'])) {
      $priority = $cached_values['priority'];
    }
    if (!empty($cached_values) && isset($cached_values['receipt'])) {
      $receipt = $cached_values['receipt'];
    }
    if (!empty($cached_values) && isset($cached_values['headers'])) {
      $headers = $cached_values['headers'];
    }
    if ($message === NULL) {
      $message = [];
    }
    if (!isset($message['value'])) {
      $message['value'] = '';
    }
    if (!isset($message['format'])) {
      $message['format'] = 'basic_html';
    }
    if ($headers === NULL) {
      $headers = '';
    }
    if ($priority === NULL) {
      $priority = 0;
    }
    if ($receipt === NULL) {
      $receipt = 0;
    }
    if ($direct === NULL) {
      $direct = 1;
    }
    if ($carbon_copy === NULL) {
      $carbon_copy = 1;
    }

    $form['intro'] = [
      '#markup' => '<p>' . $this->t('Now provide the content of the message to send.') . '</p>',
      '#weight' => 0,
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#default_value' => !empty($subject) ? $subject : '',
      '#title' => $this->t('Subject'),
    ];

    $form['message'] = [
      '#title' => $this->t('Message'),
      '#type' => 'text_format',
      '#format' => $message['format'],
      '#wysiwyg' => TRUE,
      '#default_value' => $message['value'],
      '#allowed_formats' => ['basic_html', 'plain_text'],
      '#rows' => 10,
      '#description' => $this->t('Enter the body of the message. You can use tokens in the message.'),
    ];

    if (!\Drupal::moduleHandler()->moduleExists('token')) {
      // TODO : implement mail subscriber specific tokens
      // $form['token']['tokens'] = array(
      //   '#markup' => mail_subscribers_token_help($fields_name_text)
      // );
    }
    else {

      $form['token'] = [
        '#type' => 'details',
        '#title' => $this->t('Replacements'),
        '#description' => $this->t('You can use the following tokens in the subject or message.'),
      ];

      // standard tokens from drupal
      //      $form['token']['general'] = array(
      //        '#type' => 'details',
      //        '#title' => $this->t('General tokens'),
      //      );

      $token_types = ['site', 'user', 'node', 'current-date'];
      $form['token']['tokens'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => $token_types,
      ];

      // TODO : implement mail subscriber specific tokens
      //      $form['token']['mail_subscribers'] = array(
      //        '#type' => 'details',
      //        '#title' => $this->t('Mail Subscribers specific tokens'),
      //      );
      //      $form['token']['mail_subscribers']['tokens'] = array(
      //        '#markup' => mail_subscribers_token_help($fields_name_text)
      //      );
      //      $form['token']['general'] = array(
      //        '#type' => 'details',
      //        '#title' => $this->t('General tokens'),
      //      );
    }

    $form['additional'] = [
      '#type' => 'details',
      '#title' => $this->t('Additional email options'),
    ];
    $form['additional']['priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Priority'),
      '#options' => [
        0 => $this->t('none'),
        1 => $this->t('highest'),
        2 => $this->t('high'),
        3 => $this->t('normal'),
        4 => $this->t('low'),
        5 => $this->t('lowest'),
      ],
      '#description' => $this->t('Note that email priority is ignored by a lot of email programs.'),
      '#default_value' => $priority,
    ];
    $form['additional']['receipt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Request receipt'),
      '#default_value' => $receipt,
      '#description' => $this->t('Request a Read Receipt from your emails. A lot of e-mail programs ignore these so it is not a definitive indication of how many people have read your message.'),
    ];
    $form['additional']['headers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional headers'),
      '#description' => $this->t("Additional headers to be sent with the message. Enter one per line. Example:<pre>Reply-To: noreply@example.com\nX-MyCustomHeader: Value</pre>"),
      '#rows' => 4,
      '#default_value' => $headers,
    ];

    $form['direct'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send the message directly using the Batch API.'),
      '#default_value' => $direct,
    ];
    $form['carbon_copy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send a copy of the message to the sender.'),
      '#default_value' => $carbon_copy,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (empty($form_state->getUserInput()['subject'])) {
      $form_state->setErrorByName('subject', $this->t('You must enter a Subject.'));
      return FALSE;
    }
    if (empty($form_state->getUserInput()['message'])) {
      $form_state->setErrorByName('message', $this->t('You must enter some content for the message.'));
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
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
