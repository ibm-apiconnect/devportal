<?php
/**
 * @file
 * Contains Drupal\ibm_apic_flood_control_ui\Form\FloodControlUIForm.
 */

namespace Drupal\ibm_apic_flood_control_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FloodControlUIForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'user.flood',
      'ibm_apim.settings',
      'contact.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ibm_apic_flood_control_ui_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form = parent::buildForm($form, $form_state);
    $flood_config = $this->config('user.flood');
    $contact_config = $this->config('contact.settings');
    $ibm_apim_config = $this->config('ibm_apim.settings');

    if ((bool) \Drupal::state()->get('ibm_apim.ip_ban_enabled', TRUE)) {
      $form['intro'] = [
        '#markup' => t('Here you can define the number of failed login attempts that are allowed for each time window before further attempts from a user, or a client IP address, are blocked for the duration of the time window. By default the site allows no more than five failed login attempts from the same user in a 6 hour period, and no more than 50 failed login attempts from the same client IP address in a 1 hour period. The site administrator can unblock users, and client IP addresses, before the end of the time window by using Configuration->Flood unblock. You can also configure a limit on the number of "Contact Us" emails that users can send in a specified time period.'),
        '#weight' => -20,
      ];
    } else {
      $form['intro'] = [
        '#markup' => t('Here you can define the number of failed login attempts that are allowed for each time window before further attempts from a user are blocked for the duration of the time window. By default the site allows no more than five failed login attempts from the same user in a 6 hour period. The site administrator can unblock users before the end of the time window by using Configuration->Flood unblock. You can also configure a limit on the number of "Contact Us" emails that users can send in a specified time period.'),
        '#weight' => -20,
      ];
    }

    $form['user'] = [
      '#type' => 'fieldset',
      '#title' => t('Login settings'),
      '#access' => \Drupal::currentUser()->hasPermission('administer users'),
    ];
    if ((bool) \Drupal::state()->get('ibm_apim.ip_ban_enabled', TRUE)) {
      $form['user']['ip_limit'] = [
        '#type' => 'number',
        '#title' => t('Failed IP login limit (min 1)'),
        '#default_value' => $flood_config->get('ip_limit'),
        '#min' => 1,
      ];
      $form['user']['ip_window'] = [
        '#type' => 'number',
        '#title' => $this->t('Failed IP login window in seconds (0 = Off)'),
        '#default_value' => $flood_config->get('ip_window'),
        '#min' => 0,
      ];
    } else {
      \Drupal::messenger()->addWarning(t('IP based security is currently disabled for this portal service so IP based restrictions are not available.'));
    }
    $form['user']['user_limit'] = [
      '#type' => 'number',
      '#title' => t('Failed user login limit (min 1)'),
      '#default_value' => $flood_config->get('user_limit'),
      '#min' => 1,
    ];
    $form['user']['user_window'] = [
      '#type' => 'number',
      '#title' => t('Failed user login window in seconds (0 = Off)'),
      '#default_value' => $flood_config->get('user_window'),
      '#min' => 0,
    ];

    $form['contact'] = [
      '#type' => 'fieldset',
      '#title' => t('Contact forms'),
      '#access' => \Drupal::currentUser()->hasPermission('administer contact forms'),
    ];
    $form['contact']['flood']['limit'] = [
      '#type' => 'number',
      '#title' => t('Emails sent limit'),
      '#default_value' => $contact_config->get('flood.limit'),
      '#min' => 0,
    ];
    $form['contact']['flood']['interval'] = [
      '#type' => 'number',
      '#title' => t('Emails sent window in seconds'),
      '#default_value' => $contact_config->get('flood.interval'),
      '#min' => 0,
    ];

    $form['organization_invitations'] = [
      '#type' => 'fieldset',
      '#title' => t('Organization Invitations'),
      '#access' => \Drupal::currentUser()->hasPermission('administer users'),
    ];
    $form['organization_invitations']['invitation_resend_limit'] = [
      '#type' => 'number',
      '#title' => t('Invitation resend limit'),
      '#default_value' => $ibm_apim_config->get('invitation_resend_limit') ?? 10,
      '#min' => 0,
      '#description' => t('Maximum number of times a single organization invitation can be resent within the configured window. Set to 0 to disable the resend limit.'),
    ];
    $form['organization_invitations']['invitation_resend_window'] = [
      '#type' => 'number',
      '#title' => t('Invitation resend window in seconds'),
      '#default_value' => $ibm_apim_config->get('invitation_resend_window') ?? 3600,
      '#min' => 0,
      '#description' => t('Time window used when counting invitation resend attempts.'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('user.flood')
      ->set('user_limit', $form_state->getValue('user_limit'))
      ->set('user_window', $form_state->getValue('user_window'))
      ->save();
    if ((bool) \Drupal::state()->get('ibm_apim.ip_ban_enabled', TRUE)) {
      $this->config('user.flood')
        ->set('ip_limit', $form_state->getValue('ip_limit'))
        ->set('ip_window', $form_state->getValue('ip_window'))
        ->save();
    }
    $this->config('contact.settings')
      ->set('flood.limit', $form_state->getValue('limit'))
      ->set('flood.interval', $form_state->getValue('interval'))
      ->save();
    $this->config('ibm_apim.settings')
      ->set('invitation_resend_limit', $form_state->getValue('invitation_resend_limit'))
      ->set('invitation_resend_window', $form_state->getValue('invitation_resend_window'))
      ->save();
  }

} 
