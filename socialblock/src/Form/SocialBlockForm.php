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

/**
 * @file
 * Contains \Drupal\socialblock\Form\SocialBlockForm
 */

namespace Drupal\socialblock\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\encrypt\Entity\EncryptionProfile;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SocialBlockForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * SocialBlockForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(ConfigFactoryInterface $config_factory, Messenger $messenger) {
    ConfigFormBase::__construct($config_factory);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Load the service required to construct this class
    return new static($container->get('config.factory'), $container->get('messenger'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'socialblock_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('socialblock.settings');
    $data = $config->get('credentials');

    if ($data !== NULL && !empty($data)) {
      $encryptionProfile = EncryptionProfile::load('socialblock');
      if ($encryptionProfile !== NULL) {
        $settings = unserialize(\Drupal::service('encryption')->decrypt($data, $encryptionProfile), ['allowed_classes' => FALSE]);
      }
      else {
        $form_state->setError($form, t('The "socialblock" encryption profile is missing.'));
        $settings = [];
      }
    }
    else {
      $settings = [];
    }

    if (!isset($settings['consumerKey'])) {
      $settings['consumerKey'] = '';
    }
    if (!isset($settings['consumerSecret'])) {
      $settings['consumerSecret'] = '';
    }
    if (!isset($settings['accessToken'])) {
      $settings['accessToken'] = '';
    }
    if (!isset($settings['accessTokenSecret'])) {
      $settings['accessTokenSecret'] = '';
    }

    $form['consumerKey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Application Consumer Key'),
      '#default_value' => $settings['consumerKey'],
      '#description' => $this->t('The Consumer Key displayed in \'Keys and Access Tokens\' in your Twitter application'),
      '#required' => TRUE,
      //'#attributes' => ['autocomplete' => 'off']
    ];

    $form['consumerSecret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Application Consumer Secret'),
      '#default_value' => $settings['consumerSecret'],
      '#description' => $this->t('The Consumer Secret displayed in \'Keys and Access Tokens\' in your Twitter application'),
      '#required' => TRUE,
      //'#attributes' => ['autocomplete' => 'off']
    ];

    $form['accessToken'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Application Access Token'),
      '#default_value' => $settings['accessToken'],
      '#description' => $this->t('The Access Token displayed in \'Keys and Access Tokens\' in your Twitter application'),
      '#required' => TRUE,
      //'#attributes' => ['autocomplete' => 'off']
    ];

    $form['accessTokenSecret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Application Access Token Secret'),
      '#default_value' => $settings['accessTokenSecret'],
      '#description' => $this->t('The Access Token Secret displayed in \'Keys and Access Tokens\' in your Twitter application'),
      '#required' => TRUE,
      //'#attributes' => ['autocomplete' => 'off']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {

    $credentials = [];

    $consumerKey = $form_state->getValue('consumerKey');
    $consumerSecret = $form_state->getValue('consumerSecret');
    $accessToken = $form_state->getValue('accessToken');
    $accessTokenSecret = $form_state->getValue('accessTokenSecret');

    $credentials['consumerKey'] = $consumerKey !== NULL ? trim($consumerKey) : '';
    $credentials['consumerSecret'] = $consumerSecret !== NULL ? trim($consumerSecret) : '';
    $credentials['accessToken'] = $accessToken !== NULL ? trim($accessToken) : '';
    $credentials['accessTokenSecret'] = $accessTokenSecret !== NULL ? trim($accessTokenSecret) : '';

    $noCred = empty($consumerKey) || empty($consumerSecret) || empty($accessToken) || empty($accessTokenSecret);
    $response = socialblock_call_twitter_api($credentials, 'application/rate_limit_status', []);

    if ($noCred || empty($response)) {
      $form_state->setError($form, t('The credentials you have entered are invalid'));
      $this->config('socialblock.settings')->delete();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('socialblock.settings');

    $credentials = [];
    $consumerKey = $form_state->getValue('consumerKey');
    $consumerSecret = $form_state->getValue('consumerSecret');
    $accessToken = $form_state->getValue('accessToken');
    $accessTokenSecret = $form_state->getValue('accessTokenSecret');

    $credentials['consumerKey'] = $consumerKey !== NULL ? trim($consumerKey) : '';
    $credentials['consumerSecret'] = $consumerSecret !== NULL ? trim($consumerSecret) : '';
    $credentials['accessToken'] = $accessToken !== NULL ? trim($accessToken) : '';
    $credentials['accessTokenSecret'] = $accessTokenSecret !== NULL ? trim($accessTokenSecret) : '';


    $encryptionProfile = EncryptionProfile::load('socialblock');
    if ($encryptionProfile !== NULL) {
      $encrypted = \Drupal::service('encryption')->encrypt(serialize($credentials), $encryptionProfile);
    }
    else {
      $this->messenger->addError(t('The "socialblock" encryption profile is missing.'));
      $encrypted = [];
    }

    $config->set('credentials', $encrypted);
    $config->save();

    // run cron to populate the cache
    socialblock_cron();

    parent::submitForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */

  public function getEditableConfigNames(): array {

    return ['socialblock.settings', 'socialblock.validate'];

  }

}
