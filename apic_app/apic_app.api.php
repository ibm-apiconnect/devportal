<?php

/**
 * @file
 * Hooks related to IBM APIC Applications.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\node\NodeInterface;
use Drupal\Core\Url;

/**
 * Triggered when an application is created
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param array $app
 *   The array of data returned by API Manager
 */
function hook_apic_app_create(NodeInterface $node, $app) {

}

/**
 * Triggered when an application is updated
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param array $app
 *   The array of data returned by API Manager
 */
function hook_apic_app_update(NodeInterface $node, $app) {

}

/**
 * Triggered when an application is deleted
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param array $data
 *   The array of data returned by API Manager (could be empty)
 * @param string $appId
 *   The ID of the application that has been deleted
 */
function hook_apic_app_delete(NodeInterface $node, $data, $appId) {

}

/**
 * Triggered when an application is promoted
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param array $data
 *   The array of data returned by API Manager (could be empty)
 * @param string $appId
 *   The ID of the application
 */
function hook_apic_app_promote(NodeInterface $node, $data, $appId) {

}

/**
 * Triggered when a new set of credentials is created for an application
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param array $data
 *   The array of data returned by API Manager (could be empty)
 * @param string $credId
 *   The ID of the credentials
 */
function hook_apic_app_creds_create(NodeInterface $node, $data, $credId) {

}

/**
 * Triggered when a set of credentials is updated for an application
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param array $data
 *   The array of data returned by API Manager (could be empty)
 * @param string $credId
 *   The ID of the credentials
 */
function hook_apic_app_creds_update(NodeInterface $node, $data, $credId) {

}

/**
 * Triggered when a set of credentials is deleted for an application
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param array $data
 *   The array of data returned by API Manager (could be empty)
 * @param string $credId
 *   The ID of the credentials
 */
function hook_apic_app_creds_delete(NodeInterface $node, $data, $credId) {

}

/**
 * Triggered when a subscription is created
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param array $data
 *   The array of data returned by API Manager (could be empty)
 * @param string $appId
 *   The application ID
 * @param string $product_url
 *   The URL reference to the product
 * @param string $plan
 *   The plan being subscribed to
 * @param string $subId
 *   The subscription ID
 */
function hook_apic_app_subscribe(NodeInterface $node, $data, $appId, $product_url, $plan, $subId) {

}

/**
 * Triggered when a subscription is migrated to a new plan
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param array $data
 *   The array of data returned by API Manager (could be empty)
 * @param string $appId
 *   The application ID
 * @param string $planId
 *   The plan ID
 * @param string $subId
 *   The subscription ID
 */
function hook_apic_app_migrate(NodeInterface $node, $data, $appId, $planId, $subId) {

}

/**
 * Triggered when an application is unsubscribed from a plan
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param array $data
 *   The array of data returned by API Manager (could be empty)
 * @param string $appId
 *   The application ID
 * @param string $product_url
 *   The URL reference to the product
 * @param string $plan
 *   The plan being subscribed to
 * @param string $subId
 *   The subscription ID
 */
function hook_apic_app_unsubscribe(NodeInterface $node, $data, $appId, $product_url, $plan, $subId) {

}

/**
 * Triggered when a custom application image is created
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param string $appId
 *   The application ID
 */
function hook_apic_app_image_create(NodeInterface $node, $appId) {

}

/**
 * Triggered when a custom application image is deleted
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param string $appId
 *   The application ID
 */
function hook_apic_app_image_delete(NodeInterface $node, $appId) {

}

/**
 * Triggered when a credential client ID is reset
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param array $data
 *   The array of data returned by API Manager (could be empty)
 * @param string $appId
 *   The application ID
 * @param string $credId
 *   The credential ID
 */
function hook_apic_app_clientid_reset(NodeInterface $node, $data, $appId, $credId) {

}

/**
 * Triggered when a credential client secret is reset
 *
 * @param NodeInterface $node
 *   The Drupal node representing this application
 * @param array $data
 *   The array of data returned by API Manager (could be empty)
 * @param string $appId
 *   The application ID
 * @param string $credId
 *   The credential ID
 */
function hook_apic_app_clientsecret_reset(NodeInterface $node, $data, $appId, $credId) {

}

/**
 * Alter the application placeholder image provided in \Drupal\apic_app\Application::getPlaceholderImage().
 * This can be used to define a specific placeholder image used when the consumer has not uploaded their own
 * custom image for their application.
 *
 * @param array $placeholderImage
 *   The path to a placeholder image file.
 */
function hook_apic_app_getplaceholderimage_alter(array &$placeholderImage) {
  $placeholderImage = Url::fromUri('internal:/' . drupal_get_path('module', 'mycustommodule') . '/images/foo.png')
    ->toString();
}

/**
 * Alter the application placeholder image provided in \Drupal\apic_app\Application::getImageForApp().
 * This can be used to provide a full path to a specific image to use for an application overriding any custom
 * image they might have uploaded.
 *
 * @param array $appImage
 *   The path to a placeholder image file.
 */
function hook_apic_app_getimageforapp_alter(array &$appImage) {
  $appImage = 'https://example.com/path/foo.png';
}

/**
 * Alter the client ID provided by API Manager when the ID is reset
 *
 * @param $appId
 * @param $data
 */
function hook_apic_app_client_id_reset_alter($appId, &$data) {
  $data['client_id'] = '12345';
}

/**
 * Alter the client secret provided by API Manager when the secret is reset
 *
 * @param $appId
 * @param $data
 */
function hook_apic_app_client_secret_reset_alter($appId, &$data) {
  $data['client_secret'] = 'abcdefgh';
}

/**
 * Alter the credentials provided by API Manager when a new application is created
 *
 * @param $appId
 * @param $data
 */
function hook_apic_app_create_alter($appId, &$data) {
  $data['client_id'] = '12345';
  $data['client_secret'] = 'abcdefgh';
}

/**
 * Alter the credentials provided by API Manager when new credentials are created
 *
 * @param $appId
 * @param $data
 */
function hook_apic_app_credentials_create_alter($appId, &$data) {
  $data['client_id'] = '12345';
  $data['client_secret'] = 'abcdefgh';
}
/**
 * @} End of "addtogroup hooks".
 */
