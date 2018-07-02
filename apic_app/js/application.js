/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Inspired by "password_toggle" module.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Add a "Show password" checkbox to each password field.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches "Show password" checkbox to password fields.
   */
  Drupal.behaviors.showPassword = {
    attach: function (context) {
      // Find the checkbox.
      var showPassword = $(context).find('.password-toggle.checkbox');

      // Add click handler to checkboxes.
      $(':checkbox', showPassword).click(function () {
        var password_field = $(this).closest('.toggleParent').find('.toggle')[0];
        if (!password_field.value.length > 0) {
          return;
        }

        if ($(this).is(':checked')) {
          password_field.type = "text";
        }
        else {
          password_field.type = "password";
        }
      });

    }
  };

  /**
   * Provides JS click actions for multiple credential tabs on application page
   *
   * @type {{attach: Drupal.behaviors.multipleCredentials.attach}}
   */
  Drupal.behaviors.multipleCredentials = {
    attach: function (context) {
      // Find the tabs list.
      var tabsList = $(context).find('.applicationCredentials .credentialsTable .credentialsContent .credentialsTabs ul');

      var clientIDField = $(context).find('.applicationCredentials .credentialsTable .credentialsContent .credentialsData #clientIDInput');

      var summaryDiv = $(context).find('.applicationCredentials .credentialsTable .credentialsContent .credentialsData .credentialSummary');

      function updateLinkTarget(linkClass, credid) {
        var currentLink = $(context).find(linkClass + ' a');
        var currentValue = currentLink.prop("href");
        var the_arr = currentValue.split('/');
        the_arr.pop();
        var newLink = ( the_arr.join('/') );
        currentLink.prop("href", newLink + "/" + credid);
      }

      // Add click handler to li.
      $('li', tabsList).click(function () {
        var client_id = '';
        var summary = '';
        var credid = $(this).attr('data-credid');
        // remove selected from all other tabs
        $('li', tabsList).removeClass("selected");
        // add it to this one
        $(this).addClass("selected");
        // update client ID value
        $.each(drupalSettings.application.credentials, function (index, item) {
          if (item.id == credid) {
            client_id = item['client_id'];
            summary = item['summary'];
          }
        });
        clientIDField.val(client_id);
        // update links
        updateLinkTarget('.applicationCredentials .credentialsTable .credentialsActionsManage .editCredentials', credid);
        updateLinkTarget('.applicationCredentials .credentialsTable .credentialsActionsManage .resetClientID', credid);
        updateLinkTarget('.applicationCredentials .credentialsTable .credentialsActionsManage .resetClientSecret', credid);
        updateLinkTarget('.applicationCredentials .credentialsTable .credentialsActionsManage .deleteCredentials', credid);
        // update verify clientsecret link
        updateLinkTarget('.clientSecretContainer .apicAppCheckButton.verifyButton', credid);

        if (summary) {
          summaryDiv.removeClass("hidden");
          summaryDiv.text(summary);
        } else {
          summaryDiv.addClass("hidden");
          summaryDiv.text();
        }

        // force disable show client id
        var showPassword = $(context).find('.password-toggle.checkbox');
        $(':checkbox', showPassword).prop("checked", false);
        var password_field = $(':checkbox', showPassword).closest('.toggleParent').find('.toggle')[0];
        password_field.type = "password";
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
