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

    function Product(product, apis, expandedapis) {

        var self = this;

        self.product = product;
        self.apis = apis;
        self.expandedapis = expandedapis;
        self.product.apis = self.apis;
        self.product.expandedapis = self.expandedapis;

        self.selectedPath = "product";

        var $window = $(window);

        // add support for data-title
        $(document).tooltip({
            items: "[data-ratelimits]",
            content: function () {
                var element = $(this);
                if (element.is("[data-ratelimits]")) {
                    var data = jQuery.parseJSON(element[0].dataset.ratelimits);
                    var output = '<div class="ratePopup">';
                    if (data.rates && data.rates.length > 0) {
                        if (data.rateLabel) {
                            output += '<span class="burstLabel">' + data.rateLabel + '</span><br/>';
                        } else {
                            output += '<span class="burstLabel">Rate limits</span><br/>';
                        }
                        data.rates.forEach(function (rate) {
                            output += rate + '<br/>';
                        });
                    }
                    if (data.bursts && data.bursts.length > 0) {
                        if (data.burstLabel) {
                            output += '<span class="burstLabel">' + data.burstLabel + '</span><br/>';
                        } else {
                            output += '<span class="burstLabel">Burst limits</span><br/>';
                        }
                        data.bursts.forEach(function (rate) {
                            output += rate + '<br/>';
                        });
                    }
                    output += '</div>';
                    return output;
                }
            }
        });

        function checkWidth() {
            var headerdiv = $("#header");
            if (headerdiv) {
                var headertop = headerdiv.offset().top;
                var scrolltop = $(document).scrollTop();
                var headerbottom = headertop - scrolltop + headerdiv.outerHeight(true);
                $("#columns").css({'margin-top': ((headerbottom) + 'px')});
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

            // set width of plans section
            var plansdiv = $(".plansSection .plans .plansinner");
            var apisdiv = $(".plansSection .apiList");
            var titlediv = $(".plansSection .plansectiontitle");
            if (plansdiv && apisdiv && titlediv) {
                var left = apisdiv.width();
                var width = titlediv.width();
                plansdiv.css({'max-width': ((width - left) + 'px')});
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

        function cleanUpKey(key) {
            return key.replace(/\W/g, '');
        }

        function cleanUpClassName(input) {
            input = input.toLowerCase();
            input = input.replace(/ /g, "-").replace(/_/g, "-").replace(/\[/g, "-").replace(/]/g, "-");

            // As defined in http://www.w3.org/TR/html4/types.html#type-name, HTML IDs can
            // only contain letters, digits ([0-9]), hyphens ("-"), underscores ("_"),
            // colons (":"), and periods ("."). We strip out any character not in that
            // list. Note that the CSS spec doesn't allow colons or periods in identifiers
            // (http://www.w3.org/TR/CSS21/syndata.html#characters), so we strip those two
            // characters as well.
            input = input.replace(/[^A-Za-z0-9\-_]+/gi, '', input);

            return input;
        }


        var waypoints = [
            {path: "product"},
            {path: "apis"}
        ];
        self.product.apis.forEach(function (api) {
            if (!api.basePath) {
                api.basePath = '';
            }
            var cleanedapiname = cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]);
            // set the height of the expanded section in the plan table to match that of the expanded API ops
            var apidiv = $(".apiList .planapiwrapper-" + cleanedapiname);
            if (apidiv) {
                var headerbottom = apidiv.outerHeight(true);
                $(".plan .planapiwrapper-" + cleanedapiname).css({'height': (headerbottom + 'px')});
            }
            waypoints.push({
                path: "apis_" + cleanedapiname,
                expand: "apis_" + cleanedapiname
            });
            waypoints.push({
                path: "apis_" + cleanedapiname + "_definitions",
                expand: "apis_" + cleanedapiname
            });
            waypoints.push({
                path: "apis_" + cleanedapiname + "_security",
                expand: "apis_" + cleanedapiname
            });
            self.product.expandedapis.forEach(function (expanded) {
                if (expanded.info['x-ibm-name'] && expanded.info['x-ibm-name'] == api.info['x-ibm-name'] && expanded.info['version'] && expanded.info['version'] == api.info['version']) {

                }
            });
        });

        function registerWaypoints() {
            var headerHeight = ($("#header").height() + 1);
            waypoints.forEach(function (waypoint) {
                var element = document.querySelector(".navigate-" + waypoint.path);
                if (!element) return;
                new Waypoint({
                    element: element,
                    offset: (waypoint.offset) ? waypoint.offset + headerHeight : headerHeight,
                    handler: function (direction) {
                        if (self.userNavigate) {
                            setTimeout(function () {
                                self.userNavigate = false;
                            }, 100);
                            return;
                        }
                        self.setSelectedPath(waypoint.path, waypoint.verb, waypoint.expand);
                    }
                });
            });
        }

        registerWaypoints();
    }

    Product.prototype.navigate = function (path, expand) {
        this.userNavigate = true;
        this.setSelectedPath(path, null, expand);
        var node = document.querySelector('.productDetails .navigate-' + path);
        if (node) {
            node.scrollIntoView();
            window.scrollBy(0, -150);
        }
    };

    Product.prototype.toggleplanapi = function (api) {
        $(".planapiwrapper-" + api).toggleClass("open");
        // set the height of the expanded section in the plan table to match that of the expanded API ops
        var apidiv = $(".apiList .planapiwrapper-" + api);
        var headerbottom = apidiv.outerHeight(true);
        $(".plan .planapiwrapper-" + api).css({'height': (headerbottom + 'px')});
    };

    Product.prototype.togglesection = function (section) {
        $("." + section + "Container").toggleClass("open");
    };

    Product.prototype.getSelectedPath = function () {
        return this.selectedPath;
    };

    Product.prototype.setSelectedPath = function (path, verb, expand) {
        this.selectedPath = path;
        $('.mesh-portal-product .tocItem a.selected').removeClass('selected');
        $('.mesh-portal-product .tocItem.toc-' + path + ' > a').addClass('selected');
        $(".toc .toc-container").addClass("hidden");
        if (expand) {
            $(".toc .toc-container-" + expand).removeClass("hidden");
        }
    };

    window.Product = Product;

    $(document).ready(function () {
        window.product = new Product(window.productJson, window.apiJson, window.expandedapiJson);
    });


}(jQuery));
