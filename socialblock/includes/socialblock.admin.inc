<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @param $form
 * @param $form_state
 * @return mixed
 */
function socialblock_admin($form, &$form_state) {
  $client_id = variable_get('ibm_apim_client_id');
  $data = variable_get('twitter_oauth_settings');
  $iv_size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CBC);
  $iv = substr($data, 0, $iv_size);
  if (strlen($client_id) < 32) {
    $char = 32 - strlen($client_id);
    $client_id = str_pad($client_id, 32, $char);
  }
  $client_id = (strlen($client_id) > 32) ? substr($client_id,0,32) : $client_id;
  if (isset($data) && !empty($data) && isset($client_id) && isset($iv_size)) {
    $settings = unserialize(openssl_decrypt(substr($data, $iv_size), "AES-256-CBC", $client_id, 0, $iv));
  } else {
    $settings = array();
  }
  if (!isset($settings['twitter_consumer_key'])) {
    $settings['twitter_consumer_key'] = '';
  }
  if (!isset($settings['twitter_consumer_secret'])) {
    $settings['twitter_consumer_secret'] = '';
  }
  if (!isset($settings['twitter_access_token'])) {
    $settings['twitter_access_token'] = '';
  }
  if (!isset($settings['twitter_token_secret'])) {
    $settings['twitter_token_secret'] = '';
  }
  $form['intro'] = array(
    '#markup' => '<p>' . t('You must register a Twitter App in order to configure the social block. For information on creating Twitter Apps, see <a href="https://apps.twitter.com" target="_blank">https://apps.twitter.com</a>. Then enter the Twitter application information below.') . '</p>'
  );
  $form['twitter_consumer_key'] = array(
    '#type' => 'textfield',
    '#title' => t('Consumer key'),
    '#default_value' => $settings['twitter_consumer_key'],
    '#required' => FALSE
  );
  $form['twitter_consumer_secret'] = array(
    '#type' => 'textfield',
    '#title' => t('Consumer secret'),
    '#default_value' => $settings['twitter_consumer_secret'],
    '#required' => FALSE
  );
  $form['twitter_access_token'] = array(
    '#type' => 'textfield',
    '#title' => t('Access token'),
    '#default_value' => $settings['twitter_access_token'],
    '#required' => FALSE
  );
  $form['twitter_token_secret'] = array(
    '#type' => 'textfield',
    '#title' => t('Token secret'),
    '#default_value' => $settings['twitter_token_secret'],
    '#required' => FALSE
  );

  $form = system_settings_form($form);
  $form['#attributes']['autocomplete'] = 'off';

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save configuration'));
  if (!empty($_POST) && form_get_errors()) {
    drupal_set_message(t('The settings have not been saved because of the errors.'), 'error');
  }
  $form['#validate'][] = 'socialblock_admin_variable_settings_form_validate';
  $form['#submit'][] = 'socialblock_admin_variable_settings_form_submit';
  // By default, render the form using theme_system_settings_form().
  if (!isset($form['#theme'])) {
    $form['#theme'] = 'system_settings_form';
  }

  return $form;
}


function socialblock_admin_variable_settings_form_validate($form, &$form_state) {
  $credentials = array();
  $credentials['twitter_consumer_key'] = isset($form_state['values']['twitter_consumer_key']) ? trim($form_state['values']['twitter_consumer_key']) : '';
  $credentials['twitter_consumer_secret'] = isset($form_state['values']['twitter_consumer_secret']) ? trim($form_state['values']['twitter_consumer_secret']) : '';
  $credentials['twitter_access_token'] = isset($form_state['values']['twitter_access_token']) ? trim($form_state['values']['twitter_access_token']) : '';
  $credentials['twitter_token_secret'] = isset($form_state['values']['twitter_token_secret']) ? trim($form_state['values']['twitter_token_secret']) : '';
  $no_cred = empty($form_state['values']['twitter_consumer_key']) && empty($form_state['values']['twitter_consumer_secret']) && empty($form_state['values']['twitter_access_token']) && empty($form_state['values']['twitter_token_secret']);
  $response = socialblock_call_twitter_api($credentials, 'application/rate_limit_status', array());
  if (!$no_cred && $response == NULL) {
    form_set_error(t('The credentials you have entered are invalid.'));
    drupal_set_message(t('The credentials you have entered are invalid.'), 'error');
  }
}

/**
 * @param $form
 * @param $form_state
 */
function socialblock_admin_variable_settings_form_submit($form, &$form_state) {
  // Exclude unnecessary elements.
  form_state_values_clean($form_state);
  $credentials = array();
  if (isset($form_state['values']['twitter_consumer_key'])) {
    $credentials['twitter_consumer_key'] = trim($form_state['values']['twitter_consumer_key']);
  }
  if (isset($form_state['values']['twitter_consumer_secret'])) {
    $credentials['twitter_consumer_secret'] = trim($form_state['values']['twitter_consumer_secret']);
  }
  if (isset($form_state['values']['twitter_access_token'])) {
    $credentials['twitter_access_token'] = trim($form_state['values']['twitter_access_token']);
  }
  if (isset($form_state['values']['twitter_token_secret'])) {
    $credentials['twitter_token_secret'] = trim($form_state['values']['twitter_token_secret']);
  }
  $client_id = variable_get('ibm_apim_client_id');
  $iv_size = mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CBC);
  $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
  if (strlen($client_id) < 32) {
    $char = 32 - strlen($client_id);
    $client_id = str_pad($client_id, 32, $char);
  }
  $client_id = (strlen($client_id) > 32) ? substr($client_id,0,32) : $client_id;
  $value = openssl_encrypt(serialize($credentials), "AES-256-CBC", $client_id, 0, $iv);
  variable_set('twitter_oauth_settings', $iv . $value);
  drupal_set_message(t('The configuration options have been saved.'));
  // run cron to populate the cache
  socialblock_cron();
}