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
 * Used for doing client side password validation
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.ibmApimValidatePassword = {
    attach: function attach(context, drupalSettings) {
      var $passwordInput = $(context).find('input.js-password-field').once('ibmApimValidatePassword');

      if ($passwordInput.length) {

        var wrapper = $(context).find('#ibm-apim-password-policy-status');
        var statusBox = $(context).find('#ibm-apim-password-policy-status div');

        var passwordCheck = function passwordCheck() {
          // remove all existing child content from status box
          statusBox.html('');

          drupalSettings.ibmApimPassword.forEach(function (constraint) {
            var result;
            switch (constraint['id']) {
              case 'character_types':
                result = Drupal.ibmApimEvalatePasswordCharTypes($passwordInput.val(), constraint['character_types']);
                break;
              case 'password_length':
                if (constraint['character_operation'] === 'maximum') {
                  result = Drupal.ibmApimEvalatePasswordMaxLength($passwordInput.val(), constraint['character_length']);
                } else {
                  result = Drupal.ibmApimEvalatePasswordMinLength($passwordInput.val(), constraint['character_length']);
                }
                break;
              case 'password_username':
                result = Drupal.ibmApimEvalatePasswordUsername($passwordInput.val(), constraint['disallow_username'], constraint['username']);
                break;
              case 'password_strength_constraint':
                result = Drupal.ibmApimEvaluatePasswordStrength($passwordInput.val(), constraint['strength_score']);
                break;
              case 'consecutive':
                result = Drupal.ibmApimEvalateConsecutiveChars($passwordInput.val(), constraint['max_consecutive_characters']);
                break;
            }
            if (result) {
              statusBox.append('<span class="passed"><span class="icon glyphicon glyphicon-ok" aria-hidden="true"></span> ' + constraint['message'] + '</span><br/><br/>');
            } else {
              statusBox.append('<span class="failed"><span class="icon glyphicon glyphicon-remove" aria-hidden="true"></span> ' + constraint['message'] + '</span><br/><br/>');
            }
          });

          // switch wrapper class
          var failedElements = $(statusBox).find('span.failed');
          if (failedElements.length === 0) {
            wrapper.removeClass('alert-danger').addClass('alert-success');
          } else {
            wrapper.removeClass('alert-success').addClass('alert-danger');
          }
        };

        $passwordInput.on('input', passwordCheck);
      }
    }
  };

  /**
   *
   * @param password
   * @param types
   * @returns {boolean}
   */
  Drupal.ibmApimEvalatePasswordCharTypes = function (password, types) {
    password = password.trim();
    var numtypes = 0;
    var hasLowercase = /[a-z]/.test(password);
    var hasUppercase = /[A-Z]/.test(password);
    var hasNumbers = /[0-9]/.test(password);
    var hasPunctuation = /[^a-zA-Z0-9]/.test(password);
    var result = false;

    if (hasLowercase) {
      numtypes++;
    }
    if (hasUppercase) {
      numtypes++;
    }
    if (hasNumbers) {
      numtypes++;
    }
    if (hasPunctuation) {
      numtypes++;
    }
    if (numtypes >= parseInt(types)) {
      result = true;
    }
    return result;
  };

  /**
   *
   * @param password
   * @param length
   * @returns {boolean}
   */
  Drupal.ibmApimEvalatePasswordMinLength = function (password, length) {
    password = password.trim();
    var result = false;

    if (password.length >= parseInt(length)) {
      result = true;
    }
    return result;
  };

  /**
   *
   * @param password
   * @param length
   * @returns {boolean}
   */
  Drupal.ibmApimEvalatePasswordMaxLength = function (password, length) {
    password = password.trim();
    var result = false;

    if (password.length > 0 && password.length <= parseInt(length)) {
      result = true;
    }
    return result;
  };

  /**
   *
   * @param password
   * @param disallow_username
   * @param settingsUsername
   * @returns {boolean}
   */
  Drupal.ibmApimEvalatePasswordUsername = function (password, disallow_username, settingsUsername) {
    password = password.trim();
    var result = true;
    var $usernameBox = $('input.username');
    var username = $usernameBox.length > 0 ? $usernameBox.val() : settingsUsername;

    if (password.length === 0 || password.indexOf(username) !== -1) {
      result = false;
    }
    return result;
  };

  /**
   *
   * @param password
   * @param number
   * @returns {boolean}
   */
  Drupal.ibmApimEvalateConsecutiveChars = function (password, number) {
    password = password.trim();
    var result = false;
    var groups = parseInt(number) - 1;
    var regex = "(.)\\1{" + groups + ",}";
    var matches = new RegExp(regex, 'g').test(password);

    if (password.length > 0 && !matches) {
      result = true;
    }
    return result;
  };

  /**
   * This uses zxcvbn from dropbox
   * https://github.com/dropbox/zxcvbn
   *
   * @param password
   * @param strengthTarget
   * @returns {boolean}
   */
  Drupal.ibmApimEvaluatePasswordStrength = function (password, strengthTarget) {
    password = password.trim();
    var result = false;
    // JS validator seems stricter than PHP version so use fudge factor
    var target = parseInt(strengthTarget) - 1;
    var zxcvbnResult = zxcvbn(password);
    if (zxcvbnResult.score && zxcvbnResult.score >= target) {
      result = true;
    }

    return result;
  };
})(jQuery, Drupal, drupalSettings);