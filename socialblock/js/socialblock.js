/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Setup masonry for the social block
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.socialblockSetup = {
    attach: function (context) {

      $('.socialblock.container').masonry({
        // options
        itemSelector: '.socialblock.card', columnWidth: '.socialblock.card', gutter: 10
      });

    }
  };

})(jQuery, Drupal, drupalSettings);


