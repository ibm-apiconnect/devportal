
/**
 * @file
 * Add image to generator radio options
 */

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.generatorRadios = {
    attach: function (context) {
      var entityMap = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
        "/": "&#x2F;",
        "`": "&#x60;",
        "=": "&#x3D;",
      };

      function escapeHtml(string) {
        return String(string).replace(/[&<>"'`=\/]/g, function (s) {
          return entityMap[s];
        });
      }

      // show screenshots next to radio buttons
      $("#edit-dashboard>.js-form-type-checkbox").each(function (
        index,
        element
      ) {
        let label = $(element).find(".option:not(.image)").first();
        let optionElem = $(element).find("input").first();
        if (label && optionElem) {
          let labelText = "<span>" + label.text() + "</span>";
          let optionValue = $(optionElem).val();
          if (optionValue) {
            let imagePath =
              drupalSettings.analytics.adminform.module_path +
              "/images/analytics/" +
              optionValue +
              ".png";
            let image =
              '<div class="chart-wrapper"><img class="chart_screenshot" alt="" src="' +
              escapeHtml(imagePath) +
              '" /></div>';
            label.html(labelText + image);
            label.addClass("image");
          }
        }
      });

      // add class to parent when selected
      $("#edit-dashboard>.js-form-type-checkbox input").click(function () {
        $("#edit-dashboard>.js-form-type-checkbox input:not(:checked)")
          .parent()
          .removeClass("selected");
        $("#edit-dashboard>.js-form-type-checkbox input:checked")
          .parent()
          .addClass("selected");
      });
      $("#edit-dashboard>.js-form-type-checkbox input:checked")
        .parent()
        .addClass("selected");
    },
  };
})(jQuery, Drupal, drupalSettings);
