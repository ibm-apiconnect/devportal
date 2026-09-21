/**
 * @file
 * Stamps a CSP nonce onto <style> elements created by CKEditor5.
 *
 * CKEditor5 injects a <style> block at runtime via document.createElement.
 * Without this shim, a strict style-src 'self' CSP blocks it because the
 * browser sees no nonce attribute. This shim intercepts createElement for
 * 'style' tags and adds the per-request nonce from drupalSettings before
 * CKEditor5 inserts the element into the DOM.
 *
 * The nonce is provided by the CSP module (drupalSettings.csp.nonce) and
 * added to the style-src-elem CSP directive via CspPolicySubscriber.
 */
(function () {
  'use strict';

  var origCreateElement = document.createElement.bind(document);
  document.createElement = function (tagName, options) {
    var el = origCreateElement(tagName, options);
    if (typeof tagName === 'string' && tagName.toLowerCase() === 'style') {
      var nonce = window.drupalSettings
        && window.drupalSettings.csp
        && window.drupalSettings.csp.nonce;
      if (nonce) {
        el.setAttribute('nonce', nonce);
      }
    }
    return el;
  };

}());
