/**
 * @file
 * This is "borrowed" from the Page Load Progress module, but tweaked to work for OIDC login button links.
 *
 * Page Load Progress sets a screen lock showing a spinner when the user clicks
 * on an element that triggers a time consuming task.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.oidc_page_load_progress = {
    attach: function (context, settings) {
      var esc_key = Boolean(settings.oidc_page_load_progress.esc_key);
      var screen_lock = '<div class="page-load-progress-lock-screen page-load-progress-hidden">\n\
                         <div class="page-load-progress-throbber"></div>\n\
                         </div>';
      var body = $('body', context, settings);

      // Add the throbber for internal links only if requested in the UI.
      $("a.registry-button[href]").on("click", function (evnt) {
        // Do not lock the screen if the link is being opened in a new tab.
        // Source: https://stackoverflow.com/a/20087506/9637665.
        if (evnt.ctrlKey || evnt.shiftKey || evnt.metaKey || (evnt.button && evnt.button == 1)) {
          return;
        }

        // Do not lock the screen if the link is within a modal.
        if ($(this).parents('.modal').length > 0) {
          return;
        }

        lockScreen();
      });


      // Allows ESC key to kill the throbber.
      if (esc_key) {
        document.onkeydown = function (evt) {
          evt = evt || window.event;
          var isEscape = false;
          if ("key" in evt) {
            // "Escape" is standard in modern browsers. "Esc" is primarily for
            // Internet Explorer 9 and Firefox 36 and earlier.
            isEscape = (evt.key === "Escape" || evt.key === "Esc");
          } else {
            // keyCode is getting deprecated. Keeping it for legacy reasons.
            isEscape = evt.keyCode === 27;
          }
          if (isEscape) {
            $('.page-load-progress-lock-screen').remove();
          }
        };
      }

      /**
       * Lock screen method.
       *
       * This method locks the screen by displaying the screen_lock HTML DOM
       * part as a full page overlay.
       */
      var lockScreen = function () {
        body.append(screen_lock);
        body.css({
          'overflow': 'hidden'
        });
        $('.page-load-progress-lock-screen').fadeIn('slow');
      }
    }
  };

})(jQuery, Drupal);
