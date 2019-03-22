/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

(function ($) {
    'use strict';

    function APP() {
        var self = this;

        var $window = $(window);

        function checkWidth() {
            var headerdiv = $("#header");
            if (headerdiv) {
                var headertop = headerdiv.offset().top;
                var scrolltop = $(document).scrollTop();
                var headerbottom = headertop - scrolltop + headerdiv.outerHeight(true);
                $("#columns").css({'margin-top': ((headerbottom) + 'px'), 'padding-top': '10px'});
                if ($("body").hasClass("page-comment-reply")) {
                    // move entire page over to allow comment content to appear at the top
                    $('#page').css({'margin-left': '180px'});
                } else {
                    // set toc top to height of header
                    $(".navigate-toc").css({
                        'top': ((headerbottom) + 'px'),
                        'height': ('calc(100% - ' + (headerbottom) + 'px)')
                    });
                }
            }

            var windowsize = $window.width();
            if (windowsize < 800) {
                // use hamburger menu
                $("#hamburger").removeClass("hidden");
                $('.navigate-toc').addClass('hidden');
            } else {
                $("#hamburger").addClass("hidden");
                $('.navigate-toc').removeClass('hidden');
            }
        }

        // Execute on load
        setTimeout(function () {
            checkWidth();
        }, 0);
        // Bind hamburger event listener
        $window.resize(checkWidth);

        $("#hamburger").click(function () {
            $('.navigate-toc').removeClass('hidden');
        });

        // close the menu
        $('.navigate-toc').click(function () {
            var windowsize = $window.width();
            if (windowsize < 800) {
                $('.navigate-toc').addClass('hidden');
            }
        });
        $('.mesh-portal-product').click(function () {
            var windowsize = $window.width();
            if (windowsize < 800) {
                $('.navigate-toc').addClass('hidden');
            }
        });
    }

    window.APP = APP;

    $(document).ready(function () {
        window.APP = new APP();
    });
}(jQuery));