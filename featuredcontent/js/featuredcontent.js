/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

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
