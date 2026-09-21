/**
 * @file
 * Adds a translatable tooltip to the admin toolbar logo.
 */

(function (Drupal) {
  'use strict';

  Drupal.behaviors.adminToolbarTooltip = {
    attach: function (context, settings) {
      // Find the admin toolbar logo element
      const logoElement = context.querySelector('.admin-toolbar__logo');
      
      if (logoElement && !logoElement.hasAttribute('data-tooltip-processed')) {
        // Add title attribute for tooltip with translatable text
        logoElement.setAttribute('title', Drupal.t('Go to homepage'));
        
        // Mark as processed to avoid duplicate processing
        logoElement.setAttribute('data-tooltip-processed', 'true');
        
        // Make it a proper link if it isn't already
        if (logoElement.tagName !== 'A') {
          logoElement.style.cursor = 'pointer';
          logoElement.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = '/';
          });
        }
      }
    }
  };

})(Drupal);
