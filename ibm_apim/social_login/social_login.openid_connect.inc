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
 * @file
 * Forms for OpenID Connect.
 */

/**
 * Form submit handler: Log in with an OpenID Connect login provider.
 */
function social_login_oidc_generic_login() {
  $openid_connect_clients_enabled = variable_get('openid_connect_clients_enabled', array());
  if (module_exists('openid_connect') && array_key_exists('generic', $openid_connect_clients_enabled)) {
    openid_connect_save_destination();

    $client = openid_connect_get_client('generic');
    $scopes = openid_connect_get_scopes();
    $_SESSION['openid_connect_op'] = 'login';
    $client->authorize($scopes);
  } else {
    drupal_goto('user/login');
  }
}

/**
 * Form submit handler: Log in with an OpenID Connect login provider.
 */
function social_login_oidc_google_login() {
  $openid_connect_clients_enabled = variable_get('openid_connect_clients_enabled', array());
  if (module_exists('openid_connect') && array_key_exists('google', $openid_connect_clients_enabled)) {
    openid_connect_save_destination();

    $client = openid_connect_get_client('google');
    $scopes = openid_connect_get_scopes();
    $_SESSION['openid_connect_op'] = 'login';
    $client->authorize($scopes);
  } else {
    drupal_goto('user/login');
  }
}
