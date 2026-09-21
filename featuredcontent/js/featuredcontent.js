
/**
 * @file
 * Setup masonry for the featured content block
 */

(function($, Drupal, drupalSettings) {

  Drupal.behaviors.featuredContentSetup = {
    attach: function (context) {
      var $container = $(".featuredcontentNodeContainer", context);
      var isRTL = document.documentElement.getAttribute('dir') === 'rtl';

      $(".featuredcontentNodeContainer").masonry({
        // options
        itemSelector: ".featuredcontentNode",
        columnWidth: ".featuredcontentNode",
        gutter: 24,
        percentPosition: true,
        isOriginLeft: !isRTL
      });
      
      var resizeTimer;
      $(window).resize(function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
          $container.masonry('layout');
        }, 200);
      });
    },
  };

})(jQuery, Drupal, drupalSettings);
