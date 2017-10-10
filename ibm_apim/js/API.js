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
    'use strict';

    function API(apis, expandedapis) {

        var self = this;

        self.apis = apis;
        self.expandedapis = expandedapis;
        self.codeSnippets = window.codeSnippets;

        self.selectedPath = "product";

        var $window = $(window);

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

            var columnWidth = $('.readAndInteract .interact').width();
            $(".readAndInteract .rightHeader").css({'width': ((columnWidth) + 'px')});

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
            if (apisdiv && titlediv && plansdiv) {
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

        // Returns a function, that, as long as it continues to be invoked, will not
        // be triggered. The function will be called after it stops being called for
        // N milliseconds. If `immediate` is passed, trigger the function on the
        // leading edge, instead of the trailing.
        function debounce(func, wait, immediate) {
            var timeout;
            return function () {
                var context = this, args = arguments;
                var later = function () {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }

        var stickyCheck = debounce(function () {
            var headerdiv = $("#header");
            if (headerdiv) {
                var headertop = headerdiv.offset().top;
                var scrolltop = $(document).scrollTop();
                var headerbottom = headertop - scrolltop + headerdiv.outerHeight(true);
                //var div_top = $('.readAndInteract .rightHeader').offset().top;
                var div = $('.readAndInteract')[0];
                if (div) {
                    var div_top = div.getBoundingClientRect().top;
                    if (headerbottom >= div_top) {
                        $('.readAndInteract .rightHeader').addClass('stick').css({'top': ((headerbottom) + 'px')});
                    } else {
                        $('.readAndInteract .rightHeader').removeClass('stick');
                    }
                }
            }
        }, 100);

        window.addEventListener('scroll', stickyCheck);

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

        /**
         * Populate the code snippets
         * @param api
         * @param path
         * @param verb
         */
        function populateCodeSnippets(api, path, verb) {
            var id = "apis_" + cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]) + "_paths_" + cleanUpKey(path) + "_" + verb;
            var operation = api.paths[path][verb];
            var config;
            var tester = self.testers[api.info["x-ibm-name"] + api.info["version"] + "_" + verb + "_" + path];
            if (tester) {
                config = tester.config;
            } else {
                config = window.CommonAPI.getConfigurationForOperation(operation, api.paths[path], api);
            }

            var languages = ["curl", "ruby", "python", "php", "java", "node", "go", "swift", "c", "csharp"];
            var endpoint = self.getEndpoint(api);
            var content = window.exampleGenerator.generateCodeSnippets(api, path, verb, operation, config, 'REPLACE_THIS_KEY', 'REPLACE_THIS_KEY', languages, null, endpoint);

            var body = null;
            var parameters = [];
            if (operation.parameters) {
                parameters = parameters.concat(operation.parameters);
            }
            if (api.paths[path].parameters) {
                parameters = parameters.concat(api.paths[path].parameters);
            }

            // body
            var bodyParameters = parameters.filter(function (parameter) {
                return (parameter.in == "body");
            });
            if (bodyParameters.length > 0) {
                // use the first one only
                // should be inline schema by now
                try {
                    var example = window.exampleGenerator.generateExampleParameter(api, path, verb, bodyParameters[0], null, false);
                    $("#body_" + id + " textarea").val(example).height(150);
                } catch (e) {

                }
            }
            var truncated = '';
            if (codeSnippets.curl && codeSnippets.curl == 1) {
                if (content.curlContent.length > 2000) {
                    truncated = content.curlContent.substring(0, 2000);
                    $("#langtab-" + id + "-curl pre.truncatedPre code").text(truncated);
                    $("#langtab-" + id + "-curl pre.truncatedPre").addClass('truncate');
                    $("#langtab-" + id + "-curl .popup-inner pre code").text(content.curlContent);
                    $("#langtab-" + id + "-curl .showMore").removeClass('hidden');
                } else {
                    $("#langtab-" + id + "-curl pre.truncatedPre code").text(content.curlContent);
                }
            }
            if (codeSnippets.ruby && codeSnippets.ruby == 1) {
                if (content.rubyContent.length > 2000) {
                    truncated = content.rubyContent.substring(0, 2000);
                    $("#langtab-" + id + "-ruby pre.truncatedPre code").text(truncated);
                    $("#langtab-" + id + "-ruby pre.truncatedPre").addClass('truncate');
                    $("#langtab-" + id + "-ruby .popup-inner pre code").text(content.rubyContent);
                    $("#langtab-" + id + "-ruby .showMore").removeClass('hidden');
                } else {
                    $("#langtab-" + id + "-ruby pre.truncatedPre code").text(content.rubyContent);
                }
            }
            if (codeSnippets.python && codeSnippets.python == 1) {
                if (content.pythonContent.length > 2000) {
                    truncated = content.pythonContent.substring(0, 2000);
                    $("#langtab-" + id + "-python pre.truncatedPre code").text(truncated);
                    $("#langtab-" + id + "-python pre.truncatedPre").addClass('truncate');
                    $("#langtab-" + id + "-python .popup-inner pre code").text(content.pythonContent);
                    $("#langtab-" + id + "-python .showMore").removeClass('hidden');
                } else {
                    $("#langtab-" + id + "-python pre.truncatedPre code").text(content.pythonContent);
                }
            }
            if (codeSnippets.php && codeSnippets.php == 1) {
                if (content.phpContent.length > 2000) {
                    truncated = content.phpContent.substring(0, 2000);
                    $("#langtab-" + id + "-php pre.truncatedPre code").text(truncated);
                    $("#langtab-" + id + "-php pre.truncatedPre").addClass('truncate');
                    $("#langtab-" + id + "-php .popup-inner pre code").text(content.phpContent);
                    $("#langtab-" + id + "-php .showMore").removeClass('hidden');
                } else {
                    $("#langtab-" + id + "-php pre.truncatedPre code").text(content.phpContent);
                }
            }
            if (codeSnippets.java && codeSnippets.java == 1) {
                if (content.javaContent.length > 2000) {
                    truncated = content.javaContent.substring(0, 2000);
                    $("#langtab-" + id + "-java pre.truncatedPre code").text(truncated);
                    $("#langtab-" + id + "-java pre.truncatedPre").addClass('truncate');
                    $("#langtab-" + id + "-java .popup-inner pre code").text(content.javaContent);
                    $("#langtab-" + id + "-java .showMore").removeClass('hidden');
                } else {
                    $("#langtab-" + id + "-java pre.truncatedPre code").text(content.javaContent);
                }
            }
            if (codeSnippets.node && codeSnippets.node == 1) {
                if (content.nodeContent.length > 2000) {
                    truncated = content.nodeContent.substring(0, 2000);
                    $("#langtab-" + id + "-node pre.truncatedPre code").text(truncated);
                    $("#langtab-" + id + "-node pre.truncatedPre").addClass('truncate');
                    $("#langtab-" + id + "-node .popup-inner pre code").text(content.nodeContent);
                    $("#langtab-" + id + "-node .showMore").removeClass('hidden');
                } else {
                    $("#langtab-" + id + "-node pre.truncatedPre code").text(content.nodeContent);
                }
            }
            if (codeSnippets.go && codeSnippets.go == 1) {
                if (content.goContent.length > 2000) {
                    truncated = content.goContent.substring(0, 2000);
                    $("#langtab-" + id + "-go pre.truncatedPre code").text(truncated);
                    $("#langtab-" + id + "-go pre.truncatedPre").addClass('truncate');
                    $("#langtab-" + id + "-go .popup-inner pre code").text(content.goContent);
                    $("#langtab-" + id + "-go .showMore").removeClass('hidden');
                } else {
                    $("#langtab-" + id + "-go pre.truncatedPre code").text(content.goContent);
                }
            }
            if (codeSnippets.swift && codeSnippets.swift == 1) {
                if (content.swiftContent.length > 2000) {
                    truncated = content.swiftContent.substring(0, 2000);
                    $("#langtab-" + id + "-swift pre.truncatedPre code").text(truncated);
                    $("#langtab-" + id + "-swift pre.truncatedPre").addClass('truncate');
                    $("#langtab-" + id + "-swift .popup-inner pre code").text(content.swiftContent);
                    $("#langtab-" + id + "-swift .showMore").removeClass('hidden');
                } else {
                    $("#langtab-" + id + "-swift pre.truncatedPre code").text(content.swiftContent);
                }
            }
            if (codeSnippets.c && codeSnippets.c == 1) {
                if (content.cContent.length > 2000) {
                    truncated = content.cContent.substring(0, 2000);
                    $("#langtab-" + id + "-c pre.truncatedPre code").text(truncated);
                    $("#langtab-" + id + "-c pre.truncatedPre").addClass('truncate');
                    $("#langtab-" + id + "-c .popup-inner pre code").text(content.cContent);
                    $("#langtab-" + id + "-c .showMore").removeClass('hidden');
                } else {
                    $("#langtab-" + id + "-c pre.truncatedPre code").text(content.cContent);
                }
            }
            if (codeSnippets.csharp && codeSnippets.csharp == 1) {
                if (content.csharpContent.length > 2000) {
                    truncated = content.csharpContent.substring(0, 2000);
                    $("#langtab-" + id + "-csharp pre.truncatedPre code").text(truncated);
                    $("#langtab-" + id + "-csharp pre.truncatedPre").addClass('truncate');
                    $("#langtab-" + id + "-csharp .popup-inner pre code").text(content.csharpContent);
                    $("#langtab-" + id + "-csharp .showMore").removeClass('hidden');
                } else {
                    $("#langtab-" + id + "-csharp pre.truncatedPre code").text(content.csharpContent);
                }
            }
        }

        /**
         * Generate the examples
         * @param api
         * @param path
         * @param verb
         */
        function generateExamples(api, path, verb) {
            var exampleid = "exampleresponse_apis_" + cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]) + "_paths_" + cleanUpKey(path) + "_" + verb;

            function hideTab(api, path, verb) {
                var cleanedapiname = cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]);
                var container = $(".navigate-apis_" + cleanedapiname + "_paths");
                // hide example section since nothing worthwhile putting in it
                var tabid = "tab-content_example_" + cleanedapiname + "_paths_" + cleanUpKey(path) + "_" + verb;
                $("#" + tabid, container).hide();
            }

            try {
                var example = window.exampleGenerator.generateExampleResponse(api, path, verb);
                if (example.length > 1000) {
                    var truncated = example.substring(0, 1000);
                    $("#" + exampleid + " pre.exampleResponsePre code").text(truncated);
                    $("#" + exampleid + " pre.exampleResponsePre").addClass('truncate');
                    $("#" + exampleid + " .popup-inner pre code").text(example);
                    $("#" + exampleid + " .showMore").removeClass('hidden');
                } else {
                    $("#" + exampleid + " pre.exampleResponsePre code").text(example);
                }
            } catch (e) {
                hideTab(api, path, verb);
            }
        }

        function populateCodeSnippetsTimeout(expanded, path, verb) {
            setTimeout(function () {
                populateCodeSnippets(expanded, path, verb);
            }, 0);
            // if multiple security requirements then watch for a change
            var id = "content-apis_" + cleanUpClassName(expanded.info["x-ibm-name"] + expanded.info["version"]) + "_paths_" + cleanUpKey(path) + "_" + verb;
            var requestForm = $("." + id + " form[name='request_" + cleanUpClassName(expanded.info["x-ibm-name"] + expanded.info["version"]) + "_" + cleanUpKey(path) + "_" + verb + "']");
            $(".securitySelectionSection .apimSecurityType .securityType", requestForm).change(
                function () {
                    // has to be on timeout to ensure the security config has been reparsed by the test tool code
                    setTimeout(function () {
                        populateCodeSnippets(expanded, path, verb);
                        $('.langtab pre').each(function (i, block) {
                            hljs.highlightBlock(block);
                        });
                    }, 0);
                }
            );
        }

        function generateExamplesTimeout(expanded, path, verb) {
            setTimeout(function () {
                generateExamples(expanded, path, verb);
            }, 0);
        }

        var waypoints = [
            {path: "product"},
            {path: "apis"}
        ];
        self.apis.forEach(function (api) {
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
            self.expandedapis.forEach(function (expanded) {
                if (expanded.info['x-ibm-name'] && expanded.info['x-ibm-name'] == api.info['x-ibm-name'] && expanded.info['version'] && expanded.info['version'] == api.info['version']) {
                    Object.keys(api.paths).forEach(function (path) {
                        Object.keys(api.paths[path]).forEach(function (verb) {
                            if (["PUT", "POST", "GET", "DELETE", "OPTIONS", "HEAD", "PATCH"].indexOf(verb.toUpperCase()) != -1) {
                                if (!expanded.basePath) {
                                    expanded.basePath = '';
                                }
                                waypoints.push({
                                    path: "apis_" + cleanedapiname + "_paths_" + cleanUpKey(path) + "_" + verb,
                                    verb: verb,
                                    offset: 16,
                                    expand: "apis_" + cleanedapiname
                                });
                                populateCodeSnippetsTimeout(expanded, path, verb);
                                generateExamplesTimeout(expanded, path, verb);
                            }
                        });
                    });
                    if (api.definitions) {
                        Object.keys(api.definitions).forEach(function (definition) {
                            waypoints.push({
                                path: "apis_" + cleanedapiname + "_definitions_" + cleanUpClassName(definition),
                                expand: "apis_" + cleanedapiname
                            });
                        });
                    }
                }
            });
        });
        /* On timeout so it runs after the code snippets and examples have been generated */
        setTimeout(function () {
            $('.langtab pre').each(function (i, block) {
                hljs.highlightBlock(block);
            });
            $('.exampleRespData pre').each(function (i, block) {
                hljs.highlightBlock(block);
            });
            $('pre.inlineSchema').each(function (i, block) {
                hljs.highlightBlock(block);
            });
            $('.markdown pre').each(function (i, block) {
                $(this).addClass('inlineSchema');
                hljs.highlightBlock(block);
            });
        }, 0);

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

        function watchCodeList() {
            $(".langs-menu a").click(function (event) {
                event.preventDefault();
                $(this).parent().addClass("current");
                $(this).parent().siblings().removeClass("current");
                var tab = $(this).attr("href");
                tab = tab.replace(/^\#/g, '');
                $('.' + tab).siblings().removeClass("show");
                $('.' + tab).addClass("show").fadeIn();
            });
        }

        watchCodeList();

        function createTesters() {
            self.testers = {};
            self.expandedapis.forEach(function (api) {
                Object.keys(api.paths).forEach(function (path) {
                    var pathObject = api.paths[path];
                    Object.keys(pathObject).forEach(function (verb) {
                        if (["PUT", "POST", "GET", "DELETE", "OPTIONS", "HEAD", "PATCH"].indexOf(verb.toUpperCase()) != -1) {
                            self.testers[api['info']['x-ibm-name'] + api['info']['version'] + "_" + verb + "_" + path] = new Test({
                                    api: api,
                                    path: path,
                                    verb: verb,
                                    definition: pathObject[verb]
                                },
                                api,
                                self);
                        }
                    });
                });
            });
        }

        createTesters();

        function watchEndpoint() {
            $(".endpointSelect").change(function (event) {
                event.preventDefault();
                var endpoint = this.value;
                if (endpoint) {
                    self.setEndpoint(endpoint);
                    var apistring = this.dataset.api;
                    self.expandedapis.forEach(function (expanded) {
                        var cleanedapiname = cleanUpClassName(expanded.info["x-ibm-name"] + expanded.info["version"]);
                        if (cleanedapiname == apistring) {
                            Object.keys(expanded.paths).forEach(function (path) {
                                Object.keys(expanded.paths[path]).forEach(function (verb) {
                                    if (["PUT", "POST", "GET", "DELETE", "OPTIONS", "HEAD", "PATCH"].indexOf(verb.toUpperCase()) != -1) {
                                        if (!expanded.basePath) {
                                            expanded.basePath = '';
                                        }
                                        populateCodeSnippetsTimeout(expanded, path, verb);
                                        var exampleid = cleanUpClassName(expanded.info["x-ibm-name"] + expanded.info["version"]) + "_paths_" + cleanUpKey(path) + "_" + verb;
                                        var tryid = cleanUpClassName(expanded.info["x-ibm-name"] + expanded.info["version"]) + "_" + cleanUpKey(path) + "_" + verb;
                                        $("#tab-content_example_" + exampleid + " .exampleDefinition").text(verb.toUpperCase() + " " + endpoint + expanded['basePath'] + path);
                                        $("#tab-content_try_" + tryid + " .apiURL").text(endpoint + expanded['basePath'] + path);
                                    }
                                });
                            });
                        }
                    });
                    setTimeout(function () {
                        $('.langtab pre').each(function (i, block) {
                            hljs.highlightBlock(block);
                        });
                    }, 0);
                }
            });
        }

        watchEndpoint();

        $('.definitionsSection pre code').each(function (i, block) {
            hljs.highlightBlock(block);
        });
    }

    API.prototype.test = function (apiname, verb, path) {
        path = path.replace(/%27/g, "\'");
        var tester = this.testers[apiname + "_" + verb + "_" + path];
        if (tester) {
            tester.test();
        }
        // prevent submit button from actually submitting the form
        if (event.preventDefault) {
            event.preventDefault();
        } else {
            event.returnValue = false; // for IE as doesn't support preventDefault;
        }
        return false;
    };

    API.prototype.authorize = function (apiname, verb, path) {
        path = path.replace(/%27/g, "\'");
        var tester = this.testers[apiname + "_" + verb + "_" + path];
        if (tester) {
            tester.authorize();
        }
        // prevent submit button from actually submitting the form
        if (event.preventDefault) {
            event.preventDefault();
        } else {
            event.returnValue = false; // for IE as doesn't support preventDefault;
        }
        return false;
    };

    API.prototype.getToken = function (apiname, verb, path) {
        path = path.replace(/%27/g, "\'");
        var tester = this.testers[apiname + "_" + verb + "_" + path];
        if (tester) {
            tester.getToken();
        }
        // prevent submit button from actually submitting the form
        if (event.preventDefault) {
            event.preventDefault();
        } else {
            event.returnValue = false; // for IE as doesn't support preventDefault;
        }
        return false;
    };

    API.prototype.refreshToken = function (apiname, verb, path) {
        path = path.replace(/%27/g, "\'");
        var tester = this.testers[apiname + "_" + verb + "_" + path];
        if (tester) {
            tester.refreshToken();
        }
        // prevent submit button from actually submitting the form
        if (event.preventDefault) {
            event.preventDefault();
        } else {
            event.returnValue = false; // for IE as doesn't support preventDefault;
        }
        return false;
    };

    API.prototype.forgetToken = function (apiname, verb, path) {
        var tester = this.testers[apiname + "_" + verb + "_" + path];
        if (tester) {
            tester.forgetToken();
        }
        // prevent submit button from actually submitting the form
        if (event.preventDefault) {
            event.preventDefault();
        } else {
            event.returnValue = false; // for IE as doesn't support preventDefault;
        }
        return false;
    };

    API.prototype.navigate = function (path, expand) {
        this.userNavigate = true;
        this.setSelectedPath(path, null, expand);
        var node = document.querySelector('.navigate-apis .navigate-' + path);
        if (node) {
            node.scrollIntoView();
            window.scrollBy(0, -150);
        }
    };

    API.prototype.navigateop = function (path, expand) {
        this.userNavigate = true;
        this.setSelectedPath(path, null, expand);
        var node = document.querySelector('.navigate-apis .navigate-' + path);
        if (node) {
            $(".opwrapper-" + path).addClass("open");
            node.scrollIntoView();
            window.scrollBy(0, -150);
        }
    };

    API.prototype.navigatedefs = function (api, def, expand) {
        $(".definitions_toggle_apis-" + api).addClass("open");
        this.userNavigate = true;
        this.setSelectedPath('apis_' + api + '_definitions_' + def, null, expand);
        var node = document.querySelector('.navigate-apis .navigate-' + 'apis_' + api + '_definitions_' + def);
        if (node) {
            node.scrollIntoView();
            window.scrollBy(0, -150);
        }
    };

    API.prototype.selecttag = function (api, tag) {
        $(".apis_" + api + " .operation").addClass("hidden");
        $(".apis_" + api + " .operation-tag-" + tag).removeClass("hidden");
        $(".apis_" + api + " .apiTag").addClass("hidden").removeClass("selected");
        $(".apis_" + api + " .apiTag." + tag).removeClass("hidden").addClass("selected");
        $(".apis_" + api + " .apiTag." + tag + " .unselect").removeClass("hidden");
        $(".apis_" + api + " .pathWrapper").each(function (index, value) {
            if ($(".readAndInteract.operation", this).not('.hidden').length < 1) {
                $(this).addClass("hidden");
            }
        });
    };
    API.prototype.unselecttag = function (api) {
        $(".apis_" + api + " .operation").removeClass("hidden");
        $(".apis_" + api + " .apiTag").removeClass("hidden").removeClass("selected");
        $(".apis_" + api + " .apiTag .unselect").addClass("hidden");
        $(".apis_" + api + " .pathWrapper").removeClass("hidden");
    };

    API.prototype.toggleplanapi = function (api) {
        $(".planapiwrapper-" + api).toggleClass("open");
        // set the height of the expanded section in the plan table to match that of the expanded API ops
        var apidiv = $(".apiList .planapiwrapper-" + api);
        var headerbottom = apidiv.outerHeight(true);
        $(".plan .planapiwrapper-" + api).css({'height': (headerbottom + 'px')});
    };

    API.prototype.togglesection = function (section) {
        $("." + section + "Container").toggleClass("open");
    };

    API.prototype.toggleop = function (operation) {
        $(".opwrapper-" + operation).toggleClass("open");
    };
    API.prototype.toggledefs = function (api) {
        $(".definitions_toggle_apis-" + api).toggleClass("open");
    };

    API.prototype.getSelectedPath = function () {
        return this.selectedPath;
    };

    API.prototype.setSelectedPath = function (path, verb, expand) {
        this.selectedPath = path;
        $('.mesh-portal-product .tocItem a.selected').removeClass('selected');
        $('.mesh-portal-product .tocItem.toc-' + path + ' > a').addClass('selected');
        if (expand) {
            $(".toc .toc-container-" + expand).removeClass("hidden");
        }
    };

    API.prototype.getEndpoint = function (api) {
        if (this.endpoint) {
            return this.endpoint;
        } else if (api && api['x-ibm-endpoints'] && api['x-ibm-endpoints'][0] && api['x-ibm-endpoints'][0].endpointUrl) {
            return api['x-ibm-endpoints'][0].endpointUrl;
        } else if (api && api.host){
            var endpoint = "://" + api.host;
            if (api.schemes) {
                endpoint = api.schemes[0] + endpoint;
            } else {
                endpoint = "https" + endpoint;
            }
            return endpoint;
        } else {
            return "";
        }
    };

    API.prototype.setEndpoint = function (string) {
        this.endpoint = string;
    };

    window.API = API;

    $(document).ready(function () {
        window.API = new API(window.apiJson, window.expandedapiJson);
        // Handle the popups for large requests and responses
        //----- OPEN
        $('[data-popup-open]').on('click', function () {
            var targeted_popup_class = jQuery(this).attr('data-popup-open');
            $('[data-popup="' + targeted_popup_class + '"]').fadeIn(350);

            if (event.preventDefault) {
                event.preventDefault();
            } else {
                event.returnValue = false; // for IE as doesn't support preventDefault;
            }
            return false;
        });

        //----- CLOSE
        $('[data-popup-close]').on('click', function () {
            var targeted_popup_class = jQuery(this).attr('data-popup-close');
            $('[data-popup="' + targeted_popup_class + '"]').fadeOut(350);

            if (event.preventDefault) {
                event.preventDefault();
            } else {
                event.returnValue = false; // for IE as doesn't support preventDefault;
            }
            return false;
        });
    });

    function Test(operation, api, apiObj) {

        var self = this;

        self.operation = operation;
        self.api = api;
        // the API instance
        self.apiObj = apiObj;

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

        function parseSecurityConfig() {
            // any requirement for identification?
            if (self.config.requiresClientId || self.config.requiresClientSecret || !$.isEmptyObject(self.config.externalApiKeys)) {
                $(".identificationSection", self.requestForm).removeClass("hidden");
            }
            // any requirement for client ID?
            if (self.config.requiresClientId) {
                $(".identificationSection .clientId", self.requestForm).removeClass("hidden");
            }
            // any requirement for client secret?
            if (self.config.requiresClientSecret) {
                $(".identificationSection .clientSecret", self.requestForm).removeClass("hidden");
            }
            // any external apiKey security
            if (!$.isEmptyObject(self.config.externalApiKeys)) {
                $.each(self.config.externalApiKeys, function (key, value) {
                    var keydiv = document.createElement('div');
                    keydiv.className = "parameter externalSecurity";
                    var labeldiv = document.createElement('div');
                    labeldiv.className = 'parameterName';
                    var labelText = document.createTextNode(value.name);
                    labeldiv.appendChild(labelText);
                    var input = document.createElement('input');
                    input.setAttribute('type', 'password');
                    input.setAttribute('name', value.name);
                    input.setAttribute('id', key);
                    input.className = 'parameterValue';
                    keydiv.appendChild(labeldiv);
                    keydiv.appendChild(input);
                    $(".identificationSection .contrast", self.requestForm).append(keydiv);
                });
                if (!self.config.requiresClientId && !self.config.requiresClientSecret) {
                    // hide the message to login if not using clientID or clientSecret
                    $(".identificationSection .loginMessage", self.requestForm).addClass("hidden");
                }
            }
            // any requirement for authorization?
            if (self.config.requiresBasicAuth || self.config.requiresOauth) {
                $(".authorizationSection", self.requestForm).removeClass("hidden");
            }

            // any requirement for an authorization call-out?
            if (self.config.requiresOauth) {
                $(".authorizationSection .apimAuthorize", self.requestForm).removeClass("hidden");
                $(".authorizationSection .apimAuthUrl", self.requestForm).removeClass("hidden");
                $(".apimScopes", self.requestForm).removeClass("hidden");
                // show client type field
                // leave this hidden for now since assuming confidential client types everywhere
                // $(".authorizationSection .apimAuthUrl .apimClientType", self.requestForm).removeClass("hidden");

                if (self.config.oauthScopes) {
                    $(".apimScopes .oauthscopes", self.requestForm).empty();

                    var scopesList = [];
                    // If there are no operation level scopes defined, then just use the API ones
                    if (!self.operation.definition["security"]){
                        $.each(self.config.oauthScopes, function (key, value) {
                            scopesList.push(value);
                        });
                    } else {
                        // check if an oauth flow has been selected - we only want to show the scopes from that flow
                        var flowName = null;
                        if ($('.securityType', self.requestForm).is(":visible")){
                           flowName = $('.securityType option:selected', self.requestForm).val();
                           if (!flowName){
                              // if not set by user, then just use the first one.
                              flowName = $('.securityType option:first-child', self.requestForm).val();
                           }
                        }
                        // loop through the security operations and add all those that are of type oauth and match the
                        // selected (or first if none selected) flow name.
                        for (var i=0; i<self.operation.definition.security.length; i++){
                            $.each(self.operation.definition.security[i], function (key, value) {
                                // check if key exists in api.securityDefinitions and check if it is type oauth
                                if (api.securityDefinitions[key]){
                                    if (api.securityDefinitions[key].type == "oauth2"){
                                        // if flowname is not set then it means .securityType was not visible - so just
                                        // show scopes, otherwise only show scopes for selected flow.
                                        if (!flowName || (key == flowName)){
                                            for (var x=0; x<value.length; x++){
                                                scopesList.push(value[x]);
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    }

                    for (var i=0; i<scopesList.length; i++){
                        var inputdiv = document.createElement('div');
                        var input = document.createElement('input');
                        input.setAttribute('type', 'checkbox');
                        input.setAttribute('name', 'scope[]');
                        input.setAttribute('value', scopesList[i]);
                        input.setAttribute('checked', 'checked');
                        input.value = scopesList[i];
                        var inputText = document.createTextNode(scopesList[i]);
                        inputdiv.appendChild(input);
                        inputdiv.appendChild(inputText);
                        $(".apimScopes .oauthscopes", self.requestForm).append(inputdiv);
                    }

                }
                // before filling out any OAuth URLs, replace any $(catalog.url) instances
                var endpoint = self.apiObj.getEndpoint(self.api);
                if (self.config.oauthAuthUrl) {
                    self.config.oauthAuthUrl = self.config.oauthAuthUrl.replace('$(catalog.url)', endpoint);
                }
                if (self.config.oauthTokenUrl) {
                    self.config.oauthTokenUrl = self.config.oauthTokenUrl.replace('$(catalog.url)', endpoint);
                }

                // any requirement for token refresh?
                if (self.config.oauthFlow == "accessCode") {
                    $(".authorizationSection .apimAuthUrl .authurl", self.requestForm).text(self.config.oauthAuthUrl);
                    $(".authorizationSection .apimAuthUrl .accessCodeFlow", self.requestForm).removeClass("hidden");
                    $(".authorizationSection .apimAuthUrl .tokenurl", self.requestForm).text(self.config.oauthTokenUrl);
                    $(".authorizationSection .apimAuthUrl .threelegged", self.requestForm).removeClass("hidden");
                    $(".authorizationSection .apimAuthUrl .tokenBasedFlow", self.requestForm).removeClass("hidden");

                    $(".authorizationSection .apimAuthUrl .implicitFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .passwordFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .applicationFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .twolegged", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .notAccessCodeFlow", self.requestForm).addClass("hidden");
                }
                if (self.config.oauthFlow == "implicit") {
                    $(".authorizationSection .apimAuthUrl .authurl", self.requestForm).text(self.config.oauthAuthUrl);
                    $(".authorizationSection .apimAuthUrl .implicitFlow", self.requestForm).removeClass("hidden");
                    $(".authorizationSection .apimAuthUrl .threelegged", self.requestForm).removeClass("hidden");
                    $(".authorizationSection .apimAuthUrl .notAccessCodeFlow", self.requestForm).removeClass("hidden");

                    $(".authorizationSection .apimAuthUrl .accessCodeFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .passwordFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .applicationFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .tokenBasedFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .twolegged", self.requestForm).addClass("hidden");
                }
                if (self.config.oauthFlow == "application") {
                    $(".authorizationSection .apimAuthUrl .applicationFlow", self.requestForm).removeClass("hidden");
                    $(".authorizationSection .apimAuthUrl .twolegged", self.requestForm).removeClass("hidden");
                    $(".authorizationSection .apimAuthUrl .tokenBasedFlow", self.requestForm).removeClass("hidden");
                    $(".authorizationSection .apimAuthUrl .notAccessCodeFlow", self.requestForm).removeClass("hidden");
                    $(".authorizationSection .apimAuthUrl .tokenurl", self.requestForm).text(self.config.oauthTokenUrl);

                    $(".authorizationSection .apimAuthUrl .accessCodeFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .implicitFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .passwordFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .threelegged", self.requestForm).addClass("hidden");
                }
                if (self.config.oauthFlow == "password") {
                    $(".authorizationSection .apimAuthUrl .passwordFlow", self.requestForm).removeClass("hidden");
                    $(".authorizationSection .apimAuthUrl .twolegged", self.requestForm).removeClass("hidden");
                    $(".authorizationSection .apimAuthUrl .tokenBasedFlow", self.requestForm).removeClass("hidden");
                    $(".authorizationSection .apimAuthUrl .notAccessCodeFlow", self.requestForm).removeClass("hidden");
                    $(".authorizationSection .apimAuthUrl .tokenurl", self.requestForm).text(self.config.oauthTokenUrl);

                    $(".authorizationSection .apimAuthUrl .accessCodeFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .implicitFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .applicationFlow", self.requestForm).addClass("hidden");
                    $(".authorizationSection .apimAuthUrl .threelegged", self.requestForm).addClass("hidden");
                }
            } else {
                $(".authorizationSection .apimAuthorize", self.requestForm).addClass("hidden");
                $(".authorizationSection .apimAuthUrl", self.requestForm).addClass("hidden");
                $(".apimScopes", self.requestForm).addClass("hidden");
                $(".authorizationSection .apimAuthUrl .twolegged", self.requestForm).addClass("hidden");
                $(".authorizationSection .apimAuthUrl .accessCodeFlow", self.requestForm).addClass("hidden");
                $(".authorizationSection .apimAuthUrl .implicitFlow", self.requestForm).addClass("hidden");
                $(".authorizationSection .apimAuthUrl .applicationFlow", self.requestForm).addClass("hidden");
                $(".authorizationSection .apimAuthUrl .passwordFlow", self.requestForm).addClass("hidden");
                $(".authorizationSection .apimAuthUrl .threelegged", self.requestForm).addClass("hidden");
            }

            if (self.config.requiresBasicAuth || (self.config.oauthFlow == "password")) {
                $(".authorizationSection .userCredentials", self.requestForm).removeClass("hidden");
            } else {
                $(".authorizationSection .userCredentials", self.requestForm).addClass("hidden");
            }
        }

        var id = "content-apis_" + cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]) + "_paths_" + cleanUpKey(self.operation.path) + "_" + self.operation.verb;
        self.responseSection = $("." + id + " #response_" + cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]) + '_' + cleanUpKey(self.operation.path) + "_" + self.operation.verb);
        self.requestForm = $("." + id + " form[name='request_" + cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]) + "_" + cleanUpKey(self.operation.path) + "_" + self.operation.verb + "']");

        // check security requirements
        self.config = window.CommonAPI.getConfigurationForOperation(operation.definition, api.paths[operation.path], api);

        // any security requirements at all?
        if (self.config.requiresSecuritySection || (self.config.securityFlows && self.config.securityFlows.length > 0)) {
            $(".securitySection", self.requestForm).removeClass("hidden");
        }

        // select security type
        // TODO fix stuff below
        if (self.config.securityFlows && self.config.securityFlows.length > 1) {
            $(".securitySelectionSection", self.requestForm).removeClass("hidden");
            $.each(self.config.securityFlows, function (key, object) {
                var option = document.createElement('option');
                option.value = object.$$label;
                var optiontext = document.createTextNode(object.$$label);
                option.appendChild(optiontext);
                $(".securitySelectionSection .apimSecurityType .securityType", self.requestForm).append(option);
            });

            // set to first option by default
            self.config = window.CommonAPI.getConfigurationForOperation(operation.definition, api.paths[operation.path], api, self.config.securityFlows[0]);
            parseSecurityConfig();

            $(".securitySelectionSection .apimSecurityType .securityType", self.requestForm).change(
                function () {
                    var flow = $('.securitySelectionSection .apimSecurityType .securityType option:selected', self.requestForm).val();
                    // need to reparse the config now we know the security definition
                    $.each(self.config.securityFlows, function (key, object) {
                        if (object.$$label == flow) {
                            self.config = window.CommonAPI.getConfigurationForOperation(operation.definition, api.paths[operation.path], api, object);
                        }
                    });
                    parseSecurityConfig();
                    // empty the token field
                    $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val('');
                }
            );
        } else if (self.config.securityFlows && self.config.securityFlows.length == 1) {
            self.config = window.CommonAPI.getConfigurationForOperation(operation.definition, api.paths[operation.path], api, self.config.securityFlows[0]);
            parseSecurityConfig();
        }

        // any requirement for user credentials?
        if (self.config.requiresBasicAuth || (self.config.oauthFlow == "password")) {
            $(".authorizationSection .userCredentials", self.requestForm).removeClass("hidden");
        }

        function displayRedirectURIWarning() {
            var clientid = $(".identificationSection .clientId [name=apimClientId]").val();
            if (clientid) {
                if (self.config.requiresOauth && (self.config.oauthFlow == "accessCode" || self.config.oauthFlow == "implicit")) {
                    var redirect_uris = window.appJson;
                    var redirect_uri = redirect_uris[0][clientid];
                    if (!redirect_uri) {
                        $(".identificationSection .noRedirectURI", self.requestForm).removeClass("hidden");
                    } else {
                        $(".identificationSection .noRedirectURI", self.requestForm).addClass("hidden");
                    }
                } else {
                    $(".identificationSection .noRedirectURI", self.requestForm).addClass("hidden");
                }
            } else {
                $(".identificationSection .noRedirectURI", self.requestForm).addClass("hidden");
            }
        }

        displayRedirectURIWarning();

        $(".identificationSection .clientId [name=apimClientId]", self.requestForm).change(
            function () {
                displayRedirectURIWarning();
            });
    }

    Test.prototype.authorize = function () {
        var requestForm = {};
        this.requestForm.serializeArray().forEach(function (parameter) {
            requestForm[parameter.name] = parameter.value;
        });
        var headers = {};

        var self = this;

        function clearResponse() {
            $(".authorizationSection .oauthMessage.norefresh", self.requestForm).addClass("hidden");
            $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val('');
            $(".authorizationSection .oauthMessage.oauthError", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.unauthorized", self.requestForm).addClass("hidden");
            $(".authorizationSection .refreshButton .refreshDone", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.mixedContentWarning", self.responseSection).addClass("hidden");
            $(".authorizationSection .oauthMessage.corsWarning", self.responseSection).addClass("hidden");
        }

        function errorHandler(xhrObj, textStatus, error) {
            $(".authorizationSection .oauthMessage.norefresh", self.requestForm).addClass("hidden");
            // detect MixedContent
            if (self.config.oauthTokenUrl.lastIndexOf(window.location.protocol, 0) === 0) {
                $(".authorizationSection .oauthMessage.mixedContentWarning", self.requestForm).addClass("hidden");
                if (xhrObj.status && xhrObj.status == 0) {
                    $(".authorizationSection .oauthMessage.corsWarning", self.requestForm).removeClass("hidden");
                } else {
                    $(".authorizationSection .oauthMessage.corsWarning", self.requestForm).addClass("hidden");
                    if (xhrObj.status && xhrObj.status == 401) {
                        $(".authorizationSection .oauthMessage.unauthorized", self.requestForm).removeClass("hidden");
                        $(".authorizationSection .oauthMessage.oauthError", self.requestForm).addClass("hidden");
                    } else {
                        $(".authorizationSection .oauthMessage.oauthError", self.requestForm).removeClass("hidden");
                        $(".authorizationSection .oauthMessage.unauthorized", self.requestForm).addClass("hidden");
                    }
                }
            } else {
                $(".authorizationSection .oauthMessage.mixedContentWarning", self.requestForm).removeClass("hidden");
            }
        }

        function successHandler(data, status, xhrObj) {
            $(".authorizationSection .oauthMessage.norefresh", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.oauthError", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.unauthorized", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.mixedContentWarning", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.corsWarning", self.requestForm).addClass("hidden");
            if (self.config.oauthFlow == "accessCode") {
                if (data.access_token) {
                    $(".authorizationSection .apimAuthUrl .accesscode .result", self.requestForm).val(data.access_token);
                } else {
                    $(".authorizationSection .apimAuthUrl .accesscode .result", self.requestForm).val(data);
                }
            } else {
                if (data.access_token) {
                    $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val(data.access_token);
                } else {
                    $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val(data);
                }
            }
            if (data.refresh_token) {
                self.refresh_token = data.refresh_token;
                $(".authorizationSection .refreshButton", self.requestForm).removeClass("hidden");
            } else {
                $(".authorizationSection .refreshButton", self.requestForm).addClass("hidden");
            }
        }

        var authurl = this.config.oauthTokenUrl;
        var scope = '';
        $.each($(".apimScopes .oauthscopes input[name='scope[]']:checked", self.requestForm), function () {
            scope += $(this).val() + ' ';
        });
        scope = scope.trim();
        var redirect_uri = '';
        if (requestForm.apimClientId) {
            var redirect_uris = window.appJson;
            redirect_uri = redirect_uris[0][requestForm.apimClientId];
        }
        if (this.config.oauthFlow == "application") {
            authurl += '?grant_type=client_credentials';
            if (scope) {
                authurl += '&scope=' + scope;
            }
        } else if (this.config.oauthFlow == "password") {
            var data = 'grant_type=password';
            if (scope) {
                data += '&scope=' + scope;
            }
            if (requestForm.apimUsername && requestForm.apimPassword) {
                data += '&username=' + requestForm.apimUsername + "&password=" + requestForm.apimPassword;
            }
        } else if (this.config.oauthFlow == "implicit") {
            authurl = this.config.oauthAuthUrl;
            authurl += '?response_type=token&client_id=' + requestForm.apimClientId + "&redirect_uri=" + redirect_uri;
            if (scope) {
                authurl += '&scope=' + scope;
            }
        } else if (this.config.oauthFlow == "accessCode") {
            authurl = this.config.oauthAuthUrl;
            authurl += '?response_type=code&client_id=' + requestForm.apimClientId + "&redirect_uri=" + redirect_uri;
            if (scope) {
                authurl += '&scope=' + scope;
            }
        }
        if (self.config.oauthFlow == "accessCode" || self.config.oauthFlow == "implicit") {
            window.open(authurl, '_blank');
        } else {
            // application or password flows we can invoke direct
            if (requestForm.apimClientId && requestForm.apimClientSecret) {
                headers['Authorization'] = "Basic " + btoa(requestForm.apimClientId + ":" + requestForm.apimClientSecret);
                headers["Content-Type"] = "application/x-www-form-urlencoded";
            }

            var oauthXhrOpts = {
                headers: headers,
                error: errorHandler,
                success: successHandler
            };
            var recentJQuery = false;
            if ($.fn.jquery) {
                var vernums = $.fn.jquery.split('.');
                if (parseInt(vernums[0]) == 1 && parseInt(vernums[1]) >= 9 || parseInt(vernums[0]) > 1) {
                    oauthXhrOpts.method = 'POST';
                    recentJQuery = true;
                }
            }
            if (recentJQuery == false) {
                oauthXhrOpts.type = 'POST';
            }
            if (self.config.oauthFlow == "password" && data) {
                oauthXhrOpts.data = data;
            }

            clearResponse();

            this.oauthXhrOpts = oauthXhrOpts;

            $.ajax(authurl, oauthXhrOpts);
        }
    };

    /**
     * Used to convert the access code into an access token when using accessCode oauth flow
     */
    Test.prototype.getToken = function () {
        var requestForm = {};
        this.requestForm.serializeArray().forEach(function (parameter) {
            requestForm[parameter.name] = parameter.value;
        });
        var headers = {};

        var self = this;

        function clearResponse() {
            $(".authorizationSection .oauthMessage.norefresh", self.requestForm).addClass("hidden");
            $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val('');
            $(".authorizationSection .oauthMessage.oauthError", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.unauthorized", self.requestForm).addClass("hidden");
            $(".authorizationSection .refreshButton .refreshDone", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.mixedContentWarning", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.corsWarning", self.requestForm).addClass("hidden");
        }

        function errorHandler(xhrObj, textStatus, error) {
            $(".authorizationSection .oauthMessage.norefresh", self.requestForm).addClass("hidden");
            // detect MixedContent
            if (self.config.oauthTokenUrl.lastIndexOf(window.location.protocol, 0) === 0) {
                $(".authorizationSection .oauthMessage.mixedContentWarning", self.requestForm).addClass("hidden");
                if (xhrObj.status && xhrObj.status == 0) {
                    $(".authorizationSection .oauthMessage.corsWarning", self.requestForm).removeClass("hidden");
                } else {
                    $(".authorizationSection .oauthMessage.corsWarning", self.requestForm).addClass("hidden");
                    if (xhrObj.status && xhrObj.status == 401) {
                        $(".authorizationSection .oauthMessage.unauthorized", self.requestForm).removeClass("hidden");
                        $(".authorizationSection .oauthMessage.oauthError", self.requestForm).addClass("hidden");
                    } else {
                        $(".authorizationSection .oauthMessage.oauthError", self.requestForm).removeClass("hidden");
                        $(".authorizationSection .oauthMessage.unauthorized", self.requestForm).addClass("hidden");
                    }
                }
            } else {
                $(".authorizationSection .oauthMessage.mixedContentWarning", self.requestForm).removeClass("hidden");
            }
        }

        function successHandler(data, status, xhrObj) {
            $(".authorizationSection .oauthMessage.norefresh", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.oauthError", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.unauthorized", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.mixedContentWarning", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.corsWarning", self.requestForm).addClass("hidden");
            if (data.access_token) {
                $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val(data.access_token);
            } else {
                $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val(data);
            }
            if (data.refresh_token) {
                self.refresh_token = data.refresh_token;
            }
        }

        var authurl = this.config.oauthTokenUrl;
        var scope = '';
        $.each($(".apimScopes .oauthscopes input[name='scope[]']:checked", self.requestForm), function () {
            scope += $(this).val() + ' ';
        });
        scope = scope.trim();

        if (requestForm.apimClientId && requestForm.apimClientSecret) {
            headers['Authorization'] = "Basic " + btoa(requestForm.apimClientId + ":" + requestForm.apimClientSecret);
        }
        headers["Content-Type"] = "application/x-www-form-urlencoded";
        var oauthXhrOpts = {
            headers: headers,
            error: errorHandler,
            success: successHandler
        };
        var recentJQuery = false;
        if ($.fn.jquery) {
            var vernums = $.fn.jquery.split('.');
            if (parseInt(vernums[0]) == 1 && parseInt(vernums[1]) >= 9 || parseInt(vernums[0]) > 1) {
                oauthXhrOpts.method = 'POST';
                recentJQuery = true;
            }
        }
        if (recentJQuery == false) {
            oauthXhrOpts.type = 'POST';
        }
        var authCode = $(".authorizationSection .apimAuthUrl .accesscode .result", self.requestForm).val();
        var redirect_uri = '';
        if (requestForm.apimClientId) {
            var redirect_uris = window.appJson;
            redirect_uri = redirect_uris[0][requestForm.apimClientId];
        }
        oauthXhrOpts.data = "grant_type=authorization_code&code=" + encodeURIComponent(authCode) +
            "&redirect_uri=" + redirect_uri + "&scope=" + scope;

        clearResponse();

        $.ajax(authurl, oauthXhrOpts);
    };

    Test.prototype.refreshToken = function () {
        var requestForm = {};
        this.requestForm.serializeArray().forEach(function (parameter) {
            requestForm[parameter.name] = parameter.value;
        });
        var headers = {};

        var self = this;

        function clearResponse() {
            $(".authorizationSection .oauthMessage.norefresh", self.requestForm).addClass("hidden");
            $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val('');
            $(".authorizationSection .oauthMessage.oauthError", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.unauthorized", self.requestForm).addClass("hidden");
            $(".authorizationSection .refreshButton .refreshDone", self.requestForm).addClass("hidden");
        }

        function errorHandler(xhrObj, textStatus, error) {
            if (xhrObj.status && xhrObj.status == 401) {
                $(".authorizationSection .oauthMessage.norefresh", self.requestForm).addClass("hidden");
                $(".authorizationSection .oauthMessage.unauthorized", self.requestForm).removeClass("hidden");
                $(".authorizationSection .oauthMessage.oauthError", self.requestForm).addClass("hidden");
            } else {
                $(".authorizationSection .oauthMessage.oauthError", self.requestForm).removeClass("hidden");
                $(".authorizationSection .oauthMessage.unauthorized", self.requestForm).addClass("hidden");
            }
        }

        function successHandler(data, status, xhrObj) {
            $(".authorizationSection .refreshButton .refreshDone", self.requestForm).removeClass("hidden");
            $(".authorizationSection .oauthMessage.norefresh", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.oauthError", self.requestForm).addClass("hidden");
            $(".authorizationSection .oauthMessage.unauthorized", self.requestForm).addClass("hidden");
            if (data.access_token) {
                $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val(data.access_token);
            } else {
                $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val(data);
            }
            if (data.refresh_token) {
                self.refresh_token = data.refresh_token;
            }
        }

        var authurl = this.config.oauthTokenUrl;

        headers["Content-Type"] = "application/x-www-form-urlencoded";
        var oauthXhrOpts = {
            headers: headers,
            error: errorHandler,
            success: successHandler
        };
        var recentJQuery = false;
        if ($.fn.jquery) {
            var vernums = $.fn.jquery.split('.');
            if (parseInt(vernums[0]) == 1 && parseInt(vernums[1]) >= 9 || parseInt(vernums[0]) > 1) {
                oauthXhrOpts.method = 'POST';
                recentJQuery = true;
            }
        }
        if (recentJQuery == false) {
            oauthXhrOpts.type = 'POST';
        }

        clearResponse();

        if (this.refresh_token) {
            $(".authorizationSection .oauthMessage.norefresh", self.requestForm).addClass("hidden");
            oauthXhrOpts.data = "grant_type=refresh_token&refresh_token=" + this.refresh_token +
                "&client_id=" + requestForm.apimClientId + "&client_secret=" + requestForm.apimClientSecret;
        } else {
            $(".authorizationSection .oauthMessage.norefresh", self.requestForm).removeClass("hidden");
        }

        $.ajax(authurl, oauthXhrOpts);
    };

    Test.prototype.forgetToken = function () {
        $(".authorizationSection .apimAuthUrl .accesstoken .result", this.requestForm).val('');
    };

    Test.prototype.test = function () {
        var requestForm = {};
        this.requestForm.serializeArray().forEach(function (parameter) {
            requestForm[parameter.name] = parameter.value;
        });
        if (!this.api.basePath) {
            this.api.basePath = '';
        }

        var scheme = "https";
        if (requestForm.scheme) {
            scheme = requestForm.scheme;
        }
        var targetUrl = window.API.getEndpoint(this.api);
        if (!targetUrl) {
            targetUrl = scheme + "://" + this.api.host;
        }
        targetUrl = targetUrl + this.api.basePath + this.operation.path;

        // set up headers
        var headers = {};
        if (this.config.requiresBasicAuth) {
            headers['Authorization'] = "Basic " + btoa(requestForm.apimUsername + ":" + requestForm.apimPassword);
        }
        if (this.config.requiresOauth && requestForm.authToken) {
            headers["Authorization"] = "Bearer " + requestForm.authToken;
        }
        if (this.config.requiresClientId && this.config.clientIdLocation == "header" && requestForm.apimClientId) {
            if (this.config.clientIdName) {
                headers[this.config.clientIdName] = requestForm.apimClientId;
            } else {
                headers["X-IBM-Client-Id"] = requestForm.apimClientId;
            }
        }
        if (this.config.requiresClientSecret && this.config.clientSecretLocation == "header" && requestForm.apimClientSecret) {
            if (this.config.clientSecretName) {
                headers[this.config.clientSecretName] = requestForm.apimClientSecret;
            } else {
                headers["X-IBM-Client-Secret"] = requestForm.apimClientSecret;
            }
        }

        if (this.config.soapAction !== undefined) {
            headers["SOAPAction"] = this.config.soapAction;
        }
        // is this SOAP? if so, ditch the operationId from the end...
        if (this.operation.definition['x-ibm-soap'] && this.operation.definition && this.operation.definition.operationId && targetUrl.endsWith("/" + this.operation.definition.operationId)) {
            targetUrl = targetUrl.substring(0, targetUrl.length - (this.operation.definition.operationId.length + 1));
        }

        var parameters = [];
        if (this.operation.definition.parameters) {
            parameters = parameters.concat(this.operation.definition.parameters);
        }
        if (this.api.paths[this.operation.path].parameters) {
            parameters = parameters.concat(this.api.paths[this.operation.path].parameters);
        }

        // path parameters
        var pathParameters = parameters.filter(function (parameter) {
            return (parameter.in == "path");
        });
        if (pathParameters.length > 0) {
            pathParameters.forEach(function (parameter) {
                targetUrl = targetUrl.replace("{" + parameter.name + "}", requestForm['param' + parameter.name]);
            });
        }
        // remove double //
        targetUrl = targetUrl.replace(/(https?:\/\/)|(\/)+/g, "$1$2");

        // query parameters
        var queryParameters = parameters.filter(function (parameter) {
            return (parameter.in == "query");
        });
        var queryParametersAdded = (targetUrl.indexOf('?') >= 0);
        if (queryParameters.length > 0) {
            targetUrl += (queryParametersAdded) ? "&" : "?";
            queryParameters.forEach(function (parameter) {
                if (parameter.name) {
                    // if boolean then must be set to true or false
                    if ((requestForm['param' + parameter.name] !== undefined && (!parameter.type || (parameter.type != "boolean" && !parameter.enum && requestForm['param' + parameter.name]))) || (parameter.type && parameter.type == "boolean" && (requestForm['param' + parameter.name] == "true" || requestForm['param' + parameter.name] == "false")) || (parameter.enum && parameter.enum.indexOf(requestForm['param' + parameter.name]) != -1)) {
                        targetUrl += parameter.name + "=" + requestForm['param' + parameter.name] + "&";
                        queryParametersAdded = true;
                    } else if (parameter.type && parameter.type == "boolean" && parameter.required) {
                        // purely here to catch required boolean fields that are set to false
                        targetUrl += parameter.name + "=false&";
                        queryParametersAdded = true;
                    }
                }
            });
            targetUrl = targetUrl.substring(0, targetUrl.length - 1);
        }
        if (this.config.requiresClientId && this.config.clientIdLocation == "query" && requestForm.apimClientId) {
            var clientIdName = 'client_id';
            if (this.config.clientIdName) {
                clientIdName = this.config.clientIdName;
            }
            if (queryParametersAdded) {
                targetUrl += "&" + clientIdName + "=" + requestForm.apimClientId;
            } else {
                targetUrl += "?" + clientIdName + "=" + requestForm.apimClientId;
                queryParametersAdded = true;
            }
        }
        if (this.config.requiresClientSecret && this.config.clientSecretLocation == "query" && requestForm.apimClientSecret) {
            var clientSecretName = 'client_secret';
            if (this.config.clientSecretName) {
                clientSecretName = this.config.clientSecretName;
            }
            if (queryParametersAdded) {
                targetUrl += "&" + clientSecretName + "=" + requestForm.apimClientSecret;
            } else {
                targetUrl += "?" + clientSecretName + "=" + requestForm.apimClientSecret;
                queryParametersAdded = true;
            }
        }

        // headers
        if (requestForm['content-type']) {
            headers['content-type'] = requestForm['content-type'];
        }
        headers['accept'] = requestForm['accept'];
        var headerParameters = parameters.filter(function (parameter) {
            return (parameter.in == "header");
        });
        if (headerParameters.length > 0) {
            headerParameters.forEach(function (parameter) {
                if ((requestForm['param' + parameter.name] !== undefined && (!parameter.type || (parameter.type != "boolean" && !parameter.enum))) || (parameter.type && parameter.type == "boolean" && (requestForm['param' + parameter.name] == "true" || requestForm['param' + parameter.name] == "false")) || (parameter.enum && parameter.enum.indexOf(requestForm['param' + parameter.name]) != -1)) {
                    headers[parameter.name] = requestForm['param' + parameter.name];
                } else if (parameter.type && parameter.type == "boolean" && parameter.required) {
                    // purely here to catch required boolean fields that are set to false
                    headers[parameter.name] = "false";
                }
            });
        }

        // handle external security
        if (!$.isEmptyObject(this.config.externalApiKeys)) {
            $.each(this.config.externalApiKeys, function (key, value) {
                if (value.in == 'query') {
                    if (queryParametersAdded) {
                        targetUrl += "&" + value.name + "=" + requestForm[key];
                    } else {
                        targetUrl += "?" + value.name + "=" + requestForm[key];
                        queryParametersAdded = true;
                    }
                } else if (value.in == 'header') {
                    headers[value.name] = requestForm[key];
                }
            });
        }

        // body
        var body;
        var bodyParameters = parameters.filter(function (parameter) {
            return (parameter.in == "body");
        });
        var formDataParameters = parameters.filter(function (parameter) {
            return (parameter.in == "formData");
        });
        if (bodyParameters.length > 0) {
            // use the first one only
            body = requestForm['param' + bodyParameters[0].name];
        } else if (formDataParameters.length > 0) {
            var formDataArray = [];
            formDataParameters.forEach(function (parameter) {
                if ((requestForm['param' + parameter.name] !== undefined && (!parameter.type || (parameter.type != "boolean" && !parameter.enum))) || (parameter.type && parameter.type == "boolean" && (requestForm['param' + parameter.name] == "true" || requestForm['param' + parameter.name] == "false")) || (parameter.enum && parameter.enum.indexOf(requestForm['param' + parameter.name]) != -1)) {
                    formDataArray.push(encodeURIComponent(parameter.name).replace(/%20/g, '+') + '=' + encodeURIComponent(requestForm['param' + parameter.name]).replace(/%20/g, '+'));
                } else if (parameter.type && parameter.type == "boolean" && parameter.required) {
                    // purely here to catch required boolean fields that are set to false
                    formDataArray.push(encodeURIComponent(parameter.name).replace(/%20/g, '+') + "=" + encodeURIComponent("false"));
                }
            });
            body = formDataArray.join('&');
        }

        var self = this;

        function clearResponse() {
            $(".requestDetails", self.responseSection).text("");
            $(".responseDetails.responseStatus", self.responseSection).text("");
            $(".responseDetails.responseHeaders", self.responseSection).text("");
            $(".responseDetails.responseBody", self.responseSection).text("");
            $(".responseDetails.corsWarning", self.responseSection).addClass("hidden");
            self.responseSection.addClass("hidden");
        }

        function stringifyRequest() {
            $(".requestDetails.requestUrl", self.responseSection).text(self.operation.verb.toUpperCase() + " " + self.targetUrl);
            var requestHeaders = "";
            if (self.xhrOpts && self.xhrOpts.headers) {
                Object.keys(self.xhrOpts.headers).forEach(function (headerName) {
                    if (headerName == 'X-IBM-Client-Secret' || (self.config.clientSecretName && headerName == self.config.clientSecretName)) {
                        requestHeaders += headerName + ": ∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙\n";
                    } else if (headerName == 'Authorization') {
                        if (self.xhrOpts.headers[headerName].toLowerCase().indexOf("bearer") >= 0) {
                            requestHeaders += headerName + ": Bearer ∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙\n";
                        } else {
                            requestHeaders += headerName + ": ∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙\n";
                        }
                    } else {
                        requestHeaders += headerName + ": " + self.xhrOpts.headers[headerName] + "\n";
                    }
                });
                $(".requestDetails.requestHeaders", self.responseSection).text(requestHeaders);
            }
        }

        function stringifyResponse(xhrObj) {
            /**
             * XmlHttpRequest's getAllResponseHeaders() method returns a string of response
             * headers according to the format described here:
             * http://www.w3.org/TR/XMLHttpRequest/#the-getallresponseheaders-method
             * This method parses that string into a user-friendly key/value pair object.
             */
            function parseResponseHeaders(headerStr) {
                var headers = {};
                if (!headerStr) {
                    return headers;
                }
                var headerPairs = headerStr.split('\u000d\u000a');
                for (var i = 0; i < headerPairs.length; i++) {
                    var headerPair = headerPairs[i];
                    // Can't use split() here because it does the wrong thing
                    // if the header value has the string ": " in it.
                    var index = headerPair.indexOf('\u003a\u0020');
                    if (index > 0) {
                        var key = headerPair.substring(0, index);
                        headers[key] = headerPair.substring(index + 2);
                    }
                }
                return headers;
            }

            $(".responseDetails.responseStatus", self.responseSection).text(xhrObj.status + " " + xhrObj.statusText);
            // detect MixedContent
            if (self.targetUrl.lastIndexOf(window.location.protocol, 0) === 0) {
                $(".responseDetails.mixedContentWarning", self.responseSection).addClass("hidden");
                if (xhrObj.status == 0) {
                    $(".responseDetails.corsWarning", self.responseSection).removeClass("hidden");
                } else {
                    $(".responseDetails.corsWarning", self.responseSection).addClass("hidden");
                }
            } else {
                $(".responseDetails.mixedContentWarning", self.responseSection).removeClass("hidden");
            }

            var responseHeaders = xhrObj.getAllResponseHeaders();
            var responseHeaderArray = parseResponseHeaders(responseHeaders);
            if (responseHeaders && responseHeaders !== "") {
                $(".responseDetails.responseHeaders", self.responseSection).text(responseHeaders);
            }
            if (xhrObj.responseText && xhrObj.responseText !== "") {
                if ((responseHeaderArray['content-type'] || responseHeaderArray['Content-Type']) && (responseHeaderArray['content-type'] == 'application/json' || responseHeaderArray['Content-Type'] == 'application/json')) {
                    $(".responseDetails.responseBody", self.responseSection).text(window.vkbeautify.json(xhrObj.responseText));
                } else if ((responseHeaderArray['content-type'] || responseHeaderArray['Content-Type']) && (responseHeaderArray['content-type'] == 'application/xml' || responseHeaderArray['Content-Type'] == 'application/xml')) {
                    $(".responseDetails.responseBody", self.responseSection).text(window.vkbeautify.xml(xhrObj.responseText));
                } else {
                    $(".responseDetails.responseBody", self.responseSection).text(xhrObj.responseText);
                }
            }
            $('pre code', self.responseSection).each(function (i, block) {
                hljs.highlightBlock(block);
            });
        }

        function errorHandler(xhrObj, textStatus, error) {
            stringifyRequest();
            stringifyResponse(xhrObj);
            self.responseSection.removeClass("hidden");
        }

        function successHandler(data, status, xhrObj) {
            stringifyRequest();
            stringifyResponse(xhrObj);
            self.responseSection.removeClass("hidden");
        }

        var xhrOpts = {
            headers: headers,
            error: errorHandler,
            success: successHandler
        };
        var recentJQuery = false;
        if ($.fn.jquery) {
            var vernums = $.fn.jquery.split('.');
            if (parseInt(vernums[0]) == 1 && parseInt(vernums[1]) >= 9 || parseInt(vernums[0]) > 1) {
                xhrOpts.method = this.operation.verb;
                recentJQuery = true;
            }
        }
        if (recentJQuery == false) {
            xhrOpts.type = this.operation.verb;
        }
        if (body) xhrOpts.data = body;

        clearResponse();

        self.targetUrl = targetUrl;
        self.xhrOpts = xhrOpts;

        $.ajax(targetUrl, xhrOpts);
    };

    window.Test = Test;

}(jQuery));
