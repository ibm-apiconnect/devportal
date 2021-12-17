<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\product\Product;

/**
 * Provides a block to add the title to the subscription wizard.
 *
 * @Block(
 *   id = "ibm_apim_wizard_title",
 *   admin_label = @Translation("Subscription Wizard Title"),
 *   category = @Translation("Subscription Wizard Title"),
 * )
 */
class SubWizardTitleBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];

    $current_route = \Drupal::routeMatch()->getRouteName();
    $wizard_routes = [
      'ibm_apim.subscription_wizard',
      'ibm_apim.subscription_wizard.step',
      'ibm_apim.subscription_wizard.noplan',
      'ibm_apim.subscription_wizard.noplan.step',
    ];
    if (isset($current_route) && in_array($current_route, $wizard_routes)) {
      /** @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory $temp_store_factory */
      $temp_store_factory = \Drupal::service('session_based_temp_store');
      $temp_store = $temp_store_factory->get('ibm_apim.wizard');
      // First time through, the productId comes from the url
      $product_id = \Drupal::request()->query->get('productId');
      if (empty($product_id)) {
        // If someone pushed "previous" from the choose app page, we need the productId out of the wizard context
        $product_id = $temp_store->get('productId');
      }
      $productTitle = '';
      $productVersion = '';
      if (isset($product_id)) {
        $product_node = Node::load($product_id);
        if ($product_node !== NULL && $product_node->bundle() === 'product' && Product::checkAccess($product_node)) {
          $productTitle = $product_node->getTitle();
          $productVersion = $product_node->apic_version->value;
        }
      }

      $build['#header'] = t('Subscribe to @product @version', ['@product' => $productTitle, '@version' => $productVersion]);
      $build['#theme'] = 'ibm_apim_wizard_title_block';
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return 0;
  }

}
