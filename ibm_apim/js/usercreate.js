/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

jQuery(document).ready(function () {
    var delay = (function () {
        var timer = 0;
        return function (callback, ms) {
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        };
    })();
    var checkValues = function () {
        var firstname = jQuery('#edit-field-first-name-und-0-value').val();
        var lastname = jQuery('#edit-field-last-name-und-0-value').val();
        var out = '';
        if (firstname) {
            out = out + firstname;
        }
        if (lastname) {
            out = out + ' ' + lastname;
        }
        jQuery('#edit-field-developer-organization-und-0-value').val(out);
    };

    checkValues();

    jQuery('#edit-field-first-name-und-0-value').keyup(function () {
        delay(function () {
            checkValues();
        }, 500);
    });

    jQuery('#edit-field-last-name-und-0-value').keyup(function () {
        delay(function () {
            checkValues();
        }, 500);
    });

});