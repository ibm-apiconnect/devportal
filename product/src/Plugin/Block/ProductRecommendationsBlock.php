<?php

namespace Drupal\product\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\product\Product;
use Drupal\node\Entity\Node;
use Drupal\Core\Plugin\Context\ContextDefinition;


/**
 * Provides a 'API Product Recommendations' Block.
 */
#[Block(
  id: "productrecommendations_block",
  admin_label: new TranslatableMarkup("API Product Recommendations"),
  category: new TranslatableMarkup("API Product Recommendations"),
)]

class ProductRecommendationsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('prodNode') ?? \Drupal::routeMatch()->getParameter('node');
    if(isset($this->configuration['prodNode']) && empty($node)) {
      $node = Node::load($this->configuration['prodNode']);
    }
    $recommendedProducts = [];
    if (!empty($node)) {
      $products = Product::getProductRecommendations($node->apic_url->value);
      foreach ($products as $product) {
        $recommendedProducts[] = $product->id();
      }
    }

    return [
      '#theme' => 'productrecommendations_block',
      '#productRecommendations' => $recommendedProducts,
      '#attached' => [
        'library' => [
            'masonry/masonry.layout',
            'product/productrecommendations'
        ]
      ],
      '#cache' => [
        'max-age' => 0
      ],
    ];
  }

}