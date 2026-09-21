
/**
 * @file
 * Set width of modal dialogs and fix modal visibility issues
 */

(function($, Drupal, drupalSettings) {
  'use strict';

  // Set modal width on dialog creation
  $(window).on({
    'dialog:aftercreate': function dialogAftercreate(event, dialog, $element, settings) {
      $("#drupal-modal").dialog( "option", { width: '560px' } );
    },
  });

  // Fix modal visibility issues
  function unhideDrupalModal() {
    const el = document.getElementById('drupal-modal');
    if (!el) return;

    el.classList.remove('display-none');
    el.style.display = 'block';

    setTimeout(function () {
      el.classList.remove('display-none');
      el.style.display = 'block';
    }, 0);

    requestAnimationFrame(function () {
      el.classList.remove('display-none');
      el.style.display = 'block';
      
      if (window.jQuery && window.jQuery.fn.dialog) {
        const $el = window.jQuery(el);
        if ($el.dialog && $el.dialog('instance')) {
          $el.dialog('option', 'height', 'auto');
          window.jQuery(window).triggerHandler('resize');
        }
      }

      // Add aria-label to dialog close button for accessibility
      const closeButton = el.parentElement?.querySelector('.ui-dialog-titlebar-close');
      if (closeButton && !closeButton.getAttribute('aria-label')) {
        closeButton.setAttribute('aria-label', 'Close');
      }
    });
  }

  // Patch Drupal.AjaxCommands.prototype.openDialog
  if (Drupal && Drupal.AjaxCommands) {
    const proto = Drupal.AjaxCommands.prototype;
    if (proto && proto.openDialog && !proto.__apicModalFixPatched) {
      const originalOpenDialog = proto.openDialog;

      proto.openDialog = function (ajax, response, status) {
        const result = originalOpenDialog.call(this, ajax, response, status);
        unhideDrupalModal();
        return result;
      };

      proto.__apicModalFixPatched = true;
    }
  }
})(jQuery, Drupal, drupalSettings);
