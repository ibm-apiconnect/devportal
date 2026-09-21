
/**
 * @file
 * Setup masonry for the featured content block
 */

(function($, Drupal, drupalSettings) {

    Drupal.behaviors.productRecommendationSetup = {
        attach: function(context) {
          var isRTL = document.documentElement.getAttribute('dir') === 'rtl';

          $('.productrecommendationsCards').masonry({
            // options
            itemSelector: '.node--view-mode-card', columnWidth: '.node--view-mode-card', gutter: 2, isOriginLeft: !isRTL
          });

        }
      };

  })(jQuery, Drupal, drupalSettings);