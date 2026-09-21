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

/**
 * @file
 * Contains \Drupal\socialblock\Form\SocialBlockForm
 */

namespace Drupal\socialblock\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\encrypt\Entity\EncryptionProfile;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ibm_apim\External\SerializationUtility;

class SocialBlockForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * SocialBlockForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager, Messenger $messenger) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Load the service required to construct this class
    return new static($container->get('config.factory'), $container->get('config.typed'), $container->get('messenger'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'socialblock_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form = parent::buildForm($form, $form_state);
    $settings = $this->getSettings($form_state);

    if (!isset($settings['blueSkyUserName'])) {
      $settings['blueSkyUserName'] = '';
    }
    if (!isset($settings['blueSkyAppPassword'])) {
      $settings['blueSkyAppPassword'] = '';
    }

    $form = $this->buildBlueSkyForm($form, $settings);

    $form['top_actions'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['custom-top-buttons']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $credentials = [];
    //Connect to Bluesky
      if ($form_state->getValue('blueSkyUserName') !== NULL && $form_state->getValue('blueSkyAppPassword') !== NULL ) {
        $blueSkyUserName = $form_state->getValue('blueSkyUserName');
        $blueSkyUserName = $this->toBasicAscii($blueSkyUserName);
        $blueSkyAppPassword = $form_state->getValue('blueSkyAppPassword');
        $blueSkyAppPassword = $this->toBasicAscii($blueSkyAppPassword);

        $credentials['blueSkyUserName'] = $blueSkyUserName !== NULL ? trim($blueSkyUserName) : '';
        $credentials['blueSkyAppPassword'] = $blueSkyAppPassword !== NULL ? trim($blueSkyAppPassword) : '';

        $noCred = empty($blueSkyUserName) || empty($blueSkyAppPassword);

        $response = socialblock_call_bluesky_api($credentials, 'app.bsky.actor.getProfile', [
          'actor' => $blueSkyUserName
        ]);
        if ($noCred || empty($response)) {
          $form_state->setError($form, t('The credentials you have entered are invalid'));
          
          //Remove Values From Config
          $settings = $this->getSettings($form_state);
          unset($settings['blueSkyUserName']);
          unset($settings['blueSkyAppPassword']);
          $this->setSettings($settings);
        }
      }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $credentials = $this->getSettings($form_state) ?? [];
    if ($form_state->getValue('consumerKey') !== NULL && $form_state->getValue('consumerSecret') !== NULL && $form_state->getValue('accessToken') !== NULL && $form_state->getValue('accessTokenSecret') !== NULL) {
      $consumerKey = $form_state->getValue('consumerKey');
      $consumerSecret = $form_state->getValue('consumerSecret');
      $accessToken = $form_state->getValue('accessToken');
      $accessTokenSecret = $form_state->getValue('accessTokenSecret');
  
      $credentials['consumerKey'] = $consumerKey !== NULL ? trim($consumerKey) : '';
      $credentials['consumerSecret'] = $consumerSecret !== NULL ? trim($consumerSecret) : '';
      $credentials['accessToken'] = $accessToken !== NULL ? trim($accessToken) : '';
      $credentials['accessTokenSecret'] = $accessTokenSecret !== NULL ? trim($accessTokenSecret) : '';
    }
    elseif ($form_state->getValue('blueSkyUserName') !== NULL && $form_state->getValue('blueSkyAppPassword') !== NULL ) {
      $blueSkyUserName = $form_state->getValue('blueSkyUserName');
      $blueSkyAppPassword = $form_state->getValue('blueSkyAppPassword');

      $credentials['blueSkyUserName'] = $blueSkyUserName !== NULL ? trim($blueSkyUserName) : '';
      $credentials['blueSkyAppPassword'] = $blueSkyAppPassword !== NULL ? trim($blueSkyAppPassword) : '';
    }
    $this->setSettings($credentials);
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
  

  public function buildBlueSkyForm(array $form, array $settings) {
    $form['blueSkyUserName'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bluesky Username'),
      '#default_value' => $settings['blueSkyUserName'],
      '#description' => $this->t('Your full Bluesky handle as shown in your profile'),
      '#required' => TRUE,
      '#default_value' => $settings['blueSkyUserName'] ?? '',
    ];

    $form['blueSkyAppPassword'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bluesky App Password'),
      '#default_value' => $settings['blueSkyAppPassword'],
      '#description' => $this->t('The App Password generated in the \'App Passwords\' section of your Bluesky account settings'),
      '#required' => TRUE,
      '#default_value' => $settings['blueSkyAppPassword'] ?? '',
    ];
    return $form;
  }

  public function getSettings($form_state) {
    $config = $this->config('socialblock.settings');
    $data = $config->get('credentials');

    if ($data !== NULL && !empty($data)) {
      $encryptionProfile = EncryptionProfile::load('socialblock');
      if ($encryptionProfile !== NULL) {
        return SerializationUtility::decodeData(\Drupal::service('encryption')->decrypt($data, $encryptionProfile), TRUE, FALSE);
      }
      else {
        $form_state->setError($form, t('The "socialblock" encryption profile is missing.'));
        return [];
      }
    }
    return [];
  }

  public function setSettings($credentials) {
    $config = $this->config('socialblock.settings');
    $encryptionProfile = EncryptionProfile::load('socialblock');
    if ($encryptionProfile !== NULL) {
      $encrypted = \Drupal::service('encryption')->encrypt(SerializationUtility::encodeData($credentials), $encryptionProfile);
    }
    else {
      $this->messenger->addError(t('The "socialblock" encryption profile is missing.'));
      $encrypted = [];
    }
    $config->set('credentials', $encrypted);
    $config->save();
  }

  public function toBasicAscii($text) {
    $text = mb_convert_encoding($text, 'UTF-8', 'auto');
    $text = preg_replace('/[\x00-\x1F\x7F\x{200B}-\x{200D}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u', '', $text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    return $text;
  }
}
