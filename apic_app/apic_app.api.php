<?php

/**
 * @file
 * Hooks related to IBM APIC Applications.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\apic_app\Entity\ApplicationCredentials;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

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
 *
 * @deprecated this hook will be removed. Please use hook_apic_app_pre_delete
 *             or hook_apic_app_post_delete instead.
 *
 */
function hook_apic_app_delete(NodeInterface $node, $data, $appId) {

}

/**
 * Triggered on application deletion before the node deletion or cascade has happened.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The Drupal node representing this application
 * @param array $data
 *   Data related to the application, if present the following is available:
 *      $data['nid'] - the node id
 *      $data['id'] - the application_id field of the application
 *      $data['url'] - the apic_url field of the application
 *      $data['name'] - the application_name field of the application
 *      $data['consumerorg_url'] - the application_consumer_org_url field from the application
 *      $data['application_credentials_refs'] - the entity reference ids of credentials on the application
 */
function hook_apic_app_pre_delete(NodeInterface $node, $data) {

  // In this example we are gathering the client id's for all credentials on this application
  $clientids = [];

  if (isset($data['application_credentials_refs'])) {
    foreach ($data['application_credentials_refs'] as $ref) {
      $credEntity = ApplicationCredentials::load($ref);
      if ($credEntity !== NULL) {
        $clientids[] = $ref;
      }
    }
  }

  // $clientids now contains all of the client ids loaded from the referenced entities

}


/**
 * Triggered on application deletion before the node deletion or cascade has happened.
 *
 * @param array $data
 *   Data related to the application, if present the following is available:
 *      $data['nid'] - the node id
 *      $data['id'] - the application_id field of the application
 *      $data['url'] - the apic_url field of the application
 *      $data['name'] - the application_name field of the application
 *      $data['consumerorg_url'] - the application_consumer_org_url field from the application
 *      $data['application_credentials_refs'] - the entity reference ids of credentials on the application
 */
function hook_apic_app_post_delete($data) {

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
 * Alter the application placeholder image provided in \Drupal\apic_app\Service\ApplicationService->getPlaceholderImage().
 * This can be used to define a specific placeholder image used when the consumer has not uploaded their own
 * custom image for their application.
 *
 * @param string $placeholderImage
 *   The path to a placeholder image file.
 */
function hook_apic_app_modify_getplaceholderimage_alter(string &$placeholderImage) {
  $placeholderImage = Url::fromUri('internal:/' . drupal_get_path('module', 'mycustommodule') . '/images/foo.png')->toString();
}

/**
 * Alter the application placeholder image provided in \Drupal\apic_app\Service\ApplicationService->getImageForApp().
 * This can be used to provide a full path to a specific image to use for an application overriding any custom
 * image they might have uploaded.
 *
 * @param string $appImage
 *   The path to a placeholder image file.
 */
function hook_apic_app_modify_getimageforapp_alter(string &$appImage) {
  $appImage = 'https://example.com/path/foo.png';
}

/**
 * Alter the client ID provided by API Manager when the ID is reset
 *
 * @param $data
 * @param $appId
 */
function hook_apic_app_modify_client_id_reset_alter(&$data, $appId) {
  $data['client_id'] = '12345';
}

/**
 * Alter the client secret provided by API Manager when the secret is reset
 *
 * @param $data
 * @param $appId
 */
function hook_apic_app_modify_client_secret_reset_alter(&$data, $appId) {
  $data['client_secret'] = 'abcdefgh';
}

/**
 * Alter the credentials provided by API Manager when a new application is created
 *
 * @param $data
 * @param $appId
 * @param $formState
 */
function hook_apic_app_modify_create_alter(&$data, $appId, $formState) {
  $data['client_id'] = '12345';
  $data['client_secret'] = 'abcdefgh';
}

/**
 * Alter the credentials provided by API Manager when new credentials are created
 *
 * @param $data
 * @param $appId
 */
function hook_apic_app_modify_credentials_create_alter(&$data, $appId) {
  $data['client_id'] = '12345';
  $data['client_secret'] = 'abcdefgh';
}
/**
 * @} End of "addtogroup hooks".
 */
