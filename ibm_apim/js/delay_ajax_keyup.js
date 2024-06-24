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
 * Mainly used by the password policy validator to respond to finishing typing instead of tabbing out
 */

(function($, Drupal, drupalSettings) {
  Drupal.behaviors.delay_ajax_keyup = {
    attach: function(context, settings) {
      $('input.password-field.js-password-field').each(function() {
        var $self = $(this);
        var timeout = null;
        var delay = $self.data('delay') || 1000;
        var triggerEvent = $self.data('event') || "end_typing";

        $self.keyup(function() {
          clearTimeout(timeout);
          timeout = setTimeout(function() {
            $self.trigger(triggerEvent);
          }, delay);
        });
      });
    }
  }
})(jQuery, Drupal, drupalSettings);
