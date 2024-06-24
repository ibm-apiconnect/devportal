<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\mail_subscribers\Wizard\Mail;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MailSummary extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'mail_subscribers_wizard_result';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $result_state = \Drupal::state()->get('mail_subscribers.result');

    $cached_values = $form_state->getTemporaryValue('wizard');
    $rc = NULL;
    if ($result_state !== NULL && $cached_values['instance'] !== NULL && $result_state[$cached_values['instance']] !== NULL) {
      $rc = $result_state[$cached_values['instance']];
    }
    if ($rc === NULL) {
      $form['intro'] = [
        '#markup' => '<p>' . t('ERROR: An error has occurred. No messages sent.') . '</p>',
        '#weight' => 0,
      ];
    }
    elseif ($rc === 0) {
      $form['intro'] = [
        '#markup' => '<p>' . t('No messages sent. This is likely due to there not being any subscribers for the selected criteria.') . '</p>',
        '#weight' => 0,
      ];
    }
    elseif (isset($cached_values['direct']) && $cached_values['direct'] === TRUE) {
      $form['intro'] = [
        '#markup' => '<p>' . \Drupal::translation()
            ->formatPlural($rc, '1 message processed.', '@count messages processed.') . '</p>',
        '#weight' => 0,
      ];
    }
    else {
      $form['intro'] = [
        '#markup' => '<p>' . \Drupal::translation()
            ->formatPlural($rc, '1 message added to spool.', '@count messages added to spool.') . '</p>',
        '#weight' => 0,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $form_state->setTemporaryValue('wizard', $cached_values);

    $form_state->setRedirect('<front>');
  }

}
