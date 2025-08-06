/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Setup masonry for the featured content block
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.productNewsSetup = {
    attach: function (context) {

      $('.featuredNewsContainer').masonry({
        // options
        itemSelector: '.featuredNewsNode', columnWidth: '.featuredNewsNode', gutter: 2, percentPosition: true
      });

    }
  };

})(jQuery, Drupal, drupalSettings);