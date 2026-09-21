
/**
 * @file
 * Used by the main nav menu to handle overflow items to save wrapping additional menu items to second row
 */

(function ($, Drupal, drupalSettings) {
  function on_resize(c, t) {
    onresize = function () {
      clearTimeout(t);
      t = setTimeout(c, 300);
    };
    return c;
  }

  $(window).on("resize", function () {
    if (Drupal.behaviors.resizeMainNav.resizeTimer) {
      clearTimeout(Drupal.behaviors.resizeMainNav.resizeTimer);
    }
    Drupal.behaviors.resizeMainNav.resizeTimer = setTimeout(function () {
      Drupal.behaviors.resizeMainNav.handler();
    }, 250);
  });

  $(window).on("load", function () {
    setTimeout(function () {
      if (
        Drupal.behaviors.resizeMainNav &&
        Drupal.behaviors.resizeMainNav.handler
      ) {
        Drupal.behaviors.resizeMainNav.handler();
      }
    }, 100);
  });

  Drupal.behaviors.createMainNavOverflow = {
    attach: function (context) {
      $(".main-menu div ul.nav").overflowHandler({
        overflowItem: {
          text: "",
          href: "#",
          className: "has-child",
        },
        bootstrapMode: true,
      });
    },
  };

  Drupal.behaviors.resizeMainNav = {
    resizeTimer: null,

    handler: function () {
      if ($("#navoverflow").length) {
        /* get all the child items from #navoverflow and put back on the main nav */
        var $mainNav = $(".main-menu div ul.nav");
        var $overflowItems = $("#navoverflow ul.dropdown-menu").children("li");

        $overflowItems.each(function () {
          var $this = $(this);
          $this.appendTo($mainNav);
        });

        $("#navoverflow").remove();
      }
      /* see if it needs creating */
      $(".main-menu div ul.nav").overflowHandler({
        overflowItem: {
          text: "",
          href: "#",
          className: "has-child",
        },
        bootstrapMode: true,
      });
    },

    attach: on_resize(function () {
      Drupal.behaviors.resizeMainNav.handler();
    }),
  };
})(jQuery, Drupal, drupalSettings);
