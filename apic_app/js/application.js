/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
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
      const showPassword = $(context).find('.password-toggle.checkbox');

      // Add click handler to checkboxes.
      $(':checkbox', showPassword).click(function () {
        const password_field = $(this).closest('.toggleParent').find('.toggle')[0];
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
      const tabsList = $(context).find('.applicationCredentials .credentialsTable .credentialsContent .credentialsTabs ul');

      const clientIDField = $(context).find('.applicationCredentials .credentialsTable .credentialsContent .credentialsData #clientIDInput');

      const summaryDiv = $(context).find('.applicationCredentials .credentialsTable .credentialsContent .credentialsData .credentialSummary');

      function updateLinkTarget(linkClass, credid) {
        const currentLink = $(context).find(linkClass + ' a');
        const currentValue = currentLink.prop("href");
        const the_arr = currentValue.split('/');
        the_arr.pop();
        const newLink = (the_arr.join('/'));
        currentLink.prop("href", newLink + "/" + credid);
      }

      // Add click handler to li.
      $('li', tabsList).click(function () {
        let client_id = '';
        let summary = '';
        const credid = $(this).attr('data-credid');
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
        const showPassword = $(context).find('.password-toggle.checkbox');
        $(':checkbox', showPassword).prop("checked", false);
        const password_field = $(':checkbox', showPassword).closest('.toggleParent').find('.toggle')[0];
        password_field.type = "password";
      });
    }
  };

  Drupal.behaviors.closeAppModalDialog = {
    attach: function (context) {
      // used for the result modal dialog from creating an app within the subscribe flow.
      // drupal doesnt seem to add a close button so need to do our own
      $('#drupal-modal .modalAppResultContainer .modal-header .close').click(function (e) {
        e.preventDefault();

        // This will close the dialog
        $('#drupal-modal').dialog('close');
      });

    }
  };

})(jQuery, Drupal, drupalSettings);
