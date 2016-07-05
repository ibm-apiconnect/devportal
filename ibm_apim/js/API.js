(function ($) {
    'use strict';

    function API(apis, expandedapis) {

        var self = this;

        self.apis = apis;
        self.expandedapis = expandedapis;

        self.selectedPath = "product";

        var $window = $(window);

        function checkWidth() {
            var headerdiv = $("#header");
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
            var left = apisdiv.width();
            var width = titlediv.width();
            plansdiv.css({'max-width': ((width - left) + 'px')});
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
            var headertop = headerdiv.offset().top;
            var scrolltop = $(document).scrollTop();
            var headerbottom = headertop - scrolltop + headerdiv.outerHeight(true);
            //var div_top = $('.readAndInteract .rightHeader').offset().top;
            var div_top = $('.readAndInteract')[0].getBoundingClientRect().top;
            if (headerbottom >= div_top) {
                $('.readAndInteract .rightHeader').addClass('stick');
                $('.readAndInteract .rightHeader').css({'top': ((headerbottom) + 'px')});
            } else {
                $('.readAndInteract .rightHeader').removeClass('stick');
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

            // Removing multiple consecutive hyphens.
            input = input.replace(/\-+/gi, '-', input);

            return input;
        }

        /**
         * Does this operation only return XML and not JSON?
         * @param api
         * @param operation
         * @returns {boolean}
         */
        function returnsXML(api, operation) {
            if (operation.produces && ($.inArray('application/xml', operation.produces) !== -1 || $.inArray('application/xml+soap', operation.produces) !== -1 || $.inArray('text/xml', operation.produces) !== -1) && $.inArray('application/json', operation.produces) === -1) {
                return true;
            } else if (!operation.produces && api.produces && ($.inArray('application/xml', api.produces) !== -1 || $.inArray('application/xml+soap', api.produces) !== -1 || $.inArray('text/xml', api.produces) !== -1) && $.inArray('application/json', api.produces) === -1) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Does this operation only accept XML and not JSON?
         * @param api
         * @param operation
         * @returns {boolean}
         */
        function acceptsXML(api, operation) {
            if (operation.consumes && ($.inArray('application/xml', operation.consumes) !== -1 || $.inArray('application/xml+soap', operation.consumes) !== -1 || $.inArray('text/xml', operation.consumes) !== -1) && $.inArray('application/json', operation.consumes) === -1) {
                return true;
            } else if (!operation.consumes && api.consumes && ($.inArray('application/xml', api.consumes) !== -1 || $.inArray('application/xml+soap', api.consumes) !== -1 || $.inArray('text/xml', api.consumes) !== -1) && $.inArray('application/json', api.consumes) === -1) {
                return true;
            } else {
                return false;
            }
        }

        function createDummyValue(propertyName, property, empty) {
            var defaultValues = {
                string: '',
                number: 0.0,
                integer: 0,
                boolean: true,
                date: '1970-01-01',
                dateTime: '1970-01-01T00:00:00Z',
                'date-time': '1970-01-01T00:00:00Z',
                password: '',
                binary: '00000000',
                byte: 0
            };
            if (!property.type) {
                property.type = 'string';
            }
            if (empty == true) {
                if (typeof(defaultValues[property.type]) !== 'undefined') {
                    if (property.type == 'string') {
                        if (property.format && (property.format == 'date' || property.format == 'date-time' || property.format == 'password' || property.format == 'binary' || property.format == 'byte')) {
                            return defaultValues[property.format];
                        } else {
                            return defaultValues[property.type];
                        }
                    } else {
                        return defaultValues[property.type];
                    }
                } else {
                    return null;
                }
            } else {
                if (property.enum && property.enum.length > 0) {
                    var enumLength = property.enum.length - 1;
                    var index = chance.natural({min: 0, max: enumLength});
                    return property.enum[index];
                } else if (property.type == 'integer') {
                    var max = 100;
                    var min = 1;
                    if (propertyName && (propertyName.toLowerCase().indexOf('quantity') > -1 || propertyName.toLowerCase().indexOf('number') > -1)) {
                        if (property.maximum) {
                            if (property.exclusiveMaximum) {
                                max = property.maximum;
                            } else {
                                max = property.maximum - 1;
                            }
                        }
                        if (property.minimum) {
                            if (property.exclusiveMinimum) {
                                min = property.minimum;
                            } else {
                                min = property.minimum + 1;
                            }
                        }
                        return chance.integer({min: min, max: max});
                    } else {
                        // int32 && int64
                        max = 99999999;
                        min = 10000000;
                        if (property.maximum) {
                            if (property.exclusiveMaximum) {
                                max = property.maximum;
                            } else {
                                max = property.maximum - 1;
                            }
                        }
                        if (property.minimum) {
                            if (property.exclusiveMinimum) {
                                min = property.minimum;
                            } else {
                                min = property.minimum + 1;
                            }
                        }
                        return chance.natural({min: min, max: max});
                    }
                } else if (property.type == 'boolean') {
                    // random boolean
                    return chance.bool();
                } else if (property.type == 'number') {
                    return chance.floating({min: 0, max: 100, fixed: 8});
                } else {
                    // string
                    if (property.format && (property.format == 'date' || property.format == 'date-time')) {
                        // random date (or date time) between 2000 and now
                        var start = new Date(2000, 0, 1);
                        var end = new Date();
                        var date = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
                        if (property.format == 'date-time') {
                            return date.toISOString();
                        } else {
                            return date.toDateString().slice(0, 10);
                        }
                    } else if (property.format && property.format == 'password') {
                        return 'Passw0rd';
                    } else if (property.format && property.format == 'binary') {
                        return (Math.floor(Math.random() * (127 - 1) + 1)).toString(2);
                    } else if (property.format && property.format == 'byte') {
                        return (Math.floor(Math.random() * (8 - 1) + 1)).toString(16);
                    } else {
                        if (propertyName) {
                            var lowerCasePropName = propertyName.toLowerCase();
                            if (lowerCasePropName.indexOf('address') > -1) {
                                return chance.address();
                            } else if (lowerCasePropName.indexOf('phone') > -1) {
                                return chance.phone();
                            } else if (lowerCasePropName.indexOf('areacode') > -1) {
                                return chance.areacode();
                            } else if (lowerCasePropName.indexOf('post') > -1) {
                                return chance.postal();
                            } else if (lowerCasePropName.indexOf('city') > -1) {
                                return chance.city();
                            } else if (lowerCasePropName.indexOf('province') > -1) {
                                return chance.province();
                            } else if (lowerCasePropName.indexOf('street') > -1) {
                                return chance.street();
                            } else if (lowerCasePropName.indexOf('state') > -1) {
                                return chance.state();
                            } else if (lowerCasePropName.indexOf('country') > -1) {
                                return chance.country({full: true});
                            } else if (lowerCasePropName.indexOf('zip') > -1) {
                                return chance.zip();
                            } else if (lowerCasePropName.indexOf('coordinate') > -1) {
                                return chance.coordinates();
                            } else if (lowerCasePropName.indexOf('latitude') > -1) {
                                return chance.latitude();
                            } else if (lowerCasePropName.indexOf('longitude') > -1) {
                                return chance.longitude();
                            } else if (lowerCasePropName.indexOf('cc') > -1 || lowerCasePropName.indexOf('credit') > -1) {
                                return chance.cc();
                            } else if (lowerCasePropName.indexOf('expiry') > -1) {
                                return chance.exp();
                            } else if (lowerCasePropName.indexOf('currency') > -1) {
                                return chance.currency().code;
                            } else if (lowerCasePropName.indexOf('color') > -1 || lowerCasePropName.indexOf('colour') > -1) {
                                return chance.color({format: 'hex'});
                            } else if (lowerCasePropName.indexOf('domain') > -1) {
                                return chance.domain();
                            } else if (lowerCasePropName.indexOf('email') > -1 || lowerCasePropName.indexOf('e-mail') > -1) {
                                return chance.email();
                            } else if (lowerCasePropName.indexOf('url') > -1) {
                                return chance.url();
                            } else if (lowerCasePropName.indexOf('twitter') > -1) {
                                return chance.twitter();
                            } else if (lowerCasePropName.indexOf('birth') > -1 || lowerCasePropName.indexOf('bday') > -1) {
                                return chance.birthday({string: true});
                            } else if (lowerCasePropName.indexOf('age') > -1) {
                                return chance.age();
                            } else if (lowerCasePropName.indexOf('first') > -1 || lowerCasePropName.indexOf('fname') > -1 || lowerCasePropName.indexOf('user') > -1) {
                                return chance.first();
                            } else if (lowerCasePropName.indexOf('last') > -1 || lowerCasePropName.indexOf('lname') > -1) {
                                return chance.last();
                            } else if (lowerCasePropName.indexOf('gender') > -1 || lowerCasePropName.indexOf('sex') > -1) {
                                return chance.gender();
                            } else if (lowerCasePropName.indexOf('prefix') > -1) {
                                return chance.prefix();
                            } else if (lowerCasePropName.indexOf('ssn') > -1 || lowerCasePropName.indexOf('social') > -1) {
                                return chance.ssn();
                            } else if (lowerCasePropName.indexOf('token') > -1) {
                                return chance.apple_token();
                            } else if (lowerCasePropName.indexOf('number') > -1) {
                                return chance.natural();
                            } else if (lowerCasePropName.indexOf('temp') > -1) {
                                return change.natural({min: 0, max: 100}) + ' C';
                            } else if (lowerCasePropName.indexOf('pressure') > -1) {
                                return change.natural({min: 500, max: 1500}) + ' hPa';
                            } else if (lowerCasePropName.indexOf('humidity') > -1) {
                                return change.natural({min: 0, max: 100}) + ' %';
                            } else if (lowerCasePropName.indexOf('year') > -1) {
                                return chance.year({min: 1900, max: 2100});
                            } else if (lowerCasePropName.indexOf('month') > -1) {
                                return chance.month();
                            } else if (lowerCasePropName.indexOf('date') > -1) {
                                return chance.date({string: true});
                            } else if (lowerCasePropName.indexOf('timestamp') > -1) {
                                return chance.timestamp();
                            } else if (lowerCasePropName.indexOf('time') > -1) {
                                return chance.hour({twentyfour: true}) + ':' + chance.minute();
                            } else if (lowerCasePropName.indexOf('hour') > -1) {
                                return chance.hour({twentyfour: true});
                            } else if (lowerCasePropName.indexOf('minute') > -1) {
                                return chance.minute();
                            } else if (lowerCasePropName.indexOf('sunrise') > -1) {
                                return change.natural({min: 5, max: 10}) + ':' + chance.minute();
                            } else if (lowerCasePropName.indexOf('sunset') > -1) {
                                return change.natural({min: 17, max: 22}) + ':' + chance.minute();
                            } else if (lowerCasePropName.indexOf('string') > -1) {
                                return chance.string();
                            } else if (lowerCasePropName.indexOf('desc') > -1) {
                                return chance.paragraph({sentences: 1});
                            } else if (lowerCasePropName.indexOf('message') > -1) {
                                return chance.sentence();
                            } else if (lowerCasePropName.indexOf('name') > -1) {
                                return chance.name();
                            } else if (lowerCasePropName.indexOf('ip') > -1) {
                                return chance.ip();
                            } else if (lowerCasePropName.indexOf('id') > -1) {
                                return chance.natural();
                            } else {
                                var stringMax = 8;
                                var stringMin = 4;
                                if (property.maxLength) {
                                    stringMax = property.maxLength;
                                }
                                if (property.minLength) {
                                    stringMin = property.minLength;
                                }
                                var length = chance.natural({min: stringMin, max: stringMax});
                                return chance.word({length: length});
                            }
                        } else {
                            var mystringMax = 8;
                            var mystringMin = 4;
                            if (property.maxLength) {
                                mystringMax = property.maxLength;
                            }
                            if (property.minLength) {
                                mystringMin = property.minLength;
                            }
                            var mylength = chance.natural({min: mystringMin, max: mystringMax});
                            return chance.word({length: mylength});
                        }
                    }
                }
            }
        }

        /**
         * Generates an empty object for the specified schema
         * @param schema
         * @param empty
         *
         * @returns {*}
         */
        function createExampleObject(schema, empty, depth) {
            if (empty == null) {
                empty = false;
            }
            if (depth == null) {
                depth = 0;
            }
            depth++;
            var result = {};
            // handle loose schema'd objects which are missing their 'type'
            if (!schema.type && schema.properties) {
                schema.type = 'object';
            }

            // depth checking to ensure we don't loop infinitely - only descend x levels into a model
            if (depth >= 9) {
                return 'ERROR_MAXDEPTH';
            }

            if (schema.type === 'object') {
                // If schema has no properties (loose schema), return the empty object
                if (!schema.properties) {
                    if (schema.allOf) {
                        // loop
                        Object.keys(schema.allOf).forEach(function (key) {
                            if (schema.allOf[key].allOf) {
                                // loop again
                                Object.keys(schema.allOf[key].allOf).forEach(function (key2) {
                                    if (schema.allOf[key].allOf[key2].allOf) {

                                    } else if (schema.allOf[key].allOf[key2].type === 'object') {
                                        if (schema.allOf[key].allOf[key2].properties) {
                                            Object.keys(schema.allOf[key].allOf[key2].properties).forEach(function (propertyName) {
                                                // if this property is an object itself, recurse
                                                if (schema.allOf[key].allOf[key2].properties[propertyName].type === 'object' || schema.allOf[key].allOf[key2].properties[propertyName].type === 'array' || (!schema.allOf[key].allOf[key2].properties[propertyName].type && schema.allOf[key].allOf[key2].properties[propertyName].properties)) {
                                                    // handle loose schema'd objects which are missing their 'type'
                                                    if (!schema.allOf[key].allOf[key2].properties[propertyName].type) {
                                                        schema.allOf[key].allOf[key2].properties[propertyName].type = 'object';
                                                    }
                                                    result[propertyName] =
                                                        createExampleObject(schema.allOf[key].allOf[key2].properties[propertyName], empty, depth);
                                                    // otherwise use the defaultValues hash
                                                } else {
                                                    if (typeof(schema.allOf[key].allOf[key2].properties[propertyName].default) !== 'undefined') {
                                                        result[propertyName] = schema.allOf[key].allOf[key2].properties[propertyName].default;
                                                    } else if (typeof(schema.allOf[key].allOf[key2].properties[propertyName].example) !== 'undefined') {
                                                        result[propertyName] = schema.allOf[key].allOf[key2].properties[propertyName].example;
                                                    } else {
                                                        result[propertyName] = createDummyValue(propertyName, schema.allOf[key].allOf[key2].properties[propertyName], empty);
                                                    }
                                                }
                                            });
                                        }
                                    }
                                });
                            } else if (schema.allOf[key].type === 'object') {
                                if (schema.allOf[key].properties) {
                                    Object.keys(schema.allOf[key].properties).forEach(function (propertyName) {
                                        // if this property is an object itself, recurse
                                        if (schema.allOf[key].properties[propertyName].type === 'object' || schema.allOf[key].properties[propertyName].type === 'array' || (!schema.allOf[key].properties[propertyName].type && schema.allOf[key].properties[propertyName].properties)) {
                                            // handle loose schema'd objects which are missing their 'type'
                                            if (!schema.allOf[key].properties[propertyName].type) {
                                                schema.allOf[key].properties[propertyName].type = 'object';
                                            }
                                            result[propertyName] =
                                                createExampleObject(schema.allOf[key].properties[propertyName], empty, depth);
                                            // otherwise use the defaultValues hash
                                        } else {
                                            if (typeof(schema.allOf[key].properties[propertyName].default) !== 'undefined') {
                                                result[propertyName] = schema.allOf[key].properties[propertyName].default;
                                            } else if (typeof(schema.allOf[key].properties[propertyName].example) !== 'undefined') {
                                                result[propertyName] = schema.allOf[key].properties[propertyName].example;
                                            } else {
                                                result[propertyName] = createDummyValue(propertyName, schema.allOf[key].properties[propertyName], empty);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    } else if (typeof(schema.default) !== 'undefined') {
                        result = schema.default;
                    } else if (typeof(schema.example) !== 'undefined') {
                        result = schema.example;
                    } else {
                        result = createDummyValue("", schema, empty);
                    }
                } else {
                    Object.keys(schema.properties).forEach(function (propertyName) {
                        // if this property is an object itself, recurse
                        if (schema.properties[propertyName].type === 'object' || schema.properties[propertyName].type === 'array' || (!schema.properties[propertyName].type && schema.properties[propertyName].properties)) {
                            // handle loose schema'd objects which are missing their 'type'
                            if (!schema.properties[propertyName].type) {
                                schema.properties[propertyName].type = 'object';
                            }
                            result[propertyName] =
                                createExampleObject(schema.properties[propertyName], empty, depth);
                            // otherwise use the dummy values
                        } else {
                            if (typeof(schema.properties[propertyName].default) !== 'undefined') {
                                result[propertyName] = schema.properties[propertyName].default;
                            } else if (typeof(schema.properties[propertyName].example) !== 'undefined') {
                                result[propertyName] = schema.properties[propertyName].example;
                            } else {
                                result[propertyName] = createDummyValue(propertyName, schema.properties[propertyName], empty);
                            }
                        }
                    });
                }
                return result;
            } else if (schema.type === 'array') {
                var list = [];
                if (!schema.items) {
                    return list;
                }
                var obj = schema.items;
                // If schema has no properties (loose schema), return the empty object
                if (!obj.properties) {
                    if (obj.allOf) {
                        // loop
                        Object.keys(obj.allOf).forEach(function (key) {
                            if (obj.allOf[key].allOf) {
                                Object.keys(obj.allOf[key].allOf).forEach(function (key2) {
                                    // loop again
                                    Object.keys(obj.allOf[key].allOf).forEach(function (key2) {
                                        if (obj.allOf[key].allOf[key2].allOf) {

                                        } else if (obj.allOf[key].allOf[key2].type === 'object') {
                                            if (obj.allOf[key].allOf[key2].properties) {
                                                Object.keys(obj.allOf[key].allOf[key2].properties).forEach(function (propertyName) {
                                                    // if this property is an object itself, recurse
                                                    if (obj.allOf[key].allOf[key2].properties[propertyName].type === 'object' || obj.allOf[key].allOf[key2].properties[propertyName].type === 'array' || (!obj.allOf[key].allOf[key2].properties[propertyName].type && obj.allOf[key].allOf[key2].properties[propertyName].properties)) {
                                                        // handle loose schema'd objects which are missing their 'type'
                                                        if (!obj.allOf[key].allOf[key2].properties[propertyName].type) {
                                                            obj.allOf[key].allOf[key2].properties[propertyName].type = 'object';
                                                        }
                                                        result[propertyName] =
                                                            createExampleObject(obj.allOf[key].allOf[key2].properties[propertyName], empty, depth);
                                                        // otherwise use the defaultValues hash
                                                    } else {
                                                        if (typeof(obj.allOf[key].allOf[key2].properties[propertyName].default) !== 'undefined') {
                                                            result[propertyName] = obj.allOf[key].allOf[key2].properties[propertyName].default;
                                                        } else if (typeof(obj.allOf[key].allOf[key2].properties[propertyName].example) !== 'undefined') {
                                                            result[propertyName] = obj.allOf[key].allOf[key2].properties[propertyName].example;
                                                        } else {
                                                            result[propertyName] = createDummyValue(propertyName, obj.allOf[key].allOf[key2].properties[propertyName], empty);
                                                        }
                                                    }
                                                });
                                            }
                                        }
                                    });
                                });
                            } else if (obj.allOf[key].type === 'object') {
                                if (obj.allOf[key].properties) {
                                    Object.keys(obj.allOf[key].properties).forEach(function (propertyName) {
                                        // if this property is an object itself, recurse
                                        if (obj.allOf[key].properties[propertyName].type === 'object' || obj.allOf[key].properties[propertyName].type === 'array' || (!obj.allOf[key].properties[propertyName].type && obj.allOf[key].properties[propertyName].properties)) {
                                            // handle loose schema'd objects which are missing their 'type'
                                            if (!obj.allOf[key].properties[propertyName].type) {
                                                obj.allOf[key].properties[propertyName].type = 'object';
                                            }
                                            result[propertyName] =
                                                createExampleObject(obj.allOf[key].properties[propertyName], empty, depth);
                                            // otherwise use the defaultValues hash
                                        } else {
                                            if (typeof(obj.allOf[key].properties[propertyName].default) !== 'undefined') {
                                                result[propertyName] = obj.allOf[key].properties[propertyName].default;
                                            } else if (typeof(obj.allOf[key].properties[propertyName].example) !== 'undefined') {
                                                result[propertyName] = obj.allOf[key].properties[propertyName].example;
                                            } else {
                                                result[propertyName] = createDummyValue(propertyName, obj.allOf[key].properties[propertyName], empty);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    } else if (typeof(obj.default) !== 'undefined') {
                        result = obj.default;
                    } else if (typeof(obj.example) !== 'undefined') {
                        result = obj.example;
                    } else {
                        result = createDummyValue("", obj, empty);
                    }
                } else {
                    Object.keys(obj.properties).forEach(function (propertyName) {
                        // if this property is an object itself, recurse
                        if (obj.properties[propertyName].type === 'object' || obj.properties[propertyName].type === 'array' || (!obj.properties[propertyName].type && obj.properties[propertyName].properties)) {
                            // handle loose schema'd objects which are missing their 'type'
                            if (!obj.properties[propertyName].type) {
                                obj.properties[propertyName].type = 'object';
                            }
                            result[propertyName] =
                                createExampleObject(obj.properties[propertyName], empty, depth);
                            // otherwise use the defaultValues hash
                        } else {
                            if (typeof(obj.properties[propertyName].default) !== 'undefined') {
                                result[propertyName] = obj.properties[propertyName].default;
                            } else if (typeof(obj.properties[propertyName].example) !== 'undefined') {
                                result[propertyName] = obj.properties[propertyName].example;
                            } else {
                                result[propertyName] = createDummyValue(propertyName, obj.properties[propertyName], empty);
                            }
                        }
                    });
                }
                list.push(result);
                return list;
            } else if (schema.type === 'string' || schema.type === 'integer' || schema.type === 'number' || schema.type === 'boolean') {
                return createDummyValue('', schema, empty)
            }
            else {
                throw new TypeError('schema should be an object or array schema.');
            }
        }

        /**
         * Generate the code snippets
         * @param api
         * @param path
         * @param verb
         */
        function generateCodeSnippets(api, path, verb) {
            var id = "apis_" + cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]) + "_paths_" + cleanUpKey(path) + "_" + verb;
            var operation = api.paths[path][verb];
            var config = window.checkAPISecurity(operation, api);
            var url = "://" + api.host + api.basePath + path;
            if (api.schemes) {
                url = api.schemes[0] + url;
            } else {
                url = "https" + url;
            }
            var headersarray = [];
            var queryarray = [];
            var body = null;

            if (config && config.requiresClientId) {
                if (config.clientIdLocation == 'query') {
                    queryarray.push({'name': 'client_id', 'value': 'REPLACE_THIS_KEY'});
                } else {
                    headersarray.push({'name': 'X-IBM-Client-Id', 'value': 'REPLACE_THIS_KEY'});
                }
            }
            if (config && config.requiresClientSecret) {
                if (config.clientSecretLocation == 'query') {
                    queryarray.push({'name': 'client_secret', 'value': 'REPLACE_THIS_KEY'});
                } else {
                    headersarray.push({'name': 'X-IBM-Client-Secret', 'value': 'REPLACE_THIS_KEY'});
                }
            }
            if (config && config.requiresBasicAuth) {
                headersarray.push({'name': 'Authorization', 'value': 'Basic ' + 'REPLACE_BASIC_AUTH'});
            }
            if (config && config.requiresOauth) {
                headersarray.push({'name': 'Authorization', 'value': 'Bearer ' + 'REPLACE_BEARER_TOKEN'});
            }
            var parameters = [];
            if (operation.parameters) {
                parameters = parameters.concat(operation.parameters);
            }
            if (api.paths[path].parameters) {
                parameters = parameters.concat(api.paths[path].parameters);
            }
            // path parameters
            var pathParameters = parameters.filter(function (parameter) {
                return (parameter.in == "path");
            });
            if (pathParameters.length > 0) {
                pathParameters.forEach(function (parameter) {
                    url = url.replace("{" + parameter.name + "}", 'REPLACE_' + parameter.name.toUpperCase());
                });
            }
            // query parameters
            var queryParameters = parameters.filter(function (parameter) {
                return (parameter.in == "query");
            });
            if (queryParameters.length > 0) {
                queryParameters.forEach(function (parameter) {
                    queryarray.push({'name': parameter.name, 'value': 'REPLACE_THIS_VALUE'});
                });
            }
            // headers
            var contenttype = null;
            var accept = null;
            if (operation.consumes) {
                contenttype = operation.consumes[0];
            } else if (api.consumes) {
                contenttype = api.consumes[0];
            }
            if (operation.produces) {
                accept = operation.produces[0];
            } else if (api.produces) {
                accept = api.produces[0];
            }
            var contentheader = false;
            if (contenttype) {
                headersarray.push({'name': 'content-type', 'value': contenttype});
                contentheader = true;
            }
            if (accept) {
                headersarray.push({'name': 'accept', 'value': accept});
            }

            var headerParameters = parameters.filter(function (parameter) {
                return (parameter.in == "header");
            });
            if (headerParameters.length > 0) {
                headerParameters.forEach(function (parameter) {
                    headersarray.push({'name': parameter.name, 'value': 'REPLACE_THIS_VALUE'});
                    if (parameter.name.toLowerCase() == 'content-type') {
                        contentheader = true;
                    }
                });
            }

            // for POST and PUT we should always set content-type to *something*
            if (verb.toUpperCase() == 'POST' || verb.toUpperCase() == 'PUT') {
                if (contentheader != true) {
                    headersarray.push({'name': 'content-type', 'value': 'application/json'});
                }
            }

            // body
            var bodyParameters = parameters.filter(function (parameter) {
                return (parameter.in == "body");
            });
            if (bodyParameters.length > 0) {
                // use the first one only
                // should be inline schema by now
                var obj = bodyParameters[0].schema;
                try {
                    var example = createExampleObject(obj, false);
                    var retXML = acceptsXML(api, operation);
                    if (example) {
                        if (retXML) {
                            body = window.vkbeautify.xml(window.json2xml(example, {header: true}));
                            body = _.unescape(body);
                        } else {
                            body = JSON.stringify(example);
                        }
                    }
                    var exampleobj = null;
                    if (obj.example) {
                        exampleobj = obj.example;
                    } else {
                        exampleobj = createExampleObject(obj, true);
                    }
                    // SOAP APIs should have example objects already in XML format
                    if (!api['x-ibm-configuration']['type'] || api['x-ibm-configuration']['type'] != 'wsdl') {
                        if (retXML) {
                            exampleobj = window.vkbeautify.xml(window.json2xml(exampleobj, {header: true}));
                        } else {
                            exampleobj = JSON.stringify(exampleobj, null, 2);
                        }
                    }
                    if (exampleobj && retXML) {
                        exampleobj = _.unescape(exampleobj);
                    }
                    $("#body_" + id + " textarea").val(exampleobj).height(150);
                } catch (e) {

                }
            }

            // create the HAR request configuration
            var confHAR = {
                "method": verb.toUpperCase(),
                "url": url,
                "headers": headersarray,
                "queryString": queryarray
            };

            if (body) {
                confHAR["postData"] = {
                    "mimeType": contenttype,
                    "text": body
                };
            }

            // get a new instance of httpSnippet
            var mySnippet = new window.HTTPSnippetInstance(confHAR);

            $("#langtab-" + id + "-curl pre code").text(mySnippet.convert('shell', 'curl'));
            $("#langtab-" + id + "-ruby pre code").text(mySnippet.convert('ruby'));
            $("#langtab-" + id + "-python pre code").text(mySnippet.convert('python'));
            $("#langtab-" + id + "-php pre code").text(mySnippet.convert('php'));
            $("#langtab-" + id + "-java pre code").text(mySnippet.convert('java', 'okhttp'));
            $("#langtab-" + id + "-node pre code").text(mySnippet.convert('node'));
            $("#langtab-" + id + "-go pre code").text(mySnippet.convert('go'));
            $("#langtab-" + id + "-swift pre code").text(mySnippet.convert('swift'));
        }

        /**
         * Generate the examples
         * @param api
         * @param path
         * @param verb
         */
        function generateExamples(api, path, verb) {
            var exampleid = "exampleresponse_apis_" + cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]) + "_paths_" + cleanUpKey(path) + "_" + verb;
            var operation = api.paths[path][verb];
            var response = null;

            function hideTab(api, path, verb) {
                var cleanedapiname = cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]);
                var container = $(".navigate-apis_" + cleanedapiname + "_paths");
                // hide example section since nothing worthwhile putting in it
                var tabid = "tab-content_example_" + cleanedapiname + "_paths_" + cleanUpKey(path) + "_" + verb;
                $("#" + tabid, container).hide();
            }

            if (operation.responses["200"]) {
                response = operation.responses["200"];
            } else if (operation.responses["201"]) {
                response = operation.responses["201"];
            } else if (operation.responses["default"]) {
                response = operation.responses["default"];
            }
            if (response && response.schema) {
                try {
                    var exampleobj = null;
                    if (response.schema.example) {
                        exampleobj = response.schema.example;
                    } else {
                        exampleobj = createExampleObject(response.schema, false);
                    }
                    if (exampleobj) {
                        var retXML = returnsXML(api, operation);
                        if (retXML) {
                            exampleobj = window.vkbeautify.xml(window.json2xml(exampleobj, {header: true}));
                            exampleobj = _.unescape(exampleobj);
                        } else {
                            exampleobj = JSON.stringify(exampleobj, null, 2);
                        }
                        $("#" + exampleid + " pre code").text(exampleobj);
                    } else {
                        hideTab(api, path, verb);
                    }
                } catch (e) {
                    hideTab(api, path, verb);
                }
            } else {
                // hide example tab since nothing worthwhile putting in it
                hideTab(api, path, verb);
            }
        }

        function generateCodeSnippetsTimeout(expanded, path, verb) {
            setTimeout(function () {
                generateCodeSnippets(expanded, path, verb);
            }, 0);
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
                                generateCodeSnippetsTimeout(expanded, path, verb);
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
            });
            $('.markdown pre').each(function (i, block) {
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
                $('.' + tab).addClass("show");
                $('.' + tab).fadeIn();
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
                                api);
                        }
                    });
                });
            });
        }

        createTesters();

        $('.definitionsSection pre code').each(function (i, block) {
            hljs.highlightBlock(block);
        });
    }

    API.prototype.test = function (apiname, verb, path) {
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

    API.prototype.refreshToken = function (apiname, verb, path) {
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

    window.API = API;

    $(document).ready(function () {
        window.API = new API(window.apiJson, window.expandedapiJson);
    });

    function checkAPISecurity(operation, api) {
        var config = {};
        // check security requirements
        config.requiresClientId = false;
        config.clientIdLocation = "header";
        config.requiresClientSecret = false;
        config.clientSecretLocation = "header";
        config.requiresBasicAuth = false;
        config.requiresOauth = false;
        // any SOAP specific content?
        if (operation['definition'] && operation['definition']['x-ibm-soap']) {
            if (operation['definition']['x-ibm-soap']['soap-action'] !== undefined) {
                config.soapAction = operation['definition']['x-ibm-soap']['soap-action'];
            }
        } else if (operation['x-ibm-soap'] && operation['x-ibm-soap'] !== undefined) {
            config.soapAction = operation['x-ibm-soap']['soap-action'];
        }
        var security = null;
        if (operation.definition) {
            security = operation.definition.security;
        }
        if (!security) security = operation.security;
        if (!security) security = api.security;
        if (security) {
            security.forEach(function (securityDefs) {
                Object.keys(securityDefs).forEach(function (securityDef) {
                    var thisDef = api.securityDefinitions[securityDef];
                    if (thisDef.type == "apiKey" && (thisDef.name == "client_id" || thisDef.name == "X-IBM-Client-Id")) {
                        config.requiresClientId = true;
                        config.clientIdLocation = thisDef.in;
                    }
                    if (thisDef.type == "apiKey" && (thisDef.name == "client_secret" || thisDef.name == "X-IBM-Client-Secret")) {
                        config.requiresClientSecret = true;
                        config.clientSecretLocation = thisDef.in;
                    }
                    if (thisDef.type == "basic") {
                        config.requiresBasicAuth = true;
                    }
                    if (thisDef.type == "oauth2") {
                        config.requiresOauth = true;
                        config.oauthFlow = thisDef.flow;
                        config.requiresClientId = true;
                        if (config.oauthFlow == 'application' || config.oauthFlow == 'password') {
                            config.requiresClientSecret = true;
                        }
                        if (thisDef.authorizationUrl) {
                            if (thisDef.authorizationUrl.indexOf("http") == 0) {
                                config.oauthAuthUrl = thisDef.authorizationUrl;
                            } else {
                                if (!api.basePath) {
                                    api.basePath = '';
                                }
                                config.oauthAuthUrl = "https://" + api.host + api.basePath + thisDef.authorizationUrl;
                            }

                        }
                        if (thisDef.tokenUrl) {
                            if (thisDef.tokenUrl.indexOf("http") == 0) {
                                config.oauthTokenUrl = thisDef.tokenUrl;
                            } else {
                                if (!api.basePath) {
                                    api.basePath = '';
                                }
                                config.oauthTokenUrl = "https://" + api.host + api.basePath + thisDef.tokenUrl;
                            }

                        }
                        if (thisDef.scopes && !Object.keys(thisDef.scopes).length == 0) {
                            config.oauthScopes = thisDef.scopes;
                            config.oauthScopesString = Object.keys(thisDef.scopes).join(" ");
                        }
                    }
                });
            });
        }
        return config;
    }

    window.checkAPISecurity = checkAPISecurity;

    function Test(operation, api) {

        var self = this;

        self.operation = operation;
        self.api = api;

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

            // Removing multiple consecutive hyphens.
            input = input.replace(/\-+/gi, '-', input);

            return input;
        }

        var id = "content-apis_" + cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]) + "_paths_" + cleanUpKey(self.operation.path) + "_" + self.operation.verb;
        self.responseSection = $("." + id + " #response_" + cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]) + '_' + cleanUpKey(self.operation.path) + "_" + self.operation.verb);
        self.requestForm = $("." + id + " form[name='request_" + cleanUpClassName(api.info["x-ibm-name"] + api.info["version"]) + "_" + cleanUpKey(self.operation.path) + "_" + self.operation.verb + "']");

        // check security requirements
        self.config = window.checkAPISecurity(operation, api);

        // any security requirements at all?
        if (self.config.requiresClientId || self.config.requiresClientSecret || self.config.requiresBasicAuth || self.config.requiresOauth) {
            $(".securitySection", self.requestForm).removeClass("hidden");
        }
        // any requirement for identification?
        if (self.config.requiresClientId || self.config.requiresClientSecret) {
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
        // any requirement for authorization?
        if (self.config.requiresBasicAuth || self.config.requiresOauth) {
            $(".authorizationSection", self.requestForm).removeClass("hidden");
        }
        // any requirement for an authorization call-out?
        if (self.config.requiresOauth) {
            $(".authorizationSection .apimAuthorize", self.requestForm).removeClass("hidden");
            $(".authorizationSection .apimAuthUrl", self.requestForm).removeClass("hidden");
            if (self.config.oauthScopes) {
                $.each(self.config.oauthScopes, function (key, value) {
                    var scopediv = document.createElement('div');
                    scopediv.classList.add('scope');
                    var scopenamediv = document.createElement('div');
                    scopenamediv.classList.add('scopename');
                    var scopenamedivtext = document.createTextNode(key);
                    scopenamediv.appendChild(scopenamedivtext);
                    scopediv.appendChild(scopenamediv);
                    var scopevaluediv = document.createElement('div');
                    scopevaluediv.classList.add('scopevalue');
                    var scopevaluedivtext = document.createTextNode(value);
                    scopevaluediv.appendChild(scopevaluedivtext);
                    scopediv.appendChild(scopevaluediv);
                    $(".authorizationSection .apimAuthUrl .oauthscopes", self.requestForm).append(scopediv);
                });
            }
            // any requirement for token refresh?
            if (self.config.oauthFlow == "accessCode") {
                $(".authorizationSection .apimAuthUrl .authurl", self.requestForm).text(self.config.oauthAuthUrl);
                $(".authorizationSection .apimAuthUrl .accessCodeFlow", self.requestForm).removeClass("hidden");
                $(".authorizationSection .apimAuthUrl .tokenurl", self.requestForm).text(self.config.oauthTokenUrl);
                $(".authorizationSection .apimAuthUrl .threelegged", self.requestForm).removeClass("hidden");
                $(".authorizationSection .apimAuthUrl .tokenBasedFlow", self.requestForm).removeClass("hidden");
            }
            if (self.config.oauthFlow == "implicit") {
                $(".authorizationSection .apimAuthUrl .authurl", self.requestForm).text(self.config.oauthAuthUrl);
                $(".authorizationSection .apimAuthUrl .implicitFlow", self.requestForm).removeClass("hidden");
                $(".authorizationSection .apimAuthUrl .threelegged", self.requestForm).removeClass("hidden");
            }
            if (self.config.oauthFlow == "application") {
                $(".authorizationSection .apimAuthUrl .applicationFlow", self.requestForm).removeClass("hidden");
                $(".authorizationSection .apimAuthUrl .twolegged", self.requestForm).removeClass("hidden");
                $(".authorizationSection .apimAuthUrl .tokenBasedFlow", self.requestForm).removeClass("hidden");
                $(".authorizationSection .apimAuthUrl .tokenurl", self.requestForm).text(self.config.oauthTokenUrl);
            }
            if (self.config.oauthFlow == "password") {
                $(".authorizationSection .apimAuthUrl .passwordFlow", self.requestForm).removeClass("hidden");
                $(".authorizationSection .apimAuthUrl .twolegged", self.requestForm).removeClass("hidden");
                $(".authorizationSection .apimAuthUrl .tokenBasedFlow", self.requestForm).removeClass("hidden");
                $(".authorizationSection .apimAuthUrl .tokenurl", self.requestForm).text(self.config.oauthTokenUrl);
            }
        }

        // any requirement for user credentials?
        if (self.config.requiresBasicAuth || (self.config.oauthFlow == "password")) {
            $(".authorizationSection .userCredentials", self.requestForm).removeClass("hidden");
        }
    }

    Test.prototype.authorize = function () {
        var requestForm = {};
        this.requestForm.serializeArray().forEach(function (parameter) {
            requestForm[parameter.name] = parameter.value;
        });
        var headers = {};

        var self = this;

        function clearResponse() {
            $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val('');
        }

        function errorHandler(xhrObj, textStatus, error) {

        }

        function successHandler(data, status, xhrObj) {
            if (xhrObj.responseText.access_token) {
                $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val(xhrObj.responseText.access_token);
            } else {
                $(".authorizationSection .apimAuthUrl .accesstoken .result", self.requestForm).val(xhrObj.responseText);
            }
        }

        var authurl = this.config.oauthTokenUrl;
        if (this.config.oauthFlow == "application") {
            authurl = +'?grant_type=client_credentials&scope=' + this.config.oauthScopes[0];
        } else if (this.config.oauthFlow == "password") {
            authurl = +'?grant_type=password&scope=' + this.config.oauthScopes[0];
            if (requestForm.apimUsername && requestForm.apimPassword) {
                authurl = +'&username=' + requestForm.apimUsername + "&password=" + requestForm.apimPassword;
            }
        }
        if (requestForm.apimClientId && requestForm.apimClientSecret) {
            headers['Authorization'] = "Basic " + btoa(requestForm.apimClientId + ":" + requestForm.apimClientSecret);
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

        clearResponse();

        this.oauthXhrOpts = oauthXhrOpts;

        $.ajax(authurl, oauthXhrOpts);
    };

    Test.prototype.refreshToken = function () {

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
        var targetUrl = scheme + "://" + this.api.host + this.api.basePath + this.operation.path;

        // set up headers
        var headers = {};
        if (this.config.requiresBasicAuth) {
            headers['Authorization'] = "Basic " + btoa(requestForm.apimUsername + ":" + requestForm.apimPassword);
        }
        if (this.config.requiresOauth && requestForm.authToken) {
            headers["Authorization"] = "Bearer " + requestForm.authToken;
        }
        if (this.config.requiresClientId && this.config.clientIdLocation == "header") {
            headers["X-IBM-Client-Id"] = requestForm.apimClientId;
        }
        if (this.config.requiresClientSecret && this.config.clientSecretLocation == "header") {
            headers["X-IBM-Client-Secret"] = requestForm.apimClientSecret;
        }

        if (this.config.soapAction !== undefined) {
            headers["SOAPAction"] = this.config.soapAction;
        }
        // is this SOAP? if so, ditch the operationId from the end...
        if (this.config.soapAction !== undefined && this.operation.operationId && targetUrl.endsWith("/" + this.operation.operationId)) {
            targetUrl = targetUrl.substring(0, targetUrl.length - (this.operation.operationId.length + 1));
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
                targetUrl = targetUrl.replace("{" + parameter.name + "}", requestForm[parameter.name]);
            });
        }
        // query parameters
        var queryParameters = parameters.filter(function (parameter) {
            return (parameter.in == "query" && requestForm[parameter.name] !== undefined);
        });
        var queryParametersAdded = (targetUrl.indexOf('?') >= 0);
        if (queryParameters.length > 0) {
            targetUrl += (queryParametersAdded) ? "&" : "?";
            queryParameters.forEach(function (parameter) {
                if (parameter.name && requestForm[parameter.name]) {
                    targetUrl += parameter.name + "=" + requestForm[parameter.name] + "&";
                    queryParametersAdded = true;
                }
            });
            targetUrl = targetUrl.substring(0, targetUrl.length - 1);
        }
        if (this.config.requiresClientId && this.config.clientIdLocation == "query") {
            if (queryParametersAdded) {
                targetUrl += "&client_id=" + requestForm.apimClientId;
            } else {
                targetUrl += "?client_id=" + requestForm.apimClientId;
                queryParametersAdded = true;
            }
        }
        if (this.config.requiresClientSecret && this.config.clientSecretLocation == "query") {
            if (queryParametersAdded) {
                targetUrl += "&client_secret=" + requestForm.apimClientSecret;
            } else {
                targetUrl += "?client_secret=" + requestForm.apimClientSecret;
                queryParametersAdded = true;
            }
        }

        // headers
        headers['content-type'] = requestForm['content-type'];
        headers['accept'] = requestForm['accept'];
        var headerParameters = parameters.filter(function (parameter) {
            return (parameter.in == "header");
        });
        if (headerParameters.length > 0) {
            headerParameters.forEach(function (parameter) {
                if (requestForm[parameter.name]) {
                    headers[parameter.name] = requestForm[parameter.name];
                }
            });
        }

        // body
        var body;
        var bodyParameters = parameters.filter(function (parameter) {
            return (parameter.in == "body");
        });
        if (bodyParameters.length > 0) {
            // use the first one only
            body = requestForm[bodyParameters[0].name];
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
                    if (headerName == 'X-IBM-Client-Secret') {
                        requestHeaders += headerName + ": ∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙∙\n";
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
                        var val = headerPair.substring(index + 2);
                        headers[key] = val;
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
