/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Used by the ApicUserRegisterForm to populate the consumer org name field
 * based on user input in the first and last name fields
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Add a change listener to first and last name fields.
   * Once both fields have a value, populate the consumer org title
   * field by concatenating the two.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   */
  Drupal.behaviors.createCorgName = {

    attach: function (context) {

      var entityMap = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
        '/': '&#x2F;',
        '`': '&#x60;',
        '=': '&#x3D;'
      };

      function escapeHtml (string) {
        return String(string).replace(/[&<>"'`=\/]/g, function (s) {
          return entityMap[s];
        });
      }

      // Find the firstName and lastName fields.
      var firstName = $(context).find("[name*='first_name[0][value]']");
      var lastName = $(context).find("[name*='last_name[0][value]']");
      var corgTitle = $(context).find("[name*='consumerorg']");

      // Add change handler to the input elements
      $(firstName).change(function () {
        if ($(firstName).val() !== "" && $(lastName).val() !== "" && $(corgTitle).val() === "") {
          $(corgTitle).attr('value', escapeHtml($(firstName).val() + " " + $(lastName).val()));
        }
      });

      $(lastName).change(function () {
        if ($(firstName).val() !== "" && $(lastName).val() !== "" && $(corgTitle).val() === "") {
          $(corgTitle).attr('value', escapeHtml($(firstName).val() + " " + $(lastName).val()));
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
