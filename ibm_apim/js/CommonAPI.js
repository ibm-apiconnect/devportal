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

    function CommonAPI(api) {

        var self = this;

        self.api = api;

    }

    /**
     * Does this operation only return XML and not JSON?
     * @param api
     * @param operation
     * @returns {boolean}
     */
    CommonAPI.prototype.returnsXML = function (api, operation) {
        if (operation.produces && ($.inArray('application/xml', operation.produces) !== -1 || $.inArray('application/xml+soap', operation.produces) !== -1 || $.inArray('text/xml', operation.produces) !== -1) && $.inArray('application/json', operation.produces) === -1) {
            return true;
        } else if (!operation.produces && api.produces && ($.inArray('application/xml', api.produces) !== -1 || $.inArray('application/xml+soap', api.produces) !== -1 || $.inArray('text/xml', api.produces) !== -1) && $.inArray('application/json', api.produces) === -1) {
            return true;
        } else {
            return false;
        }
    };
    /**
     * Does this operation only accept XML and not JSON?
     * @param api
     * @param operation
     * @returns {boolean}
     */
    CommonAPI.prototype.acceptsXML = function (api, operation) {
        if (operation.consumes && ($.inArray('application/xml', operation.consumes) !== -1 || $.inArray('application/xml+soap', operation.consumes) !== -1 || $.inArray('text/xml', operation.consumes) !== -1) && $.inArray('application/json', operation.consumes) === -1) {
            return true;
        } else if (!operation.consumes && api.consumes && ($.inArray('application/xml', api.consumes) !== -1 || $.inArray('application/xml+soap', api.consumes) !== -1 || $.inArray('text/xml', api.consumes) !== -1) && $.inArray('application/json', api.consumes) === -1) {
            return true;
        } else {
            return false;
        }
    };

    /**
     * Create sample data for given property
     * @param propertyName
     * @param property
     * @param empty
     * @returns {*}
     */
    CommonAPI.prototype.createDummyValue = function (propertyName, property, empty) {
        var defaultValues = {
            string: '',
            object: {},
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
            } else if (property.type == 'object') {
                return {'id': chance.natural()};
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
                            return chance.latitude().toString();
                        } else if (lowerCasePropName.indexOf('longitude') > -1) {
                            return chance.longitude().toString();
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
                            return chance.age().toString();
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
                            return chance.natural().toString();
                        } else if (lowerCasePropName.indexOf('temp') > -1) {
                            return change.natural({min: 0, max: 100}) + ' C';
                        } else if (lowerCasePropName.indexOf('pressure') > -1) {
                            return change.natural({min: 500, max: 1500}) + ' hPa';
                        } else if (lowerCasePropName.indexOf('humidity') > -1) {
                            return change.natural({min: 0, max: 100}) + ' %';
                        } else if (lowerCasePropName.indexOf('year') > -1) {
                            return chance.year({min: 1900, max: 2100}).toString();
                        } else if (lowerCasePropName.indexOf('month') > -1) {
                            return chance.month();
                        } else if (lowerCasePropName.indexOf('date') > -1) {
                            return chance.date({string: true});
                        } else if (lowerCasePropName.indexOf('timestamp') > -1) {
                            return chance.timestamp().toString();
                        } else if (lowerCasePropName.indexOf('time') > -1) {
                            return chance.hour({twentyfour: true}) + ':' + chance.minute();
                        } else if (lowerCasePropName.indexOf('hour') > -1) {
                            return chance.hour({twentyfour: true}).toString();
                        } else if (lowerCasePropName.indexOf('minute') > -1) {
                            return chance.minute().toString();
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
                            return chance.natural().toString();
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
    };

    /**
     * Generates an empty object for the specified schema
     * @param schema
     * @param empty
     * @param depth
     *
     * @returns {*}
     */
    CommonAPI.prototype.createExampleObject = function (schema, empty, depth) {
        if (empty == null) {
            empty = false;
        }
        if (depth == null) {
            depth = 0;
        }
        depth++;
        var result = {};
        // handle loose schema'd objects which are missing their 'type'
        if (!schema.type && (schema.properties || schema.allOf)) {
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
                                                    CommonAPI.prototype.createExampleObject(schema.allOf[key].allOf[key2].properties[propertyName], empty, depth);
                                                // otherwise use the defaultValues hash
                                            } else {
                                                if (typeof(schema.allOf[key].allOf[key2].properties[propertyName].default) !== 'undefined') {
                                                    result[propertyName] = schema.allOf[key].allOf[key2].properties[propertyName].default;
                                                } else if (typeof(schema.allOf[key].allOf[key2].properties[propertyName].example) !== 'undefined') {
                                                    result[propertyName] = schema.allOf[key].allOf[key2].properties[propertyName].example;
                                                } else {
                                                    result[propertyName] = CommonAPI.prototype.createDummyValue(propertyName, schema.allOf[key].allOf[key2].properties[propertyName], empty);
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
                                            CommonAPI.prototype.createExampleObject(schema.allOf[key].properties[propertyName], empty, depth);
                                        // otherwise use the defaultValues hash
                                    } else {
                                        if (typeof(schema.allOf[key].properties[propertyName].default) !== 'undefined') {
                                            result[propertyName] = schema.allOf[key].properties[propertyName].default;
                                        } else if (typeof(schema.allOf[key].properties[propertyName].example) !== 'undefined') {
                                            result[propertyName] = schema.allOf[key].properties[propertyName].example;
                                        } else {
                                            result[propertyName] = CommonAPI.prototype.createDummyValue(propertyName, schema.allOf[key].properties[propertyName], empty);
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
                    result = CommonAPI.prototype.createDummyValue("", schema, empty);
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
                            CommonAPI.prototype.createExampleObject(schema.properties[propertyName], empty, depth);
                        // otherwise use the dummy values
                    } else {
                        if (typeof(schema.properties[propertyName].default) !== 'undefined') {
                            result[propertyName] = schema.properties[propertyName].default;
                        } else if (typeof(schema.properties[propertyName].example) !== 'undefined') {
                            result[propertyName] = schema.properties[propertyName].example;
                        } else {
                            result[propertyName] = CommonAPI.prototype.createDummyValue(propertyName, schema.properties[propertyName], empty);
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
                                                        CommonAPI.prototype.createExampleObject(obj.allOf[key].allOf[key2].properties[propertyName], empty, depth);
                                                    // otherwise use the defaultValues hash
                                                } else {
                                                    if (typeof(obj.allOf[key].allOf[key2].properties[propertyName].default) !== 'undefined') {
                                                        result[propertyName] = obj.allOf[key].allOf[key2].properties[propertyName].default;
                                                    } else if (typeof(obj.allOf[key].allOf[key2].properties[propertyName].example) !== 'undefined') {
                                                        result[propertyName] = obj.allOf[key].allOf[key2].properties[propertyName].example;
                                                    } else {
                                                        result[propertyName] = CommonAPI.prototype.createDummyValue(propertyName, obj.allOf[key].allOf[key2].properties[propertyName], empty);
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
                                            CommonAPI.prototype.createExampleObject(obj.allOf[key].properties[propertyName], empty, depth);
                                        // otherwise use the defaultValues hash
                                    } else {
                                        if (typeof(obj.allOf[key].properties[propertyName].default) !== 'undefined') {
                                            result[propertyName] = obj.allOf[key].properties[propertyName].default;
                                        } else if (typeof(obj.allOf[key].properties[propertyName].example) !== 'undefined') {
                                            result[propertyName] = obj.allOf[key].properties[propertyName].example;
                                        } else {
                                            result[propertyName] = CommonAPI.prototype.createDummyValue(propertyName, obj.allOf[key].properties[propertyName], empty);
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
                    result = CommonAPI.prototype.createDummyValue("", obj, empty);
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
                            CommonAPI.prototype.createExampleObject(obj.properties[propertyName], empty, depth);
                        // otherwise use the defaultValues hash
                    } else {
                        if (typeof(obj.properties[propertyName].default) !== 'undefined') {
                            result[propertyName] = obj.properties[propertyName].default;
                        } else if (typeof(obj.properties[propertyName].example) !== 'undefined') {
                            result[propertyName] = obj.properties[propertyName].example;
                        } else {
                            result[propertyName] = CommonAPI.prototype.createDummyValue(propertyName, obj.properties[propertyName], empty);
                        }
                    }
                });
            }
            list.push(result);
            return list;
        } else if (schema.type === 'string' || schema.type === 'integer' || schema.type === 'number' || schema.type === 'boolean') {
            return CommonAPI.prototype.createDummyValue('', schema, empty)
        }
        else {
            throw new TypeError('schema should be an object or array schema.');
        }
    };

    /**
     * Update security config now picked a flow type
     * @param config
     * @param oauthDef
     * @returns {*}
     */
    CommonAPI.prototype.updateConfigurationForOauth = function (config, oauthDef) {
        if (oauthDef.type !== "oauth2") return config;
        config.requiresOauth = true;
        config.oauthFlow = oauthDef.flow;
        config.requiresClientId = true;
        if (oauthDef.flow == 'application' || oauthDef.flow == 'accessCode') {
            config.requiresClientSecret = true;
        }
        if (oauthDef.authorizationUrl) {
            config.oauthAuthUrl = oauthDef.authorizationUrl;
        }
        if (oauthDef.tokenUrl) {
            config.oauthTokenUrl = oauthDef.tokenUrl;
        }
        if (oauthDef.scopes && Object.keys(oauthDef.scopes).length !== 0) {
            config.oauthScopes = Object.keys(oauthDef.scopes);
        }
        return config;
    };

    /**
     * look through the operation and api and determine the various configuration options required for the operation
     **/
    CommonAPI.prototype.getConfigurationForOperation = function (operation, path, api, securityFlow) {

        var config = {};

        // check security requirements
        config.requiresClientId = false;
        delete config.clientIdLocation;
        delete config.clientIdName;
        config.requiresClientSecret = false;
        delete config.clientSecretLocation;
        delete config.clientSecretName;
        config.requiresBasicAuth = false;
        config.requiresOauth = false;
        config.securityFlows = [];

        config.requiresSecuritySection = false;
        config.requiresIdentificationSection = false;
        config.requiresAuthorizationSection = false;
        config.requiresRefreshToken = false;
        config.requiresUserCredentials = false;
        config.requiresRedirectUri = false;
        config.externalApiKeys = {};

        delete config.oauthFlow;
        delete config.oauthAuthUrl;
        delete config.oauthTokenUrl;
        delete config.oauthScopes;

        var security = {};
        var securityDefinitions = operation.security;
        if (!securityDefinitions) securityDefinitions = api.security;
        if (securityDefinitions) {
            securityDefinitions.forEach(function (securityDefs) {
                var label = Object.keys(securityDefs).filter(function (key) {
                    return (key.indexOf('$$') !== 0);
                }).join(", ");
                securityDefs.$$label = label;
                config.securityFlows.push(securityDefs);

                // if a security flow has been selected, switch on all
                // the necessary security flags for the given flow
                if (securityFlow === securityDefs) {
                    Object.keys(securityDefs).forEach(function (securityDef) {
                        var thisDef = api.securityDefinitions[securityDef];
                        if (!thisDef) return;
                        security[securityDef] = thisDef;
                        if (thisDef.type === "apiKey") {
                            if (thisDef.name === "client_id" ||
                                thisDef.name === "X-IBM-Client-Id" ||
                                (thisDef['x-key-type'] && thisDef['x-key-type'] === "clientId")) {
                                config.requiresClientId = true;
                                config.clientIdLocation = thisDef["in"];
                                config.clientIdName = thisDef["name"];
                            } else if (thisDef.name === "client_secret" ||
                                thisDef.name === "X-IBM-Client-Secret" ||
                                (thisDef['x-key-type'] && thisDef['x-key-type'] === "clientSecret")) {
                                config.requiresClientSecret = true;
                                config.clientSecretLocation = thisDef["in"];
                                config.clientSecretName = thisDef["name"];
                            } else {
                                // it's an external api key
                                config.externalApiKeys[securityDef] = thisDef;
                            }
                        }
                        if (thisDef.type == "basic") {
                            config.requiresBasicAuth = true;
                        }
                        if (thisDef.type == "oauth2") {
                            config.requiresOauth = true;
                            config.requiresClientId = true;
                            config.oauthFlow = thisDef.flow;
                            if (thisDef.flow == 'application' || thisDef.flow == 'accessCode' || thisDef.flow == 'password') {
                                config.requiresClientSecret = true;
                            }
                            if (thisDef.flow == 'implicit' || thisDef.flow == 'accessCode') {
                                config.requiresRedirectUri = true;
                            }
                            if (thisDef.authorizationUrl) {
                                config.oauthAuthUrl = thisDef.authorizationUrl;
                                config.requiresAuthorizationSection = true;
                            }
                            if (thisDef.tokenUrl) {
                                config.oauthTokenUrl = thisDef.tokenUrl;
                                config.requiresAuthorizationSection = true;
                            }
                            if (thisDef.scopes && Object.keys(thisDef.scopes).length !== 0) {
                                config.oauthScopes = Object.keys(thisDef.scopes);
                            }
                        }
                    });
                }
            });
        }
        if (!_.isEmpty(security)) config.security = security;

        // any security requirements at all?
        if (config.requiresClientId || config.requiresClientSecret || config.requiresBasicAuth || config.requiresOauth) {
            config.requiresSecuritySection = true;
        }

        // any requirement for identification?
        if (config.requiresClientId || config.requiresClientSecret) {
            config.requiresIdentificationSection = true;
        }

        // any requirement for authorization?
        if (config.requiresBasicAuth || config.oauthFlow === "password") {
            config.requiresAuthorizationSection = true;
            config.requiresUserCredentials = true;
        }

        // any requirement for token refresh?
        if (config.oauthFlow === "accessCode") {
            config.requiresRefreshToken = true;
        }

        // figure out which parameters apply here
        var parameters = [];
        var dereferencedParameters = {};
        if (operation.parameters) parameters = parameters.concat(operation.parameters);
        if (path.parameters) parameters = parameters.concat(path.parameters);

        parameters.forEach(function (parameter) {
            if (parameter.$ref) {
                var parameterName = parameter.$ref.replace("#/parameters/", "");
                dereferencedParameters[parameterName] = api.parameters[parameterName];
                dereferencedParameters[parameterName].$$tmpId = Math.random();
            } else {
                dereferencedParameters[parameter.name] = parameter;
                dereferencedParameters[parameter.name].$$tmpId = Math.random();
            }
        });
        if (!_.isEmpty(dereferencedParameters)) {
            config.parameters = dereferencedParameters;
            var asArray = [];
            Object.keys(dereferencedParameters).forEach(function (parameterName) {
                asArray.push(dereferencedParameters[parameterName]);
            });
            config.parametersArray = asArray;
            config.requiresParametersSection = true;
        }

        // any SOAP specific content?
        if (operation['x-ibm-soap']) {
            if (operation['x-ibm-soap']['soap-action'] !== undefined) {
                config.soapAction = operation['x-ibm-soap']['soap-action'];
            }
        }

        // content types
        if (operation.consumes) {
            config.contentTypes = operation.consumes;
        } else if (api.consumes) {
            config.contentTypes = api.consumes;
        } else {
            config.contentTypes = ['application/json'];
        }
        if (operation.produces) {
            config.accepts = operation.produces;
        } else if (api.produces) {
            config.accepts = api.produces;
        } else {
            config.accepts = ['application/json'];
        }

        return config;
    };

    window.CommonAPI = CommonAPI;

    $(document).ready(function () {
        window.CommonAPI = new CommonAPI(window.apiJson);
    });
}(jQuery));
