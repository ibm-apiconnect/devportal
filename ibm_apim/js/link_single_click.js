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
 * Mainly used by the OIDC login links/buttons, but could be used by others too
 */

(function($, Drupal, drupalSettings) {

  /**
   * Add a change listener to links styled as buttons
   * Once clicked disable the link to prevent double submission.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   */
  Drupal.behaviors.linkSingleClick = {

    attach: function(context) {
      $("a.button").one("click", function() {
        var clas = $(this).attr("class").replace(/\s/g, '.');
        setTimeout(function() {
          $("." + clas).removeAttr('href').addClass('disabled');
        }, 100);
      });

    }
  };

})(jQuery, Drupal, drupalSettings);
