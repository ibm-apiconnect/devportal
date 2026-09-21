/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2026
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * JavaScript for Terms of Service validation in subscription wizard
 */

(function($, Drupal) {
  'use strict';

  Drupal.behaviors.subscriptionToS = {
    attach: function(context, settings) {
      // Find ToS checkbox and submit button
      const $tosCheckbox = $('input[name="tos_accepted"]', context).once('tos-validation');
      const $submitButton = $('input[type="submit"][value="Next"], button[type="submit"]', context);

      if ($tosCheckbox.length && $submitButton.length) {
        // Initial state - disable submit button if ToS exists and not checked
        $submitButton.prop('disabled', !$tosCheckbox.is(':checked'));

        // Listen for checkbox changes
        $tosCheckbox.on('change', function() {
          const isChecked = $(this).is(':checked');
          $submitButton.prop('disabled', !isChecked);          
          if (isChecked) {
            $(this).closest('.form-item').removeClass('error');
            $('.tos-error-message').remove();
          }
        });

        // Form submit validation
        $('form', context).on('submit', function(e) {
          if ($tosCheckbox.length > 0 && !$tosCheckbox.is(':checked')) {
            e.preventDefault();            
            $tosCheckbox.closest('.form-item').addClass('error');
            
            if ($('.tos-error-message').length === 0) {
              $tosCheckbox.closest('.form-item').append(
                '<div class="tos-error-message">' +
                Drupal.t('You must accept the Terms of Service to continue.') +
                '</div>'
              );
            }
            
            $('html, body').animate({
              scrollTop: $tosCheckbox.closest('.form-item').offset().top - 100
            }, 500);
            
            return false;
          }
        });
      }
    }
  };
})(jQuery, Drupal);