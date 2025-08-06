/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Setup masonry for the social block
 */

(function($, Drupal, drupalSettings) {

  Drupal.behaviors.socialblockSetup = {
    attach: function(context) {

      $('.socialblock.container').masonry({
        // options
        itemSelector: '.socialblock.card', columnWidth: '.socialblock.card', gutter: 10
      });

      if (window._cspListenerInitialized) {
        return;
      }
      window._cspListenerInitialized = true;

      // Check if the current user is an admin
      if (!drupalSettings?.csp_error_handler?.isAdmin) {
        return;
      }
      // Listen for CSP violations
      window.addEventListener('securitypolicyviolation', function (e) {
        if (e.violatedDirective.includes('img-src')) {
          if (document.querySelector('.csp-error-message')) {
            return;
          }
          const blockedURI = (e.blockedURI || '').toString().slice(0, 40);
          console.log(blockedURI);
          const message = `CSP Violation: Blocked ${blockedURI} \n For directive: ${e.violatedDirective}`;
          const url = Drupal.url('csp-check') + '?data=' + encodeURIComponent(message);

          fetch(url, {
            method: 'GET',
            headers: {
              'Accept': 'application/json'
            }
          })
          .then(res => res.json())
          .catch(err => {
            console.error('AJAX GET failed:', err);
          });
        }
      });

    }
    
  };

})(jQuery, Drupal, drupalSettings);
