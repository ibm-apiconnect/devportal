<?php

/**
 * @param $form
 * @param $form_state
 * @return mixed
 */
function socialblock_admin($form, &$form_state) {
  $client_id = variable_get('ibm_apim_client_id');
  $settings = unserialize(openssl_decrypt(variable_get('twitter_oauth_settings'), 'AES256', $client_id));
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
    '#markup' => '<p>' . t('You must register a Twitter App in order to configure the social block. For information on creating Twitter Apps, see <a href="https://apps.twitter.com">https://apps.twitter.com</a>. Then enter the Twitter application information below.') . '</p>'
  );
  $form['twitter_consumer_key'] = array(
    '#type' => 'textfield',
    '#title' => t('Consumer key'),
    '#default_value' => $settings['twitter_consumer_key'],
    '#required' => TRUE
  );
  $form['twitter_consumer_secret'] = array(
    '#type' => 'textfield',
    '#title' => t('Consumer secret'),
    '#default_value' => $settings['twitter_consumer_secret'],
    '#required' => TRUE
  );
  $form['twitter_access_token'] = array(
    '#type' => 'textfield',
    '#title' => t('Access token'),
    '#default_value' => $settings['twitter_access_token'],
    '#required' => TRUE
  );
  $form['twitter_token_secret'] = array(
    '#type' => 'textfield',
    '#title' => t('Token secret'),
    '#default_value' => $settings['twitter_token_secret'],
    '#required' => TRUE
  );

  $form = system_settings_form($form);

  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save configuration'));
  if (!empty($_POST) && form_get_errors()) {
    drupal_set_message(t('The settings have not been saved because of the errors.'), 'error');
  }
  $form['#submit'][] = 'socialblock_admin_variable_settings_form_submit';
  // By default, render the form using theme_system_settings_form().
  if (!isset($form['#theme'])) {
    $form['#theme'] = 'system_settings_form';
  }

  return $form;
}

function socialblock_admin_variable_settings_form_submit($form, &$form_state) {
  // Exclude unnecessary elements.
  form_state_values_clean($form_state);
  $settings = array();
  if (isset($form_state['values']['twitter_consumer_key'])) {
    $settings['twitter_consumer_key'] = $form_state['values']['twitter_consumer_key'];
  }
  if (isset($form_state['values']['twitter_consumer_secret'])) {
    $settings['twitter_consumer_secret'] = $form_state['values']['twitter_consumer_secret'];
  }
  if (isset($form_state['values']['twitter_access_token'])) {
    $settings['twitter_access_token'] = $form_state['values']['twitter_access_token'];
  }
  if (isset($form_state['values']['twitter_token_secret'])) {
    $settings['twitter_token_secret'] = $form_state['values']['twitter_token_secret'];
  }
  $client_id = variable_get('ibm_apim_client_id');
  $value = openssl_encrypt(serialize($settings), 'AES256', $client_id);
  variable_set('twitter_oauth_settings', $value);

  drupal_set_message(t('The configuration options have been saved.'));
  // run cron to populate the cache
  socialblock_cron();
}