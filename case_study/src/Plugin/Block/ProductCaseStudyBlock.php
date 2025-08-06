<?php

namespace Drupal\case_study\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Product Case Studies' Block.
 */
#[Block(
  id: "productcasestudy_block",
  admin_label: new TranslatableMarkup("Product Case Studies"),
  category: new TranslatableMarkup("IBM API Developer Portal (Case study)"),
)]

class ProductCaseStudyBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = \Drupal::routeMatch()->getParameter('prodNode') ?? \Drupal::routeMatch()->getParameter('node');
    if(isset($this->configuration['prodNode']) && empty($node)) {
        $node = Node::load($this->configuration['prodNode']);
    }

    $productCaseStudies = [];
    if (!empty($node)) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'case_study');
        $query->condition('field_associated_api_products', [$node->id()], 'IN');
        $productCaseStudies = $query->accessCheck()->execute();
    }

    return [
      '#theme' => 'productcasestudy_block',
      '#productCaseStudies' => $productCaseStudies,
      '#attached' => [
        'library' => [
            'masonry/masonry.layout',
            'case_study/basic'
        ]
      ],
      '#cache' => [
        'max-age' => 0
      ],
    ];
  }

}