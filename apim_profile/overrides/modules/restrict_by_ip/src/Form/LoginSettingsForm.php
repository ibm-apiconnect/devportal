<?php

namespace Drupal\restrict_by_ip\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\restrict_by_ip\IPToolsInterface;
use Drupal\restrict_by_ip\Exception\InvalidIPException;

/**
 * Class LoginSettingsForm.
 *
 * @package Drupal\restrict_by_ip\Form
 */
class LoginSettingsForm extends ConfigFormBase {

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
    return 'restrict_by_ip_login_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('restrict_by_ip.settings');

    $form['current_ip'] = [
      '#markup' => t('Your current IP address is %ipaddress. If this is wrong, make sure you have the correct header configured in general settings.', ['%ipaddress' => $this->ip_tools->getUserIP()]),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $form['restrict_by_ip_error_page'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login denied error page'),
      '#description' => $this->t("This the address of the page to which the user will be redirected if they are not allowed to login. If you don't set this the user will not know why they couldn't login."),
      '#default_value' => $config->get('error_page'),
    ];
    $form['restrict_by_ip_login_range'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Restrict global login to allowed IP range'),
      '#description' => $this->t('To restrict login for ALL users, enter global IP Address Ranges in CIDR Notation separated with semi-colons, with no trailing semi-colon. E.G. 10.20.30.0/24;192.168.199.1/32;1.0.0.0/8<br />For more information on CIDR notation click <a href="http://www.brassy.net/2007/mar/cidr_basic_subnetting">here</a>.<br />Leave field blank to disable IP restrictions for user login.'),
      '#default_value' => $config->get('login_range'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Add validation that error page is an internal path.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (strlen($form_state->getValue('restrict_by_ip_login_range')) > 0) {
      $ip_addresses = explode(";", $form_state->getValue('restrict_by_ip_login_range'));
      foreach ($ip_addresses as $ip) {
        try {
          $this->ip_tools->validateIP($ip);
        } catch (InvalidIPException $e) {
          $form_state->setErrorByName('restrict_by_ip_login_range', $this->t($e->getMessage() . ' @ip.', ['@ip' => $ip]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('restrict_by_ip.settings')
      ->set('error_page', $form_state->getValue('restrict_by_ip_error_page'))
      ->set('login_range', $form_state->getValue('restrict_by_ip_login_range'))
      ->save();
  }

}
