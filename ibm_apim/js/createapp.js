jQuery(document).ready(function () {
    setTimeout(function () {

        var appName = jQuery("#edit-title", ".node-application-form");

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