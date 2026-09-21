(function (Drupal, once) {
  Drupal.behaviors.bootstrapTableDragFix = {
    attach: function (context) {
      once(
        "bootstrap-table-drag-fix",
        "table.field-multiple-table",
        context
      ).forEach(function (table) {
        const dragHandles = table.querySelectorAll(".tabledrag-handle");
        dragHandles.forEach((handle) => {
          // if a <span> already exists, remove glyphicon classes from <a>
          if (handle.querySelector("span.glyphicon")) {
            handle.classList.remove("glyphicon", "glyphicon-move");
          } else {
            // if not add glyphicon classes if missing
            if (!handle.classList.contains("glyphicon-move")) {
              handle.classList.add("glyphicon", "glyphicon-move");
            }
          }
        });

        const toggleButton = table
          .closest(".table-responsive")
          .querySelector(".tabledrag-toggle-weight");
        if (toggleButton && !toggleButton.classList.contains("btn")) {
          toggleButton.classList.add("btn", "btn-default", "btn-sm");
        }
      });
    },
  };
})(Drupal, once);
