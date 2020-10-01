(function ($, Drupal) {
  'use strict';
  Drupal.apicStripe = {
    displayError: function (errorMessage) {
      $('#payment-errors').html(Drupal.theme('apicStripeError', errorMessage));
    }
  }
  Drupal.theme.apicStripeError = function (message) {
    return $('<div class="messages messages--error"></div>').html(message);
  }
})(jQuery, Drupal);
