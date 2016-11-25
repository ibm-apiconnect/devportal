/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};

/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {

/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;

/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			exports: {},
/******/ 			id: moduleId,
/******/ 			loaded: false
/******/ 		};

/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);

/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;

/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}


/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;

/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;

/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";

/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(0);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */
/***/ function(module, exports, __webpack_require__) {

	var __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;(function () {

	    var exampleGen = {};

	    var json2xml;

	    function json2xmlInstance() {
	        if (!json2xml) json2xml = new window.X2JS();
	        return json2xml;
	    };

	    /**
	     * Does this operation return XML?
	     * @param operation
	     * @param api
	     * @returns {boolean}
	     */
	    function returnsXML(operation, api) {
	        // assume JSON
	        if (!operation.produces && !api.produces) return false;
	        return (JSON.stringify(operation.produces || api.produces).indexOf('xml') > -1);
	    };

	    /**
	     * Does this operation return JSON?
	     * @param operation
	     * @param api
	     * @returns {boolean}
	     */
	    function returnsJSON(operation, api) {
	        // assume JSON
	        if (!operation.produces && !api.produces) return true;
	        return (JSON.stringify(operation.produces || api.produces).indexOf('json') > -1);
	    };

	    /**
	     * Does this operation accept XML?
	     * @param operation
	     * @param api
	     * @returns {boolean}
	     */
	    function acceptsXML(operation, api) {
	        // assume JSON
	        if (!operation.consumes && !api.consumes) return false;
	        return (JSON.stringify(operation.consumes || api.consumes).indexOf('xml') > -1);
	    };

	    /**
	     * Does this operation accept JSON?
	     * @param operation
	     * @param api
	     * @returns {boolean}
	     */
	    function acceptsJSON(operation, api) {
	        // assume JSON
	        if (!operation.consumes && !api.consumes) return true;
	        return (JSON.stringify(operation.consumes || api.consumes).indexOf('json') > -1);
	    };

	    /**
	     *
	     * @param propertyName
	     * @param property
	     * @param empty
	     * @returns {*}
	     */
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

	    function unwindRefs(schema, api, references) {
	        if (!schema) {
	            return;
	        }
	        var refName, refSchema;

	        // take a deep copy
	        schema = JSON.parse(JSON.stringify(schema));

	        if (schema.$ref) {
	            if (references && references[schema.$ref]) {
	                refSchema = JSON.parse(JSON.stringify(references[schema.$ref]));
	                _.extend(schema, refSchema);
	            } else {
	                refName = schema.$ref.replace("#/definitions/", "");
	                if (api.definitions && api.definitions[refName]) {
	                    refSchema = JSON.parse(JSON.stringify(api.definitions[refName]));
	                    _.extend(schema, refSchema);
	                    // delete schema.$ref;
	                }
	            }
	        }
	        if (schema.type === 'array' && schema.items && schema.items.$ref) {
	            if (references && references[schema.items.$ref]) {
	                refSchema = JSON.parse(JSON.stringify(references[schema.items.$ref]));
	                _.extend(schema.items, refSchema);
	            } else {
	                refName = schema.items.$ref.replace("#/definitions/", "");
	                if (api.definitions && api.definitions[refName]) {
	                    refSchema = JSON.parse(JSON.stringify(api.definitions[refName]));
	                    _.extend(schema.items, refSchema);
	                    // delete schema.$ref;
	                }
	            }
	        }

	        return schema;
	    };

	    function unwindParamRef(param, api, references) {
	        if (!param) {
	            return;
	        }
	        if (param.$ref) {
	            if (references && references[param.$ref]) {
	                refSchema = JSON.parse(JSON.stringify(references[param.$ref]));
	                _.extend(schema, refSchema);
	            } else {
	                var refName = param.$ref.replace("#/parameters/", "");
	                if (api.parameters && api.parameters[refName]) {
	                    var refSchema = JSON.parse(JSON.stringify(api.parameters[refName]));
	                    _.extend(param, refSchema);
	                    // delete schema.$ref;
	                }
	            }
	        }

	        return param;
	    };

	    /**
	     * Generates an empty object for the specified schema
	     * @param schema
	     * @param api
	     * @param empty
	     *
	     * @returns {*}
	     */
	    function createExampleObject(schema, api, empty, references) {
	        if (schema.schema) schema = schema.schema;
	        if (schema.$ref) schema = unwindRefs(schema, api, references);
	        if (empty == null) {
	            empty = false;
	        }
	        var result = {};
	        // handle loose schema'd objects which are missing their 'type'
	        if (!schema.type && schema.properties) {
	            schema.type = 'object';
	        }

	        if (schema.type === 'object') {
	            if (typeof(schema.default) !== 'undefined') {
	                result = schema.default;
	            } else if (typeof(schema.example) !== 'undefined') {
	                result = schema.example;
	            } else {
	                // If schema has no properties (loose schema), return the empty object
	                if (!schema.properties) {
	                    result = createDummyValue("", schema, empty);
	                } else {
	                    Object.keys(schema.properties).forEach(function (propertyName) {
	                        var thisSchema = schema.properties[propertyName];
	                        // if this property is a schema ref, unwind...
	                        if (thisSchema.$ref) thisSchema = unwindRefs(thisSchema, api, references);
	                        if (thisSchema.items && thisSchema.items.$ref) thisSchema = unwindRefs(thisSchema, api, references);
	                        // if this property is an object itself, recurse
	                        if (thisSchema.type === 'object' || thisSchema.type === 'array' || (!thisSchema.type && thisSchema.properties)) {
	                            // handle loose schema'd objects which are missing their 'type'
	                            if (!thisSchema.type) {
	                                thisSchema.type = 'object';
	                            }
	                            result[propertyName] =
	                                createExampleObject(thisSchema, api, empty, references);
	                            // otherwise use the dummy values
	                        } else {
	                            if (typeof(thisSchema.default) !== 'undefined') {
	                                result[propertyName] = thisSchema.default;
	                            } else if (typeof(thisSchema.example) !== 'undefined') {
	                                result[propertyName] = thisSchema.example;
	                            } else {
	                                result[propertyName] = createDummyValue(propertyName, thisSchema, empty);
	                            }
	                        }
	                    });
	                }
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
	                if (typeof(obj.default) !== 'undefined') {
	                    result = obj.default;
	                } else if (typeof(obj.example) !== 'undefined') {
	                    result = obj.example;
	                } else {
	                    result = createDummyValue("", obj, empty);
	                }
	            } else {
	                Object.keys(obj.properties).forEach(function (propertyName) {
	                    var thisSchema = obj.properties[propertyName];
	                    // if this property is a schema ref, unwind...
	                    if (thisSchema.$ref) thisSchema = unwindRefs(thisSchema, api, references);
	                    if (thisSchema.items && thisSchema.items.$ref) thisSchema = unwindRefs(thisSchema, api, references);
	                    // if this property is an object itself, recurse
	                    if (thisSchema.type === 'object' || thisSchema.type === 'array' || (!thisSchema.type && thisSchema.properties)) {
	                        // handle loose schema'd objects which are missing their 'type'
	                        if (!thisSchema.type) {
	                            thisSchema.type = 'object';
	                        }
	                        result[propertyName] =
	                            createExampleObject(thisSchema, api, empty, references);
	                        // otherwise use the defaultValues hash
	                    } else {
	                        if (typeof(thisSchema.default) !== 'undefined') {
	                            result[propertyName] = thisSchema.default;
	                        } else if (typeof(thisSchema.example) !== 'undefined') {
	                            result[propertyName] = thisSchema.example;
	                        } else {
	                            result[propertyName] = createDummyValue(propertyName, thisSchema, empty);
	                        }
	                    }
	                });
	            }
	            list.push(result);
	            return list;
	        } else if (schema.type === 'string' || schema.type === 'integer' || schema.type === 'number' || schema.type === 'boolean') {
	            return createDummyValue('', schema, empty);
	        } else if (schema.allOf) {
	            var sampleObj = {};
	            schema.allOf.forEach(function(subschema) {
	                _.extend(sampleObj, createExampleObject(subschema, api, false, references));
	            });
	            return sampleObj;
	        } else if (schema.anyOf) {
	            return createExampleObject(schema.anyOf[0], api, false, references);
	        } else if (schema.oneOf) {
	            return createExampleObject(schema.oneOf[0], api, false, references);
	        } else {
	            throw new TypeError('schema should be a basic type, an object, an array, or an allOf / anyOf / oneOf.');
	        }
	    };

	    /**
	     * Generate the code snippets
	     * @param api
	     * @param path
	     * @param verb
	     * @param operation
	     * @param config
	     * @param clientId
	     * @param clientSecret
	     * @param languages
	     * @returns {{}}
	     */
	    exampleGen.generateCodeSnippets = function (api, path, verb, operation, config, clientId, clientSecret, languages, references) {
	        if (!clientId) clientId = "REPLACE_THIS_KEY";
	        if (!clientSecret) clientSecret = "REPLACE_THIS_KEY";
	        var basePath = api.basePath ? api.basePath : "";
	        var url = "://" + api.host + basePath + path;
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
	                queryarray.push({'name': 'client_id', 'value': clientId});
	            } else if (config.clientIdLocation == 'header') {
	                headersarray.push({'name': 'X-IBM-Client-Id', 'value': clientId});
	            }
	        }
	        if (config && config.requiresClientSecret) {
	            if (config.clientSecretLocation == 'query') {
	                queryarray.push({'name': 'client_secret', 'value': clientSecret});
	            } else if (config.clientSecretLocation == 'header') {
	                headersarray.push({'name': 'X-IBM-Client-Secret', 'value': clientSecret});
	            }
	        }
	        if (config && config.requiresBasicAuth) {
	            headersarray.push({'name': 'Authorization', 'value': 'Basic ' + 'REPLACE_BASIC_AUTH'});
	        }
	        if (config && config.requiresOauth) {
	            headersarray.push({'name': 'Authorization', 'value': 'Bearer ' + 'REPLACE_BEARER_TOKEN'});
	        }
	        var parameters = config.parametersArray;
	        if (!parameters) parameters = [];
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

	        // is this SOAP? if so, ditch the operationId from the end...
	        if (config.soapAction !== undefined && operation.operationId && url.endsWith("/" + operation.operationId)) {
	            url = url.substring(0, url.length - (operation.operationId.length + 1));
	        }
	        if (config.soapAction !== undefined) {
	            headersarray.push({'name': 'SOAPAction', 'value': config.soapAction});
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
	        if (contenttype) {
	            headersarray.push({'name': 'content-type', 'value': contenttype});
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
	            });
	        }

	        // body
	        var bodyParameters = parameters.filter(function (parameter) {
	            return (parameter.in == "body");
	        });
	        var formDataParameters = parameters.filter(function (parameter) {
	            return (parameter.in == "formData");
	        });
	        if (bodyParameters.length > 0) {
	            // we have a body parameter - generate a sample
	            body = exampleGen.generateExampleParameter(api, path, verb, bodyParameters[0], null, true, references);
	        } else if (formDataParameters.length > 0) {
	            var formDataArray = [];
	            var formDataParams = [];
	            formDataParameters.forEach(function (parameter) {
	                var dummyValue = createDummyValue(parameter.name, parameter, false);
	                formDataArray.push(parameter.name + ': ' + dummyValue);
	                formDataParams.push({'name': parameter.name, 'value': dummyValue});
	            });
	            body = formDataArray.join('\n');
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
	            if (formDataParameters.length > 0) {
	                confHAR["postData"]['params'] = formDataParams;
	            }
	        }
	        // We must pre-process the URL to replace any $(var) inserts that may be contained in the
	        // host part of the URL as the URL parser will think they are part of the path rather than
	        // part of the host.
	        var subLookup = {};
	        var increment = 0;
	        if (api.host) {
	            var matches = api.host.match(/(\$\([^\)]*\))/g);
	            if (matches) {
	                var newURL = confHAR.url;
	                // switch each match with a generated hostname-safe value
	                var len = matches.length;
	                for (var i = 0; i < len; i++) {
	                    increment += 1;
	                    var fakeHost = "azazaz090909-" + increment;
	                    subLookup[fakeHost] = matches[i];
	                    newURL = newURL.replace(matches[i], fakeHost);
	                } // end for
	                confHAR.url = newURL;
	            }
	        }
	        // use the lookup table to switch the fake values with real ones in the actual output
	        var restoreURL = function (snippet) {
	            var ret = snippet;
	            if (increment > 0) {
	                for (var key in subLookup) {
	                    var replacement = subLookup[key];
	                    ret = ret.replace(key, replacement);
	                } // end for
	            }
	            return ret;
	        };

	        // get a new instance of httpSnippet
	        var mySnippet = new window.HTTPSnippetInstance(confHAR);

	        if (!languages) languages = ["curl", "ruby", "python", "php", "java", "node", "go", "swift", "c", "csharp"];

	        var content = {};
	        languages.forEach(function (language) {
	            if (language === "curl") {
	                content.curlContent = restoreURL(mySnippet.convert('shell', 'curl'));
	            } else if (language === "java") {
	                content.javaContent = restoreURL(mySnippet.convert('java', 'okhttp'));
	            } else if (language === "node") {
	                content.nodeContent = restoreURL(mySnippet.convert('node', 'request'));
	            } else {
	                content[language + "Content"] = restoreURL(mySnippet.convert(language));
	            }
	        });

	        return content;
	    };

	    /**
	     * Generate the examples
	     * @param api
	     * @param path
	     * @param verb
	     */
	    exampleGen.generateExampleResponse = function (api, path, verb, references) {

	        var operation = api.paths[path][verb];

	        var example;
	        try {
	            if (operation.responses["200"] && operation.responses["200"].schema) {
	                // example = operation.getResponse("200").getSample();
	                example = createExampleObject(operation.responses["200"], api, false, references);
	            } else if (operation.responses["201"] && operation.responses["201"].schema) {
	                // example = operation.getResponse("201").getSample();
	                example = createExampleObject(operation.responses["201"], api, false, references);
	            } else if (operation.responses["default"] && operation.responses["default"].schema) {
	                // example = operation.getResponse("default").getSample();
	                example = createExampleObject(operation.responses["default"], api, false, references);
	            }
	        } catch (e) {
	            // unable to parse parameter, report & continue
	            console.error(e);
	        }

	        if (!example) return;

	        var retXML = returnsXML(operation, api) && !returnsJSON(operation, api);
	        if (retXML) {
	            example = window.vkbeautify.xml(json2xmlInstance().js2xml(example));
	            example = _.unescape(example);
	        } else {
	            example = JSON.stringify(example, null, 2);
	        }
	        return example;
	    };

	    /**
	     * Generate example for a single parameter (e.g. body)
	     * @param api
	     * @param path
	     * @param verb
	     * @param parameter
	     * @param contentType
	     * @param skipBeautify
	     * @returns {*}
	     */
	    exampleGen.generateExampleParameter = function (api, path, verb, parameter, contentType, skipBeautify, references) {

	        var operation = api.paths[path][verb];

	        if (!parameter) {
	            var parameters = [];
	            // use operation specific parameters
	            if (api.paths[path][verb].parameters) parameters = parameters.concat(JSON.parse(JSON.stringify(api.paths[path][verb].parameters)));
	            // only check for path level parameters if didn't have an operation specific example
	            if (parameters.length < 1) {
	                if (api.paths[path].parameters) parameters = parameters.concat(JSON.parse(JSON.stringify(api.paths[path].parameters)));
	            }

	            parameters.forEach(function (param) {
	                if (param.$ref) param = unwindParamRef(param, api, references);
	            });

	            var theParameter = parameters.filter(function (param) {
	                return (param.name == parameter.name)
	            });

	            if (theParameter.length !== 1) return;

	            parameter = theParameter[0];
	        }

	        var example = "";
	        try {
	            example = createExampleObject(parameter, api, false, references);
	        } catch (e) {
	            // unable to parse parameter, report & continue
	            console.error(e);
	        }

	        var retXML = acceptsXML(operation, api) && !acceptsJSON(operation, api);

	        // if we have been passed a content type header, override
	        if (contentType) {
	            if (contentType.indexOf('json') >= 0) {
	                retXML = false;
	            } else if (contentType.indexOf('xml') >= 0) {
	                retXML = true;
	            }
	        }

	        if (retXML) {
	            var schema = parameter.schema;
	            if (schema.example) return schema.example;
	            if (schema.xml && schema.xml.name) {
	                // wrap it up inside the top-level element
	                var wrappedExample = {};
	                wrappedExample[schema.xml.name] = example;
	                example = json2xmlInstance().js2xml(wrappedExample);
	                if (!skipBeautify) {
	                    example = window.vkbeautify.xml(example);
	                    example = _.unescape(example);
	                }
	            } else {
	                example = json2xmlInstance().js2xml(example);
	                if (!skipBeautify) {
	                    example = window.vkbeautify.xml(example);
	                    example = _.unescape(example);
	                }
	            }
	        } else {
	            // beautify body parameters only
	            if (parameter.in == "body") {
	                if (skipBeautify) {
	                    example = JSON.stringify(example);
	                } else {
	                    example = JSON.stringify(example, null, 2);
	                }
	            } else {
	                if (typeof example == "object") {
	                    if (skipBeautify) {
	                        example = JSON.stringify(example);
	                    } else {
	                        example = JSON.stringify(example, null, 2);
	                    }
	                }
	            }
	        }
	        return example;
	    };

	    // CommonJS module
	    if (true) {
	        if (typeof module !== 'undefined' && module.exports) {
	            exports = module.exports = exampleGen;
	        }
	        exports.exampleGen = exampleGen;
	    }

	    // Register as an anonymous AMD module
	    if (true) {
	        !(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_RESULT__ = function () {
	            return exampleGen;
	        }.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__), __WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
	    }

	    // If there is a window object, that at least has a document property,
	    // instantiate and define chance on the window
	    if (typeof window === "object" && typeof window.document === "object") {
	        window.exampleGenerator = exampleGen;
	    }

	    return exampleGen;
	})();

/***/ }
/******/ ]);