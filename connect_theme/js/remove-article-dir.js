/**
 * @file
 * Remove dir attribute from article elements.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Remove dir attribute from article elements.
   */
  Drupal.behaviors.removeArticleDir = {
    attach: function (context, settings) {
      once('remove-article-dir', 'article', context).forEach(function (article) {
        article.removeAttribute('dir');
      });
    }
  };

  /**
   * Override toolbar displacement offset to 0px.
   */
  Drupal.behaviors.toolbarOffsetOverride = {
    attach: function (context, settings) {
      // Override the CSS variable on the document root
      document.documentElement.style.setProperty('--drupal-displace-offset-top', '0px');
    }
  };

})(Drupal, once);