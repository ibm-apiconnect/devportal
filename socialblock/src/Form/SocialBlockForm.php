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
/**
 * @file
 * Contains \Drupal\socialblock\Form\SocialBlockForm
 */

namespace Drupal\socialblock\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\encrypt\Entity\EncryptionProfile;

class SocialBlockForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'socialblock_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('socialblock.settings');
    $data = $config->get('credentials');

    if (isset($data) && !empty($data)) {
      $encryption_profile = EncryptionProfile::load('socialblock');
      if (isset($encryption_profile)) {
        $settings = unserialize(\Drupal::service('encryption')->decrypt($data, $encryption_profile));
      } else {
        $form_state->setError($form, t('The "socialblock" encryption profile is missing.'));
        $settings = array();
      }
    } else {
      $settings = array();
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

    $form['consumerKey'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Application Consumer Key'),
      '#default_value' => $settings['consumerKey'],
      '#description' => $this->t('The Consumer Key displayed in \'Keys and Access Tokens\' in your Twitter application'),
      '#required' => TRUE,
      //'#attributes' => ['autocomplete' => 'off']
    );

    $form['consumerSecret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Application Consumer Secret'),
      '#default_value' => $settings['consumerSecret'],
      '#description' => $this->t('The Consumer Secret displayed in \'Keys and Access Tokens\' in your Twitter application'),
      '#required' => TRUE,
      //'#attributes' => ['autocomplete' => 'off']
    );

    $form['accessToken'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Application Access Token'),
      '#default_value' => $settings['accessToken'],
      '#description' => $this->t('The Access Token displayed in \'Keys and Access Tokens\' in your Twitter application'),
      '#required' => TRUE,
      //'#attributes' => ['autocomplete' => 'off']
    );

    $form['accessTokenSecret'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Application Access Token Secret'),
      '#default_value' => $settings['accessTokenSecret'],
      '#description' => $this->t('The Access Token Secret displayed in \'Keys and Access Tokens\' in your Twitter application'),
      '#required' => TRUE,
      //'#attributes' => ['autocomplete' => 'off']
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $credentials = array();

    $consumerKey = $form_state->getValue('consumerKey');
    $consumerSecret = $form_state->getValue('consumerSecret');
    $accessToken = $form_state->getValue('accessToken');
    $accessTokenSecret = $form_state->getValue('accessTokenSecret');

    $credentials['consumerKey'] = isset($consumerKey) ? trim($consumerKey) : '';
    $credentials['consumerSecret'] = isset($consumerSecret) ? trim($consumerSecret) : '';
    $credentials['accessToken'] = isset($accessToken) ? trim($accessToken) : '';
    $credentials['accessTokenSecret'] = isset($accessTokenSecret) ? trim($accessTokenSecret) : '';

    $no_cred = empty($consumerKey) || empty($consumerSecret) || empty($accessToken) || empty($accessTokenSecret);
    $response = socialblock_call_twitter_api($credentials, 'application/rate_limit_status', array());

    if ($no_cred || empty($response)) {
      $form_state->setError($form, t('The credentials you have entered are invalid'));
      $this->config('socialblock.settings')->delete();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('socialblock.settings');

    $credentials = array();
    $consumerKey = $form_state->getValue('consumerKey');
    $consumerSecret = $form_state->getValue('consumerSecret');
    $accessToken = $form_state->getValue('accessToken');
    $accessTokenSecret = $form_state->getValue('accessTokenSecret');

    $credentials['consumerKey'] = isset($consumerKey) ? trim($consumerKey) : '';
    $credentials['consumerSecret'] = isset($consumerSecret) ? trim($consumerSecret) : '';
    $credentials['accessToken'] = isset($accessToken) ? trim($accessToken) : '';
    $credentials['accessTokenSecret'] = isset($accessTokenSecret) ? trim($accessTokenSecret) : '';


    $encryption_profile = EncryptionProfile::load('socialblock');
    if (isset($encryption_profile)) {
      $encrypted = \Drupal::service('encryption')->encrypt(serialize($credentials), $encryption_profile);
    } else {
      drupal_set_message(t('The "socialblock" encryption profile is missing.'), 'error');
      $encrypted = array();
    }

    $config->set('credentials', $encrypted);
    $config->save();

    // run cron to populate the cache
    socialblock_cron();

    parent::submitForm($form, $form_state);

    return;
  }

  /**
   * {@inheritdoc}
   */

  public function getEditableConfigNames() {

    return ['socialblock.settings', 'socialblock.validate'];

  }

}
