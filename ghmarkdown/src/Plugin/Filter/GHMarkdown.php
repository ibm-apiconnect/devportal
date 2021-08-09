<?php

namespace Drupal\ghmarkdown\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Url;
use Drupal\ghmarkdown\cebe\markdown\GithubMarkdown;

/**
 * Provides a filter for markdown.
 *
 * @Filter(
 *   id = "ghmarkdown",
 *   module = "ghmarkdown",
 *   title = @Translation("Markdown"),
 *   description = @Translation("Allows content to be submitted using Github Markdown, a simple plain-text syntax that
 *   is filtered into valid HTML."), type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class GHMarkdown extends FilterBase {

  /**
   * @param string $text
   * @param string $langcode
   *
   * @return \Drupal\filter\FilterProcessResult
   */
  public function process($text, $langcode): FilterProcessResult {
    if ($text !== NULL && !empty($text)) {
      $text = (string) self::parse($text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      $returnValue = $this->t('Quick Tips:<ul>
      <li>Two or more spaces at a line\'s end = Line break</li>
      <li>Double returns = Paragraph</li>
      <li>*Single asterisks* or _single underscores_ = <em>Emphasis</em></li>
      <li>**Double** or __double__ = <strong>Strong</strong></li>
      <li>This is [a link](http://the.link.example.com "The optional title text")</li>
      </ul>For complete details on the Markdown syntax, see the <a href="http://daringfireball.net/projects/markdown/syntax">Markdown documentation</a> and <a href="http://michelf.com/projects/php-markdown/extra/">Markdown Extra documentation</a> for tables, footnotes, and more.');
    }
    else {
      $returnValue = $this->t('You can use <a href="@filter_tips">Markdown syntax</a> to format and style the text. Also see <a href="@markdown_extra">Markdown Extra</a> for tables, footnotes, and more.', [
        '@filter_tips' => Url::fromRoute('filter.tips_all')->toString(),
        '@markdown_extra' => 'http://michelf.com/projects/php-markdown/extra/',
      ]);
    }
    return $returnValue;
  }

  /**
   * @param string $text
   *
   * @return null|string
   */
  public static function parse(string $text): ?string {
    if ($text !== NULL && !empty($text)) {
      $parser = new GithubMarkdown();
      $text = $parser->parse($text);
      $returnValue = $text;
    }
    else {
      $returnValue = NULL;
    }
    return $returnValue;
  }

}
