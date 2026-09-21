/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2026
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

(function (Drupal) {
  'use strict';

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

  if (!Drupal || !Drupal.AjaxCommands) return;

  const proto = Drupal.AjaxCommands.prototype;
  if (!proto || !proto.openDialog || proto.__apicModalFixPatched) return;

  const originalOpenDialog = proto.openDialog;

  proto.openDialog = function (ajax, response, status) {
    const result = originalOpenDialog.call(this, ajax, response, status);
    unhideDrupalModal();
    return result;
  };

  proto.__apicModalFixPatched = true;
})(window.Drupal);
