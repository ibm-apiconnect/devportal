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