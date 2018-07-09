/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Add image to generator radio options
 */

(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.generatorRadios = {
    attach: function (context) {

      // show screenshots next to radio buttons
      $("#edit-template>.js-form-type-radio").each(function (index, element) {
        var label = $(element).find('.option').first();
        var optionElem = $(element).find('input').first();
        if (label && optionElem) {
          var optionValue = $(optionElem).val();
          if (optionValue) {
            var imagePath = drupalSettings.themegenerator.adminform.module_path + '/stub/templates/' + optionValue + '/screenshot.png';
            if (optionValue == 'connect_theme') {
              imagePath = drupalSettings.themegenerator.adminform.connect_theme_path + '/screenshot.png';
            }
            label.parent().children('.theme_screenshot').remove();
            label.parent().append('<img class="theme_screenshot" src="' + imagePath + '" />')
          }
        }
      });

      // add class to parent when selected
      $('#edit-template>.js-form-type-radio input').click(function () {
        $('#edit-template>.js-form-type-radio input:not(:checked)').parent().removeClass("selected");
        $('#edit-template>.js-form-type-radio input:checked').parent().addClass("selected");
      });
      $('#edit-template>.js-form-type-radio input:checked').parent().addClass("selected");
    }
  };

})(jQuery, Drupal, drupalSettings);


