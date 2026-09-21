/**
 * @file
 * Language switcher dropdown.
 * Bootstrap 3 handles open/close via data-toggle="dropdown" on the trigger.
 * This only rotates the chevron on open/close using Bootstrap's events.
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.languageSwitcherSelect = {
    attach: function (context) {
      $(context).find('.language-switcher-dropdown').once('lang-switcher').each(function () {
        var $dropdown = $(this);
        $dropdown
          .on('show.bs.dropdown', function () {
            $dropdown.find('.language-switcher-trigger svg').css('transform', 'rotate(180deg)');
          })
          .on('hide.bs.dropdown', function () {
            $dropdown.find('.language-switcher-trigger svg').css('transform', '');
          });
      });
    }
  };

}(jQuery, Drupal));
