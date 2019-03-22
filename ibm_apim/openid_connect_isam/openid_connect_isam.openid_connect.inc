<?php

function openid_connect_isam_oidc_login() {
  $openid_connect_clients_enabled = variable_get('openid_connect_clients_enabled', array());
  if (module_exists('openid_connect') && array_key_exists('isam', $openid_connect_clients_enabled)) {
    openid_connect_save_destination();

    $client = openid_connect_get_client('isam');
    $scopes = openid_connect_get_scopes();
    $_SESSION['openid_connect_op'] = 'login';
    $client->authorize($scopes);
  } else {
    drupal_goto('user/login');
  }
}
