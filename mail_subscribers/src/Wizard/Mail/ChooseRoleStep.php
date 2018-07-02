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

class ChooseRoleStep extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mail_subscribers_wizard_choose_role';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    if ($cached_values['objectType'] == 'all') {
      $form['intro'] = array(
        '#markup' => '<p>' . t('This wizard will email all consumer organizations. Each recipient will be sent an individual email.') . '</p>'
          . '<p>' . t('Specify below whether to email all members of each consumer organization or just their owners.') . '</p>',
        '#weight' => 0
      );
    }
    else {
      $form['intro'] = array(
        '#markup' => '<p>' . t('Specify below whether to email all members of each consumer organization or just their owners.') . '</p>',
        '#weight' => 0
      );
    }
    $options = array(
      'owners' => t('Owners'),
      'members' => t('Members')
    );

    $form['role'] = array(
      '#type' => 'radios',
      '#title' => t('Recipient role'),
      '#options' => $options,
      '#description' => t('For each subscribing consumer organization email just the owner or all members?'),
      '#default_value' => 'owners',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (empty($form_state->getUserInput()['role'])) {
      $form_state->setErrorByName('role', t('You must select a role.'));
      return FALSE;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');

    $role = $form_state->getUserInput()['role'];

    $cached_values['role'] = $role;

    $form_state->setTemporaryValue('wizard', $cached_values);

  }

}
