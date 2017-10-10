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
        var billing_label = 'Update Card Details';
        var billing_logoPath = '/sites/all/modules/ibm_apim/images/icons/product/product_05.png';
        var billing_key = '';
        var billing_description = 'Update your Credit Card Information';
        var billing_siteName = 'Developer Portal';
        var targetUrl = '';
        if (Drupal.settings.billing_label) {
            billing_label = Drupal.settings.billing_label;
        }
        if (Drupal.settings.billing_description) {
            billing_description = Drupal.settings.billing_description;
        }
        if (Drupal.settings.billing_logoPath) {
            billing_logoPath = Drupal.settings.billing_logoPath;
        }
        if (Drupal.settings.billing_key) {
            billing_key = Drupal.settings.billing_key;
        }
        if (Drupal.settings.billing_siteName) {
            billing_siteName = Drupal.settings.billing_siteName;
        }
        if (Drupal.settings.billing_endpoint) {
            targetUrl = Drupal.settings.billing_endpoint;
        }

        jQuery("#billing_clear").click(function(){
            jQuery("#card_ending").val("");
            jQuery("#card_expiring").val("");
            jQuery("#billing_name").val("");
            jQuery("#billing_email").val("");
        });
        var handler = StripeCheckout.configure({
            key: billing_key,
            image: billing_logoPath,
            locale: "auto",
            panelLabel: billing_label,
            token: function(token) {
                jQuery("#card_ending").text(' ' + token.card.last4);
                jQuery("#card_expiring").text(' ' + token.card.exp_month + "/" + token.card.exp_year);
                jQuery("#billing_name").text(' ' + token.card.name);
                jQuery("#billing_email").text(' ' + token.email);
                jQuery("#billing_button").text('Submitted');
                jQuery("#billing_button").prop('disabled', true);
                function errorHandler(xhrObj, textStatus, error) {

                }

                function successHandler(data, status, xhrObj) {

                }
                var headers = [];

                var xhrOpts = {
                    headers: headers,
                    error: errorHandler,
                    success: successHandler
                };
                var recentJQuery = false;
                if (jQuery.fn.jquery) {
                    var vernums = jQuery.fn.jquery.split('.');
                    if (parseInt(vernums[0]) == 1 && parseInt(vernums[1]) >= 9 || parseInt(vernums[0]) > 1) {
                        xhrOpts.method = 'GET';
                        recentJQuery = true;
                    }
                }
                if (recentJQuery == false) {
                    xhrOpts.type = 'GET';
                }
                var encoded_token = btoa(JSON.stringify(token));
                encoded_token.replace(/\+/g, "=");
                encoded_token.replace(/-/g, "_");

                jQuery.ajax(targetUrl + '/' + encoded_token, xhrOpts);
            }
        });
        document.getElementById("billing_button").addEventListener("click", function(e) {
            handler.open({
                name: billing_siteName,
                description: billing_description,
                zipCode: true,
                billingAddress: true,
                allowRememberMe: false
            });
            e.preventDefault();
        });
        window.addEventListener("popstate", function() {
            handler.close();
        });
    }, 0);
});
