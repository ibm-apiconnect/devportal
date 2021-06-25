<?php

namespace Drupal\restrict_by_ip\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\restrict_by_ip\IPToolsInterface;
use Drupal\restrict_by_ip\Exception\InvalidIPException;
use Drupal\Core\Url;

/**
 * Class RoleSettingsForm.
 *
 * @package Drupal\restrict_by_ip\Form
 */
class RoleSettingsForm extends ConfigFormBase {

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
    return 'restrict_by_ip_role_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('restrict_by_ip.settings');

    $user_roles = user_roles(TRUE); // Get all roles except anonymous
    unset($user_roles['authenticated']); // Remove default authenticated user role

    if (count($user_roles) === 0) {
      $form['no_roles'] = [
        '#markup' => $this->t('No roles configured. <a href="@add-role">Add a role</a>.', ['@add-role' => Url::fromRoute('entity.user_role.collection')]),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }

    foreach ($user_roles as $role) {
      $form['restrict_by_ip_role_' . $role->id()] = [
        '#type' => 'textfield',
        '#title' => $this->t('@role-label role IP range', ['@role-label' => $role->label()]),
        '#maxlength' => NULL,
        '#description' => $this->t('Enter IP Address Ranges in CIDR Notation separated with semi-colons, with no trailing semi-colon. E.G. 10.20.30.0/24;192.168.199.1/32;1.0.0.0/8<br />For more information on CIDR notation click <a href="http://www.brassy.net/2007/mar/cidr_basic_subnetting">here</a>.<br />Leave field blank to disable IP restrictions for ' . $role->label() . '.'),
        '#default_value' => $config->get('role.' . $role->id()),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'restrict_by_ip_role_') !== FALSE && strlen($value) > 0) {
        $ip_addresses = explode(";", $value);
        foreach ($ip_addresses as $ip) {
          try {
            $this->ip_tools->validateIP($ip);
          } catch (InvalidIPException $e) {
            $form_state->setErrorByName($key, $this->t($e->getMessage() . ' @ip.', ['@ip' => $ip]));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'restrict_by_ip_role_') === FALSE) {
        continue;
      }

      $this->config('restrict_by_ip.settings')
        ->set(str_replace('restrict_by_ip_role_', 'role.', $key), $value);
    }

    $this->config('restrict_by_ip.settings')
      ->save();
  }

}
