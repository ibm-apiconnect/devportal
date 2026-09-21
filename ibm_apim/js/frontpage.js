/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

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
