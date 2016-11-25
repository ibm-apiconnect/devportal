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

(function ($) {

    /**
     * Toggles the collapsible region.
     */
    Drupal.behaviors.connect_themeCollapsRegionToggle = {
        attach: function (context, settings) {
            $('.collapsible-toggle a, context').unbind('click').click(function () {
                $('#section-collapsible').toggleClass('toggle-active').find('.region-collapsible').slideToggle('fast');
                if (event.stopPropagation) {
                    event.stopPropagation();
                }
                // prevent submit button from actually submitting the form
                if (event.preventDefault) {
                    event.preventDefault();
                } else {
                    event.returnValue = false; // for IE as doesn't support preventDefault;
                }
                return false;
            });
        }
    };

    Drupal.behaviors.connect_themeCollapsMenuToggle = {
        attach: function (context, settings) {
            $('.menu-toggle a, context').click(function () {
                $('#menu-bar').toggleClass('toggle-active').find('nav').slideToggle('fast');
                if (event.stopPropagation) {
                    event.stopPropagation();
                }
                // prevent submit button from actually submitting the form
                if (event.preventDefault) {
                    event.preventDefault();
                } else {
                    event.returnValue = false; // for IE as doesn't support preventDefault;
                }
                return false;
            });
        }
    };

    /**
     * CSS Help for IE.
     * - Adds even odd striping and containers for images in posts.
     * - Adds a .first-child class to the first paragraph in each wrapper.
     * - Adds a prompt containing the link to a comment for the permalink.
     */
    Drupal.behaviors.connect_themePosts = {
        attach: function (context, settings) {
            // Detects IE6-8.
            if (!jQuery.support.leadingWhitespace) {
                $('.article-content p:first-child').addClass('first-child');
                $('.article-content img, context').parent(':not(.field-item, .user-picture)').each(function (index) {
                    var stripe = (index / 2) ? 'even' : 'odd';
                    $(this).wrap('<div class="content-image-' + stripe + '"></div>');
                });
            }
            // Comment link copy promt.
            $("time span a").click(function () {
                prompt('Link to this comment:', this.href);
                return false;
            });
        }
    }

})(jQuery);
