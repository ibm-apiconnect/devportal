/**
 * @file
 * JS for Taxonomy menu block module
 */

(function ($) {
    Drupal.behaviors.Taxonomy_Menu_Block = {
        attach: function (context) {
            if ($('#edit-parent').val() === '1') {
                $('#dropdown-fixed-parent').show();
            } else {
                $('#dropdown-fixed-parent').hide();
            }
            $('#edit-parent').change(function () {
                $('#dropdown-fixed-parent').toggle($(this).val() == '1');
            });

            if ($('#edit-parent').val() === '2') {
                $('.form-item-parent-dynamic').show();
            } else {
                $('.form-item-parent-dynamic').hide();
            }
            $('#edit-parent').change(function () {
                $('.form-item-parent-dynamic').toggle($(this).val() == '2');
            });
        }
    };
})(jQuery);
