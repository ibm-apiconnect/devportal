<?php

/**
 * Allows modules to intercept the application credentials from API Connect
 * and sync or modify them as necessary before display to the end user.
 *
 * @param $appId
 *   The application ID
 * @param $data
 *   The array of new application data
 */
function hook_ibm_apim_application_add_application_alter($appId, &$data) {
  // invoke external server to get new credentials
  $newdata = make_rest_call_to_external_server();
  // set $data to the new credentials
  $data['clientID'] = $newdata['clientid'];
  $data['clientSecret'] = $newdata['clientsecret'];
}

/**
 * Allows modules to intercept the new credentials from API Connect
 * and sync of modify them as necessary before display to the end user.
 *
 * @param $appId
 *   The application ID
 * @param $data
 *   The array of new credentials data
 */
function hook_ibm_apim_application_new_application_clientcreds_alter($appId, &$data) {
  $newdata = make_rest_call_to_external_server($appId);
  $data['clientID'] = $newdata['clientid'];
  $data['clientSecret'] = $newdata['clientsecret'];
}

/**
 * @param $node
 *   The API node
 * @param $api
 *   The data returned by API Connect
 */
function hook_ibm_apim_api_create($node, $api) {

}

/**
 * @param $node
 *   The API node
 * @param $api
 *   The data returned by API Connect
 */
function hook_ibm_apim_api_update($node, $api) {

}

/**
 * @param $node
 *   The product node
 * @param $product
 *   The data returned by API Connect
 */
function hook_ibm_apim_product_create($node, $product) {

}

/**
 * @param $node
 *   The product node
 * @param $product
 *   The data returned by API Connect
 */
function hook_ibm_apim_product_update($node, $product) {

}

/**
 * @param $node
 *   The devorg node
 * @param $devorg
 *   The data returned by API Connect
 */
function hook_ibm_apim_devorg_create($node, $devorg) {

}

/**
 * @param $node
 *   The devorg node
 * @param $devorg
 *   The data returned by API Connect
 */
function hook_ibm_apim_devorg_update($node, $devorg) {

}

/**
 * @param $node
 *   The application node
 * @param $data
 *   The data returned by API Connect
 */
function hook_ibm_apim_application_create($node, $data) {

}

/**
 * @param $node
 *   The application node
 * @param $data
 *   The data returned by API Connect
 */
function hook_ibm_apim_application_delete($node, $data) {

}

/**
 * @param $node
 *   The application node
 * @param $data
 *   The data returned by API Connect
 */
function hook_ibm_apim_application_edit($node, $data) {

}

/**
 * @param $node
 *   The application node
 * @param $data
 *   The data returned by API Connect
 */
function hook_ibm_apim_application_clientsecret_reset($node, $data) {

}

/**
 * @param $node
 *   The application node
 * @param $data
 *   The data returned by API Connect
 */
function hook_ibm_apim_application_clientid_reset($node, $data) {

}

/**
 * @param $node
 *   The application node
 * @param $data
 *   The data returned by API Connect
 */
function hook_ibm_apim_application_unsubscribe($node, $data) {

}

/**
 * @param $node
 *   The application node
 * @param $data
 *   The data returned by API Connect
 */
function hook_ibm_apim_application_subscribe($node, $data) {

}

/**
 * @param $node
 *   The application node
 * @param $data
 *   The data returned by API Connect
 */
function hook_ibm_apim_application_creds_update($node, $data) {

}

/**
 * @param $node
 *   The application node
 * @param $data
 *   The data returned by API Connect
 */
function hook_ibm_apim_application_creds_create($node, $data) {

}

/**
 * @param $node
 *   The application node
 * @param $data
 *   The data returned by API Connect
 */
function hook_ibm_apim_application_creds_delete($node, $data) {

}


