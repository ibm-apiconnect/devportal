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

use Symfony\Component\HttpFoundation\RedirectResponse;

use Drupal\Core\Form\FormBase;

class MailSummary extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_subscribers_wizard_result';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $cached_values = $form_state->getTemporaryValue('wizard');
    $rc = $cached_values['result'];

    if ($rc == 0) {
      $form['intro'] = array(
        '#markup' => '<p>' . t('No messages sent. This is likely due to there not being any subscribers for the selected criteria.') . '</p>',
        '#weight' => 0
      );
    }
    elseif (isset($cached_values['direct']) && $cached_values['direct'] == true) {
      $form['intro'] = array(
        '#markup' => '<p>' . \Drupal::translation()->formatPlural($rc, '1 message processed.', '@count messages processed.') . '</p>',
        '#weight' => 0
      );
    } else {
      $form['intro'] = array(
        '#markup' => '<p>' . \Drupal::translation()->formatPlural($rc, '1 message added to spool.', '@count messages added to spool.') . '</p>',
        '#weight' => 0
      );
    }

    $form_state->setTemporaryValue('wizard', $cached_values);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $form_state->setTemporaryValue('wizard', $cached_values);

    $form_state->setRedirect('<front>');
  }

}
