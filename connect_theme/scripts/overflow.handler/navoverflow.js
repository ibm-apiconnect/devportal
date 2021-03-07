/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Used by the main nav menu to handle overflow items to save wrapping additional menu items to second row
 */

(function ($, Drupal, drupalSettings) {

  function on_resize(c,t){onresize=function(){clearTimeout(t);t=setTimeout(c,100)};return c};

  Drupal.behaviors.createMainNavOverflow = {

    attach: function (context) {
      $('.main-menu div ul.nav').overflowHandler({
        overflowItem: {
          text: '',
          href: '#',
          className: 'has-child'
        },
        bootstrapMode: true
      });
    }
  };

  Drupal.behaviors.resizeMainNav = {
    attach: on_resize( function() {
        if ($('#navoverflow').length) {
          /* get all the child items from #navoverflow and put back on the main nav */
          var $mainNav = $('.main-menu div ul.nav');
          var $overflowItems = $('#navoverflow ul.dropdown-menu').children('li');

          $overflowItems.each(function () {
            var $this = $(this);
            $this.appendTo($mainNav);
          });

          $('#navoverflow').remove();
        }
        /* see if it needs creating */
        $('.main-menu div ul.nav').overflowHandler({
          overflowItem: {
            text: '',
            href: '#',
            className: 'has-child'
          },
          bootstrapMode: true
        });
      }
    )
  };


})(jQuery, Drupal, drupalSettings);