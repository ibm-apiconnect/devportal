<?php

namespace Drupal\noopener_filter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Define class NoopenerFilter.
 *
 * @Filter(
 *   id = "filter_noopener",
 *   title = @Translation("Add noopener to all links"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR
 * )
 */
class NoopenerFilter extends FilterBase {

  /**
   * Implement processAttributes().
   */
  public function processAttributes($text) {
    $html_dom = Html::load($text);

    $links = $html_dom->getElementsByTagName('a');

    foreach ($links as $link) {
      if (!empty($link->getAttribute('target')) && $link->getAttribute('target') === '_blank') {
        if (!empty($link->getAttribute('rel'))) {
          $link->setAttribute('rel', 'noopener ' . $link->getAttribute('rel'));
        }
        else {
          $link->setAttribute('rel', 'noopener');
        }
      }
    }

    $text = Html::serialize($html_dom);

    return trim($text);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($this->processAttributes($text));
  }

}
