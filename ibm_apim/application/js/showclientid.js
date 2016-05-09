jQuery(document).ready(function () {
    jQuery("#show-clientID").change(function () {
        jQuery("#clientID").hideShowPassword(jQuery(this).prop("checked"));
    });
    jQuery("#show-clientSecret").change(function () {
        jQuery("#clientSecret").hideShowPassword(jQuery(this).prop("checked"));
    });
});