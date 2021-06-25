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

namespace Drupal\mail_subscribers\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * APIC settings form.
 */
class AdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID(): string {
    return 'mail_subscribers_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['mail_subscribers.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('mail_subscribers.settings');
    $siteConfig = \Drupal::config('system.site');

    $form['intro'] = [
      '#markup' => t('This form allows the configuration of the mail subscriber wizards.'),
      '#weight' => -30,
    ];
    $fromName = $config->get('from_name');
    $form['from']['from_name'] = [
      '#type' => 'textfield',
      '#title' => t('Sender\'s name'),
      '#description' => t("Enter the sender's human readable name."),
      '#default_value' => $fromName ?? $siteConfig->get('name'),
      '#maxlen' => 255,
    ];
    $fromMail = $config->get('from_mail');
    $form['from']['from_mail'] = [
      '#type' => 'textfield',
      '#title' => t('Sender\'s email'),
      '#description' => t("Enter the sender's email address."),
      '#required' => TRUE,
      '#default_value' => $fromMail ?? $siteConfig->get('mail'),
      '#maxlen' => 255,
    ];

    $throttleValues = [1, 10, 20, 30, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000];
    $throttle = array_combine($throttleValues, $throttleValues);
    array_unshift($throttle, $this->t('Unlimited'));

    $throttleDesc = $this->t('Sets the numbers of messages sent per cron run. Failure to send will also be counted. Cron execution must not exceed the PHP maximum execution time of %max seconds.',
      ['%max' => ini_get('max_execution_time')]);
    if (\Drupal::moduleHandler()->moduleExists('dblog')) {
      $throttleDesc .= ' ' . $this->t('You find the time spent to send e-mails in the <a href="@dblog-url">recent log messages</a>.', ['@dblog-url' => Url::fromRoute('dblog.overview')]);
    }
    $form['throttle'] = [
      '#type' => 'select',
      '#title' => $this->t('Cron throttle'),
      '#options' => $throttle,
      '#default_value' => $config->get('throttle'),
      '#description' => $throttleDesc,
    ];

    $form['spool_expire'] = [
      '#type' => 'select',
      '#title' => $this->t('Mail spool expiration'),
      '#options' => [
        0 => $this->t('Immediate'),
        1 => $this->t('1 day'),
        7 => $this->t('1 week'),
        14 => $this->t('2 weeks'),
      ],
      '#default_value' => $config->get('spool_expire'),
      '#description' => $this->t('E-mails are spooled. How long must messages be retained in the spool after successfull sending.'),
    ];

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log e-mails'),
      '#default_value' => $config->get('debug'),
      '#description' => $this->t('When checked all outgoing messages are logged in the system log. A logged e-mail does not guarantee that it is sent or will be delivered. It only indicates that a message is send to the PHP mail() function. No status information is available of delivery by the PHP mail() function.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    // Set the submitted configuration setting
    $this->config('mail_subscribers.settings')
      ->set('throttle', (int) $form_state->getValue('throttle'))
      ->set('spool_expire', (int) $form_state->getValue('spool_expire'))
      ->set('debug', (bool) $form_state->getValue('debug'))
      ->save();

    // only save the from details if differ from site defaults
    $siteConfig = \Drupal::config('system.site');
    if ($form_state->getValue('from_name') !== $siteConfig->get('name')) {
      $this->config('mail_subscribers.settings')
        ->set('from_name', $form_state->getValue('from_name'))
        ->save();
    }
    if ($form_state->getValue('from_mail') !== $siteConfig->get('mail')) {
      $this->config('mail_subscribers.settings')
        ->set('from_mail', $form_state->getValue('from_mail'))
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
