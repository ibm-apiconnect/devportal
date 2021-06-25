<?php

namespace Drupal\restrict_by_ip\Form;

use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\restrict_by_ip\IPToolsInterface;
use Drupal\restrict_by_ip\Exception\InvalidIPException;

/**
 * Class UserSettingsForm.
 *
 * @package Drupal\restrict_by_ip\Form
 */
class UserSettingsForm extends ConfigFormBase {

  protected $ip_tools;

  public function __construct(IPToolsInterface $ip_tools) {
    $this->ip_tools = $ip_tools;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('restrict_by_ip.ip_tools')
    );
  }

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
    return 'restrict_by_ip_user_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('restrict_by_ip.settings');

    $form['new_restriction'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add new user allowed IP range'),
    ];
    $form['new_restriction']['name'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => t('Username'),
    ];
    $form['new_restriction']['restriction'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed IP range'),
      '#description' => t('Enter IP Address Ranges in CIDR Notation separated with semi-colons, with no trailing semi-colon. E.G. 10.20.30.0/24;192.168.199.1/32;1.0.0.0/8<br />For more information on CIDR notation click <a href="http://www.brassy.net/2007/mar/cidr_basic_subnetting">here</a>.'),
      '#maxlength' => NULL,
    ];

    // Current restrictions.
    foreach ($config->get('user') as $key => $value) {
      $account = User::load($key);
      $form['restrict_by_ip_user_' . $key] = [
        '#type' => 'textfield',
        '#title' => $this->t('@name user IP range', ['@name' => $account->label()]),
        '#description' => $this->t('Enter IP Address Ranges in CIDR Notation separated with semi-colons, with no trailing semi-colon. E.G. 10.20.30.0/24;192.168.199.1/32;1.0.0.0/8<br />For more information on CIDR notation click <a href="http://www.brassy.net/2007/mar/cidr_basic_subnetting">here</a>.<br />Leave field blank to disable IP restrictions for @name.', ['@name' => $account->label()]),
        '#default_value' => $config->get('user.' . $key),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $config = $this->config('restrict_by_ip.settings');

    // Validation for existing restrictions.
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'restrict_by_ip_user_') !== FALSE && strlen($value) > 0) {
        $ip_addresses = explode(";", $value);
        foreach ($ip_addresses as $ip) {
          try {
            $this->ip_tools->validateIP($ip);
          }
          catch (InvalidIPException $e) {
            $form_state->setErrorByName($key, $this->t($e->getMessage() . ' @ip.', ['@ip' => $ip]));
          }
        }
      }
    }

    // Validation for new restriction.
    if (strlen($form_state->getValue('name')) > 0) {
      // Validate no existing restriction.
      if ($config->get('user.' . $form_state->getValue('name')) !== NULL) {
        $form_state->setErrorByName('name', $this->t('Restriction for that user already exist.'));
      }

      // Validate restriction.
      $ip_addresses = explode(";", $form_state->getValue('restriction'));
      foreach ($ip_addresses as $ip) {
        try {
          $this->ip_tools->validateIP($ip);
        }
        catch (InvalidIPException $e) {
          $form_state->setErrorByName('restriction', $this->t($e->getMessage() . ' @ip.', ['@ip' => $ip]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('restrict_by_ip.settings');

    // Existing restrictions.
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'restrict_by_ip_user_') === FALSE) {
        continue;
      }

      if (strlen($value) > 0) {
        $config->set(str_replace('restrict_by_ip_user_', 'user.', $key), $value);
      }
      else {
        $config->clear(str_replace('restrict_by_ip_user_', 'user.', $key));
      }
    }

    // New restriction.
    if (strlen($form_state->getValue('name')) > 0) {
      $config->set('user.' . $form_state->getValue('name'), $form_state->getValue('restriction'));
    }

    $config->save();
  }

}
