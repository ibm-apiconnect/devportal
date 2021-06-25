(function($, Drupal) {
  'use strict';
  Drupal.apicStripe = {
    displayError: function(errorMessage) {
      $('#payment-errors').html(Drupal.theme('apicStripeError', errorMessage));
    }
  }
  Drupal.theme.apicStripeError = function(message) {
    return $('<div class="alert alert-danger alert-dismissible" role="alert"></div>').html('<div class="alert-details">\n' +
      '<span class="icon icon-error" aria-hidden="true">\n' +
      '<svg version="1.1" id="icon" xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="16px" height="16px" viewBox="0 0 16 16" style="enable-background:new 0 0 16 16;" xml:space="preserve">\n' +
      '<style type="text/css">\t.st0{fill:none;}\t.st1{opacity:0;fill-opacity:0;}</style>\n' +
      '<rect id="_Transparent_Rectangle_" class="st0" width="16" height="16"></rect>\n' +
      '<path d="M8,1C4.1,1,1,4.1,1,8s3.1,7,7,7s7-3.1,7-7S11.9,1,8,1z M10.7,11.5L4.5,5.3l0.8-0.8l6.2,6.2L10.7,11.5z"></path>\n' +
      '<path id="inner-path" class="st1" d="M10.7,11.5L4.5,5.3l0.8-0.8l6.2,6.2L10.7,11.5z"></path>\n' +
      '</svg>\n' + '' +
      '</span>\<div class="alert-text-wrapper"><h4 class="sr-only">Error message</h4>' +
      message +
      '</div></div><a href="#" role="button" class="close" data-dismiss="alert" aria-label="Close">\n' +
      '<svg aria-label="Close" focusable="false" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32" role="img" class="bx--inline-notification__close-icon">\n' +
      '<path d="M24 9.4L22.6 8 16 14.6 9.4 8 8 9.4 14.6 16 8 22.6 9.4 24 16 17.4 22.6 24 24 22.6 17.4 16 24 9.4z"></path>\n' +
      '</svg></a>');
  }
})(jQuery, Drupal);
