<?php

namespace Drupal\restrict_by_ip\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GeneralSettingsForm.
 *
 * @package Drupal\restrict_by_ip\Form
 */
class GeneralSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'restrict_by_ip.settings'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'restrict_by_ip_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('restrict_by_ip.settings');
    $form['restrict_by_ip_header'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header to check'),
      '#description' => $this->t('This is the HTTP request header that contains the client IP Address. It is sometimes re-written by reverse proxies and Content Distribution Networks.'),
      '#default_value' => $config->get('header'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('restrict_by_ip.settings')
      ->set('header', $form_state->getValue('restrict_by_ip_header'))
      ->save();
  }

}
