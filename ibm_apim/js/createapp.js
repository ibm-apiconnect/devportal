/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

jQuery(document).ready(function () {
    setTimeout(function () {

        var appName = jQuery("#edit-title-field input.form-text", ".node-application-form");

        var checkValue = function (appName) {
            var value = appName.val();
            if (jQuery.trim(value).length > 0) {
                // enable submit button.
                jQuery("input[id='edit-submit']").removeAttr("disabled");
            }
            else {
                // disable submit button.
                jQuery("input[id='edit-submit']").attr("disabled", "");
            }
        };

        // initial check to disable button upfront
        checkValue(appName);
        // watch for change
        appName.on('input propertychange paste', function () {
            checkValue(appName);
        });
    }, 0);
});