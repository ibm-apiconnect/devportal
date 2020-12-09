/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
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
            var error_svg = '<svg width="8px" height="8px" viewBox="0 0 8 8" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">\n' +
                '    <title>close</title>\n' +
                '    <g id="Latest" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">\n' +
                '        <g id="close" transform="translate(-3.973005, -4.000000)" fill="#161616">\n' +
                '            <polygon id="Fill" points="12 4.7 11.3 4 8 7.3 4.7 4 4 4.7 7.3 8 4 11.3 4.7 12 8 8.7 11.3 12 12 11.3 8.7 8"></polygon>\n' +
                '        </g>\n' +
                '    </g>\n' +
                '</svg>';
            var success_svg = '<svg width="13px" height="8px" viewBox="0 0 13 8" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">\n' +
                '    <title>Organization / Status / checkmark / 16</title>\n' +
                '    <g id="Latest" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">\n' +
                '        <g id="Fill" transform="translate(-0.946009, -4.000000)" fill="#000000">\n' +
                '            <polygon points="6 10.6 2.5 7.1 1.8 7.8 5.3 11.3 6 12 13.1 4.9 12.4 4.2"></polygon>\n' +
                '        </g>\n' +
                '    </g>\n' +
                '</svg>';
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
              statusBox.append('<span class="passed"><span class="icon glyphicon" aria-hidden="true">' + success_svg + '</span> ' + constraint['message'] + '</span><br/><br/>');
            } else {
              // no icon for info mode
              if ($passwordInput.val().length === 0) {
                statusBox.append('<span class="failed"><span class="icon glyphicon" aria-hidden="true"></span> ' + constraint['message'] + '</span><br/><br/>');
              } else {
                statusBox.append('<span class="failed"><span class="icon glyphicon" aria-hidden="true">' + error_svg + '</span> ' + constraint['message'] + '</span><br/><br/>');
              }
            }
          });

          // switch wrapper class
          var failedElements = $(statusBox).find('span.failed');
          // if no password at all then use info mode
          if ($passwordInput.val().length === 0) {
            wrapper.removeClass('alert-danger').removeClass('alert-success').addClass('alert-info');
          } else if (failedElements.length === 0) {
            wrapper.removeClass('alert-danger').removeClass('alert-info').addClass('alert-success');
          } else {
            wrapper.removeClass('alert-success').removeClass('alert-info').addClass('alert-danger');
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
    var target = parseInt(strengthTarget);
    var zxcvbnResult = zxcvbn(password);
    if (zxcvbnResult.score && zxcvbnResult.score >= target) {
      result = true;
    }

    return result;
  };
})(jQuery, Drupal, drupalSettings);