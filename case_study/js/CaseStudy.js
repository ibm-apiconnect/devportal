/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2024
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

  Drupal.behaviors.productCaseStudySetup = {
    attach: function (context) {

      $('.productcasestudyCards').masonry({
        // options
        itemSelector: '.node--view-mode-card', columnWidth: '.node--view-mode-card', gutter: 2
      });

      $('.featuredCaseStudiesContainer').masonry({
        // options
        itemSelector: '.featuredCaseStudiesNode', columnWidth: '.featuredCaseStudiesNode', gutter: 2, percentPosition: true
      });

    }
  };

})(jQuery, Drupal, drupalSettings);