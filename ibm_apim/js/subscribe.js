/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

jQuery(document).ready(function () {
    jQuery("#simple-dialog-container").on("simpleDialogLoaded", function () {
        setTimeout(function () {
            // cache reference to all radio buttons.
            var radioButtons = jQuery("input:radio", "#application-subscribeapp-modal-form");

            var checkRadios = function (radioButtons) {
                var anyRadioButtonHasValue = false;
                // iterate through all radio buttons
                radioButtons.each(function () {
                    if (this.checked) {
                        // indicate we found a radio button which has a value
                        anyRadioButtonHasValue = true;

                        // break out of each loop
                        return false;
                    }
                });
                // check if we found any radio button which has a value
                if (anyRadioButtonHasValue) {
                    // enable submit button.
                    jQuery("input[id='edit-submit']").removeAttr("disabled");
                }
                else {
                    // disable submit button.
                    jQuery("input[id='edit-submit']").attr("disabled", "");
                }
            };
            // initial check to disable button upfront
            checkRadios(radioButtons);
            //watch for change
            radioButtons.change(function () {
                checkRadios(radioButtons);
            });
        }, 0);
    });
});