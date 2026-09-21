
/**
 * @file
 * Setup masonry for the social block
 */

(function($, Drupal, drupalSettings) {

  Drupal.behaviors.socialblockSetup = {
    attach: function(context) {

      const isRTL = document.documentElement.getAttribute('dir') === 'rtl';
      $('.socialblock.container').masonry({
        // options
        isOriginLeft: !isRTL,
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
