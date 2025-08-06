<?php

namespace Drupal\ibm_apic_news\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[Block(
  id: "home_page_news_block",
  admin_label: new TranslatableMarkup("Home Page News"),
  category: new TranslatableMarkup("Home Page News")
)]

class HomePageNewsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $articles = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1)
      ->condition('type', 'article')
      ->sort('created', 'DESC')
      ->range(0, 3)
      ->execute();

    $nodes = Node::loadMultiple($articles);
    $newsContainer = [];
    $lang_code = \Drupal::languageManager()->getCurrentLanguage()->getId();
    foreach ($nodes as $node) {
      $hasTranslation = $node->hasTranslation($lang_code);
      if ($hasTranslation === TRUE) {
        $node = $node->getTranslation($lang_code);
      }
      $title = $node->label();
      $url = $node->toUrl()->toString();

      $image_url = '';
      if ($node->hasField('field_image') && !$node->get('field_image')->isEmpty()) {
        $file = File::load($node->get('field_image')->target_id);
        if (isset($file)) {
          $image_url = $file->createFileUrl();
        }
      }

      $summary = $node->get('body')->summary;
      if (empty($summary) || strlen($summary) === 0) {
        $body = $node->get('body')->value;
        $body = strip_tags((string)$body);
        $summary = substr($body, 0, 200);
      }

      $newsContainer[] = [
        'nid' => $node->id(),
        'title' => $title,
        'url' => $url,
        'image' => $image_url,
        'summary' => $summary,
      ];
    }

    return [
      '#theme' => 'home_page_news_block',
      '#newsContainer' => $newsContainer,
      '#attached' => [
        'library' => [
            'masonry/masonry.layout',
            'ibm_apic_news/news_style'
        ]
      ],
      '#cache' => ['max-age' => 0],
    ];
  }

}
