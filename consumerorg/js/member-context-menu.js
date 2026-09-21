/**
 * @file
 * Closes open consumer organization row action menus on outside click.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.consumerOrgMemberContextMenu = {
    attach: function (context) {
      once('consumerorg-row-actions-outside-click', 'html', context).forEach(function (element) {
        element.addEventListener('click', function (event) {
          document.querySelectorAll('.consumerorg-row-actions[open]').forEach(function (details) {
            if (!details.contains(event.target)) {
              details.removeAttribute('open');
            }
          });
        });

        element.addEventListener('keydown', function (event) {
          if (event.key === 'Escape') {
            document.querySelectorAll('.consumerorg-row-actions[open]').forEach(function (details) {
              details.removeAttribute('open');
            });
          }
        });
      });
    }
  };

})(Drupal, once);
