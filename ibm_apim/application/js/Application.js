/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2015, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

(function ($) {
    'use strict';

    $(document).ready(function () {
        $("#show-clientID").change(function () {
            $("#clientID").hideShowPassword($(this).prop("checked"));
        });
        $("#show-clientSecret").change(function () {
            $("#clientSecret").hideShowPassword($(this).prop("checked"));
        });
    });
    if ($(document).tooltip && typeof($(document).tooltip) == "function") {
        // add support for data-title
        $(document).tooltip({
            items: "[data-ratelimits]",
            content: function () {
                var element = $(this);
                if (element.is("[data-ratelimits]")) {
                    var data = jQuery.parseJSON(element[0].dataset.ratelimits);
                    var output = '<div class="ratePopup">';
                    if (data.rates && data.rates.length > 0) {
                        if (data.rateLabel) {
                            output += '<span class="burstLabel">' + data.rateLabel + '</span><br/>';
                        } else {
                            output += '<span class="burstLabel">Rate limits</span><br/>';
                        }
                        data.rates.forEach(function (rate) {
                            output += rate + '<br/>';
                        });
                    }
                    if (data.bursts && data.bursts.length > 0) {
                        if (data.burstLabel) {
                            output += '<span class="burstLabel">' + data.burstLabel + '</span><br/>';
                        } else {
                            output += '<span class="burstLabel">Burst limits</span><br/>';
                        }
                        data.bursts.forEach(function (rate) {
                            output += rate + '<br/>';
                        });
                    }
                    output += '</div>';
                    return output;
                }
            }
        });
    }

}(jQuery));
