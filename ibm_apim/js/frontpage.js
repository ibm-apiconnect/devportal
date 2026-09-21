
/**
 * @file
 * Setup masonry for get help block & apps subscribe wizard
 */

(function($, Drupal, drupalSettings) {

  Drupal.behaviors.frontpageSetup = {
    attach: function(context) {
      var isRTL = document.documentElement.getAttribute('dir') === 'rtl';
      $('.apicSubscribeAppsList').masonry({
        // options
        itemSelector: '.apicSubAppCard', columnWidth: '.apicSubAppCard', gutter: 0, isOriginLeft: !isRTL,
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
