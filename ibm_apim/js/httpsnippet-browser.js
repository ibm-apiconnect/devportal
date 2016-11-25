(function e(t, n, r) {
    function s(o, u) {
        if (!n[o]) {
            if (!t[o]) {
                var a = typeof require == "function" && require;
                if (!u && a)return a(o, !0);
                if (i)return i(o, !0);
                var f = new Error("Cannot find module '" + o + "'");
                throw f.code = "MODULE_NOT_FOUND", f
            }
            var l = n[o] = {exports: {}};
            t[o][0].call(l.exports, function (e) {
                var n = t[o][1][e];
                return s(n ? n : e)
            }, l, l.exports, e, t, n, r)
        }
        return n[o].exports
    }

    var i = typeof require == "function" && require;
    for (var o = 0; o < r.length; o++)s(r[o]);
    return s
})({
    1: [function (require, module, exports) {

        /**
         * This is the web browser implementation of `debug()`.
         *
         * Expose `debug()` as the module.
         */

        exports = module.exports = require('./debug');
        exports.log = log;
        exports.formatArgs = formatArgs;
        exports.save = save;
        exports.load = load;
        exports.useColors = useColors;
        exports.storage = 'undefined' != typeof chrome
        && 'undefined' != typeof chrome.storage
            ? chrome.storage.local
            : localstorage();

        /**
         * Colors.
         */

        exports.colors = [
            'lightseagreen',
            'forestgreen',
            'goldenrod',
            'dodgerblue',
            'darkorchid',
            'crimson'
        ];

        /**
         * Currently only WebKit-based Web Inspectors, Firefox >= v31,
         * and the Firebug extension (any Firefox version) are known
         * to support "%c" CSS customizations.
         *
         * TODO: add a `localStorage` variable to explicitly enable/disable colors
         */

        function useColors() {
            // is webkit? http://stackoverflow.com/a/16459606/376773
            return ('WebkitAppearance' in document.documentElement.style) ||
                    // is firebug? http://stackoverflow.com/a/398120/376773
                (window.console && (console.firebug || (console.exception && console.table))) ||
                    // is firefox >= v31?
                    // https://developer.mozilla.org/en-US/docs/Tools/Web_Console#Styling_messages
                (navigator.userAgent.toLowerCase().match(/firefox\/(\d+)/) && parseInt(RegExp.$1, 10) >= 31);
        }

        /**
         * Map %j to `JSON.stringify()`, since no Web Inspectors do that by default.
         */

        exports.formatters.j = function (v) {
            return JSON.stringify(v);
        };


        /**
         * Colorize log arguments if enabled.
         *
         * @api public
         */

        function formatArgs() {
            var args = arguments;
            var useColors = this.useColors;

            args[0] = (useColors ? '%c' : '')
                + this.namespace
                + (useColors ? ' %c' : ' ')
                + args[0]
                + (useColors ? '%c ' : ' ')
                + '+' + exports.humanize(this.diff);

            if (!useColors) return args;

            var c = 'color: ' + this.color;
            args = [args[0], c, 'color: inherit'].concat(Array.prototype.slice.call(args, 1));

            // the final "%c" is somewhat tricky, because there could be other
            // arguments passed either before or after the %c, so we need to
            // figure out the correct index to insert the CSS into
            var index = 0;
            var lastC = 0;
            args[0].replace(/%[a-z%]/g, function (match) {
                if ('%%' === match) return;
                index++;
                if ('%c' === match) {
                    // we only are interested in the *last* %c
                    // (the user may have provided their own)
                    lastC = index;
            }
            });

            args.splice(lastC, 0, c);
            return args;
    }

        /**
         * Invokes `console.log()` when available.
         * No-op when `console.log` is not a "function".
         *
         * @api public
         */

        function log() {
            // this hackery is required for IE8/9, where
            // the `console.log` function doesn't have 'apply'
            return 'object' === typeof console
                && console.log
                && Function.prototype.apply.call(console.log, console, arguments);
        }

        /**
         * Save `namespaces`.
         *
         * @param {String} namespaces
         * @api private
         */

        function save(namespaces) {
            try {
                if (null == namespaces) {
                    exports.storage.removeItem('debug');
                } else {
                    exports.storage.debug = namespaces;
                }
            } catch (e) {
            }
        }

        /**
         * Load `namespaces`.
         *
         * @return {String} returns the previously persisted debug modes
         * @api private
         */

        function load() {
            var r;
            try {
                r = exports.storage.debug;
            } catch (e) {
            }
            return r;
        }

        /**
         * Enable namespaces listed in `localStorage.debug` initially.
         */

        exports.enable(load());

        /**
         * Localstorage attempts to return the localstorage.
         *
         * This is necessary because safari throws
         * when a user disables cookies/localstorage
         * and you attempt to access it.
         *
         * @return {LocalStorage}
         * @api private
         */

        function localstorage() {
            try {
                return window.localStorage;
            } catch (e) {
            }
        }

    }, {"./debug": 2}],
    2: [function (require, module, exports) {

        /**
         * This is the common logic for both the Node.js and web browser
         * implementations of `debug()`.
         *
         * Expose `debug()` as the module.
         */

        exports = module.exports = debug;
        exports.coerce = coerce;
        exports.disable = disable;
        exports.enable = enable;
        exports.enabled = enabled;
        exports.humanize = require('ms');

        /**
         * The currently active debug mode names, and names to skip.
         */

        exports.names = [];
        exports.skips = [];

        /**
         * Map of special "%n" handling functions, for the debug "format" argument.
         *
         * Valid key names are a single, lowercased letter, i.e. "n".
         */

        exports.formatters = {};

        /**
         * Previously assigned color.
         */

        var prevColor = 0;

        /**
         * Previous log timestamp.
         */

        var prevTime;

        /**
         * Select a color.
         *
         * @return {Number}
         * @api private
         */

        function selectColor() {
            return exports.colors[prevColor++ % exports.colors.length];
        }

        /**
         * Create a debugger with the given `namespace`.
         *
         * @param {String} namespace
         * @return {Function}
         * @api public
         */

        function debug(namespace) {

            // define the `disabled` version
            function disabled() {
            }

            disabled.enabled = false;

            // define the `enabled` version
            function enabled() {

                var self = enabled;

                // set `diff` timestamp
                var curr = +new Date();
                var ms = curr - (prevTime || curr);
                self.diff = ms;
                self.prev = prevTime;
                self.curr = curr;
                prevTime = curr;

                // add the `color` if not set
                if (null == self.useColors) self.useColors = exports.useColors();
                if (null == self.color && self.useColors) self.color = selectColor();

                var args = Array.prototype.slice.call(arguments);

                args[0] = exports.coerce(args[0]);

                if ('string' !== typeof args[0]) {
                    // anything else let's inspect with %o
                    args = ['%o'].concat(args);
                }

                // apply any `formatters` transformations
            var index = 0;
                args[0] = args[0].replace(/%([a-z%])/g, function (match, format) {
                    // if we encounter an escaped % then don't increase the array index
                    if (match === '%%') return match;
                index++;
                    var formatter = exports.formatters[format];
                    if ('function' === typeof formatter) {
                        var val = args[index];
                        match = formatter.call(self, val);

                        // now we need to remove `args[index]` since it's inlined in the `format`
                        args.splice(index, 1);
                        index--;
                }
                    return match;
            });

                if ('function' === typeof exports.formatArgs) {
                    args = exports.formatArgs.apply(self, args);
            }
                var logFn = enabled.log || exports.log || console.log.bind(console);
                logFn.apply(self, args);
        }

            enabled.enabled = true;

            var fn = exports.enabled(namespace) ? enabled : disabled;

            fn.namespace = namespace;

            return fn;
        }

        /**
         * Enables a debug mode by namespaces. This can include modes
         * separated by a colon and wildcards.
         *
         * @param {String} namespaces
         * @api public
         */

        function enable(namespaces) {
            exports.save(namespaces);

            var split = (namespaces || '').split(/[\s,]+/);
            var len = split.length;

            for (var i = 0; i < len; i++) {
                if (!split[i]) continue; // ignore empty strings
                namespaces = split[i].replace(/\*/g, '.*?');
                if (namespaces[0] === '-') {
                    exports.skips.push(new RegExp('^' + namespaces.substr(1) + '$'));
                } else {
                    exports.names.push(new RegExp('^' + namespaces + '$'));
            }
        }
        }

        /**
         * Disable debug output.
         *
         * @api public
         */

        function disable() {
            exports.enable('');
        }

        /**
         * Returns true if the given mode name is enabled, false otherwise.
         *
         * @param {String} name
         * @return {Boolean}
         * @api public
         */

        function enabled(name) {
            var i, len;
            for (i = 0, len = exports.skips.length; i < len; i++) {
                if (exports.skips[i].test(name)) {
                    return false;
            }
        }
            for (i = 0, len = exports.names.length; i < len; i++) {
                if (exports.names[i].test(name)) {
                    return true;
            }
        }
            return false;
        }

        /**
         * Coerce `val`.
         *
         * @param {Mixed} val
         * @return {Mixed}
         * @api private
         */

        function coerce(val) {
            if (val instanceof Error) return val.stack || val.message;
            return val;
        }

    }, {"ms": 3}],
    3: [function (require, module, exports) {
        /**
         * Helpers.
         */

        var s = 1000;
        var m = s * 60;
        var h = m * 60;
        var d = h * 24;
        var y = d * 365.25;

        /**
         * Parse or format the given `val`.
         *
         * Options:
         *
         *  - `long` verbose formatting [false]
         *
         * @param {String|Number} val
         * @param {Object} options
         * @return {String|Number}
         * @api public
         */

        module.exports = function (val, options) {
            options = options || {};
            if ('string' == typeof val) return parse(val);
            return options.long
                ? long(val)
                : short(val);
        };

        /**
         * Parse the given `str` and return milliseconds.
         *
         * @param {String} str
         * @return {Number}
         * @api private
         */

        function parse(str) {
            str = '' + str;
            if (str.length > 10000) return;
            var match = /^((?:\d+)?\.?\d+) *(milliseconds?|msecs?|ms|seconds?|secs?|s|minutes?|mins?|m|hours?|hrs?|h|days?|d|years?|yrs?|y)?$/i.exec(str);
            if (!match) return;
            var n = parseFloat(match[1]);
            var type = (match[2] || 'ms').toLowerCase();
            switch (type) {
                case 'years':
                case 'year':
                case 'yrs':
                case 'yr':
                case 'y':
                    return n * y;
                case 'days':
                case 'day':
                case 'd':
                    return n * d;
                case 'hours':
                case 'hour':
                case 'hrs':
                case 'hr':
                case 'h':
                    return n * h;
                case 'minutes':
                case 'minute':
                case 'mins':
                case 'min':
                case 'm':
                    return n * m;
                case 'seconds':
                case 'second':
                case 'secs':
                case 'sec':
                case 's':
                    return n * s;
                case 'milliseconds':
                case 'millisecond':
                case 'msecs':
                case 'msec':
                case 'ms':
                    return n;
        }
        }

        /**
         * Short format for `ms`.
         *
         * @param {Number} ms
         * @return {String}
         * @api private
         */

        function short(ms) {
            if (ms >= d) return Math.round(ms / d) + 'd';
            if (ms >= h) return Math.round(ms / h) + 'h';
            if (ms >= m) return Math.round(ms / m) + 'm';
            if (ms >= s) return Math.round(ms / s) + 's';
            return ms + 'ms';
        }

        /**
         * Long format for `ms`.
         *
         * @param {Number} ms
         * @return {String}
         * @api private
         */

        function long(ms) {
            return plural(ms, d, 'day')
                || plural(ms, h, 'hour')
                || plural(ms, m, 'minute')
                || plural(ms, s, 'second')
                || ms + ' ms';
        }

        /**
         * Pluralization helper.
         */

        function plural(ms, n, name) {
            if (ms < n) return;
            if (ms < n * 1.5) return Math.floor(ms / n) + ' ' + name;
            return Math.ceil(ms / n) + ' ' + name + 's';
        }

    }, {}],
    4: [function (require, module, exports) {
        (function (process, global, Buffer) {
//filter will reemit the data if cb(err,pass) pass is truthy

// reduce is more tricky
// maybe we want to group the reductions or emit progress updates occasionally
// the most basic reduce just emits one 'data' event after it has recieved 'end'

            var Stream = require('stream').Stream
                , es = exports
                , through = require('through')
                , from = require('from')
                , duplex = require('duplexer')
                , map = require('map-stream')
                , pause = require('pause-stream')
                , split = require('split')
                , pipeline = require('stream-combiner')
                , immediately = global.setImmediate || process.nextTick;

            es.Stream = Stream //re-export Stream from core
            es.through = through
            es.from = from
            es.duplex = duplex
            es.map = map
            es.pause = pause
            es.split = split
            es.pipeline = es.connect = es.pipe = pipeline
// merge / concat
//
// combine multiple streams into a single stream.
// will emit end only once

            es.concat = //actually this should be called concat
                es.merge = function (/*streams...*/) {
                    var toMerge = [].slice.call(arguments)
                    if (toMerge.length === 1 && (toMerge[0] instanceof Array)) {
                        toMerge = toMerge[0] //handle array as arguments object
                    }
                    var stream = new Stream()
                    stream.setMaxListeners(0) // allow adding more than 11 streams
                    var endCount = 0
                    stream.writable = stream.readable = true

                    toMerge.forEach(function (e) {
                        e.pipe(stream, {end: false})
                        var ended = false
                        e.on('end', function () {
                            if (ended) return
                            ended = true
                            endCount++
                            if (endCount == toMerge.length)
                                stream.emit('end')
                        })
                    })
                    stream.write = function (data) {
                        this.emit('data', data)
                    }
                    stream.destroy = function () {
                    toMerge.forEach(function (e) {
                        if (e.destroy) e.destroy()
                    })
                }
                    return stream
                }


// writable stream, collects all events into an array
// and calls back when 'end' occurs
// mainly I'm using this to test the other functions

            es.writeArray = function (done) {
                if ('function' !== typeof done)
                    throw new Error('function writeArray (done): done must be function')

                var a = new Stream()
                    , array = [], isDone = false
                a.write = function (l) {
                    array.push(l)
            }
                a.end = function () {
                    isDone = true
                    done(null, array)
                }
                a.writable = true
                a.readable = false
                a.destroy = function () {
                    a.writable = a.readable = false
                    if (isDone) return
                    done(new Error('destroyed before end'), array)
                }
                return a
            }

//return a Stream that reads the properties of an object
//respecting pause() and resume()

            es.readArray = function (array) {
                var stream = new Stream()
                    , i = 0
                    , paused = false
                    , ended = false

                stream.readable = true
                stream.writable = false

                if (!Array.isArray(array))
                    throw new Error('event-stream.read expects an array')

                stream.resume = function () {
                    if (ended) return
                    paused = false
                    var l = array.length
                    while (i < l && !paused && !ended) {
                        stream.emit('data', array[i++])
                }
                    if (i == l && !ended)
                        ended = true, stream.readable = false, stream.emit('end')
            }
                process.nextTick(stream.resume)
                stream.pause = function () {
                    paused = true
                }
                stream.destroy = function () {
                    ended = true
                    stream.emit('close')
                }
                return stream
            }

//
// readable (asyncFunction)
// return a stream that calls an async function while the stream is not paused.
//
// the function must take: (count, callback) {...
//

            es.readable =
                function (func, continueOnError) {
                    var stream = new Stream()
                        , i = 0
                        , paused = false
                        , ended = false
                        , reading = false

                    stream.readable = true
                    stream.writable = false

                    if ('function' !== typeof func)
                        throw new Error('event-stream.readable expects async function')

                    stream.on('end', function () {
                        ended = true
                    })

                    function get(err, data) {

                        if (err) {
                            stream.emit('error', err)
                            if (!continueOnError) stream.emit('end')
                        } else if (arguments.length > 1)
                            stream.emit('data', data)

                        immediately(function () {
                            if (ended || paused || reading) return
                            try {
                                reading = true
                                func.call(stream, i++, function () {
                                    reading = false
                                    get.apply(null, arguments)
                                })
                            } catch (err) {
                            stream.emit('error', err)
                            }
                        })
                }

                    stream.resume = function () {
                        paused = false
                        get()
                    }
                    process.nextTick(get)
                    stream.pause = function () {
                        paused = true
                    }
                    stream.destroy = function () {
                        stream.emit('end')
                        stream.emit('close')
                        ended = true
                    }
                    return stream
                }


//
// map sync
//

            es.mapSync = function (sync) {
                return es.through(function write(data) {
                    var mappedData = sync(data)
                    if (typeof mappedData !== 'undefined')
                        this.emit('data', mappedData)
                })
            }

//
// log just print out what is coming through the stream, for debugging
//

            es.log = function (name) {
                return es.through(function (data) {
                    var args = [].slice.call(arguments)
                    if (name) console.error(name, data)
                    else     console.error(data)
                    this.emit('data', data)
                })
            }


//
// child -- pipe through a child process
//

            es.child = function (child) {

                return es.duplex(child.stdin, child.stdout)

            }

//
// parse
//
// must be used after es.split() to ensure that each chunk represents a line
// source.pipe(es.split()).pipe(es.parse())

            es.parse = function (options) {
                var emitError = !!(options ? options.error : false)
                return es.through(function (data) {
                    var obj
                    try {
                        if (data) //ignore empty lines
                            obj = JSON.parse(data.toString())
                    } catch (err) {
                        if (emitError)
                            return this.emit('error', err)
                        return console.error(err, 'attempting to parse:', data)
                    }
                    //ignore lines that where only whitespace.
                    if (obj !== undefined)
                        this.emit('data', obj)
                })
            }
//
// stringify
//

            es.stringify = function () {
                var Buffer = require('buffer').Buffer
                return es.mapSync(function (e) {
                    return JSON.stringify(Buffer.isBuffer(e) ? e.toString() : e) + '\n'
                })
            }

//
// replace a string within a stream.
//
// warn: just concatenates the string and then does str.split().join().
// probably not optimal.
// for smallish responses, who cares?
// I need this for shadow-npm so it's only relatively small json files.

            es.replace = function (from, to) {
                return es.pipeline(es.split(from), es.join(to))
            }

//
// join chunks with a joiner. just like Array#join
// also accepts a callback that is passed the chunks appended together
// this is still supported for legacy reasons.
//

            es.join = function (str) {

                //legacy api
                if ('function' === typeof str)
                    return es.wait(str)

                var first = true
                return es.through(function (data) {
                    if (!first)
                        this.emit('data', str)
                    first = false
                    this.emit('data', data)
                    return true
                })
            }


//
// wait. callback when 'end' is emitted, with all chunks appended as string.
//

            es.wait = function (callback) {
                var arr = []
                return es.through(function (data) {
                        arr.push(data)
                    },
                    function () {
                        var body = Buffer.isBuffer(arr[0]) ? Buffer.concat(arr)
                            : arr.join('')
                        this.emit('data', body)
                        this.emit('end')
                        if (callback) callback(null, body)
                    })
            }

            es.pipeable = function () {
                throw new Error('[EVENT-STREAM] es.pipeable is deprecated')
            }

        }).call(this, require('_process'), typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {}, require("buffer").Buffer)
    }, {
        "_process": 92,
        "buffer": 84,
        "duplexer": 5,
        "from": 6,
        "map-stream": 7,
        "pause-stream": 8,
        "split": 9,
        "stream": 110,
        "stream-combiner": 10,
        "through": 11
    }],
    5: [function (require, module, exports) {
        var Stream = require("stream")
        var writeMethods = ["write", "end", "destroy"]
        var readMethods = ["resume", "pause"]
        var readEvents = ["data", "close"]
        var slice = Array.prototype.slice

        module.exports = duplex

        function forEach(arr, fn) {
            if (arr.forEach) {
                return arr.forEach(fn)
            }

            for (var i = 0; i < arr.length; i++) {
                fn(arr[i], i)
        }
        }

        function duplex(writer, reader) {
            var stream = new Stream()
            var ended = false

            forEach(writeMethods, proxyWriter)

            forEach(readMethods, proxyReader)

            forEach(readEvents, proxyStream)

            reader.on("end", handleEnd)

            writer.on("drain", function () {
                stream.emit("drain")
            })

            writer.on("error", reemit)
            reader.on("error", reemit)

            stream.writable = writer.writable
            stream.readable = reader.readable

            return stream

            function proxyWriter(methodName) {
                stream[methodName] = method

                function method() {
                    return writer[methodName].apply(writer, arguments)
            }
            }

            function proxyReader(methodName) {
                stream[methodName] = method

                function method() {
                    stream.emit(methodName)
                    var func = reader[methodName]
                    if (func) {
                        return func.apply(reader, arguments)
                }
                    reader.emit(methodName)
            }
            }

            function proxyStream(methodName) {
                reader.on(methodName, reemit)

                function reemit() {
                var args = slice.call(arguments)
                    args.unshift(methodName)
                stream.emit.apply(stream, args)
            }
            }

            function handleEnd() {
                if (ended) {
                    return
            }
                ended = true
                var args = slice.call(arguments)
                args.unshift("end")
                stream.emit.apply(stream, args)
        }

            function reemit(err) {
                stream.emit("error", err)
            }
        }

    }, {"stream": 110}],
    6: [function (require, module, exports) {
        (function (process) {

            'use strict';

            var Stream = require('stream')

// from
//
// a stream that reads from an source.
// source may be an array, or a function.
// from handles pause behaviour for you.

            module.exports =
                function from(source) {
                    if (Array.isArray(source)) {
                        source = source.slice()
                        return from(function (i) {
                            if (source.length)
                                this.emit('data', source.shift())
                            else
                                this.emit('end')
                            return true
                        })
                    }
                    var s = new Stream(), i = 0
                    s.ended = false
                    s.started = false
                    s.readable = true
                    s.writable = false
                    s.paused = false
                    s.ended = false
                    s.pause = function () {
                        s.started = true
                        s.paused = true
                    }
                    function next() {
                        s.started = true
                        if (s.ended) return
                        while (!s.ended && !s.paused && source.call(s, i++, function () {
                            if (!s.ended && !s.paused)
                                next()
                        }))
                            ;
                    }

                    s.resume = function () {
                        s.started = true
                    s.paused = false
                        next()
                }
                    s.on('end', function () {
                        s.ended = true
                        s.readable = false
                        process.nextTick(s.destroy)
                    })
                    s.destroy = function () {
                        s.ended = true
                        s.emit('close')
                    }
                    /*
                     by default, the stream will start emitting at nextTick
                     if you want, you can pause it, after pipeing.
                     you can also resume before next tick, and that will also
                     work.
                     */
                    process.nextTick(function () {
                        if (!s.started) s.resume()
                    })
                    return s
                }

        }).call(this, require('_process'))
    }, {"_process": 92, "stream": 110}],
    7: [function (require, module, exports) {
        (function (process) {
//filter will reemit the data if cb(err,pass) pass is truthy

// reduce is more tricky
// maybe we want to group the reductions or emit progress updates occasionally
// the most basic reduce just emits one 'data' event after it has recieved 'end'


            var Stream = require('stream').Stream


//create an event stream and apply function to each .write
//emitting each response as data
//unless it's an empty callback

            module.exports = function (mapper, opts) {

                var stream = new Stream()
                    , self = this
                    , inputs = 0
                    , outputs = 0
                    , ended = false
                    , paused = false
                    , destroyed = false
                    , lastWritten = 0
                    , inNext = false

                this.opts = opts || {};
                var errorEventName = this.opts.failures ? 'failure' : 'error';

                // Items that are not ready to be written yet (because they would come out of
                // order) get stuck in a queue for later.
                var writeQueue = {}

                stream.writable = true
                stream.readable = true

                function queueData(data, number) {
                    var nextToWrite = lastWritten + 1

                    if (number === nextToWrite) {
                        // If it's next, and its not undefined write it
                    if (data !== undefined) {
                        stream.emit.apply(stream, ['data', data])
                    }
                        lastWritten++
                        nextToWrite++
                    } else {
                        // Otherwise queue it for later.
                        writeQueue[number] = data
                }

                    // If the next value is in the queue, write it
                    if (writeQueue.hasOwnProperty(nextToWrite)) {
                        var dataToWrite = writeQueue[nextToWrite]
                        delete writeQueue[nextToWrite]
                        return queueData(dataToWrite, nextToWrite)
                }

                    outputs++
                    if (inputs === outputs) {
                        if (paused) paused = false, stream.emit('drain') //written all the incoming events
                        if (ended) end()
                }
                }

                function next(err, data, number) {
                    if (destroyed) return
                    inNext = true

                    if (!err || self.opts.failures) {
                        queueData(data, number)
                }

                    if (err) {
                        stream.emit.apply(stream, [errorEventName, err]);
                }

                    inNext = false;
            }

                // Wrap the mapper function by calling its callback with the order number of
                // the item in the stream.
                function wrappedMapper(input, number, callback) {
                    return mapper.call(null, input, function (err, data) {
                        callback(err, data, number)
                    })
                }

                stream.write = function (data) {
                    if (ended) throw new Error('map stream is not writable')
                    inNext = false
                    inputs++

                    try {
                        //catch sync errors and handle them like async errors
                        var written = wrappedMapper(data, inputs, next)
                        paused = (written === false)
                        return !paused
                    } catch (err) {
                        //if the callback has been called syncronously, and the error
                        //has occured in an listener, throw it again.
                        if (inNext)
                            throw err
                        next(err)
                        return !paused
                    }
                }

                function end(data) {
                    //if end was called with args, write it,
                    ended = true //write will emit 'end' if ended is true
                    stream.writable = false
                    if (data !== undefined) {
                        return queueData(data, inputs)
                    } else if (inputs == outputs) { //wait for processing
                        stream.readable = false, stream.emit('end'), stream.destroy()
                    }
                }

                stream.end = function (data) {
                    if (ended) return
                    end()
                }

                stream.destroy = function () {
                    ended = destroyed = true
                    stream.writable = stream.readable = paused = false
                    process.nextTick(function () {
                        stream.emit('close')
                    })
                }
                stream.pause = function () {
                    paused = true
                }

                stream.resume = function () {
                    paused = false
                }

                return stream
            }


        }).call(this, require('_process'))
    }, {"_process": 92, "stream": 110}],
    8: [function (require, module, exports) {
//through@2 handles this by default!
        module.exports = require('through')


    }, {"through": 11}],
    9: [function (require, module, exports) {
//filter will reemit the data if cb(err,pass) pass is truthy

// reduce is more tricky
// maybe we want to group the reductions or emit progress updates occasionally
// the most basic reduce just emits one 'data' event after it has recieved 'end'


        var through = require('through')
        var Decoder = require('string_decoder').StringDecoder

        module.exports = split

//TODO pass in a function to map across the lines.

        function split(matcher, mapper, options) {
            var decoder = new Decoder()
            var soFar = ''
            var maxLength = options && options.maxLength;
            if ('function' === typeof matcher)
                mapper = matcher, matcher = null
            if (!matcher)
                matcher = /\r?\n/

            function emit(stream, piece) {
                if (mapper) {
                    try {
                        piece = mapper(piece)
                }
                    catch (err) {
                        return stream.emit('error', err)
                    }
                    if ('undefined' !== typeof piece)
                    stream.queue(piece)
            }
                else
                    stream.queue(piece)
            }

            function next(stream, buffer) {
                var pieces = ((soFar != null ? soFar : '') + buffer).split(matcher)
                soFar = pieces.pop()

                if (maxLength && soFar.length > maxLength)
                    stream.emit('error', new Error('maximum buffer reached'))

                for (var i = 0; i < pieces.length; i++) {
                    var piece = pieces[i]
                    emit(stream, piece)
            }
        }

            return through(function (b) {
                    next(this, decoder.write(b))
                },
                function () {
                    if (decoder.end)
                        next(this, decoder.end())
                    if (soFar != null)
                        emit(this, soFar)
                    this.queue(null)
                })
        }


    }, {"string_decoder": 111, "through": 11}],
    10: [function (require, module, exports) {
        var duplexer = require('duplexer')

        module.exports = function () {

            var streams = [].slice.call(arguments)
                , first = streams[0]
                , last = streams[streams.length - 1]
                , thepipe = duplexer(first, last)

            if (streams.length == 1)
                return streams[0]
            else if (!streams.length)
                throw new Error('connect called with empty args')

            //pipe all the streams together

            function recurse(streams) {
                if (streams.length < 2)
                    return
                streams[0].pipe(streams[1])
                recurse(streams.slice(1))
            }

            recurse(streams)

            function onerror() {
                var args = [].slice.call(arguments)
                args.unshift('error')
                thepipe.emit.apply(thepipe, args)
        }

            //es.duplex already reemits the error from the first and last stream.
            //add a listener for the inner streams in the pipeline.
            for (var i = 1; i < streams.length - 1; i++)
                streams[i].on('error', onerror)

            return thepipe
        }


    }, {"duplexer": 5}],
    11: [function (require, module, exports) {
        (function (process) {
            var Stream = require('stream')

// through
//
// a stream that does nothing but re-emit the input.
// useful for aggregating a series of changing but not ending streams into one stream)

            exports = module.exports = through
            through.through = through

//create a readable writable stream.

            function through(write, end, opts) {
                write = write || function (data) {
                        this.queue(data)
                    }
                end = end || function () {
                        this.queue(null)
                    }

                var ended = false, destroyed = false, buffer = [], _ended = false
                var stream = new Stream()
                stream.readable = stream.writable = true
                stream.paused = false

//  stream.autoPause   = !(opts && opts.autoPause   === false)
                stream.autoDestroy = !(opts && opts.autoDestroy === false)

                stream.write = function (data) {
                    write.call(this, data)
                    return !stream.paused
                }

                function drain() {
                    while (buffer.length && !stream.paused) {
                        var data = buffer.shift()
                        if (null === data)
                            return stream.emit('end')
                        else
                            stream.emit('data', data)
                }
                }

                stream.queue = stream.push = function (data) {
//    console.error(ended)
                    if (_ended) return stream
                    if (data === null) _ended = true
                    buffer.push(data)
                    drain()
                    return stream
                }

                //this will be registered as the first 'end' listener
                //must call destroy next tick, to make sure we're after any
                //stream piped from here.
                //this is only a problem if end is not emitted synchronously.
                //a nicer way to do this is to make sure this is the last listener for 'end'

                stream.on('end', function () {
                    stream.readable = false
                    if (!stream.writable && stream.autoDestroy)
                        process.nextTick(function () {
                        stream.destroy()
                        })
            })

                function _end() {
                    stream.writable = false
                    end.call(stream)
                    if (!stream.readable && stream.autoDestroy)
                        stream.destroy()
                }

                stream.end = function (data) {
                    if (ended) return
                    ended = true
                    if (arguments.length) stream.write(data)
                    _end() // will emit or queue
                    return stream
            }

                stream.destroy = function () {
                    if (destroyed) return
                    destroyed = true
                    ended = true
                    buffer.length = 0
                    stream.writable = stream.readable = false
                    stream.emit('close')
                    return stream
            }

                stream.pause = function () {
                    if (stream.paused) return
                    stream.paused = true
                    return stream
                }

                stream.resume = function () {
                    if (stream.paused) {
                        stream.paused = false
                        stream.emit('resume')
                    }
                    drain()
                    //may have become paused again,
                    //as drain emits 'data'.
                    if (!stream.paused)
                        stream.emit('drain')
                    return stream
                }
                return stream
            }


        }).call(this, require('_process'))
    }, {"_process": 92, "stream": 110}],
    12: [function (require, module, exports) {
        module.exports = FormData;
    }, {}],
    13: [function (require, module, exports) {
        'use strict'

        function ValidationError(errors) {
            this.name = 'ValidationError'
            this.errors = errors
        }

        ValidationError.prototype = Error.prototype

        module.exports = ValidationError

    }, {}],
    14: [function (require, module, exports) {
        'use strict'

        var schemas = require('./schemas')
        var ValidationError = require('./error')
        var validator = require('is-my-json-valid')

        var runner = function (schema, data, cb) {
            var validate = validator(schema, {
                greedy: true,
                verbose: true,
                schemas: schemas
            })

            var valid = false

            if (data !== undefined) {
                // execute is-my-json-valid
                valid = validate(data)
            }

            // callback?
            if (!cb) {
            return valid
            } else {
                return cb(validate.errors ? new ValidationError(validate.errors) : null, valid)
        }

            return valid
        }

        module.exports = function (data, cb) {
            return runner(schemas.har, data, cb)
        }

        Object.keys(schemas).map(function (name) {
            module.exports[name] = function (data, cb) {
                return runner(schemas[name], data, cb)
        }
        })

    }, {"./error": 13, "./schemas": 22, "is-my-json-valid": 32}],
    15: [function (require, module, exports) {
        module.exports = {
            "properties": {
                "beforeRequest": {
                    "$ref": "#cacheEntry"
                },
                "afterRequest": {
                    "$ref": "#cacheEntry"
                },
                "comment": {
                    "type": "string"
            }
            }
        }

    }, {}],
    16: [function (require, module, exports) {
        module.exports = {
            "oneOf": [{
                "type": "object",
                "optional": true,
                "required": [
                    "lastAccess",
                    "eTag",
                    "hitCount"
                ],
            "properties": {
                "expires": {
                    "type": "string"
                },
                "lastAccess": {
                    "type": "string"
                },
                "eTag": {
                    "type": "string"
                },
                "hitCount": {
                    "type": "integer"
                },
                "comment": {
                    "type": "string"
                }
            }
            }, {
                "type": null,
                "additionalProperties": false
            }]
        }

    }, {}],
    17: [function (require, module, exports) {
        module.exports = {
            "type": "object",
            "required": [
                "size",
                "mimeType"
            ],
            "properties": {
                "size": {
                    "type": "integer"
                },
                "compression": {
                    "type": "integer"
                },
                "mimeType": {
                    "type": "string"
                },
                "text": {
                    "type": "string"
                },
                "encoding": {
                    "type": "string"
                },
                "comment": {
                    "type": "string"
                }
        }
        }

    }, {}],
    18: [function (require, module, exports) {
        module.exports = {
            "type": "object",
            "required": [
                "name",
                "value"
            ],
            "properties": {
                "name": {
                    "type": "string"
                },
                "value": {
                    "type": "string"
                },
                "path": {
                    "type": "string"
                },
                "domain": {
                    "type": "string"
                },
                "expires": {
                    "type": ["string", "null"],
                    "format": "date-time"
                },
                "httpOnly": {
                    "type": "boolean"
                },
                "secure": {
                    "type": "boolean"
                },
                "comment": {
                    "type": "string"
                }
            }
        }

    }, {}],
    19: [function (require, module, exports) {
        module.exports = {
            "type": "object",
            "required": [
                "name",
                "version"
            ],
            "properties": {
                "name": {
                    "type": "string"
                },
                "version": {
                    "type": "string"
                },
                "comment": {
                    "type": "string"
                }
            }
        }

    }, {}],
    20: [function (require, module, exports) {
        module.exports = {
            "type": "object",
            "optional": true,
            "required": [
                "startedDateTime",
                "time",
                "request",
                "response",
                "cache",
                "timings"
            ],
            "properties": {
                "pageref": {
                    "type": "string"
                },
                "startedDateTime": {
                    "type": "string",
                    "format": "date-time",
                    "pattern": "^(\\d{4})(-)?(\\d\\d)(-)?(\\d\\d)(T)?(\\d\\d)(:)?(\\d\\d)(:)?(\\d\\d)(\\.\\d+)?(Z|([+-])(\\d\\d)(:)?(\\d\\d))"
                },
                "time": {
                    "type": "number",
                    "min": 0
                },
                "request": {
                    "$ref": "#request"
                },
                "response": {
                    "$ref": "#response"
                },
                "cache": {
                    "$ref": "#cache"
                },
                "timings": {
                    "$ref": "#timings"
                },
                "serverIPAddress": {
                    "type": "string",
                    "oneOf": [
                        {"format": "ipv4"},
                        {"format": "ipv6"}
                    ]
                },
                "connection": {
                    "type": "string"
                },
                "comment": {
                    "type": "string"
                }
            }
        }

    }, {}],
    21: [function (require, module, exports) {
        module.exports = {
            "type": "object",
            "required": [
                "log"
            ],
            "properties": {
                "log": {
                    "$ref": "#log"
                }
            }
        }

    }, {}],
    22: [function (require, module, exports) {
        'use strict'

        var schemas = {
            cache: require('./cache.json'),
            cacheEntry: require('./cacheEntry.json'),
            content: require('./content.json'),
            cookie: require('./cookie.json'),
            creator: require('./creator.json'),
            entry: require('./entry.json'),
            har: require('./har.json'),
            log: require('./log.json'),
            page: require('./page.json'),
            pageTimings: require('./pageTimings.json'),
            postData: require('./postData.json'),
            record: require('./record.json'),
            request: require('./request.json'),
            response: require('./response.json'),
            timings: require('./timings.json')
        }

// is-my-json-valid does not provide meaningful error messages for external schemas
// this is a workaround
        schemas.cache.properties.beforeRequest = schemas.cacheEntry
        schemas.cache.properties.afterRequest = schemas.cacheEntry

        schemas.page.properties.pageTimings = schemas.pageTimings

        schemas.request.properties.cookies.items = schemas.cookie
        schemas.request.properties.headers.items = schemas.record
        schemas.request.properties.queryString.items = schemas.record
        schemas.request.properties.postData = schemas.postData

        schemas.response.properties.cookies.items = schemas.cookie
        schemas.response.properties.headers.items = schemas.record
        schemas.response.properties.content = schemas.content

        schemas.entry.properties.request = schemas.request
        schemas.entry.properties.response = schemas.response
        schemas.entry.properties.cache = schemas.cache
        schemas.entry.properties.timings = schemas.timings

        schemas.log.properties.creator = schemas.creator
        schemas.log.properties.browser = schemas.creator
        schemas.log.properties.pages.items = schemas.page
        schemas.log.properties.entries.items = schemas.entry

        schemas.har.properties.log = schemas.log

        module.exports = schemas

    }, {
        "./cache.json": 15,
        "./cacheEntry.json": 16,
        "./content.json": 17,
        "./cookie.json": 18,
        "./creator.json": 19,
        "./entry.json": 20,
        "./har.json": 21,
        "./log.json": 23,
        "./page.json": 24,
        "./pageTimings.json": 25,
        "./postData.json": 26,
        "./record.json": 27,
        "./request.json": 28,
        "./response.json": 29,
        "./timings.json": 30
    }],
    23: [function (require, module, exports) {
        module.exports = {
            "type": "object",
            "required": [
                "version",
                "creator",
                "entries"
            ],
            "properties": {
                "version": {
                    "type": "string"
                },
                "creator": {
                    "$ref": "#creator"
                },
                "browser": {
                    "$ref": "#creator"
                },
                "pages": {
                    "type": "array",
                    "items": {
                        "$ref": "#page"
                    }
                },
                "entries": {
                    "type": "array",
                    "items": {
                        "$ref": "#entry"
                    }
                },
                "comment": {
                    "type": "string"
                }
            }
        }

    }, {}],
    24: [function (require, module, exports) {
        module.exports = {
            "type": "object",
            "optional": true,
            "required": [
                "startedDateTime",
                "id",
                "title",
                "pageTimings"
            ],
            "properties": {
                "startedDateTime": {
                    "type": "string",
                    "format": "date-time",
                    "pattern": "^(\\d{4})(-)?(\\d\\d)(-)?(\\d\\d)(T)?(\\d\\d)(:)?(\\d\\d)(:)?(\\d\\d)(\\.\\d+)?(Z|([+-])(\\d\\d)(:)?(\\d\\d))"
                },
                "id": {
                    "type": "string",
                    "unique": true
                },
                "title": {
                    "type": "string"
                },
                "pageTimings": {
                    "$ref": "#pageTimings"
                },
                "comment": {
                    "type": "string"
                }
            }
        }

    }, {}],
    25: [function (require, module, exports) {
        module.exports = {
            "type": "object",
            "properties": {
                "onContentLoad": {
                    "type": "number",
                    "min": -1
                },
                "onLoad": {
                    "type": "number",
                    "min": -1
                },
                "comment": {
                    "type": "string"
                }
            }
        }

    }, {}],
    26: [function (require, module, exports) {
        module.exports = {
            "type": "object",
            "optional": true,
            "required": [
                "mimeType"
            ],
            "properties": {
                "mimeType": {
                    "type": "string"
                },
                "text": {
                    "type": "string"
                },
                "params": {
                    "type": "array",
                "required": [
                    "name"
                ],
                "properties": {
                    "name": {
                        "type": "string"
                    },
                    "value": {
                        "type": "string"
                    },
                    "fileName": {
                        "type": "string"
                    },
                    "contentType": {
                        "type": "string"
                    },
                    "comment": {
                        "type": "string"
                    }
                }
                },
                "comment": {
                    "type": "string"
            }
        }
        }

    }, {}],
    27: [function (require, module, exports) {
        module.exports = {
            "type": "object",
            "required": [
                "name",
                "value"
            ],
            "properties": {
                "name": {
                    "type": "string"
                },
                "value": {
                    "type": "string"
                },
                "comment": {
                    "type": "string"
            }
        }
        }

    }, {}],
    28: [function (require, module, exports) {
        module.exports = {
            "type": "object",
            "required": [
                "method",
                "url",
                "httpVersion",
                "cookies",
                "headers",
                "queryString",
                "headersSize",
                "bodySize"
            ],
            "properties": {
                "method": {
                    "type": "string"
                },
                "url": {
                    "type": "string",
                    "format": "uri"
                },
                "httpVersion": {
                    "type": "string"
                },
                "cookies": {
                    "type": "array",
                    "items": {
                        "$ref": "#cookie"
                }
                },
                "headers": {
                    "type": "array",
                    "items": {
                        "$ref": "#record"
                }
                },
                "queryString": {
                    "type": "array",
                    "items": {
                        "$ref": "#record"
                }
                },
                "postData": {
                    "$ref": "#postData"
                },
                "headersSize": {
                    "type": "integer"
                },
                "bodySize": {
                    "type": "integer"
                },
                "comment": {
                    "type": "string"
            }
        }
        }

    }, {}],
    29: [function (require, module, exports) {
        module.exports = {
            "type": "object",
            "required": [
                "status",
                "statusText",
                "httpVersion",
                "cookies",
                "headers",
                "content",
                "redirectURL",
                "headersSize",
                "bodySize"
            ],
            "properties": {
                "status": {
                    "type": "integer"
                },
                "statusText": {
                    "type": "string"
                },
                "httpVersion": {
                    "type": "string"
                },
                "cookies": {
                    "type": "array",
                    "items": {
                        "$ref": "#cookie"
                }
                },
                "headers": {
                    "type": "array",
                    "items": {
                        "$ref": "#record"
                }
                },
                "content": {
                    "$ref": "#content"
                },
                "redirectURL": {
                    "type": "string"
                },
                "headersSize": {
                    "type": "integer"
                },
                "bodySize": {
                    "type": "integer"
                },
                "comment": {
                    "type": "string"
            }
        }
        }

    }, {}],
    30: [function (require, module, exports) {
        module.exports = {
            "required": [
                "send",
                "wait",
                "receive"
            ],
            "properties": {
                "dns": {
                    "type": "number",
                    "min": -1
                },
                "connect": {
                    "type": "number",
                    "min": -1
                },
                "blocked": {
                    "type": "number",
                    "min": -1
                },
                "send": {
                    "type": "number",
                    "min": -1
                },
                "wait": {
                    "type": "number",
                    "min": -1
                },
                "receive": {
                    "type": "number",
                    "min": -1
                },
                "ssl": {
                    "type": "number",
                    "min": -1
                },
                "comment": {
                    "type": "string"
            }
        }
        }

    }, {}],
    31: [function (require, module, exports) {
        exports['date-time'] = /^\d{4}-(?:0[0-9]{1}|1[0-2]{1})-[0-9]{2}[tT ]\d{2}:\d{2}:\d{2}(\.\d+)?([zZ]|[+-]\d{2}:\d{2})$/
        exports['date'] = /^\d{4}-(?:0[0-9]{1}|1[0-2]{1})-[0-9]{2}$/
        exports['time'] = /^\d{2}:\d{2}:\d{2}$/
        exports['email'] = /^\S+@\S+$/
        exports['ip-address'] = exports['ipv4'] = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/
        exports['ipv6'] = /^\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}))|:)))(%.+)?\s*$/
        exports['uri'] = /^[a-zA-Z][a-zA-Z0-9+-.]*:[^\s]*$/
        exports['color'] = /(#?([0-9A-Fa-f]{3,6})\b)|(aqua)|(black)|(blue)|(fuchsia)|(gray)|(green)|(lime)|(maroon)|(navy)|(olive)|(orange)|(purple)|(red)|(silver)|(teal)|(white)|(yellow)|(rgb\(\s*\b([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\b\s*,\s*\b([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\b\s*,\s*\b([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\b\s*\))|(rgb\(\s*(\d?\d%|100%)+\s*,\s*(\d?\d%|100%)+\s*,\s*(\d?\d%|100%)+\s*\))/
        exports['hostname'] = /^([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])(\.([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]{0,61}[a-zA-Z0-9]))*$/
        exports['alpha'] = /^[a-zA-Z]+$/
        exports['alphanumeric'] = /^[a-zA-Z0-9]+$/
        exports['style'] = /\s*(.+?):\s*([^;]+);?/g
        exports['phone'] = /^\+(?:[0-9] ?){6,14}[0-9]$/
        exports['utc-millisec'] = /^[0-9]{1,15}\.?[0-9]{0,15}$/

    }, {}],
    32: [function (require, module, exports) {
        var genobj = require('generate-object-property')
        var genfun = require('generate-function')
        var jsonpointer = require('jsonpointer')
        var xtend = require('xtend')
        var formats = require('./formats')

        var get = function (obj, additionalSchemas, ptr) {
            if (/^https?:\/\//.test(ptr)) return null

            var visit = function (sub) {
                if (sub && sub.id === ptr) return sub
                if (typeof sub !== 'object' || !sub) return null
                return Object.keys(sub).reduce(function (res, k) {
                    return res || visit(sub[k])
                }, null)
        }

            var res = visit(obj)
            if (res) return res

            ptr = ptr.replace(/^#/, '')
            ptr = ptr.replace(/\/$/, '')

            try {
                return jsonpointer.get(obj, decodeURI(ptr))
            } catch (err) {
                var end = ptr.indexOf('#')
                var other
                // external reference
                if (end !== 0) {
                    // fragment doesn't exist.
                    if (end === -1) {
                    other = additionalSchemas[ptr]
                } else {
                        var ext = ptr.slice(0, end)
                        other = additionalSchemas[ext]
                        var fragment = ptr.slice(end).replace(/^#/, '')
                        try {
                            return jsonpointer.get(other, fragment)
                        } catch (err) {
                        }
                }
                } else {
                    other = additionalSchemas[ptr]
                }
                return other || null
            }
        }

        var formatName = function (field) {
            field = JSON.stringify(field)
            var pattern = /\[([^\[\]"]+)\]/
            while (pattern.test(field)) field = field.replace(pattern, '."+$1+"')
            return field
        }

        var types = {}

        types.any = function () {
            return 'true'
        }

        types.null = function (name) {
            return name + ' === null'
        }

        types.boolean = function (name) {
            return 'typeof ' + name + ' === "boolean"'
        }

        types.array = function (name) {
            return 'Array.isArray(' + name + ')'
        }

        types.object = function (name) {
            return 'typeof ' + name + ' === "object" && ' + name + ' && !Array.isArray(' + name + ')'
        }

        types.number = function (name) {
            return 'typeof ' + name + ' === "number"'
        }

        types.integer = function (name) {
            return 'typeof ' + name + ' === "number" && (Math.floor(' + name + ') === ' + name + ' || ' + name + ' > 9007199254740992 || ' + name + ' < -9007199254740992)'
        }

        types.string = function (name) {
            return 'typeof ' + name + ' === "string"'
        }

        var unique = function (array) {
            var list = []
            for (var i = 0; i < array.length; i++) {
                list.push(typeof array[i] === 'object' ? JSON.stringify(array[i]) : array[i])
            }
            for (var i = 1; i < list.length; i++) {
                if (list.indexOf(list[i]) !== i) return false
            }
            return true
        }

        var toType = function (node) {
            return node.type
        }

        var compile = function (schema, cache, root, reporter, opts) {
            var fmts = opts ? xtend(formats, opts.formats) : formats
            var scope = {unique: unique, formats: fmts}
            var verbose = opts ? !!opts.verbose : false;
            var greedy = opts && opts.greedy !== undefined ?
                opts.greedy : false;

            var syms = {}
            var gensym = function (name) {
                return name + (syms[name] = (syms[name] || 0) + 1)
            }

            var reversePatterns = {}
            var patterns = function (p) {
                if (reversePatterns[p]) return reversePatterns[p]
                var n = gensym('pattern')
                scope[n] = new RegExp(p)
                reversePatterns[p] = n
                return n
            }

            var vars = ['i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'x', 'y', 'z']
            var genloop = function () {
                var v = vars.shift()
                vars.push(v + v[0])
                return v
            }

            var visit = function (name, node, reporter, filter) {
                var properties = node.properties
                var type = node.type
                var tuple = false

                if (Array.isArray(node.items)) { // tuple type
                    properties = {}
                    node.items.forEach(function (item, i) {
                        properties[i] = item
                    })
                    type = 'array'
                    tuple = true
                }

                var indent = 0
                var error = function (msg, prop, value) {
                    validate('errors++')
                    if (reporter === true) {
                        validate('if (validate.errors === null) validate.errors = []')
                        if (verbose) {
                            validate('validate.errors.push({field:%s,message:%s,value:%s})', formatName(prop || name), JSON.stringify(msg), value || name)
                        } else {
                            validate('validate.errors.push({field:%s,message:%s})', formatName(prop || name), JSON.stringify(msg))
                        }
                }
                }

                if (node.required === true) {
                    indent++
                    validate('if (%s === undefined) {', name)
                    error('is required')
                    validate('} else {')
                } else {
                    indent++
                    validate('if (%s !== undefined) {', name)
                }

                var valid = [].concat(type)
                        .map(function (t) {
                            return types[t || 'any'](name)
                        })
                        .join(' || ') || 'true'

                if (valid !== 'true') {
                    indent++
                    validate('if (!(%s)) {', valid)
                    error('is the wrong type')
                    validate('} else {')
                }

                if (tuple) {
                    if (node.additionalItems === false) {
                        validate('if (%s.length > %d) {', name, node.items.length)
                        error('has additional items')
                        validate('}')
                    } else if (node.additionalItems) {
                    var i = genloop()
                        validate('for (var %s = %d; %s < %s.length; %s++) {', i, node.items.length, i, name, i)
                        visit(name + '[' + i + ']', node.additionalItems, reporter, filter)
                        validate('}')
                    }
                }

                if (node.format && fmts[node.format]) {
                    if (type !== 'string' && formats[node.format]) validate('if (%s) {', types.string(name))
                    var n = gensym('format')
                    scope[n] = fmts[node.format]

                    if (typeof scope[n] === 'function') validate('if (!%s(%s)) {', n, name)
                    else validate('if (!%s.test(%s)) {', n, name)
                    error('must be ' + node.format + ' format')
                    validate('}')
                    if (type !== 'string' && formats[node.format]) validate('}')
                }

                if (Array.isArray(node.required)) {
                    var isUndefined = function (req) {
                        return genobj(name, req) + ' === undefined'
                    }

                    var checkRequired = function (req) {
                        var prop = genobj(name, req);
                        validate('if (%s === undefined) {', prop)
                        error('is required', prop)
                        validate('missing++')
                        validate('}')
                    }
                    validate('if ((%s)) {', type !== 'object' ? types.object(name) : 'true')
                    validate('var missing = 0')
                    node.required.map(checkRequired)
                    validate('}');
                    if (!greedy) {
                        validate('if (missing === 0) {')
                        indent++
                    }
                }

                if (node.uniqueItems) {
                    if (type !== 'array') validate('if (%s) {', types.array(name))
                    validate('if (!(unique(%s))) {', name)
                    error('must be unique')
                    validate('}')
                    if (type !== 'array') validate('}')
                }

                if (node.enum) {
                    var complex = node.enum.some(function (e) {
                        return typeof e === 'object'
                    })

                    var compare = complex ?
                        function (e) {
                            return 'JSON.stringify(' + name + ')' + ' !== JSON.stringify(' + JSON.stringify(e) + ')'
                        } :
                        function (e) {
                            return name + ' !== ' + JSON.stringify(e)
                    }

                    validate('if (%s) {', node.enum.map(compare).join(' && ') || 'false')
                    error('must be an enum value')
                    validate('}')
                }

                if (node.dependencies) {
                    if (type !== 'object') validate('if (%s) {', types.object(name))

                    Object.keys(node.dependencies).forEach(function (key) {
                        var deps = node.dependencies[key]
                        if (typeof deps === 'string') deps = [deps]

                        var exists = function (k) {
                            return genobj(name, k) + ' !== undefined'
                    }

                        if (Array.isArray(deps)) {
                            validate('if (%s !== undefined && !(%s)) {', genobj(name, key), deps.map(exists).join(' && ') || 'true')
                            error('dependencies not set')
                            validate('}')
                        }
                        if (typeof deps === 'object') {
                            validate('if (%s !== undefined) {', genobj(name, key))
                            visit(name, deps, reporter, filter)
                            validate('}')
                        }
                    })

                    if (type !== 'object') validate('}')
                }

                if (node.additionalProperties || node.additionalProperties === false) {
                    if (type !== 'object') validate('if (%s) {', types.object(name))

                    var i = genloop()
                    var keys = gensym('keys')

                    var toCompare = function (p) {
                        return keys + '[' + i + '] !== ' + JSON.stringify(p)
                }

                    var toTest = function (p) {
                        return '!' + patterns(p) + '.test(' + keys + '[' + i + '])'
                    }

                    var additionalProp = Object.keys(properties || {}).map(toCompare)
                            .concat(Object.keys(node.patternProperties || {}).map(toTest))
                            .join(' && ') || 'true'

                    validate('var %s = Object.keys(%s)', keys, name)
                    ('for (var %s = 0; %s < %s.length; %s++) {', i, i, keys, i)
                    ('if (%s) {', additionalProp)

                    if (node.additionalProperties === false) {
                        if (filter) validate('delete %s', name + '[' + keys + '[' + i + ']]')
                        error('has additional properties', null, JSON.stringify(name + '.') + ' + ' + keys + '[' + i + ']')
                    } else {
                        visit(name + '[' + keys + '[' + i + ']]', node.additionalProperties, reporter, filter)
                    }

                    validate
                    ('}')
                    ('}')

                    if (type !== 'object') validate('}')
                }

                if (node.$ref) {
                    var sub = get(root, opts && opts.schemas || {}, node.$ref)
                    if (sub) {
                        var fn = cache[node.$ref]
                        if (!fn) {
                            cache[node.$ref] = function proxy(data) {
                                return fn(data)
                        }
                            fn = compile(sub, cache, root, false, opts)
                    }
                        var n = gensym('ref')
                        scope[n] = fn
                        validate('if (!(%s(%s))) {', n, name)
                        error('referenced schema does not match')
                        validate('}')
                }
                }

                if (node.not) {
                    var prev = gensym('prev')
                    validate('var %s = errors', prev)
                    visit(name, node.not, false, filter)
                    validate('if (%s === errors) {', prev)
                    error('negative schema matches')
                    validate('} else {')
                    ('errors = %s', prev)
                    ('}')
                }

                if (node.items && !tuple) {
                    if (type !== 'array') validate('if (%s) {', types.array(name))

                    var i = genloop()
                    validate('for (var %s = 0; %s < %s.length; %s++) {', i, i, name, i)
                    visit(name + '[' + i + ']', node.items, reporter, filter)
                    validate('}')

                    if (type !== 'array') validate('}')
                }

                if (node.patternProperties) {
                    if (type !== 'object') validate('if (%s) {', types.object(name))
                    var keys = gensym('keys')
                    var i = genloop()
                    validate
                    ('var %s = Object.keys(%s)', keys, name)
                    ('for (var %s = 0; %s < %s.length; %s++) {', i, i, keys, i)

                    Object.keys(node.patternProperties).forEach(function (key) {
                        var p = patterns(key)
                        validate('if (%s.test(%s)) {', p, keys + '[' + i + ']')
                        visit(name + '[' + keys + '[' + i + ']]', node.patternProperties[key], reporter, filter)
                        validate('}')
                    })

                    validate('}')
                    if (type !== 'object') validate('}')
                }

                if (node.pattern) {
                    var p = patterns(node.pattern)
                    if (type !== 'string') validate('if (%s) {', types.string(name))
                    validate('if (!(%s.test(%s))) {', p, name)
                    error('pattern mismatch')
                    validate('}')
                    if (type !== 'string') validate('}')
                }

                if (node.allOf) {
                    node.allOf.forEach(function (sch) {
                        visit(name, sch, reporter, filter)
                    })
                }

                if (node.anyOf && node.anyOf.length) {
                    var prev = gensym('prev')

                    node.anyOf.forEach(function (sch, i) {
                        if (i === 0) {
                            validate('var %s = errors', prev)
                        } else {
                            validate('if (errors !== %s) {', prev)
                            ('errors = %s', prev)
                        }
                        visit(name, sch, false, false)
                    })
                    node.anyOf.forEach(function (sch, i) {
                        if (i) validate('}')
                    })
                    validate('if (%s !== errors) {', prev)
                    error('no schemas match')
                    validate('}')
                }

                if (node.oneOf && node.oneOf.length) {
                    var prev = gensym('prev')
                    var passes = gensym('passes')

                    validate
                    ('var %s = errors', prev)
                    ('var %s = 0', passes)

                    node.oneOf.forEach(function (sch, i) {
                        visit(name, sch, false, false)
                    validate('if (%s === errors) {', prev)
                    ('%s++', passes)
                    ('} else {')
                    ('errors = %s', prev)
                    ('}')
                    })

                    validate('if (%s !== 1) {', passes)
                    error('no (or more than one) schemas match')
                    validate('}')
                }

                if (node.multipleOf !== undefined) {
                    if (type !== 'number' && type !== 'integer') validate('if (%s) {', types.number(name))

                    var factor = ((node.multipleOf | 0) !== node.multipleOf) ? Math.pow(10, node.multipleOf.toString().split('.').pop().length) : 1
                    if (factor > 1) validate('if ((%d*%s) % %d) {', factor, name, factor * node.multipleOf)
                    else validate('if (%s % %d) {', name, node.multipleOf)

                    error('has a remainder')
                    validate('}')

                    if (type !== 'number' && type !== 'integer') validate('}')
                }

                if (node.maxProperties !== undefined) {
                    if (type !== 'object') validate('if (%s) {', types.object(name))

                    validate('if (Object.keys(%s).length > %d) {', name, node.maxProperties)
                    error('has more properties than allowed')
                    validate('}')

                    if (type !== 'object') validate('}')
                }

                if (node.minProperties !== undefined) {
                    if (type !== 'object') validate('if (%s) {', types.object(name))

                    validate('if (Object.keys(%s).length < %d) {', name, node.minProperties)
                    error('has less properties than allowed')
                    validate('}')

                    if (type !== 'object') validate('}')
                }

                if (node.maxItems !== undefined) {
                    if (type !== 'array') validate('if (%s) {', types.array(name))

                    validate('if (%s.length > %d) {', name, node.maxItems)
                    error('has more items than allowed')
                    validate('}')

                    if (type !== 'array') validate('}')
                }

                if (node.minItems !== undefined) {
                    if (type !== 'array') validate('if (%s) {', types.array(name))

                    validate('if (%s.length < %d) {', name, node.minItems)
                    error('has less items than allowed')
                    validate('}')

                    if (type !== 'array') validate('}')
                }

                if (node.maxLength !== undefined) {
                    if (type !== 'string') validate('if (%s) {', types.string(name))

                    validate('if (%s.length > %d) {', name, node.maxLength)
                    error('has longer length than allowed')
                    validate('}')

                    if (type !== 'string') validate('}')
                }

                if (node.minLength !== undefined) {
                    if (type !== 'string') validate('if (%s) {', types.string(name))

                    validate('if (%s.length < %d) {', name, node.minLength)
                    error('has less length than allowed')
                    validate('}')

                    if (type !== 'string') validate('}')
                }

                if (node.minimum !== undefined) {
                    validate('if (%s %s %d) {', name, node.exclusiveMinimum ? '<=' : '<', node.minimum)
                    error('is less than minimum')
                    validate('}')
                }

                if (node.maximum !== undefined) {
                    validate('if (%s %s %d) {', name, node.exclusiveMaximum ? '>=' : '>', node.maximum)
                    error('is more than maximum')
                    validate('}')
                }

                if (properties) {
                    Object.keys(properties).forEach(function (p) {
                        if (Array.isArray(type) && type.indexOf('null') !== -1) validate('if (%s !== null) {', name)

                        visit(genobj(name, p), properties[p], reporter, filter)

                        if (Array.isArray(type) && type.indexOf('null') !== -1) validate('}')
                    })
                }

                while (indent--) validate('}')
            }

            var validate = genfun
            ('function validate(data) {')
            ('validate.errors = null')
            ('var errors = 0')

            visit('data', schema, reporter, opts && opts.filter)

            validate
            ('return errors === 0')
            ('}')

            validate = validate.toFunction(scope)
            validate.errors = null

            if (Object.defineProperty) {
                Object.defineProperty(validate, 'error', {
                    get: function () {
                        if (!validate.errors) return ''
                        return validate.errors.map(function (err) {
                            return err.field + ' ' + err.message;
                        }).join('\n')
                }
                })
            }

            validate.toJSON = function () {
                return schema
            }

            return validate
        }

        module.exports = function (schema, opts) {
            if (typeof schema === 'string') schema = JSON.parse(schema)
            return compile(schema, {}, schema, true, opts)
        }

        module.exports.filter = function (schema, opts) {
            var validate = module.exports(schema, xtend(opts, {filter: true}))
            return function (sch) {
                validate(sch)
                return sch
            }
        }

    }, {"./formats": 31, "generate-function": 33, "generate-object-property": 34, "jsonpointer": 36, "xtend": 37}],
    33: [function (require, module, exports) {
        var util = require('util')

        var INDENT_START = /[\{\[]/
        var INDENT_END = /[\}\]]/

        module.exports = function () {
            var lines = []
            var indent = 0

            var push = function (str) {
                var spaces = ''
                while (spaces.length < indent * 2) spaces += '  '
                lines.push(spaces + str)
        }

            var line = function (fmt) {
                if (!fmt) return line

                if (INDENT_END.test(fmt.trim()[0]) && INDENT_START.test(fmt[fmt.length - 1])) {
                    indent--
                    push(util.format.apply(util, arguments))
                    indent++
                    return line
            }
                if (INDENT_START.test(fmt[fmt.length - 1])) {
                    push(util.format.apply(util, arguments))
                    indent++
                    return line
            }
                if (INDENT_END.test(fmt.trim()[0])) {
                    indent--
                push(util.format.apply(util, arguments))
                return line
            }

                push(util.format.apply(util, arguments))
            return line
        }

            line.toString = function () {
                return lines.join('\n')
        }

            line.toFunction = function (scope) {
                var src = 'return (' + line.toString() + ')'

                var keys = Object.keys(scope || {}).map(function (key) {
                    return key
                })

                var vals = keys.map(function (key) {
                    return scope[key]
                })

                return Function.apply(null, keys.concat(src)).apply(null, vals)
        }

            if (arguments.length) line.apply(null, arguments)

            return line
        }

    }, {"util": 114}],
    34: [function (require, module, exports) {
        var isProperty = require('is-property')

        var gen = function (obj, prop) {
            return isProperty(prop) ? obj + '.' + prop : obj + '[' + JSON.stringify(prop) + ']'
        }

        gen.valid = isProperty
        gen.property = function (prop) {
            return isProperty(prop) ? prop : JSON.stringify(prop)
        }

        module.exports = gen

    }, {"is-property": 35}],
    35: [function (require, module, exports) {
        "use strict"
        function isProperty(str) {
            return /^[$A-Z\_a-z\xaa\xb5\xba\xc0-\xd6\xd8-\xf6\xf8-\u02c1\u02c6-\u02d1\u02e0-\u02e4\u02ec\u02ee\u0370-\u0374\u0376\u0377\u037a-\u037d\u0386\u0388-\u038a\u038c\u038e-\u03a1\u03a3-\u03f5\u03f7-\u0481\u048a-\u0527\u0531-\u0556\u0559\u0561-\u0587\u05d0-\u05ea\u05f0-\u05f2\u0620-\u064a\u066e\u066f\u0671-\u06d3\u06d5\u06e5\u06e6\u06ee\u06ef\u06fa-\u06fc\u06ff\u0710\u0712-\u072f\u074d-\u07a5\u07b1\u07ca-\u07ea\u07f4\u07f5\u07fa\u0800-\u0815\u081a\u0824\u0828\u0840-\u0858\u08a0\u08a2-\u08ac\u0904-\u0939\u093d\u0950\u0958-\u0961\u0971-\u0977\u0979-\u097f\u0985-\u098c\u098f\u0990\u0993-\u09a8\u09aa-\u09b0\u09b2\u09b6-\u09b9\u09bd\u09ce\u09dc\u09dd\u09df-\u09e1\u09f0\u09f1\u0a05-\u0a0a\u0a0f\u0a10\u0a13-\u0a28\u0a2a-\u0a30\u0a32\u0a33\u0a35\u0a36\u0a38\u0a39\u0a59-\u0a5c\u0a5e\u0a72-\u0a74\u0a85-\u0a8d\u0a8f-\u0a91\u0a93-\u0aa8\u0aaa-\u0ab0\u0ab2\u0ab3\u0ab5-\u0ab9\u0abd\u0ad0\u0ae0\u0ae1\u0b05-\u0b0c\u0b0f\u0b10\u0b13-\u0b28\u0b2a-\u0b30\u0b32\u0b33\u0b35-\u0b39\u0b3d\u0b5c\u0b5d\u0b5f-\u0b61\u0b71\u0b83\u0b85-\u0b8a\u0b8e-\u0b90\u0b92-\u0b95\u0b99\u0b9a\u0b9c\u0b9e\u0b9f\u0ba3\u0ba4\u0ba8-\u0baa\u0bae-\u0bb9\u0bd0\u0c05-\u0c0c\u0c0e-\u0c10\u0c12-\u0c28\u0c2a-\u0c33\u0c35-\u0c39\u0c3d\u0c58\u0c59\u0c60\u0c61\u0c85-\u0c8c\u0c8e-\u0c90\u0c92-\u0ca8\u0caa-\u0cb3\u0cb5-\u0cb9\u0cbd\u0cde\u0ce0\u0ce1\u0cf1\u0cf2\u0d05-\u0d0c\u0d0e-\u0d10\u0d12-\u0d3a\u0d3d\u0d4e\u0d60\u0d61\u0d7a-\u0d7f\u0d85-\u0d96\u0d9a-\u0db1\u0db3-\u0dbb\u0dbd\u0dc0-\u0dc6\u0e01-\u0e30\u0e32\u0e33\u0e40-\u0e46\u0e81\u0e82\u0e84\u0e87\u0e88\u0e8a\u0e8d\u0e94-\u0e97\u0e99-\u0e9f\u0ea1-\u0ea3\u0ea5\u0ea7\u0eaa\u0eab\u0ead-\u0eb0\u0eb2\u0eb3\u0ebd\u0ec0-\u0ec4\u0ec6\u0edc-\u0edf\u0f00\u0f40-\u0f47\u0f49-\u0f6c\u0f88-\u0f8c\u1000-\u102a\u103f\u1050-\u1055\u105a-\u105d\u1061\u1065\u1066\u106e-\u1070\u1075-\u1081\u108e\u10a0-\u10c5\u10c7\u10cd\u10d0-\u10fa\u10fc-\u1248\u124a-\u124d\u1250-\u1256\u1258\u125a-\u125d\u1260-\u1288\u128a-\u128d\u1290-\u12b0\u12b2-\u12b5\u12b8-\u12be\u12c0\u12c2-\u12c5\u12c8-\u12d6\u12d8-\u1310\u1312-\u1315\u1318-\u135a\u1380-\u138f\u13a0-\u13f4\u1401-\u166c\u166f-\u167f\u1681-\u169a\u16a0-\u16ea\u16ee-\u16f0\u1700-\u170c\u170e-\u1711\u1720-\u1731\u1740-\u1751\u1760-\u176c\u176e-\u1770\u1780-\u17b3\u17d7\u17dc\u1820-\u1877\u1880-\u18a8\u18aa\u18b0-\u18f5\u1900-\u191c\u1950-\u196d\u1970-\u1974\u1980-\u19ab\u19c1-\u19c7\u1a00-\u1a16\u1a20-\u1a54\u1aa7\u1b05-\u1b33\u1b45-\u1b4b\u1b83-\u1ba0\u1bae\u1baf\u1bba-\u1be5\u1c00-\u1c23\u1c4d-\u1c4f\u1c5a-\u1c7d\u1ce9-\u1cec\u1cee-\u1cf1\u1cf5\u1cf6\u1d00-\u1dbf\u1e00-\u1f15\u1f18-\u1f1d\u1f20-\u1f45\u1f48-\u1f4d\u1f50-\u1f57\u1f59\u1f5b\u1f5d\u1f5f-\u1f7d\u1f80-\u1fb4\u1fb6-\u1fbc\u1fbe\u1fc2-\u1fc4\u1fc6-\u1fcc\u1fd0-\u1fd3\u1fd6-\u1fdb\u1fe0-\u1fec\u1ff2-\u1ff4\u1ff6-\u1ffc\u2071\u207f\u2090-\u209c\u2102\u2107\u210a-\u2113\u2115\u2119-\u211d\u2124\u2126\u2128\u212a-\u212d\u212f-\u2139\u213c-\u213f\u2145-\u2149\u214e\u2160-\u2188\u2c00-\u2c2e\u2c30-\u2c5e\u2c60-\u2ce4\u2ceb-\u2cee\u2cf2\u2cf3\u2d00-\u2d25\u2d27\u2d2d\u2d30-\u2d67\u2d6f\u2d80-\u2d96\u2da0-\u2da6\u2da8-\u2dae\u2db0-\u2db6\u2db8-\u2dbe\u2dc0-\u2dc6\u2dc8-\u2dce\u2dd0-\u2dd6\u2dd8-\u2dde\u2e2f\u3005-\u3007\u3021-\u3029\u3031-\u3035\u3038-\u303c\u3041-\u3096\u309d-\u309f\u30a1-\u30fa\u30fc-\u30ff\u3105-\u312d\u3131-\u318e\u31a0-\u31ba\u31f0-\u31ff\u3400-\u4db5\u4e00-\u9fcc\ua000-\ua48c\ua4d0-\ua4fd\ua500-\ua60c\ua610-\ua61f\ua62a\ua62b\ua640-\ua66e\ua67f-\ua697\ua6a0-\ua6ef\ua717-\ua71f\ua722-\ua788\ua78b-\ua78e\ua790-\ua793\ua7a0-\ua7aa\ua7f8-\ua801\ua803-\ua805\ua807-\ua80a\ua80c-\ua822\ua840-\ua873\ua882-\ua8b3\ua8f2-\ua8f7\ua8fb\ua90a-\ua925\ua930-\ua946\ua960-\ua97c\ua984-\ua9b2\ua9cf\uaa00-\uaa28\uaa40-\uaa42\uaa44-\uaa4b\uaa60-\uaa76\uaa7a\uaa80-\uaaaf\uaab1\uaab5\uaab6\uaab9-\uaabd\uaac0\uaac2\uaadb-\uaadd\uaae0-\uaaea\uaaf2-\uaaf4\uab01-\uab06\uab09-\uab0e\uab11-\uab16\uab20-\uab26\uab28-\uab2e\uabc0-\uabe2\uac00-\ud7a3\ud7b0-\ud7c6\ud7cb-\ud7fb\uf900-\ufa6d\ufa70-\ufad9\ufb00-\ufb06\ufb13-\ufb17\ufb1d\ufb1f-\ufb28\ufb2a-\ufb36\ufb38-\ufb3c\ufb3e\ufb40\ufb41\ufb43\ufb44\ufb46-\ufbb1\ufbd3-\ufd3d\ufd50-\ufd8f\ufd92-\ufdc7\ufdf0-\ufdfb\ufe70-\ufe74\ufe76-\ufefc\uff21-\uff3a\uff41-\uff5a\uff66-\uffbe\uffc2-\uffc7\uffca-\uffcf\uffd2-\uffd7\uffda-\uffdc][$A-Z\_a-z\xaa\xb5\xba\xc0-\xd6\xd8-\xf6\xf8-\u02c1\u02c6-\u02d1\u02e0-\u02e4\u02ec\u02ee\u0370-\u0374\u0376\u0377\u037a-\u037d\u0386\u0388-\u038a\u038c\u038e-\u03a1\u03a3-\u03f5\u03f7-\u0481\u048a-\u0527\u0531-\u0556\u0559\u0561-\u0587\u05d0-\u05ea\u05f0-\u05f2\u0620-\u064a\u066e\u066f\u0671-\u06d3\u06d5\u06e5\u06e6\u06ee\u06ef\u06fa-\u06fc\u06ff\u0710\u0712-\u072f\u074d-\u07a5\u07b1\u07ca-\u07ea\u07f4\u07f5\u07fa\u0800-\u0815\u081a\u0824\u0828\u0840-\u0858\u08a0\u08a2-\u08ac\u0904-\u0939\u093d\u0950\u0958-\u0961\u0971-\u0977\u0979-\u097f\u0985-\u098c\u098f\u0990\u0993-\u09a8\u09aa-\u09b0\u09b2\u09b6-\u09b9\u09bd\u09ce\u09dc\u09dd\u09df-\u09e1\u09f0\u09f1\u0a05-\u0a0a\u0a0f\u0a10\u0a13-\u0a28\u0a2a-\u0a30\u0a32\u0a33\u0a35\u0a36\u0a38\u0a39\u0a59-\u0a5c\u0a5e\u0a72-\u0a74\u0a85-\u0a8d\u0a8f-\u0a91\u0a93-\u0aa8\u0aaa-\u0ab0\u0ab2\u0ab3\u0ab5-\u0ab9\u0abd\u0ad0\u0ae0\u0ae1\u0b05-\u0b0c\u0b0f\u0b10\u0b13-\u0b28\u0b2a-\u0b30\u0b32\u0b33\u0b35-\u0b39\u0b3d\u0b5c\u0b5d\u0b5f-\u0b61\u0b71\u0b83\u0b85-\u0b8a\u0b8e-\u0b90\u0b92-\u0b95\u0b99\u0b9a\u0b9c\u0b9e\u0b9f\u0ba3\u0ba4\u0ba8-\u0baa\u0bae-\u0bb9\u0bd0\u0c05-\u0c0c\u0c0e-\u0c10\u0c12-\u0c28\u0c2a-\u0c33\u0c35-\u0c39\u0c3d\u0c58\u0c59\u0c60\u0c61\u0c85-\u0c8c\u0c8e-\u0c90\u0c92-\u0ca8\u0caa-\u0cb3\u0cb5-\u0cb9\u0cbd\u0cde\u0ce0\u0ce1\u0cf1\u0cf2\u0d05-\u0d0c\u0d0e-\u0d10\u0d12-\u0d3a\u0d3d\u0d4e\u0d60\u0d61\u0d7a-\u0d7f\u0d85-\u0d96\u0d9a-\u0db1\u0db3-\u0dbb\u0dbd\u0dc0-\u0dc6\u0e01-\u0e30\u0e32\u0e33\u0e40-\u0e46\u0e81\u0e82\u0e84\u0e87\u0e88\u0e8a\u0e8d\u0e94-\u0e97\u0e99-\u0e9f\u0ea1-\u0ea3\u0ea5\u0ea7\u0eaa\u0eab\u0ead-\u0eb0\u0eb2\u0eb3\u0ebd\u0ec0-\u0ec4\u0ec6\u0edc-\u0edf\u0f00\u0f40-\u0f47\u0f49-\u0f6c\u0f88-\u0f8c\u1000-\u102a\u103f\u1050-\u1055\u105a-\u105d\u1061\u1065\u1066\u106e-\u1070\u1075-\u1081\u108e\u10a0-\u10c5\u10c7\u10cd\u10d0-\u10fa\u10fc-\u1248\u124a-\u124d\u1250-\u1256\u1258\u125a-\u125d\u1260-\u1288\u128a-\u128d\u1290-\u12b0\u12b2-\u12b5\u12b8-\u12be\u12c0\u12c2-\u12c5\u12c8-\u12d6\u12d8-\u1310\u1312-\u1315\u1318-\u135a\u1380-\u138f\u13a0-\u13f4\u1401-\u166c\u166f-\u167f\u1681-\u169a\u16a0-\u16ea\u16ee-\u16f0\u1700-\u170c\u170e-\u1711\u1720-\u1731\u1740-\u1751\u1760-\u176c\u176e-\u1770\u1780-\u17b3\u17d7\u17dc\u1820-\u1877\u1880-\u18a8\u18aa\u18b0-\u18f5\u1900-\u191c\u1950-\u196d\u1970-\u1974\u1980-\u19ab\u19c1-\u19c7\u1a00-\u1a16\u1a20-\u1a54\u1aa7\u1b05-\u1b33\u1b45-\u1b4b\u1b83-\u1ba0\u1bae\u1baf\u1bba-\u1be5\u1c00-\u1c23\u1c4d-\u1c4f\u1c5a-\u1c7d\u1ce9-\u1cec\u1cee-\u1cf1\u1cf5\u1cf6\u1d00-\u1dbf\u1e00-\u1f15\u1f18-\u1f1d\u1f20-\u1f45\u1f48-\u1f4d\u1f50-\u1f57\u1f59\u1f5b\u1f5d\u1f5f-\u1f7d\u1f80-\u1fb4\u1fb6-\u1fbc\u1fbe\u1fc2-\u1fc4\u1fc6-\u1fcc\u1fd0-\u1fd3\u1fd6-\u1fdb\u1fe0-\u1fec\u1ff2-\u1ff4\u1ff6-\u1ffc\u2071\u207f\u2090-\u209c\u2102\u2107\u210a-\u2113\u2115\u2119-\u211d\u2124\u2126\u2128\u212a-\u212d\u212f-\u2139\u213c-\u213f\u2145-\u2149\u214e\u2160-\u2188\u2c00-\u2c2e\u2c30-\u2c5e\u2c60-\u2ce4\u2ceb-\u2cee\u2cf2\u2cf3\u2d00-\u2d25\u2d27\u2d2d\u2d30-\u2d67\u2d6f\u2d80-\u2d96\u2da0-\u2da6\u2da8-\u2dae\u2db0-\u2db6\u2db8-\u2dbe\u2dc0-\u2dc6\u2dc8-\u2dce\u2dd0-\u2dd6\u2dd8-\u2dde\u2e2f\u3005-\u3007\u3021-\u3029\u3031-\u3035\u3038-\u303c\u3041-\u3096\u309d-\u309f\u30a1-\u30fa\u30fc-\u30ff\u3105-\u312d\u3131-\u318e\u31a0-\u31ba\u31f0-\u31ff\u3400-\u4db5\u4e00-\u9fcc\ua000-\ua48c\ua4d0-\ua4fd\ua500-\ua60c\ua610-\ua61f\ua62a\ua62b\ua640-\ua66e\ua67f-\ua697\ua6a0-\ua6ef\ua717-\ua71f\ua722-\ua788\ua78b-\ua78e\ua790-\ua793\ua7a0-\ua7aa\ua7f8-\ua801\ua803-\ua805\ua807-\ua80a\ua80c-\ua822\ua840-\ua873\ua882-\ua8b3\ua8f2-\ua8f7\ua8fb\ua90a-\ua925\ua930-\ua946\ua960-\ua97c\ua984-\ua9b2\ua9cf\uaa00-\uaa28\uaa40-\uaa42\uaa44-\uaa4b\uaa60-\uaa76\uaa7a\uaa80-\uaaaf\uaab1\uaab5\uaab6\uaab9-\uaabd\uaac0\uaac2\uaadb-\uaadd\uaae0-\uaaea\uaaf2-\uaaf4\uab01-\uab06\uab09-\uab0e\uab11-\uab16\uab20-\uab26\uab28-\uab2e\uabc0-\uabe2\uac00-\ud7a3\ud7b0-\ud7c6\ud7cb-\ud7fb\uf900-\ufa6d\ufa70-\ufad9\ufb00-\ufb06\ufb13-\ufb17\ufb1d\ufb1f-\ufb28\ufb2a-\ufb36\ufb38-\ufb3c\ufb3e\ufb40\ufb41\ufb43\ufb44\ufb46-\ufbb1\ufbd3-\ufd3d\ufd50-\ufd8f\ufd92-\ufdc7\ufdf0-\ufdfb\ufe70-\ufe74\ufe76-\ufefc\uff21-\uff3a\uff41-\uff5a\uff66-\uffbe\uffc2-\uffc7\uffca-\uffcf\uffd2-\uffd7\uffda-\uffdc0-9\u0300-\u036f\u0483-\u0487\u0591-\u05bd\u05bf\u05c1\u05c2\u05c4\u05c5\u05c7\u0610-\u061a\u064b-\u0669\u0670\u06d6-\u06dc\u06df-\u06e4\u06e7\u06e8\u06ea-\u06ed\u06f0-\u06f9\u0711\u0730-\u074a\u07a6-\u07b0\u07c0-\u07c9\u07eb-\u07f3\u0816-\u0819\u081b-\u0823\u0825-\u0827\u0829-\u082d\u0859-\u085b\u08e4-\u08fe\u0900-\u0903\u093a-\u093c\u093e-\u094f\u0951-\u0957\u0962\u0963\u0966-\u096f\u0981-\u0983\u09bc\u09be-\u09c4\u09c7\u09c8\u09cb-\u09cd\u09d7\u09e2\u09e3\u09e6-\u09ef\u0a01-\u0a03\u0a3c\u0a3e-\u0a42\u0a47\u0a48\u0a4b-\u0a4d\u0a51\u0a66-\u0a71\u0a75\u0a81-\u0a83\u0abc\u0abe-\u0ac5\u0ac7-\u0ac9\u0acb-\u0acd\u0ae2\u0ae3\u0ae6-\u0aef\u0b01-\u0b03\u0b3c\u0b3e-\u0b44\u0b47\u0b48\u0b4b-\u0b4d\u0b56\u0b57\u0b62\u0b63\u0b66-\u0b6f\u0b82\u0bbe-\u0bc2\u0bc6-\u0bc8\u0bca-\u0bcd\u0bd7\u0be6-\u0bef\u0c01-\u0c03\u0c3e-\u0c44\u0c46-\u0c48\u0c4a-\u0c4d\u0c55\u0c56\u0c62\u0c63\u0c66-\u0c6f\u0c82\u0c83\u0cbc\u0cbe-\u0cc4\u0cc6-\u0cc8\u0cca-\u0ccd\u0cd5\u0cd6\u0ce2\u0ce3\u0ce6-\u0cef\u0d02\u0d03\u0d3e-\u0d44\u0d46-\u0d48\u0d4a-\u0d4d\u0d57\u0d62\u0d63\u0d66-\u0d6f\u0d82\u0d83\u0dca\u0dcf-\u0dd4\u0dd6\u0dd8-\u0ddf\u0df2\u0df3\u0e31\u0e34-\u0e3a\u0e47-\u0e4e\u0e50-\u0e59\u0eb1\u0eb4-\u0eb9\u0ebb\u0ebc\u0ec8-\u0ecd\u0ed0-\u0ed9\u0f18\u0f19\u0f20-\u0f29\u0f35\u0f37\u0f39\u0f3e\u0f3f\u0f71-\u0f84\u0f86\u0f87\u0f8d-\u0f97\u0f99-\u0fbc\u0fc6\u102b-\u103e\u1040-\u1049\u1056-\u1059\u105e-\u1060\u1062-\u1064\u1067-\u106d\u1071-\u1074\u1082-\u108d\u108f-\u109d\u135d-\u135f\u1712-\u1714\u1732-\u1734\u1752\u1753\u1772\u1773\u17b4-\u17d3\u17dd\u17e0-\u17e9\u180b-\u180d\u1810-\u1819\u18a9\u1920-\u192b\u1930-\u193b\u1946-\u194f\u19b0-\u19c0\u19c8\u19c9\u19d0-\u19d9\u1a17-\u1a1b\u1a55-\u1a5e\u1a60-\u1a7c\u1a7f-\u1a89\u1a90-\u1a99\u1b00-\u1b04\u1b34-\u1b44\u1b50-\u1b59\u1b6b-\u1b73\u1b80-\u1b82\u1ba1-\u1bad\u1bb0-\u1bb9\u1be6-\u1bf3\u1c24-\u1c37\u1c40-\u1c49\u1c50-\u1c59\u1cd0-\u1cd2\u1cd4-\u1ce8\u1ced\u1cf2-\u1cf4\u1dc0-\u1de6\u1dfc-\u1dff\u200c\u200d\u203f\u2040\u2054\u20d0-\u20dc\u20e1\u20e5-\u20f0\u2cef-\u2cf1\u2d7f\u2de0-\u2dff\u302a-\u302f\u3099\u309a\ua620-\ua629\ua66f\ua674-\ua67d\ua69f\ua6f0\ua6f1\ua802\ua806\ua80b\ua823-\ua827\ua880\ua881\ua8b4-\ua8c4\ua8d0-\ua8d9\ua8e0-\ua8f1\ua900-\ua909\ua926-\ua92d\ua947-\ua953\ua980-\ua983\ua9b3-\ua9c0\ua9d0-\ua9d9\uaa29-\uaa36\uaa43\uaa4c\uaa4d\uaa50-\uaa59\uaa7b\uaab0\uaab2-\uaab4\uaab7\uaab8\uaabe\uaabf\uaac1\uaaeb-\uaaef\uaaf5\uaaf6\uabe3-\uabea\uabec\uabed\uabf0-\uabf9\ufb1e\ufe00-\ufe0f\ufe20-\ufe26\ufe33\ufe34\ufe4d-\ufe4f\uff10-\uff19\uff3f]*$/.test(str)
        }

        module.exports = isProperty
    }, {}],
    36: [function (require, module, exports) {
        var untilde = function (str) {
            return str.replace(/~./g, function (m) {
                switch (m) {
                    case "~0":
                        return "~";
                    case "~1":
                        return "/";
            }
                throw new Error("Invalid tilde escape: " + m);
            });
        }

        var traverse = function (obj, pointer, value) {
            // assert(isArray(pointer))
            var part = untilde(pointer.shift());
            if (!obj.hasOwnProperty(part)) {
                return null;
        }
            if (pointer.length !== 0) { // keep traversin!
                return traverse(obj[part], pointer, value);
            }
            // we're done
            if (typeof value === "undefined") {
                // just reading
                return obj[part];
            }
            // set new value, return old value
            var old_value = obj[part];
            if (value === null) {
                delete obj[part];
            } else {
                obj[part] = value;
            }
            return old_value;
        }

        var validate_input = function (obj, pointer) {
            if (typeof obj !== "object") {
                throw new Error("Invalid input object.");
        }

            if (pointer === "") {
                return [];
        }

            if (!pointer) {
                throw new Error("Invalid JSON pointer.");
            }

            pointer = pointer.split("/");
            var first = pointer.shift();
            if (first !== "") {
                throw new Error("Invalid JSON pointer.");
            }

            return pointer;
        }

        var get = function (obj, pointer) {
            pointer = validate_input(obj, pointer);
            if (pointer.length === 0) {
                return obj;
            }
            return traverse(obj, pointer);
        }

        var set = function (obj, pointer, value) {
            pointer = validate_input(obj, pointer);
            if (pointer.length === 0) {
                throw new Error("Invalid JSON pointer for set.")
            }
            return traverse(obj, pointer, value);
        }

        exports.get = get
        exports.set = set

    }, {}],
    37: [function (require, module, exports) {
        module.exports = extend

        var hasOwnProperty = Object.prototype.hasOwnProperty;

        function extend() {
            var target = {}

            for (var i = 0; i < arguments.length; i++) {
                var source = arguments[i]

                for (var key in source) {
                    if (hasOwnProperty.call(source, key)) {
                        target[key] = source[key]
                    }
            }
            }

            return target
        }

    }, {}],
    38: [function (require, module, exports) {
        'use strict'

        var util = require('util')

        /**
         * Helper object to format and aggragate lines of code.
         * Lines are aggregated in a `code` array, and need to be joined to obtain a proper code snippet.
         *
         * @class
         *
         * @param {string} indentation Desired indentation character for aggregated lines of code
         * @param {string} join Desired character to join each line of code
         */
        var CodeBuilder = function (indentation, join) {
            this.code = []
            this.indentation = indentation
            this.lineJoin = join ? join : '\n'
        }

        /**
         * Add given indentation level to given string and format the string (variadic)
         * @param {number} [indentationLevel=0] - Desired level of indentation for this line
         * @param {string} line - Line of code. Can contain formatting placeholders
         * @param {...anyobject} - Parameter to bind to `line`'s formatting placeholders
         * @return {string}
         *
         * @example
         *   var builder = CodeBuilder('\t')
         *
         *   builder.buildLine('console.log("hello world")')
         *   // returns: 'console.log("hello world")'
         *
         *   builder.buildLine(2, 'console.log("hello world")')
         *   // returns: 'console.log("\t\thello world")'
         *
         *   builder.buildLine(2, 'console.log("%s %s")', 'hello', 'world')
         *   // returns: 'console.log("\t\thello world")'
         */
        CodeBuilder.prototype.buildLine = function (indentationLevel, line) {
            var lineIndentation = ''
            var slice = 2
            if (Object.prototype.toString.call(indentationLevel) === '[object String]') {
                slice = 1
                line = indentationLevel
                indentationLevel = 0
            } else if (indentationLevel === null) {
                return null
        }

            while (indentationLevel) {
                lineIndentation += this.indentation
                indentationLevel--
        }

            var format = Array.prototype.slice.call(arguments, slice, arguments.length)
            format.unshift(lineIndentation + line)

            return util.format.apply(this, format)
        }

        /**
         * Invoke buildLine() and add the line at the top of current lines
         * @param {number} [indentationLevel=0] Desired level of indentation for this line
         * @param {string} line Line of code
         * @return {this}
         */
        CodeBuilder.prototype.unshift = function () {
            this.code.unshift(this.buildLine.apply(this, arguments))

            return this
        }

        /**
         * Invoke buildLine() and add the line at the bottom of current lines
         * @param {number} [indentationLevel=0] Desired level of indentation for this line
         * @param {string} line Line of code
         * @return {this}
         */
        CodeBuilder.prototype.push = function () {
            this.code.push(this.buildLine.apply(this, arguments))

            return this
        }

        /**
         * Add an empty line at the end of current lines
         * @return {this}
         */
        CodeBuilder.prototype.blank = function () {
            this.code.push(null)

            return this
        }

        /**
         * Concatenate all current lines using the given lineJoin
         * @return {string}
         */
        CodeBuilder.prototype.join = function () {
            return this.code.join(this.lineJoin)
        }

        module.exports = CodeBuilder

    }, {"util": 114}],
    39: [function (require, module, exports) {
        'use strict'

        module.exports = function (obj, pair) {
            if (obj[pair.name] === undefined) {
                obj[pair.name] = pair.value
            return obj
        }

            // convert to array
            var arr = [
                obj[pair.name],
                pair.value
            ]

            obj[pair.name] = arr

            return obj
        }

    }, {}],
    40: [function (require, module, exports) {
        'use strict'

        var util = require('util')

        module.exports = {
            /**
             * Use 'strong quoting' using single quotes so that we only need
             * to deal with nested single quote characters.
             * http://wiki.bash-hackers.org/syntax/quoting#strong_quoting
             */
            quote: function (value) {
                var safe = /^[a-z0-9-_/.@%^=:]+$/i

                // Unless `value` is a simple shell-safe string, quote it.
                if (!safe.test(value)) {
                    return util.format('\'%s\'', value.replace(/'/g, "\'\\'\'"))
            }

                return value
            },

            escape: function (value) {
                return value.replace(/\r/g, '\\r').replace(/\n/g, '\\n')
        }
        }

    }, {"util": 114}],
    41: [function (require, module, exports) {
        'use strict'

        var debug = require('debug')('httpsnippet')
        var es = require('event-stream')
        var MultiPartForm = require('form-data')
        var qs = require('querystring')
        var reducer = require('./helpers/reducer')
        var targets = require('./targets')
        var url = require('url')
        var util = require('util')
        var validate = require('har-validator')

// constructor
        var HTTPSnippet = function (data) {
            var entries
            var self = this
            var input = util._extend({}, data)

            // prep the main container
            self.requests = []

            // is it har?
            if (input.log && input.log.entries) {
                entries = input.log.entries
            } else {
                entries = [{
                    request: input
                }]
            }

            entries.forEach(function (entry) {
                // add optional properties to make validation successful
                entry.request.httpVersion = entry.request.httpVersion || 'HTTP/1.1'
                entry.request.queryString = entry.request.queryString || []
                entry.request.headers = entry.request.headers || []
                entry.request.cookies = entry.request.cookies || []
                entry.request.postData = entry.request.postData || {}
                entry.request.postData.mimeType = entry.request.postData.mimeType || 'application/octet-stream'

                entry.request.bodySize = 0
                entry.request.headersSize = 0
                entry.request.postData.size = 0

                validate.request(entry.request, function (err, valid) {
                    if (!valid) {
                        throw err
                    }

                    self.requests.push(self.prepare(entry.request))
            })
            })
        }

        HTTPSnippet.prototype.prepare = function (request) {
            // construct utility properties
            request.queryObj = {}
            request.headersObj = {}
            request.cookiesObj = {}
            request.allHeaders = {}
            request.postData.jsonObj = false
            request.postData.paramsObj = false

            // construct query objects
            if (request.queryString && request.queryString.length) {
                debug('queryString found, constructing queryString pair map')

                request.queryObj = request.queryString.reduce(reducer, {})
        }

            // construct headers objects
            if (request.headers && request.headers.length) {
                // loweCase header keys
                request.headersObj = request.headers.reduceRight(function (headers, header) {
                    headers[header.name.toLowerCase()] = header.value
                    return headers
                }, {})
            }

            // construct headers objects
            if (request.cookies && request.cookies.length) {
                request.cookiesObj = request.cookies.reduceRight(function (cookies, cookie) {
                    cookies[cookie.name] = cookie.value
                    return cookies
                }, {})
            }

            // construct Cookie header
            var cookies = request.cookies.map(function (cookie) {
                return encodeURIComponent(cookie.name) + '=' + encodeURIComponent(cookie.value)
            })

            if (cookies.length) {
                request.allHeaders.cookie = cookies.join('; ')
            }

            switch (request.postData.mimeType) {
                case 'multipart/mixed':
                case 'multipart/related':
                case 'multipart/form-data':
                case 'multipart/alternative':
                    // reset values
                    request.postData.text = ''
                    request.postData.mimeType = 'multipart/form-data'

                    if (request.postData.params) {
                        var form = new MultiPartForm()

                        // easter egg
                        form._boundary = '---011000010111000001101001'

                        request.postData.params.forEach(function (param) {
                            form.append(param.name, param.value || '', {
                                filename: param.fileName || null,
                                contentType: param.contentType || null
                            })
                        })

                        es.pipe(es.map(function (data, cb) {
                            request.postData.text += data
                        }))

                        request.postData.boundary = form._boundary
                        request.headersObj['content-type'] = 'multipart/form-data; boundary=' + form._boundary
                    }
                    break

                case 'application/x-www-form-urlencoded':
                    if (!request.postData.params) {
                    request.postData.text = ''
                    } else {
                        request.postData.paramsObj = request.postData.params.reduce(reducer, {})

                        // always overwrite
                        request.postData.text = qs.stringify(request.postData.paramsObj)
                    }
                    break

                case 'text/json':
                case 'text/x-json':
                case 'application/json':
                case 'application/x-json':
                    request.postData.mimeType = 'application/json'

                    if (request.postData.text) {
                        try {
                            request.postData.jsonObj = JSON.parse(request.postData.text)
                        } catch (e) {
                            debug(e)

                            // force back to text/plain
                            // if headers have proper content-type value, then this should also work
                            request.postData.mimeType = 'text/plain'
                    }
                    }
                    break
            }

            // create allHeaders object
            request.allHeaders = util._extend(request.allHeaders, request.headersObj)

            // deconstruct the uri
            request.uriObj = url.parse(request.url, true, true)

            // merge all possible queryString values
            request.queryObj = util._extend(request.queryObj, request.uriObj.query)

            // reset uriObj values for a clean url
            request.uriObj.query = null
            request.uriObj.search = null
            request.uriObj.path = request.uriObj.pathname

            // keep the base url clean of queryString
            request.url = url.format(request.uriObj)

            // update the uri object
            request.uriObj.query = request.queryObj
            request.uriObj.search = qs.stringify(request.queryObj)

            if (request.uriObj.search) {
                request.uriObj.path = request.uriObj.pathname + '?' + request.uriObj.search
            }

            // construct a full url
            request.fullUrl = url.format(request.uriObj)

            return request
        }

        HTTPSnippet.prototype.convert = function (target, client, opts) {
            if (!opts && client) {
                opts = client
            }

            var func = this._matchTarget(target, client)

            if (func) {
                var results = this.requests.map(function (request) {
                    return func(request, opts)
                })

                return results.length === 1 ? results[0] : results
        }

            return false
        }

        HTTPSnippet.prototype._matchTarget = function (target, client) {
            // does it exist?
            if (!targets.hasOwnProperty(target)) {
            return false
        }

            // shorthand
            if (typeof client === 'string' && typeof targets[target][client] === 'function') {
                return targets[target][client]
        }

            // default target
            return targets[target][targets[target].info.default]
        }

// exports
        module.exports = HTTPSnippet

        module.exports.availableTargets = function () {
            return Object.keys(targets).map(function (key) {
                var target = util._extend({}, targets[key].info)
                var clients = Object.keys(targets[key])

                    .filter(function (prop) {
                        return !~['info', 'index'].indexOf(prop)
                    })

                    .map(function (client) {
                        return targets[key][client].info
                    })

                if (clients.length) {
                    target.clients = clients
                }

                return target
            })
        }

        module.exports.extname = function (target) {
            return targets[target] ? targets[target].info.extname : ''
        }

    }, {
        "./helpers/reducer": 39,
        "./targets": 48,
        "debug": 1,
        "event-stream": 4,
        "form-data": 12,
        "har-validator": 14,
        "querystring": 96,
        "url": 112,
        "util": 114
    }],
    42: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'c',
                title: 'C',
                extname: '.c',
                default: 'libcurl'
            },

            libcurl: require('./libcurl')
        }

    }, {"./libcurl": 43}],
    43: [function (require, module, exports) {
        'use strict'

        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var code = new CodeBuilder()

            code.push('CURL *hnd = curl_easy_init();')
                .blank()
                .push('curl_easy_setopt(hnd, CURLOPT_CUSTOMREQUEST, "%s");', source.method.toUpperCase())
                .push('curl_easy_setopt(hnd, CURLOPT_URL, "%s");', source.fullUrl)

            // Add headers, including the cookies
            var headers = Object.keys(source.headersObj)

            // construct headers
            if (headers.length) {
                code.blank()
                    .push('struct curl_slist *headers = NULL;')

                headers.forEach(function (key) {
                    code.push('headers = curl_slist_append(headers, "%s: %s");', key, source.headersObj[key])
                })

                code.push('curl_easy_setopt(hnd, CURLOPT_HTTPHEADER, headers);')
            }

            // construct cookies
            if (source.allHeaders.cookie) {
            code.blank()
                .push('curl_easy_setopt(hnd, CURLOPT_COOKIE, "%s");', source.allHeaders.cookie)
        }

            if (source.postData.text) {
                code.blank()
                    .push('curl_easy_setopt(hnd, CURLOPT_POSTFIELDS, %s);', JSON.stringify(source.postData.text))
        }

            code.blank()
                .push('CURLcode ret = curl_easy_perform(hnd);')

            return code.join()
        }

        module.exports.info = {
            key: 'libcurl',
            title: 'Libcurl',
            link: 'http://curl.haxx.se/libcurl/',
            description: 'Simple REST and HTTP API Client for C'
        }

    }, {"../../helpers/code-builder": 38}],
    44: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'csharp',
                title: 'C#',
                extname: '.cs',
                default: 'restsharp'
            },

            restsharp: require('./restsharp')
        }

    }, {"./restsharp": 45}],
    45: [function (require, module, exports) {
        'use strict'

        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var code = new CodeBuilder()
            var methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS']

            if (methods.indexOf(source.method.toUpperCase()) === -1) {
                return 'Method not supported'
            } else {
                code.push('var client = new RestClient("%s");', source.fullUrl)
                code.push('var request = new RestRequest(Method.%s);', source.method.toUpperCase())
            }

            // Add headers, including the cookies
            var headers = Object.keys(source.headersObj)

            // construct headers
            if (headers.length) {
                headers.forEach(function (key) {
                    code.push('request.AddHeader("%s", "%s");', key, source.headersObj[key])
                })
        }

            // construct cookies
            if (source.cookies.length) {
                source.cookies.forEach(function (cookie) {
                    code.push('request.AddCookie("%s", "%s");', cookie.name, cookie.value)
                })
        }

            if (source.postData.text) {
                code.push('request.AddParameter("%s", %s, ParameterType.RequestBody);', source.allHeaders['content-type'], JSON.stringify(source.postData.text))
        }

            code.push('IRestResponse response = client.Execute(request);')
            return code.join()
        }

        module.exports.info = {
            key: 'restsharp',
            title: 'RestSharp',
            link: 'http://restsharp.org/',
            description: 'Simple REST and HTTP API Client for .NET'
        }

    }, {"../../helpers/code-builder": 38}],
    46: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'go',
                title: 'Go',
                extname: '.go',
                default: 'native'
            },

            native: require('./native')
        }

    }, {"./native": 47}],
    47: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for native Go.
         *
         * @author
         * @montanaflynn
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            // Let's Go!
            var code = new CodeBuilder('\t')

            // Define Options
            var opts = util._extend({
                showBoilerplate: true,
                checkErrors: false,
                printBody: true,
                timeout: -1
            }, options)

            var errorPlaceholder = opts.checkErrors ? 'err' : '_'

            var indent = opts.showBoilerplate ? 1 : 0

            var errorCheck = function () {
                if (opts.checkErrors) {
                    code.push(indent, 'if err != nil {')
                        .push(indent + 1, 'panic(err)')
                    .push(indent, '}')
            }
            }

            // Create boilerplate
            if (opts.showBoilerplate) {
                code.push('package main')
                .blank()
                    .push('import (')
                    .push(indent, '"fmt"')

                if (opts.timeout > 0) {
                    code.push(indent, '"time"')
            }

                if (source.postData.text) {
                    code.push(indent, '"strings"')
            }

                code.push(indent, '"net/http"')

            if (opts.printBody) {
                code.push(indent, '"io/ioutil"')
            }

                code.push(')')
                    .blank()
                    .push('func main() {')
                    .blank()
            }

            // Create client
            var client
            if (opts.timeout > 0) {
                client = 'client'
                code.push(indent, 'client := http.Client{')
                    .push(indent + 1, 'Timeout: time.Duration(%s * time.Second),', opts.timeout)
                    .push(indent, '}')
                    .blank()
            } else {
                client = 'http.DefaultClient'
            }

            code.push(indent, 'url := "%s"', source.fullUrl)
                .blank()

            // If we have body content or not create the var and reader or nil
            if (source.postData.text) {
                code.push(indent, 'payload := strings.NewReader(%s)', JSON.stringify(source.postData.text))
                    .blank()
                    .push(indent, 'req, %s := http.NewRequest("%s", url, payload)', errorPlaceholder, source.method)
                    .blank()
            } else {
                code.push(indent, 'req, %s := http.NewRequest("%s", url, nil)', errorPlaceholder, source.method)
                    .blank()
        }

            errorCheck()

            // Add headers
            if (Object.keys(source.allHeaders).length) {
                Object.keys(source.allHeaders).forEach(function (key) {
                    code.push(indent, 'req.Header.Add("%s", "%s")', key, source.allHeaders[key])
                })

                code.blank()
        }

            // Make request
            code.push(indent, 'res, %s := %s.Do(req)', errorPlaceholder, client)
            errorCheck()

            // Get Body
            if (opts.printBody) {
                code.blank()
                    .push(indent, 'defer res.Body.Close()')
                    .push(indent, 'body, %s := ioutil.ReadAll(res.Body)', errorPlaceholder)
                errorCheck()
        }

            // Print it
            code.blank()
                .push(indent, 'fmt.Println(res)')

            if (opts.printBody) {
                code.push(indent, 'fmt.Println(string(body))')
            }

            // End main block
            if (opts.showBoilerplate) {
                code.blank()
                    .push('}')
        }

            return code.join()
        }

        module.exports.info = {
            key: 'native',
            title: 'NewRequest',
            link: 'http://golang.org/pkg/net/http/#NewRequest',
            description: 'Golang HTTP client request'
        }

    }, {"../../helpers/code-builder": 38, "util": 114}],
    48: [function (require, module, exports) {
        'use strict'

        module.exports = {
            c: require('./c'),
            csharp: require('./csharp'),
            go: require('./go'),
            java: require('./java'),
            javascript: require('./javascript'),
            node: require('./node'),
            objc: require('./objc'),
            ocaml: require('./ocaml'),
            php: require('./php'),
            python: require('./python'),
            ruby: require('./ruby'),
            shell: require('./shell'),
            swift: require('./swift')
        }

    }, {
        "./c": 42,
        "./csharp": 44,
        "./go": 46,
        "./java": 49,
        "./javascript": 53,
        "./node": 56,
        "./objc": 61,
        "./ocaml": 64,
        "./php": 69,
        "./python": 70,
        "./ruby": 73,
        "./shell": 77,
        "./swift": 80
    }],
    49: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'java',
                title: 'Java',
                extname: '.java',
                default: 'unirest'
            },

            okhttp: require('./okhttp'),
            unirest: require('./unirest'),
            restlet: require('./restlet')
        }

    }, {"./okhttp": 50, "./restlet": 51, "./unirest": 52}],
    50: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for Java using OkHttp.
         *
         * @author
         * @shashiranjan84
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                indent: '  '
            }, options)

            var code = new CodeBuilder(opts.indent)

            var methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD']

            var methodsWithBody = ['POST', 'PUT', 'DELETE', 'PATCH']

            code.push('// OkHttpClient from http://square.github.io/okhttp/')
                .blank()
            code.push('OkHttpClient client = new OkHttpClient();')
                .blank()

            if (source.postData.text) {
                if (source.postData.boundary) {
                    code.push('MediaType mediaType = MediaType.parse("%s; boundary=%s");', source.postData.mimeType, source.postData.boundary)
            } else {
                    code.push('MediaType mediaType = MediaType.parse("%s");', source.postData.mimeType)
            }
                code.push('RequestBody body = RequestBody.create(mediaType, %s);', JSON.stringify(source.postData.text))
            }

            code.push('Request request = new Request.Builder()')
            code.push(1, '.url("%s")', source.fullUrl)
            if (methods.indexOf(source.method.toUpperCase()) === -1) {
                if (source.postData.text) {
                    code.push(1, '.method("%s", body)', source.method.toUpperCase())
                } else {
                    code.push(1, '.method("%s", null)', source.method.toUpperCase())
            }
            } else if (methodsWithBody.indexOf(source.method.toUpperCase()) >= 0) {
                if (source.postData.text) {
                    code.push(1, '.%s(body)', source.method.toLowerCase())
                } else {
                    code.push(1, '.%s(null)', source.method.toLowerCase())
                }
            } else {
                code.push(1, '.%s()', source.method.toLowerCase())
            }

            // Add headers, including the cookies
            var headers = Object.keys(source.allHeaders)

            // construct headers
            if (headers.length) {
                headers.forEach(function (key) {
                    code.push(1, '.addHeader("%s", "%s")', key, source.allHeaders[key])
                })
        }

            code.push(1, '.build();')
                .blank()
                .push('Response response = client.newCall(request).execute();')

            return code.join()
        }

        module.exports.info = {
            key: 'okhttp',
            title: 'OkHttp',
            link: 'http://square.github.io/okhttp/',
            description: 'An HTTP Request Client Library'
        }

    }, {"../../helpers/code-builder": 38, "util": 114}],
    51: [function (require, module, exports) {
        'use strict'

        var util = require('util')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var service = {}

            service.setAccept = setAccept
            service.setAuthorization = setAuthorization
            service.setCookies = setCookies

            service.setUnknownMethod = setUnknownMethod
            service.isUnknownMethod = isUnknownMethod
            service.isMethodWithPostData = isMethodWithPostData
            service.setMethodWithPostData = setMethodWithPostData

            var opts = util._extend({
                indent: '  '
            }, options)

            var code = new CodeBuilder(opts.indent)

            var mimeTypes = {
                'application/json': 'MediaType.APPLICATION_JSON',
                'application/x-json': 'MediaType.APPLICATION_JSON',

                'application/xml': 'MediaType.APPLICATION_XML',
                'text/xml': 'MediaType.APPLICATION_XML',

                'application/yaml': 'MediaType.APPLICATION_YAML',
                'application/x-yaml': 'MediaType.APPLICATION_YAML',
                'text/yaml': 'MediaType.APPLICATION_YAML',

                'text/plain': 'MediaType.TEXT_PLAIN'
        }

            var methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD']
            // var knownHeaders = ['accept', 'content-type', 'authorization']

            var headers = source.allHeaders

            code.push('import org.restlet.resource.*;')

            code.push('ClientResource cr = new ClientResource("%s");', source.fullUrl)

            service.setAccept(code, headers['accept'], mimeTypes)

            service.setAuthorization(code, headers['authorization'])

            service.setCookies(code, source['cookies'])

            code.push('try {')

            if (service.isUnknownMethod(source.method)) {
                service.setUnknownMethod(code, source.method)

            } else {
                if (service.isMethodWithPostData(source.method)) {
                    service.setMethodWithPostData(code, source.method, source.postData, mimeTypes)

            } else {
                    code.push(2, 'Representation representation = cr.' + source.method.toLowerCase() + '();')

            }
            }

            code.push(2, 'System.out.println(representation.getText());')
            code.push('} catch (ResourceException e) {')
            code.push(2, 'System.err.println("Status: " + e.getStatus() + ". Response: " + cr.getResponse().getEntityAsText());')
            code.push('}')

            return code.join()

            // TODO: implement other types of authentication
            function setAuthorization(code, authorization) {
                if (authorization) {
                    code.push('ChallengeResponse credentials = new ChallengeResponse(ChallengeScheme.HTTP_BASIC);')
                    code.push(4, 'credentials')
                    code.push(8, '.setRawValue("%s");', authorization.replace('Basic ', ''))
                    code.push(4, 'cr.setChallengeResponse(credentials);')
                }
            }

            function setCookies(code, cookies) {
                if (cookies.length > 0) {
                    cookies.forEach(function (cookie) {
                        code.push('cr.getCookies().add(new Cookie("%s", "%s"));', cookie.name, cookie.value)
                })

            }
            }

            function setAccept(code, accept, mimeTypes) {
                if (mimeTypes[accept]) {
                    code.push('cr.accept(' + mimeTypes[accept] + ');')
            }
            }

            function setUnknownMethod(code, method) {
                code.push(2, 'cr.getRequest().setMethod(new Method("%s"));', method.toUpperCase())
                code.push(2, 'Representation representation = cr.handle();')
            }

            function setMethodWithPostData(code, method, postData, mimeTypes) {
                if (postData.text) {
                    code.push(2, 'Representation representation = cr')
                    code.push(6, '.%s(new StringRepresentation(', method.toLowerCase())
                    code.push(10, '%s,', JSON.stringify(postData.text))

                    if (mimeTypes[postData.mimeType]) {
                        code.push(10, '%s));', mimeTypes[postData.mimeType])
                    } else {
                        code.push(10, 'MediaType.TEXT_PLAIN));')
                    }

                } else {
                    code.push(2, 'Representation representation = cr.%s(null);', method.toLowerCase())
                }

        }

            function isMethodWithPostData(method) {
                return ['POST', 'PUT', 'PATCH'].indexOf(method.toUpperCase()) !== -1
        }

            function isUnknownMethod(method) {
                return methods.indexOf(method.toUpperCase()) === -1
            }
        }

        module.exports.info = {
            key: 'restlet',
            title: 'Restlet Framework',
            link: 'http://restlet.com/products/restlet-framework/',
            description: 'The most widely used open source solution for Java developers who want to create and use APIs'
        }

    }, {"../../helpers/code-builder": 38, "util": 114}],
    52: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for Java using Unirest.
         *
         * @author
         * @shashiranjan84
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                indent: '  '
            }, options)

            var code = new CodeBuilder(opts.indent)

            var methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS']

            if (methods.indexOf(source.method.toUpperCase()) === -1) {
                code.push('HttpResponse<String> response = Unirest.customMethod("%s","%s")', source.method.toUpperCase(), source.fullUrl)
            } else {
                code.push('HttpResponse<String> response = Unirest.%s("%s")', source.method.toLowerCase(), source.fullUrl)
        }

            // Add headers, including the cookies
            var headers = Object.keys(source.allHeaders)

            // construct headers
            if (headers.length) {
                headers.forEach(function (key) {
                    code.push(1, '.header("%s", "%s")', key, source.allHeaders[key])
                })
            }

            if (source.postData.text) {
                code.push(1, '.body(%s)', JSON.stringify(source.postData.text))
            }

            code.push(1, '.asString();')

            return code.join()
        }

        module.exports.info = {
            key: 'unirest',
            title: 'Unirest',
            link: 'http://unirest.io/java.html',
            description: 'Lightweight HTTP Request Client Library'
        }

    }, {"../../helpers/code-builder": 38, "util": 114}],
    53: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'javascript',
                title: 'JavaScript',
                extname: '.js',
                default: 'xhr'
            },

            jquery: require('./jquery'),
            xhr: require('./xhr')
        }

    }, {"./jquery": 54, "./xhr": 55}],
    54: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for native XMLHttpRequest
         *
         * @author
         * @AhmadNassri
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                indent: '  '
            }, options)

            var code = new CodeBuilder(opts.indent)

            var settings = {
                async: true,
                crossDomain: true,
                url: source.fullUrl,
                method: source.method,
                headers: source.allHeaders
            }

            switch (source.postData.mimeType) {
                case 'application/x-www-form-urlencoded':
                    settings.data = source.postData.paramsObj ? source.postData.paramsObj : source.postData.text
                    break

                case 'application/json':
                    settings.processData = false
                    settings.data = source.postData.text
                    break

                case 'multipart/form-data':
                    code.push('var form = new FormData();')

                    source.postData.params.forEach(function (param) {
                        code.push('form.append(%s, %s);', JSON.stringify(param.name), JSON.stringify(param.value || param.fileName || ''))
                    })

                    settings.processData = false
                    settings.contentType = false
                    settings.mimeType = 'multipart/form-data'
                    settings.data = '[form]'

                    // remove the contentType header
                    if (~settings.headers['content-type'].indexOf('boundary')) {
                        delete settings.headers['content-type']
                    }
                    code.blank()
                    break

                default:
                    if (source.postData.text) {
                    settings.data = source.postData.text
                    }
        }

            code.push('var settings = ' + JSON.stringify(settings, null, opts.indent).replace('"[form]"', 'form'))
                .blank()
                .push('$.ajax(settings).done(function (response) {')
                .push(1, 'console.log(response);')
                .push('});')

            return code.join()
        }

        module.exports.info = {
            key: 'jquery',
            title: 'jQuery',
            link: 'http://api.jquery.com/jquery.ajax/',
            description: 'Perform an asynchronous HTTP (Ajax) requests with jQuery'
        }

    }, {"../../helpers/code-builder": 38, "util": 114}],
    55: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for native XMLHttpRequest
         *
         * @author
         * @AhmadNassri
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                indent: '  ',
                cors: true
            }, options)

            var code = new CodeBuilder(opts.indent)

            switch (source.postData.mimeType) {
                case 'application/json':
                    code.push('var data = JSON.stringify(%s);', JSON.stringify(source.postData.jsonObj, null, opts.indent))
                        .push(null)
                    break

                case 'multipart/form-data':
                    code.push('var data = new FormData();')

                    source.postData.params.forEach(function (param) {
                        code.push('data.append(%s, %s);', JSON.stringify(param.name), JSON.stringify(param.value || param.fileName || ''))
                })

                    // remove the contentType header
                    if (source.allHeaders['content-type'].indexOf('boundary')) {
                        delete source.allHeaders['content-type']
                    }

                code.blank()
                    break

                default:
                    code.push('var data = %s;', JSON.stringify(source.postData.text || null))
                        .blank()
            }

            code.push('var xhr = new XMLHttpRequest();')

            if (opts.cors) {
                code.push('xhr.withCredentials = true;')
            }

            code.blank()
                .push('xhr.addEventListener("readystatechange", function () {')
                .push(1, 'if (this.readyState === this.DONE) {')
                .push(2, 'console.log(this.responseText);')
                .push(1, '}')
                .push('});')
                .blank()
                .push('xhr.open(%s, %s);', JSON.stringify(source.method), JSON.stringify(source.fullUrl))

            Object.keys(source.allHeaders).forEach(function (key) {
                code.push('xhr.setRequestHeader(%s, %s);', JSON.stringify(key), JSON.stringify(source.allHeaders[key]))
            })

            code.blank()
                .push('xhr.send(data);')

            return code.join()
        }

        module.exports.info = {
            key: 'xhr',
            title: 'XMLHttpRequest',
            link: 'https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest',
            description: 'W3C Standard API that provides scripted client functionality'
        }

    }, {"../../helpers/code-builder": 38, "util": 114}],
    56: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'node',
                title: 'Node.js',
                extname: '.js',
                default: 'native'
            },

            native: require('./native'),
            request: require('./request'),
            unirest: require('./unirest')
        }

    }, {"./native": 57, "./request": 58, "./unirest": 59}],
    57: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for native Node.js.
         *
         * @author
         * @AhmadNassri
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                indent: '  '
            }, options)

            var code = new CodeBuilder(opts.indent)

            var reqOpts = {
                method: source.method,
                hostname: source.uriObj.hostname,
                port: source.uriObj.port,
                path: source.uriObj.path,
                headers: source.allHeaders
        }

            code.push('var http = require("%s");', source.uriObj.protocol.replace(':', ''))

            code.blank()
                .push('var options = %s;', JSON.stringify(reqOpts, null, opts.indent))
                .blank()
                .push('var req = http.request(options, function (res) {')
                .push(1, 'var chunks = [];')
                .blank()
                .push(1, 'res.on("data", function (chunk) {')
                .push(2, 'chunks.push(chunk);')
                .push(1, '});')
                .blank()
                .push(1, 'res.on("end", function () {')
                .push(2, 'var body = Buffer.concat(chunks);')
                .push(2, 'console.log(body.toString());')
                .push(1, '});')
                .push('});')
                .blank()

            switch (source.postData.mimeType) {
                case 'application/x-www-form-urlencoded':
                    if (source.postData.paramsObj) {
                        code.unshift('var qs = require("querystring");')
                        code.push('req.write(qs.stringify(%s));', util.inspect(source.postData.paramsObj, {
                            depth: null
                        }))
                    }
                    break

                case 'application/json':
                    if (source.postData.jsonObj) {
                        code.push('req.write(JSON.stringify(%s));', util.inspect(source.postData.jsonObj, {
                            depth: null
                        }))
                    }
                    break

                default:
                    if (source.postData.text) {
                        code.push('req.write(%s);', JSON.stringify(source.postData.text, null, opts.indent))
                    }
        }

            code.push('req.end();')

            return code.join()
        }

        module.exports.info = {
            key: 'native',
            title: 'HTTP',
            link: 'http://nodejs.org/api/http.html#http_http_request_options_callback',
            description: 'Node.js native HTTP interface'
        }

    }, {"../../helpers/code-builder": 38, "util": 114}],
    58: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for Node.js using Request.
         *
         * @author
         * @AhmadNassri
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                indent: '  '
            }, options)

            var includeFS = false
            var code = new CodeBuilder(opts.indent)
            code.push('// Install request by running "npm install --save request"')
            code.push('var request = require("request");')
                .blank()

            var reqOpts = {
                method: source.method,
                url: source.url
            }

            if (Object.keys(source.queryObj).length) {
                reqOpts.qs = source.queryObj
            }

            if (Object.keys(source.headersObj).length) {
                reqOpts.headers = source.headersObj
            }

            switch (source.postData.mimeType) {
                case 'application/x-www-form-urlencoded':
                    reqOpts.form = source.postData.paramsObj
                    break

                case 'application/json':
                    if (source.postData.jsonObj) {
                        reqOpts.body = source.postData.jsonObj
                        reqOpts.json = true
                    }
                    break

                case 'multipart/form-data':
                    reqOpts.formData = {}

                    source.postData.params.forEach(function (param) {
                        var attachement = {}

                        if (!param.fileName && !param.fileName && !param.contentType) {
                            reqOpts.formData[param.name] = param.value
                            return
                        }

                        if (param.fileName && !param.value) {
                            includeFS = true

                            attachement.value = 'fs.createReadStream("' + param.fileName + '")'
                        } else if (param.value) {
                            attachement.value = param.value
                        }

                        if (param.fileName) {
                            attachement.options = {
                                filename: param.fileName,
                                contentType: param.contentType ? param.contentType : null
                            }
                        }

                        reqOpts.formData[param.name] = attachement
                })
                    break

                default:
                    if (source.postData.text) {
                        reqOpts.body = source.postData.text
                    }
            }

            // construct cookies argument
            if (source.cookies.length) {
                reqOpts.jar = 'JAR'

                code.push('var jar = request.jar();')

                var url = source.url

                source.cookies.forEach(function (cookie) {
                    code.push('jar.setCookie(request.cookie("%s=%s"), "%s");', encodeURIComponent(cookie.name), encodeURIComponent(cookie.value), url)
                })
            code.blank()
            }

            if (includeFS) {
                code.unshift('var fs = require("fs");')
            }

            code.push('var options = %s;', util.inspect(reqOpts, {depth: null}))
                .blank()

            code.push(util.format('request(options, %s', 'function (error, response, body) {'))

                .push(1, 'if (error) return console.error(\'Failed: %s\', error.message);')
                .blank()
                .push(1, 'console.log(\'Success: \', body);')
                .push('});')
                .blank()

            return code.join().replace('"JAR"', 'jar').replace(/"fs\.createReadStream\(\\\"(.+)\\\"\)\"/, 'fs.createReadStream("$1")')
        }

        module.exports.info = {
            key: 'request',
            title: 'Request',
            link: 'https://github.com/request/request',
            description: 'Simplified HTTP request client'
        }

    }, {"../../helpers/code-builder": 38, "util": 114}],
    59: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for Node.js using Unirest.
         *
         * @author
         * @AhmadNassri
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                indent: '  '
            }, options)

            var includeFS = false
            var code = new CodeBuilder(opts.indent)

            code.push('var unirest = require("unirest");')
                .blank()
                .push('var req = unirest("%s", "%s");', source.method, source.url)
                .blank()

            if (source.cookies.length) {
                code.push('var CookieJar = unirest.jar();')

                source.cookies.forEach(function (cookie) {
                    code.push('CookieJar.add("%s=%s","%s");', encodeURIComponent(cookie.name), encodeURIComponent(cookie.value), source.url)
                })

                code.push('req.jar(CookieJar);')
                .blank()
            }

            if (Object.keys(source.queryObj).length) {
                code.push('req.query(%s);', JSON.stringify(source.queryObj, null, opts.indent))
                .blank()
            }

            if (Object.keys(source.headersObj).length) {
                code.push('req.headers(%s);', JSON.stringify(source.headersObj, null, opts.indent))
                    .blank()
        }

            switch (source.postData.mimeType) {
                case 'application/x-www-form-urlencoded':
                    if (source.postData.paramsObj) {
                        code.push('req.form(%s);', JSON.stringify(source.postData.paramsObj, null, opts.indent))
                    }
                    break

                case 'application/json':
                    if (source.postData.jsonObj) {
                        code.push('req.type("json");')
                            .push('req.send(%s);', JSON.stringify(source.postData.jsonObj, null, opts.indent))
                    }
                    break

                case 'multipart/form-data':
                    var multipart = []

                    source.postData.params.forEach(function (param) {
                        var part = {}

                        if (param.fileName && !param.value) {
                            includeFS = true

                            part.body = 'fs.createReadStream("' + param.fileName + '")'
                        } else if (param.value) {
                            part.body = param.value
                        }

                        if (part.body) {
                            if (param.contentType) {
                                part['content-type'] = param.contentType
                            }

                            multipart.push(part)
                        }
                    })

                    code.push('req.multipart(%s);', JSON.stringify(multipart, null, opts.indent))
                    break

                default:
                    if (source.postData.text) {
                        code.push(opts.indent + 'req.send(%s);', JSON.stringify(source.postData.text, null, opts.indent))
                    }
        }

            if (includeFS) {
                code.unshift('var fs = require("fs");')
            }

            code.blank()
                .push('req.end(function (res) {')
                .push(1, 'if (res.error) throw new Error(res.error);')
                .blank()
                .push(1, 'console.log(res.body);')
                .push('});')
                .blank()

            return code.join().replace(/"fs\.createReadStream\(\\\"(.+)\\\"\)\"/, 'fs.createReadStream("$1")')
        }

        module.exports.info = {
            key: 'unirest',
            title: 'Unirest',
            link: 'http://unirest.io/nodejs.html',
            description: 'Lightweight HTTP Request Client Library'
        }

    }, {"../../helpers/code-builder": 38, "util": 114}],
    60: [function (require, module, exports) {
        'use strict'

        var util = require('util')

        module.exports = {
            /**
             * Create an string of given length filled with blank spaces
             *
             * @param {number} length Length of the array to return
             * @return {string}
             */
            blankString: function (length) {
                return Array.apply(null, new Array(length)).map(String.prototype.valueOf, ' ').join('')
            },

            /**
             * Create a string corresponding to a valid declaration and initialization of an Objective-C object literal.
             *
             * @param {string} nsClass Class of the litteral
             * @param {string} name Desired name of the instance
             * @param {Object} parameters Key-value object of parameters to translate to an Objective-C object litearal
             * @param {boolean} indent If true, will declare the litteral by indenting each new key/value pair.
             * @return {string} A valid Objective-C declaration and initialization of an Objective-C object litteral.
             *
             * @example
             *   nsDeclaration('NSDictionary', 'params', {a: 'b', c: 'd'}, true)
             *   // returns:
             *   NSDictionary *params = @{ @"a": @"b",
   *                             @"c": @"d" };
         *
             *   nsDeclaration('NSDictionary', 'params', {a: 'b', c: 'd'})
             *   // returns:
             *   NSDictionary *params = @{ @"a": @"b", @"c": @"d" };
         */
            nsDeclaration: function (nsClass, name, parameters, indent) {
                var opening = nsClass + ' *' + name + ' = '
                var literal = this.literalRepresentation(parameters, indent ? opening.length : undefined)
                return opening + literal + ';'
            },

        /**
         * Create a valid Objective-C string of a literal value according to its type.
         *
         * @param {*} value Any JavaScript literal
         * @return {string}
         */
        literalRepresentation: function (value, indentation) {
            var join = indentation === undefined ? ', ' : ',\n   ' + this.blankString(indentation)

            switch (Object.prototype.toString.call(value)) {
                case '[object Number]':
                    return '@' + value
                case '[object Array]':
                    var values_representation = value.map(function (v) {
                        return this.literalRepresentation(v)
                    }.bind(this))
                    return '@[ ' + values_representation.join(join) + ' ]'
                case '[object Object]':
                    var keyValuePairs = []
                    for (var k in value) {
                        keyValuePairs.push(util.format('@"%s": %s', k, this.literalRepresentation(value[k])))
                    }
                    return '@{ ' + keyValuePairs.join(join) + ' }'
                case '[object Boolean]':
                    return value ? '@YES' : '@NO'
                default:
                    return '@"' + value.toString().replace(/"/g, '\\"') + '"'
            }
        }
        }

    }, {"util": 114}],
    61: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'objc',
                title: 'Objective-C',
                extname: '.m',
                default: 'nsurlsession'
            },

            nsurlsession: require('./nsurlsession')
        }

    }, {"./nsurlsession": 62}],
    62: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for Objective-C using NSURLSession.
         *
         * @author
         * @thibaultCha
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var helpers = require('./helpers')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                indent: '    ',
                pretty: true,
                timeout: '10'
            }, options)

            var code = new CodeBuilder(opts.indent)
            // Markers for headers to be created as litteral objects and later be set on the NSURLRequest if exist
            var req = {
                hasHeaders: false,
                hasBody: false
            }

            // We just want to make sure people understand that is the only dependency
            code.push('#import <Foundation/Foundation.h>')

            if (Object.keys(source.allHeaders).length) {
                req.hasHeaders = true
            code.blank()
                .push(helpers.nsDeclaration('NSDictionary', 'headers', source.allHeaders, opts.pretty))
            }

            if (source.postData.text || source.postData.jsonObj || source.postData.params) {
                req.hasBody = true

            switch (source.postData.mimeType) {
                case 'application/x-www-form-urlencoded':
                    // By appending parameters one by one in the resulting snippet,
                    // we make it easier for the user to edit it according to his or her needs after pasting.
                    // The user can just add/remove lines adding/removing body parameters.
                    code.blank()
                        .push('NSMutableData *postData = [[NSMutableData alloc] initWithData:[@"%s=%s" dataUsingEncoding:NSUTF8StringEncoding]];',
                            source.postData.params[0].name, source.postData.params[0].value)
                    for (var i = 1, len = source.postData.params.length; i < len; i++) {
                        code.push('[postData appendData:[@"&%s=%s" dataUsingEncoding:NSUTF8StringEncoding]];',
                            source.postData.params[i].name, source.postData.params[i].value)
                    }
                    break

                case 'application/json':
                    if (source.postData.jsonObj) {
                        code.push(helpers.nsDeclaration('NSDictionary', 'parameters', source.postData.jsonObj, opts.pretty))
                            .blank()
                            .push('NSData *postData = [NSJSONSerialization dataWithJSONObject:parameters options:0 error:nil];')
                    }
                    break

                case 'multipart/form-data':
                    // By appending multipart parameters one by one in the resulting snippet,
                    // we make it easier for the user to edit it according to his or her needs after pasting.
                    // The user can just edit the parameters NSDictionary or put this part of a snippet in a multipart builder method.
                    code.push(helpers.nsDeclaration('NSArray', 'parameters', source.postData.params, opts.pretty))
                        .push('NSString *boundary = @"%s";', source.postData.boundary)
                        .blank()
                        .push('NSError *error;')
                        .push('NSMutableString *body = [NSMutableString string];')
                        .push('for (NSDictionary *param in parameters) {')
                        .push(1, '[body appendFormat:@"--%@\\r\\n", boundary];')
                        .push(1, 'if (param[@"fileName"]) {')
                        .push(2, '[body appendFormat:@"Content-Disposition:form-data; name=\\"%@\\"; filename=\\"%@\\"\\r\\n", param[@"name"], param[@"fileName"]];')
                        .push(2, '[body appendFormat:@"Content-Type: %@\\r\\n\\r\\n", param[@"contentType"]];')
                        .push(2, '[body appendFormat:@"%@", [NSString stringWithContentsOfFile:param[@"fileName"] encoding:NSUTF8StringEncoding error:&error]];')
                        .push(2, 'if (error) {')
                        .push(3, 'NSLog(@"%@", error);')
                        .push(2, '}')
                        .push(1, '} else {')
                        .push(2, '[body appendFormat:@"Content-Disposition:form-data; name=\\"%@\\"\\r\\n\\r\\n", param[@"name"]];')
                        .push(2, '[body appendFormat:@"%@", param[@"value"]];')
                        .push(1, '}')
                        .push('}')
                        .push('[body appendFormat:@"\\r\\n--%@--\\r\\n", boundary];')
                        .push('NSData *postData = [body dataUsingEncoding:NSUTF8StringEncoding];')
                    break

                default:
                    code.blank()
                        .push('NSData *postData = [[NSData alloc] initWithData:[@"' + source.postData.text + '" dataUsingEncoding:NSUTF8StringEncoding]];')
            }
            }

            code.blank()
                .push('NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:[NSURL URLWithString:@"' + source.fullUrl + '"]')
                // NSURLRequestUseProtocolCachePolicy is the default policy, let's just always set it to avoid confusion.
                .push('                                                       cachePolicy:NSURLRequestUseProtocolCachePolicy')
                .push('                                                   timeoutInterval:' + parseInt(opts.timeout, 10).toFixed(1) + '];')
                .push('[request setHTTPMethod:@"' + source.method + '"];')

            if (req.hasHeaders) {
                code.push('[request setAllHTTPHeaderFields:headers];')
            }

            if (req.hasBody) {
                code.push('[request setHTTPBody:postData];')
            }

            code.blank()
                // Retrieving the shared session will be less verbose than creating a new one.
                .push('NSURLSession *session = [NSURLSession sharedSession];')
                .push('NSURLSessionDataTask *dataTask = [session dataTaskWithRequest:request')
                .push('                                            completionHandler:^(NSData *data, NSURLResponse *response, NSError *error) {')
                .push(1, '                                            if (error) {')
                .push(2, '                                            NSLog(@"%@", error);')
                .push(1, '                                            } else {')
                // Casting the NSURLResponse to NSHTTPURLResponse so the user can see the status     .
                .push(2, '                                            NSHTTPURLResponse *httpResponse = (NSHTTPURLResponse *) response;')
                .push(2, '                                            NSLog(@"%@", httpResponse);')
                .push(1, '                                            }')
                .push('                                            }];')
                .push('[dataTask resume];')

            return code.join()
        }

        module.exports.info = {
            key: 'nsurlsession',
            title: 'NSURLSession',
            link: 'https://developer.apple.com/library/mac/documentation/Foundation/Reference/NSURLSession_class/index.html',
            description: 'Foundation\'s NSURLSession request'
        }

    }, {"../../helpers/code-builder": 38, "./helpers": 60, "util": 114}],
    63: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for OCaml using CoHTTP.
         *
         * @author
         * @SGrondin
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                indent: '  '
            }, options)

            var methods = ['get', 'post', 'head', 'delete', 'patch', 'put', 'options']
            var code = new CodeBuilder(opts.indent)

            code.push('open Cohttp_lwt_unix')
                .push('open Cohttp')
                .push('open Lwt')
                .blank()
                .push('let uri = Uri.of_string "%s" in', source.fullUrl)

            // Add headers, including the cookies
            var headers = Object.keys(source.allHeaders)

            if (headers.length === 1) {
                code.push('let headers = Header.add (Header.init ()) "%s" "%s" in', headers[0], source.allHeaders[headers[0]])
            } else if (headers.length > 1) {
                code.push('let headers = Header.add_list (Header.init ()) [')

                headers.forEach(function (key) {
                    code.push(1, '("%s", "%s");', key, source.allHeaders[key])
                })

                code.push('] in')
            }

            // Add body
            if (source.postData.text) {
                // Just text
                code.push('let body = Cohttp_lwt_body.of_string %s in', JSON.stringify(source.postData.text))
            }

            // Do the request
            code.blank()

            code.push('Client.call %s%s%s uri',
                headers.length ? '~headers ' : '',
                source.postData.text ? '~body ' : '',
                (methods.indexOf(source.method.toLowerCase()) >= 0 ? ('`' + source.method.toUpperCase()) : '(Code.method_of_string "' + source.method + '")')
            )

            // Catch result
            code.push('>>= fun (res, body_stream) ->')
                .push(1, '(* Do stuff with the result *)')

            return code.join()
        }

        module.exports.info = {
            key: 'cohttp',
            title: 'CoHTTP',
            link: 'https://github.com/mirage/ocaml-cohttp',
            description: 'Cohttp is a very lightweight HTTP server using Lwt or Async for OCaml'
        }

    }, {"../../helpers/code-builder": 38, "util": 114}],
    64: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'ocaml',
                title: 'OCaml',
                extname: '.ml',
                default: 'cohttp'
            },

            cohttp: require('./cohttp')
        }

    }, {"./cohttp": 63}],
    65: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for PHP using curl-ext.
         *
         * @author
         * @AhmadNassri
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                closingTag: false,
                indent: '  ',
                maxRedirects: 10,
                namedErrors: false,
                noTags: false,
                shortTags: false,
                timeout: 30
            }, options)

            var code = new CodeBuilder(opts.indent)

            if (!opts.noTags) {
                code.push(opts.shortTags ? '<?' : '<?php')
                .blank()
            }

            code.push('$curl = curl_init();')
                .blank()

            var curlOptions = [{
                escape: true,
                name: 'CURLOPT_PORT',
                value: source.uriObj.port
            }, {
                escape: true,
                name: 'CURLOPT_URL',
                value: source.fullUrl
            }, {
                escape: false,
                name: 'CURLOPT_RETURNTRANSFER',
                value: 'true'
            }, {
                escape: true,
                name: 'CURLOPT_ENCODING',
                value: ''
            }, {
                escape: false,
                name: 'CURLOPT_MAXREDIRS',
                value: opts.maxRedirects
            }, {
                escape: false,
                name: 'CURLOPT_TIMEOUT',
                value: opts.timeout
            }, {
                escape: false,
                name: 'CURLOPT_HTTP_VERSION',
                value: source.httpVersion === 'HTTP/1.0' ? 'CURL_HTTP_VERSION_1_0' : 'CURL_HTTP_VERSION_1_1'
            }, {
                escape: true,
                name: 'CURLOPT_CUSTOMREQUEST',
                value: source.method
            }, {
                escape: true,
                name: 'CURLOPT_POSTFIELDS',
                value: source.postData ? source.postData.text : undefined
            }]

            code.push('curl_setopt_array($curl, array(')

            var curlopts = new CodeBuilder(opts.indent, '\n' + opts.indent)

            curlOptions.forEach(function (option) {
                if (!~[null, undefined].indexOf(option.value)) {
                    curlopts.push(util.format('%s => %s,', option.name, option.escape ? JSON.stringify(option.value) : option.value))
            }
            })

            // construct cookies
            var cookies = source.cookies.map(function (cookie) {
                return encodeURIComponent(cookie.name) + '=' + encodeURIComponent(cookie.value)
            })

            if (cookies.length) {
                curlopts.push(util.format('CURLOPT_COOKIE => "%s",', cookies.join('; ')))
        }

            // construct cookies
            var headers = Object.keys(source.headersObj).sort().map(function (key) {
                return util.format('"%s: %s"', key, source.headersObj[key])
            })

            if (headers.length) {
                curlopts.push('CURLOPT_HTTPHEADER => array(')
                    .push(1, headers.join(',\n' + opts.indent + opts.indent))
                    .push('),')
        }

            code.push(1, curlopts.join())
                .push('));')
                .blank()
                .push('$response = curl_exec($curl);')
                .push('$err = curl_error($curl);')
                .blank()
                .push('curl_close($curl);')
                .blank()
                .push('if ($err) {')

            if (opts.namedErrors) {
                code.push(1, 'echo array_flip(get_defined_constants(true)["curl"])[$err];')
            } else {
                code.push(1, 'echo "cURL Error #:" . $err;')
            }

            code.push('} else {')
                .push(1, 'echo $response;')
                .push('}')

            if (!opts.noTags && opts.closingTag) {
                code.blank()
                    .push('?>')
        }

            return code.join()
        }

        module.exports.info = {
            key: 'curl',
            title: 'cURL',
            link: 'http://php.net/manual/en/book.curl.php',
            description: 'PHP with ext-curl'
        }

    }, {"../../helpers/code-builder": 38, "util": 114}],
    66: [function (require, module, exports) {
        'use strict'

        var convert = function (obj, indent, last_indent) {
            var i, result

            if (!last_indent) {
                last_indent = ''
        }

            switch (Object.prototype.toString.call(obj)) {
                case '[object Null]':
                    result = 'null'
                    break

                case '[object Undefined]':
                    result = 'null'
                    break

                case '[object String]':
                    result = "'" + obj.replace(/\\/g, '\\\\').replace(/\'/g, "\'") + "'"
                    break

                case '[object Number]':
                    result = obj.toString()
                    break

                case '[object Array]':
                    result = []

                    obj.forEach(function (item) {
                        result.push(convert(item, indent + indent, indent))
                    })

                    result = 'array(\n' + indent + result.join(',\n' + indent) + '\n' + last_indent + ')'
                    break

                case '[object Object]':
                    result = []
                    for (i in obj) {
                        if (obj.hasOwnProperty(i)) {
                            result.push(convert(i, indent) + ' => ' + convert(obj[i], indent + indent, indent))
                        }
                    }
                    result = 'array(\n' + indent + result.join(',\n' + indent) + '\n' + last_indent + ')'
                    break

                default:
                    result = 'null'
            }

            return result
        }

        module.exports = {
            convert: convert,
            methods: [
                'ACL',
                'BASELINE_CONTROL',
                'CHECKIN',
                'CHECKOUT',
                'CONNECT',
                'COPY',
                'DELETE',
                'GET',
                'HEAD',
                'LABEL',
                'LOCK',
                'MERGE',
                'MKACTIVITY',
                'MKCOL',
                'MKWORKSPACE',
                'MOVE',
                'OPTIONS',
                'POST',
                'PROPFIND',
                'PROPPATCH',
                'PUT',
                'REPORT',
                'TRACE',
                'UNCHECKOUT',
                'UNLOCK',
                'UPDATE',
                'VERSION_CONTROL'
            ]
        }

    }, {}],
    67: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for PHP using curl-ext.
         *
         * @author
         * @AhmadNassri
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var helpers = require('./helpers')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                closingTag: false,
                indent: '  ',
                noTags: false,
                shortTags: false
            }, options)

            var code = new CodeBuilder(opts.indent)

            if (!opts.noTags) {
                code.push(opts.shortTags ? '<?' : '<?php')
                .blank()
            }

            if (!~helpers.methods.indexOf(source.method.toUpperCase())) {
                code.push('HttpRequest::methodRegister(\'%s\');', source.method)
            }

            code.push('$request = new HttpRequest();')
                .push('$request->setUrl(%s);', helpers.convert(source.url))

            if (~helpers.methods.indexOf(source.method.toUpperCase())) {
                code.push('$request->setMethod(HTTP_METH_%s);', source.method.toUpperCase())
            } else {
                code.push('$request->setMethod(HttpRequest::HTTP_METH_%s);', source.method.toUpperCase())
            }

            code.blank()

            if (Object.keys(source.queryObj).length) {
                code.push('$request->setQueryData(%s);', helpers.convert(source.queryObj, opts.indent))
                    .blank()
            }

            if (Object.keys(source.headersObj).length) {
                code.push('$request->setHeaders(%s);', helpers.convert(source.headersObj, opts.indent))
                    .blank()
            }

            if (Object.keys(source.cookiesObj).length) {
                code.push('$request->setCookies(%s);', helpers.convert(source.cookiesObj, opts.indent))
                    .blank()
            }

            switch (source.postData.mimeType) {
                case 'application/x-www-form-urlencoded':
                    code.push('$request->setContentType(%s);', helpers.convert(source.postData.mimeType))
                        .push('$request->setPostFields(%s);', helpers.convert(source.postData.paramsObj, opts.indent))
                    .blank()
                    break

                default:
                    if (source.postData.text) {
                        code.push('$request->setBody(%s);', helpers.convert(source.postData.text))
                        .blank()
                }
            }

            code.push('try {')
                .push(1, '$response = $request->send();')
                .blank()
                .push(1, 'echo $response->getBody();')
                .push('} catch (HttpException $ex) {')
                .push(1, 'echo $ex;')
                .push('}')

            if (!opts.noTags && opts.closingTag) {
                code.blank()
                    .push('?>')
            }

            return code.join()
        }

        module.exports.info = {
            key: 'http1',
            title: 'HTTP v1',
            link: 'http://php.net/manual/en/book.http.php',
            description: 'PHP with pecl/http v1'
        }

    }, {"../../helpers/code-builder": 38, "./helpers": 66, "util": 114}],
    68: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for PHP using curl-ext.
         *
         * @author
         * @AhmadNassri
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var helpers = require('./helpers')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                closingTag: false,
                indent: '  ',
                noTags: false,
                shortTags: false
            }, options)

            var code = new CodeBuilder(opts.indent)
            var hasBody = false

            if (!opts.noTags) {
                code.push(opts.shortTags ? '<?' : '<?php')
                    .blank()
            }

            code.push('$client = new http\\Client;')
                .push('$request = new http\\Client\\Request;')
                .blank()

            switch (source.postData.mimeType) {
                case 'application/x-www-form-urlencoded':
                    code.push('$body = new http\\Message\\Body;')
                        .push('$body->append(new http\\QueryString(%s));', helpers.convert(source.postData.paramsObj, opts.indent))
                        .blank()
                    hasBody = true
                    break

                case 'multipart/form-data':
                    var files = []
                    var fields = {}

                    source.postData.params.forEach(function (param) {
                        if (param.fileName) {
                            files.push({
                                name: param.name,
                                type: param.contentType,
                                file: param.fileName,
                                data: param.value
                            })
                        } else if (param.value) {
                            fields[param.name] = param.value
                    }
                    })

                    code.push('$body = new http\\Message\\Body;')
                        .push('$body->addForm(%s, %s);',
                            Object.keys(fields).length ? helpers.convert(fields, opts.indent) : 'NULL',
                            files.length ? helpers.convert(files, opts.indent) : 'NULL'
                        )

                    // remove the contentType header
                    if (~source.headersObj['content-type'].indexOf('boundary')) {
                        delete source.headersObj['content-type']
                }

                    code.blank()

                    hasBody = true
                    break

                default:
                    if (source.postData.text) {
                        code.push('$body = new http\\Message\\Body;')
                            .push('$body->append(%s);', helpers.convert(source.postData.text))
                            .blank()
                        hasBody = true
                    }
            }

            code.push('$request->setRequestUrl(%s);', helpers.convert(source.url))
                .push('$request->setRequestMethod(%s);', helpers.convert(source.method))

            if (hasBody) {
                code.push('$request->setBody($body);')
                .blank()
            }

            if (Object.keys(source.queryObj).length) {
                code.push('$request->setQuery(new http\\QueryString(%s));', helpers.convert(source.queryObj, opts.indent))
                    .blank()
        }

            if (Object.keys(source.headersObj).length) {
                code.push('$request->setHeaders(%s);', helpers.convert(source.headersObj, opts.indent))
                    .blank()
        }

            if (Object.keys(source.cookiesObj).length) {
                code.blank()
                    .push('$client->setCookies(%s);', helpers.convert(source.cookiesObj, opts.indent))
                    .blank()
            }

            code.push('$client->enqueue($request)->send();')
                .push('$response = $client->getResponse();')
                .blank()
                .push('echo $response->getBody();')

            if (!opts.noTags && opts.closingTag) {
                code.blank()
                    .push('?>')
            }

            return code.join()
        }

        module.exports.info = {
            key: 'http2',
            title: 'HTTP v2',
            link: 'http://devel-m6w6.rhcloud.com/mdref/http',
            description: 'PHP with pecl/http v2'
        }

    }, {"../../helpers/code-builder": 38, "./helpers": 66, "util": 114}],
    69: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'php',
                title: 'PHP',
                extname: '.php',
                default: 'curl'
            },

            curl: require('./curl'),
            http1: require('./http1'),
            http2: require('./http2')
        }

    }, {"./curl": 65, "./http1": 67, "./http2": 68}],
    70: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'python',
                title: 'Python',
                extname: '.py',
                default: 'python3'
            },

            python3: require('./python3'),
            requests: require('./requests')
        }

    }, {"./python3": 71, "./requests": 72}],
    71: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for native Python3.
         *
         * @author
         * @montanaflynn
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var code = new CodeBuilder()
            // Start Request
            code.push('import http.client')
                .blank()

            // Check which protocol to be used for the client connection
            var protocol = source.uriObj.protocol
            if (protocol === 'https:') {
                code.push('conn = http.client.HTTPSConnection("%s")', source.uriObj.host)
                .blank()
            } else {
                code.push('conn = http.client.HTTPConnection("%s")', source.uriObj.host)
                    .blank()
            }

            // Create payload string if it exists
            var payload = JSON.stringify(source.postData.text)
            if (payload) {
                code.push('payload = %s', payload)
                .blank()
            }

            // Create Headers
            var header
            var headers = source.allHeaders
            var headerCount = Object.keys(headers).length
            if (headerCount === 1) {
                for (header in headers) {
                    code.push('headers = { \'%s\': "%s" }', header, headers[header])
                    .blank()
            }
            } else if (headerCount > 1) {
                var count = 1

                code.push('headers = {')

                for (header in headers) {
                    if (count++ !== headerCount) {
                        code.push('    \'%s\': "%s",', header, headers[header])
                    } else {
                        code.push('    \'%s\': "%s"', header, headers[header])
                    }
            }

                code.push('    }')
                    .blank()
            }

            // Make Request
            var method = source.method
            var path = source.uriObj.path
            if (payload && headerCount) {
                code.push('conn.request("%s", "%s", payload, headers)', method, path)
            } else if (payload && !headerCount) {
                code.push('conn.request("%s", "%s", payload)', method, path)
            } else if (!payload && headerCount) {
                code.push('conn.request("%s", "%s", headers=headers)', method, path)
            } else {
                code.push('conn.request("%s", "%s")', method, path)
            }

            // Get Response
            code.blank()
                .push('res = conn.getresponse()')
                .push('data = res.read()')
                .blank()
                .push('print(data.decode("utf-8"))')

            return code.join()
        }

        module.exports.info = {
            key: 'python3',
            title: 'http.client',
            link: 'https://docs.python.org/3/library/http.client.html',
            description: 'Python3 HTTP Client'
        }

    }, {"../../helpers/code-builder": 38}],
    72: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for Python using Requests
         *
         * @author
         * @montanaflynn
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            // Start snippet
            var code = new CodeBuilder('    ')

            // Import requests
            code.push('import requests')
                .blank()

            // Set URL
            code.push('url = "%s"', source.url)
                .blank()

            // Construct query string
            if (source.queryString.length) {
                var qs = 'querystring = ' + JSON.stringify(source.queryObj)

                code.push(qs)
                    .blank()
            }

            // Construct payload
            var payload = JSON.stringify(source.postData.text)

            if (payload) {
                code.push('payload = %s', payload)
            }

            // Construct headers
            var header
            var headers = source.allHeaders
            var headerCount = Object.keys(headers).length

            if (headerCount === 1) {
                for (header in headers) {
                    code.push('headers = {\'%s\': \'%s\'}', header, headers[header])
                        .blank()
            }
            } else if (headerCount > 1) {
                var count = 1

                code.push('headers = {')

                for (header in headers) {
                    if (count++ !== headerCount) {
                        code.push(1, '\'%s\': "%s",', header, headers[header])
                    } else {
                        code.push(1, '\'%s\': "%s"', header, headers[header])
                    }
                }

                code.push(1, '}')
                .blank()
            }

            // Construct request
            var method = source.method
            var request = util.format('response = requests.request("%s", url', method)

            if (payload) {
                request += ', data=payload'
        }

            if (headerCount > 0) {
                request += ', headers=headers'
        }

            if (qs) {
                request += ', params=querystring'
            }

            request += ')'

            code.push(request)
                .blank()

                // Print response
                .push('print(response.text)')

            return code.join()
        }

        module.exports.info = {
            key: 'requests',
            title: 'Requests',
            link: 'http://docs.python-requests.org/en/latest/api/#requests.request',
            description: 'Requests HTTP library'
        }

// response = requests.request("POST", url, data=payload, headers=headers, params=querystring)

    }, {"../../helpers/code-builder": 38, "util": 114}],
    73: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'ruby',
                title: 'Ruby',
                extname: '.rb',
                default: 'native'
            },

            native: require('./native')
        }

    }, {"./native": 74}],
    74: [function (require, module, exports) {
        'use strict'

        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var code = new CodeBuilder()

            code.push('require \'uri\'')
                .push('require \'openssl\'')
                .push('require \'net/http\'')
                .blank()

            // To support custom methods we check for the supported methods
            // and if doesn't exist then we build a custom class for it
            var method = source.method.toUpperCase()
            var methods = ['GET', 'POST', 'HEAD', 'DELETE', 'PATCH', 'PUT', 'OPTIONS', 'COPY', 'LOCK', 'UNLOCK', 'MOVE', 'TRACE']
            var capMethod = method.charAt(0) + method.substring(1).toLowerCase()
            if (methods.indexOf(method) < 0) {
                code.push('class Net::HTTP::%s < Net::HTTPRequest', capMethod)
                    .push('  METHOD = \'%s\'', method.toUpperCase())
                    .push('  REQUEST_HAS_BODY = \'%s\'', source.postData.text ? 'true' : 'false')
                    .push('  RESPONSE_HAS_BODY = true')
                    .push('end')
                .blank()
            }

            code.push('url = URI("%s")', source.fullUrl)
                .blank()
                .push('http = Net::HTTP.new(url.host, url.port)')

            if (source.uriObj.protocol === 'https:') {
                code.push('http.use_ssl = true')
                    .push('http.verify_mode = OpenSSL::SSL::VERIFY_PEER')
            }

            code.blank()
                .push('request = Net::HTTP::%s.new(url)', capMethod)

            var headers = Object.keys(source.allHeaders)
            if (headers.length) {
                headers.forEach(function (key) {
                    code.push('request["%s"] = \'%s\'', key, source.allHeaders[key])
                })
            }

            if (source.postData.text) {
                code.push('request.body = %s', JSON.stringify(source.postData.text))
            }

            code.blank()
                .push('response = http.request(request)')
                .push('puts response.read_body')

            return code.join()
        }

        module.exports.info = {
            key: 'native',
            title: 'net::http',
            link: 'http://ruby-doc.org/stdlib-2.2.1/libdoc/net/http/rdoc/Net/HTTP.html',
            description: 'Ruby HTTP client'
        }

    }, {"../../helpers/code-builder": 38}],
    75: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for the Shell using cURL.
         *
         * @author
         * @AhmadNassri
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var helpers = require('../../helpers/shell')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                indent: '  ',
                short: false
            }, options)

            var code = new CodeBuilder(opts.indent, opts.indent !== false ? ' \\\n' + opts.indent : ' ')

            code.push('curl %s %s', opts.short ? '-X' : '--request', source.method)
                .push(util.format('%s%s', opts.short ? '' : '--url ', helpers.quote(source.fullUrl)))

            if (source.httpVersion === 'HTTP/1.0') {
                code.push(opts.short ? '-0' : '--http1.0')
            }

            // construct headers
            Object.keys(source.headersObj).sort().forEach(function (key) {
                var header = util.format('%s: %s', key, source.headersObj[key])
                code.push('%s %s', opts.short ? '-H' : '--header', helpers.quote(header))
            })

            if (source.allHeaders.cookie) {
                code.push('%s %s', opts.short ? '-b' : '--cookie', helpers.quote(source.allHeaders.cookie))
            }

            // construct post params
            switch (source.postData.mimeType) {
                case 'multipart/form-data':
                    source.postData.params.map(function (param) {
                        var post = util.format('%s=%s', param.name, param.value)

                        if (param.fileName && !param.value) {
                            post = util.format('%s=@%s', param.name, param.fileName)
                        }

                        code.push('%s %s', opts.short ? '-F' : '--form', helpers.quote(post))
                })
                    break

                default:
                    // raw request body
                    if (source.postData.text) {
                        code.push('%s %s', opts.short ? '-d' : '--data', helpers.escape(helpers.quote(source.postData.text)))
                    }
            }

            return code.join()
        }

        module.exports.info = {
            key: 'curl',
            title: 'cURL',
            link: 'http://curl.haxx.se/',
            description: 'cURL is a command line tool and library for transferring data with URL syntax'
        }

    }, {"../../helpers/code-builder": 38, "../../helpers/shell": 40, "util": 114}],
    76: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for the Shell using HTTPie.
         *
         * @author
         * @AhmadNassri
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var shell = require('../../helpers/shell')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                body: false,
                cert: false,
                headers: false,
                indent: '  ',
                pretty: false,
                print: false,
                queryParams: false,
                short: false,
                style: false,
                timeout: false,
                verbose: false,
                verify: false
            }, options)

            var code = new CodeBuilder(opts.indent, opts.indent !== false ? ' \\\n' + opts.indent : ' ')

            var raw = false
            var flags = []

            if (opts.headers) {
                flags.push(opts.short ? '-h' : '--headers')
        }

            if (opts.body) {
                flags.push(opts.short ? '-b' : '--body')
        }

            if (opts.verbose) {
                flags.push(opts.short ? '-v' : '--verbose')
            }

            if (opts.print) {
                flags.push(util.format('%s=%s', opts.short ? '-p' : '--print', opts.print))
            }

            if (opts.verify) {
                flags.push(util.format('--verify=%s', opts.verify))
            }

            if (opts.cert) {
                flags.push(util.format('--cert=%s', opts.cert))
            }

            if (opts.pretty) {
                flags.push(util.format('--pretty=%s', opts.pretty))
            }

            if (opts.style) {
                flags.push(util.format('--style=%s', opts.pretty))
            }

            if (opts.timeout) {
                flags.push(util.format('--timeout=%s', opts.timeout))
            }

            // construct query params
            if (opts.queryParams) {
                var queryStringKeys = Object.keys(source.queryObj)

                queryStringKeys.forEach(function (name) {
                    var value = source.queryObj[name]

                    if (util.isArray(value)) {
                        value.forEach(function (val) {
                            code.push('%s==%s', name, shell.quote(val))
                        })
                    } else {
                        code.push('%s==%s', name, shell.quote(value))
                    }
            })
            }

            // construct headers
            Object.keys(source.allHeaders).sort().forEach(function (key) {
                code.push('%s:%s', key, shell.quote(source.allHeaders[key]))
            })

            if (source.postData.mimeType === 'application/x-www-form-urlencoded') {
            // construct post params
                if (source.postData.params && source.postData.params.length) {
                    flags.push(opts.short ? '-f' : '--form')

                    source.postData.params.forEach(function (param) {
                        code.push('%s=%s', param.name, shell.quote(param.value))
                    })
                }
            } else {
                raw = true
            }

            code.unshift('http %s%s %s', flags.length ? flags.join(' ') + ' ' : '', source.method, shell.quote(opts.queryParams ? source.url : source.fullUrl))

            if (raw && source.postData.text) {
                code.unshift('echo %s | ', shell.quote(source.postData.text))
            }

            return code.join()
        }

        module.exports.info = {
            key: 'httpie',
            title: 'HTTPie',
            link: 'http://httpie.org/',
            description: 'a CLI, cURL-like tool for humans'
        }

    }, {"../../helpers/code-builder": 38, "../../helpers/shell": 40, "util": 114}],
    77: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'shell',
                title: 'Shell',
                extname: '.sh',
                default: 'curl'
            },

            curl: require('./curl'),
            httpie: require('./httpie'),
            wget: require('./wget')
        }

    }, {"./curl": 75, "./httpie": 76, "./wget": 78}],
    78: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for the Shell using Wget.
         *
         * @author
         * @AhmadNassri
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var helpers = require('../../helpers/shell')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                indent: '  ',
                short: false,
                verbose: false
            }, options)

            var code = new CodeBuilder(opts.indent, opts.indent !== false ? ' \\\n' + opts.indent : ' ')

            if (opts.verbose) {
                code.push('wget %s', opts.short ? '-v' : '--verbose')
            } else {
                code.push('wget %s', opts.short ? '-q' : '--quiet')
            }

            code.push('--method %s', helpers.quote(source.method))

            Object.keys(source.allHeaders).forEach(function (key) {
                var header = util.format('%s: %s', key, source.allHeaders[key])
                code.push('--header %s', helpers.quote(header))
            })

            if (source.postData.text) {
                code.push('--body-data ' + helpers.escape(helpers.quote(source.postData.text)))
            }

            code.push(opts.short ? '-O' : '--output-document')
                .push('- %s', helpers.quote(source.fullUrl))

            return code.join()
        }

        module.exports.info = {
            key: 'wget',
            title: 'Wget',
            link: 'https://www.gnu.org/software/wget/',
            description: 'a free software package for retrieving files using HTTP, HTTPS'
        }

    }, {"../../helpers/code-builder": 38, "../../helpers/shell": 40, "util": 114}],
    79: [function (require, module, exports) {
        'use strict'

        var util = require('util')

        /**
         * Create an string of given length filled with blank spaces
         *
         * @param {number} length Length of the array to return
         * @return {string}
         */
        function buildString(length, str) {
            return Array.apply(null, new Array(length)).map(String.prototype.valueOf, str).join('')
        }

        /**
         * Create a string corresponding to a Dictionary or Array literal representation with pretty option
         * and indentation.
         */
        function concatArray(arr, pretty, indentation, indentLevel) {
            var currentIndent = buildString(indentLevel, indentation)
            var closingBraceIndent = buildString(indentLevel - 1, indentation)
            var join = pretty ? ',\n' + currentIndent : ', '

            if (pretty) {
                return '[\n' + currentIndent + arr.join(join) + '\n' + closingBraceIndent + ']'
            } else {
                return '[' + arr.join(join) + ']'
            }
        }

        module.exports = {
            /**
             * Create a string corresponding to a valid declaration and initialization of a Swift array or dictionary literal
             *
             * @param {string} name Desired name of the instance
             * @param {Object} parameters Key-value object of parameters to translate to a Swift object litearal
             * @param {Object} opts Target options
             * @return {string}
             */
            literalDeclaration: function (name, parameters, opts) {
                return util.format('let %s = %s', name, this.literalRepresentation(parameters, opts))
            },

            /**
             * Create a valid Swift string of a literal value according to its type.
             *
             * @param {*} value Any JavaScript literal
             * @param {Object} opts Target options
             * @return {string}
             */
            literalRepresentation: function (value, opts, indentLevel) {
                indentLevel = indentLevel === undefined ? 1 : indentLevel + 1

                switch (Object.prototype.toString.call(value)) {
                    case '[object Number]':
                        return value
                    case '[object Array]':
                        // Don't prettify arrays nto not take too much space
                        var pretty = false
                        var valuesRepresentation = value.map(function (v) {
                            // Switch to prettify if the value is a dictionary with multiple keys
                            if (Object.prototype.toString.call(v) === '[object Object]') {
                                pretty = Object.keys(v).length > 1
                            }
                            return this.literalRepresentation(v, opts, indentLevel)
                        }.bind(this))
                        return concatArray(valuesRepresentation, pretty, opts.indent, indentLevel)
                    case '[object Object]':
                        var keyValuePairs = []
                        for (var k in value) {
                            keyValuePairs.push(util.format('"%s": %s', k, this.literalRepresentation(value[k], opts, indentLevel)))
                        }
                        return concatArray(keyValuePairs, opts.pretty && keyValuePairs.length > 1, opts.indent, indentLevel)
                    case '[object Boolean]':
                        return value.toString()
                    default:
                        return '"' + value.toString().replace(/"/g, '\\"') + '"'
                }
            }
        }

    }, {"util": 114}],
    80: [function (require, module, exports) {
        'use strict'

        module.exports = {
            info: {
                key: 'swift',
                title: 'Swift',
                extname: '.swift',
                default: 'nsurlsession'
            },

            nsurlsession: require('./nsurlsession')
        }

    }, {"./nsurlsession": 81}],
    81: [function (require, module, exports) {
        /**
         * @description
         * HTTP code snippet generator for Swift using NSURLSession.
         *
         * @author
         * @thibaultCha
         *
         * for any questions or issues regarding the generated code snippet, please open an issue mentioning the author.
         */

        'use strict'

        var util = require('util')
        var helpers = require('./helpers')
        var CodeBuilder = require('../../helpers/code-builder')

        module.exports = function (source, options) {
            var opts = util._extend({
                indent: '  ',
                pretty: true,
                timeout: '10'
            }, options)

            var code = new CodeBuilder(opts.indent)

            // Markers for headers to be created as litteral objects and later be set on the NSURLRequest if exist
            var req = {
                hasHeaders: false,
                hasBody: false
            }

            // We just want to make sure people understand that is the only dependency
            code.push('import Foundation')

            if (Object.keys(source.allHeaders).length) {
                req.hasHeaders = true
                code.blank()
                    .push(helpers.literalDeclaration('headers', source.allHeaders, opts))
            }

            if (source.postData.text || source.postData.jsonObj || source.postData.params) {
                req.hasBody = true

            switch (source.postData.mimeType) {
                case 'application/x-www-form-urlencoded':
                    // By appending parameters one by one in the resulting snippet,
                    // we make it easier for the user to edit it according to his or her needs after pasting.
                    // The user can just add/remove lines adding/removing body parameters.
                    code.blank()
                        .push('var postData = NSMutableData(data: "%s=%s".dataUsingEncoding(NSUTF8StringEncoding)!)', source.postData.params[0].name, source.postData.params[0].value)
                    for (var i = 1, len = source.postData.params.length; i < len; i++) {
                        code.push('postData.appendData("&%s=%s".dataUsingEncoding(NSUTF8StringEncoding)!)', source.postData.params[i].name, source.postData.params[i].value)
                    }
                    break

                case 'application/json':
                    if (source.postData.jsonObj) {
                        code.push(helpers.literalDeclaration('parameters', source.postData.jsonObj, opts))
                            .blank()
                            .push('let postData = NSJSONSerialization.dataWithJSONObject(parameters, options: nil, error: nil)')
                    }
                    break

                case 'multipart/form-data':
                    /**
                     * By appending multipart parameters one by one in the resulting snippet,
                     * we make it easier for the user to edit it according to his or her needs after pasting.
                     * The user can just edit the parameters NSDictionary or put this part of a snippet in a multipart builder method.
                     */
                    code.push(helpers.literalDeclaration('parameters', source.postData.params, opts))
                        .blank()
                        .push('let boundary = "%s"', source.postData.boundary)
                        .blank()
                        .push('var body = ""')
                        .push('var error: NSError? = nil')
                        .push('for param in parameters {')
                        .push(1, 'let paramName = param["name"]!')
                        .push(1, 'body += "--\\(boundary)\\r\\n"')
                        .push(1, 'body += "Content-Disposition:form-data; name=\\"\\(paramName)\\""')
                        .push(1, 'if let filename = param["fileName"] {')
                        .push(2, 'let contentType = param["content-type"]!')
                        .push(2, 'let fileContent = String(contentsOfFile: filename, encoding: NSUTF8StringEncoding, error: &error)')
                        .push(2, 'if (error != nil) {')
                        .push(3, 'print(error)')
                        .push(2, '}')
                        .push(2, 'body += "; filename=\\"\\(filename)\\"\\r\\n"')
                        .push(2, 'body += "Content-Type: \\(contentType)\\r\\n\\r\\n"')
                        .push(2, 'body += fileContent!')
                        .push(1, '} else if let paramValue = param["value"] {')
                        .push(2, 'body += "\\r\\n\\r\\n\\(paramValue)"')
                        .push(1, '}')
                        .push('}')
                    break

                default:
                    code.blank()
                        .push('let postData = NSData(data: "%s".dataUsingEncoding(NSUTF8StringEncoding)!)', source.postData.text)
            }
            }

            code.blank()
                // NSURLRequestUseProtocolCachePolicy is the default policy, let's just always set it to avoid confusion.
                .push('var request = NSMutableURLRequest(URL: NSURL(string: "%s")!,', source.fullUrl)
                .push('                                        cachePolicy: .UseProtocolCachePolicy,')
                .push('                                    timeoutInterval: %s)', parseInt(opts.timeout, 10).toFixed(1))
                .push('request.HTTPMethod = "%s"', source.method)

            if (req.hasHeaders) {
                code.push('request.allHTTPHeaderFields = headers')
        }

            if (req.hasBody) {
                code.push('request.HTTPBody = postData')
        }

            code.blank()
                // Retrieving the shared session will be less verbose than creating a new one.
                .push('let session = NSURLSession.sharedSession()')
                .push('let dataTask = session.dataTaskWithRequest(request, completionHandler: { (data, response, error) -> Void in')
                .push(1, 'if (error != nil) {')
                .push(2, 'print(error)')
                .push(1, '} else {')
                // Casting the NSURLResponse to NSHTTPURLResponse so the user can see the status     .
                .push(2, 'let httpResponse = response as? NSHTTPURLResponse')
                .push(2, 'print(httpResponse)')
                .push(1, '}')
                .push('})')
                .blank()
                .push('dataTask.resume()')

            return code.join()
        }

        module.exports.info = {
            key: 'nsurlsession',
            title: 'NSURLSession',
            link: 'https://developer.apple.com/library/mac/documentation/Foundation/Reference/NSURLSession_class/index.html',
            description: 'Foundation\'s NSURLSession request'
        }

    }, {"../../helpers/code-builder": 38, "./helpers": 79, "util": 114}],
    82: [function (require, module, exports) {
        ;
        (function (window) {
            'use strict';

            var HTTPSnippet = require('httpsnippet');

            window.HTTPSnippetInstance = function (config) {
                return new HTTPSnippet(config);
        }

        })(window);

    }, {"httpsnippet": 41}],
    83: [function (require, module, exports) {

    }, {}],
    84: [function (require, module, exports) {
        (function (global) {
            /*!
             * The buffer module from node.js, for the browser.
         *
             * @author   Feross Aboukhadijeh <feross@feross.org> <http://feross.org>
             * @license  MIT
         */
            /* eslint-disable no-proto */

            var base64 = require('base64-js')
            var ieee754 = require('ieee754')
            var isArray = require('is-array')

            exports.Buffer = Buffer
            exports.SlowBuffer = SlowBuffer
            exports.INSPECT_MAX_BYTES = 50
            Buffer.poolSize = 8192 // not used by this implementation

            var rootParent = {}

        /**
         * If `Buffer.TYPED_ARRAY_SUPPORT`:
         *   === true    Use Uint8Array implementation (fastest)
         *   === false   Use Object implementation (most compatible, even IE6)
         *
         * Browsers that support typed arrays are IE 10+, Firefox 4+, Chrome 7+, Safari 5.1+,
         * Opera 11.6+, iOS 4.2+.
         *
         * Due to various browser bugs, sometimes the Object implementation will be used even
         * when the browser supports typed arrays.
         *
         * Note:
         *
         *   - Firefox 4-29 lacks support for adding new properties to `Uint8Array` instances,
         *     See: https://bugzilla.mozilla.org/show_bug.cgi?id=695438.
         *
         *   - Safari 5-7 lacks support for changing the `Object.prototype.constructor` property
         *     on objects.
         *
         *   - Chrome 9-10 is missing the `TypedArray.prototype.subarray` function.
         *
         *   - IE10 has a broken `TypedArray.prototype.subarray` function which returns arrays of
         *     incorrect length in some situations.

         * We detect these buggy browsers and set `Buffer.TYPED_ARRAY_SUPPORT` to `false` so they
         * get the Object implementation, which is slower but behaves correctly.
         */
        Buffer.TYPED_ARRAY_SUPPORT = global.TYPED_ARRAY_SUPPORT !== undefined
            ? global.TYPED_ARRAY_SUPPORT
            : typedArraySupport()

            function typedArraySupport() {
                function Bar() {
                }

                try {
                    var arr = new Uint8Array(1)
                    arr.foo = function () {
                        return 42
                    }
                    arr.constructor = Bar
                    return arr.foo() === 42 && // typed array instances can be augmented
                        arr.constructor === Bar && // constructor can be set
                        typeof arr.subarray === 'function' && // chrome 9-10 lack `subarray`
                        arr.subarray(1, 1).byteLength === 0 // ie10 has broken `subarray`
                } catch (e) {
                    return false
            }
        }

            function kMaxLength() {
                return Buffer.TYPED_ARRAY_SUPPORT
                    ? 0x7fffffff
                    : 0x3fffffff
        }

        /**
         * Class: Buffer
         * =============
         *
         * The Buffer constructor returns instances of `Uint8Array` that are augmented
         * with function properties for all the node `Buffer` API functions. We use
         * `Uint8Array` so that square bracket notation works as expected -- it returns
         * a single octet.
         *
         * By augmenting the instances, we can avoid modifying the `Uint8Array`
         * prototype.
         */
        function Buffer(arg) {
            if (!(this instanceof Buffer)) {
                // Avoid going through an ArgumentsAdaptorTrampoline in the common case.
                if (arguments.length > 1) return new Buffer(arg, arguments[1])
                return new Buffer(arg)
            }

            this.length = 0
            this.parent = undefined

            // Common case.
            if (typeof arg === 'number') {
                return fromNumber(this, arg)
            }

            // Slightly less common case.
            if (typeof arg === 'string') {
                return fromString(this, arg, arguments.length > 1 ? arguments[1] : 'utf8')
            }

            // Unusual.
            return fromObject(this, arg)
        }

            function fromNumber(that, length) {
                that = allocate(that, length < 0 ? 0 : checked(length) | 0)
                if (!Buffer.TYPED_ARRAY_SUPPORT) {
                    for (var i = 0; i < length; i++) {
                        that[i] = 0
                }
            }
                return that
            }

            function fromString(that, string, encoding) {
                if (typeof encoding !== 'string' || encoding === '') encoding = 'utf8'

                // Assumption: byteLength() return value is always < kMaxLength.
                var length = byteLength(string, encoding) | 0
                that = allocate(that, length)

                that.write(string, encoding)
                return that
        }

            function fromObject(that, object) {
                if (Buffer.isBuffer(object)) return fromBuffer(that, object)

                if (isArray(object)) return fromArray(that, object)

                if (object == null) {
                    throw new TypeError('must start with number, buffer, array or string')
            }

                if (typeof ArrayBuffer !== 'undefined') {
                    if (object.buffer instanceof ArrayBuffer) {
                        return fromTypedArray(that, object)
                }
                    if (object instanceof ArrayBuffer) {
                        return fromArrayBuffer(that, object)
                }
            }

                if (object.length) return fromArrayLike(that, object)

                return fromJsonObject(that, object)
            }

            function fromBuffer(that, buffer) {
                var length = checked(buffer.length) | 0
                that = allocate(that, length)
                buffer.copy(that, 0, 0, length)
                return that
            }

            function fromArray(that, array) {
                var length = checked(array.length) | 0
                that = allocate(that, length)
                for (var i = 0; i < length; i += 1) {
                    that[i] = array[i] & 255
            }
                return that
            }

// Duplicate of fromArray() to keep fromArray() monomorphic.
            function fromTypedArray(that, array) {
                var length = checked(array.length) | 0
                that = allocate(that, length)
                // Truncating the elements is probably not what people expect from typed
                // arrays with BYTES_PER_ELEMENT > 1 but it's compatible with the behavior
                // of the old Buffer constructor.
                for (var i = 0; i < length; i += 1) {
                    that[i] = array[i] & 255
            }
                return that
            }

            function fromArrayBuffer(that, array) {
                if (Buffer.TYPED_ARRAY_SUPPORT) {
                    // Return an augmented `Uint8Array` instance, for best performance
                    array.byteLength
                    that = Buffer._augment(new Uint8Array(array))
                } else {
                    // Fallback: Return an object instance of the Buffer class
                    that = fromTypedArray(that, new Uint8Array(array))
            }
                return that
            }

            function fromArrayLike(that, array) {
                var length = checked(array.length) | 0
                that = allocate(that, length)
                for (var i = 0; i < length; i += 1) {
                    that[i] = array[i] & 255
            }
                return that
            }

// Deserialize { type: 'Buffer', data: [1,2,3,...] } into a Buffer object.
// Returns a zero-length buffer for inputs that don't conform to the spec.
            function fromJsonObject(that, object) {
                var array
                var length = 0

                if (object.type === 'Buffer' && isArray(object.data)) {
                    array = object.data
                    length = checked(array.length) | 0
                }
                that = allocate(that, length)

                for (var i = 0; i < length; i += 1) {
                    that[i] = array[i] & 255
            }
                return that
            }

            if (Buffer.TYPED_ARRAY_SUPPORT) {
                Buffer.prototype.__proto__ = Uint8Array.prototype
                Buffer.__proto__ = Uint8Array
            }

            function allocate(that, length) {
            if (Buffer.TYPED_ARRAY_SUPPORT) {
                // Return an augmented `Uint8Array` instance, for best performance
                that = Buffer._augment(new Uint8Array(length))
                that.__proto__ = Buffer.prototype
            } else {
                // Fallback: Return an object instance of the Buffer class
                that.length = length
                that._isBuffer = true
            }

                var fromPool = length !== 0 && length <= Buffer.poolSize >>> 1
                if (fromPool) that.parent = rootParent

                return that
            }

            function checked(length) {
                // Note: cannot use `length < kMaxLength` here because that fails when
                // length is NaN (which is otherwise coerced to zero.)
                if (length >= kMaxLength()) {
                    throw new RangeError('Attempt to allocate Buffer larger than maximum ' +
                        'size: 0x' + kMaxLength().toString(16) + ' bytes')
            }
                return length | 0
            }

            function SlowBuffer(subject, encoding) {
                if (!(this instanceof SlowBuffer)) return new SlowBuffer(subject, encoding)

                var buf = new Buffer(subject, encoding)
                delete buf.parent
                return buf
            }

            Buffer.isBuffer = function isBuffer(b) {
                return !!(b != null && b._isBuffer)
            }

            Buffer.compare = function compare(a, b) {
                if (!Buffer.isBuffer(a) || !Buffer.isBuffer(b)) {
                    throw new TypeError('Arguments must be Buffers')
            }

                if (a === b) return 0

                var x = a.length
                var y = b.length

                var i = 0
                var len = Math.min(x, y)
                while (i < len) {
                    if (a[i] !== b[i]) break

                    ++i
            }

                if (i !== len) {
                    x = a[i]
                    y = b[i]
            }

                if (x < y) return -1
                if (y < x) return 1
                return 0
            }

            Buffer.isEncoding = function isEncoding(encoding) {
                switch (String(encoding).toLowerCase()) {
                    case 'hex':
                    case 'utf8':
                    case 'utf-8':
                    case 'ascii':
                    case 'binary':
                    case 'base64':
                    case 'raw':
                    case 'ucs2':
                    case 'ucs-2':
                    case 'utf16le':
                    case 'utf-16le':
                        return true
                    default:
                        return false
                }
            }

            Buffer.concat = function concat(list, length) {
                if (!isArray(list)) throw new TypeError('list argument must be an Array of Buffers.')

                if (list.length === 0) {
                    return new Buffer(0)
                }

                var i
                if (length === undefined) {
                    length = 0
                for (i = 0; i < list.length; i++) {
                    length += list[i].length
                }
            }

                var buf = new Buffer(length)
                var pos = 0
                for (i = 0; i < list.length; i++) {
                    var item = list[i]
                    item.copy(buf, pos)
                    pos += item.length
                }
                return buf
            }

            function byteLength(string, encoding) {
                if (typeof string !== 'string') string = '' + string

                var len = string.length
                if (len === 0) return 0

                // Use a for loop to avoid recursion
                var loweredCase = false
                for (; ;) {
                    switch (encoding) {
                        case 'ascii':
                        case 'binary':
                        // Deprecated
                        case 'raw':
                        case 'raws':
                            return len
                        case 'utf8':
                        case 'utf-8':
                            return utf8ToBytes(string).length
                        case 'ucs2':
                        case 'ucs-2':
                        case 'utf16le':
                        case 'utf-16le':
                            return len * 2
                        case 'hex':
                            return len >>> 1
                        case 'base64':
                            return base64ToBytes(string).length
                        default:
                            if (loweredCase) return utf8ToBytes(string).length // assume utf8
                            encoding = ('' + encoding).toLowerCase()
                            loweredCase = true
                }
            }
            }

            Buffer.byteLength = byteLength

// pre-set for values that may exist in the future
            Buffer.prototype.length = undefined
            Buffer.prototype.parent = undefined

            function slowToString(encoding, start, end) {
                var loweredCase = false

                start = start | 0
                end = end === undefined || end === Infinity ? this.length : end | 0

                if (!encoding) encoding = 'utf8'
                if (start < 0) start = 0
                if (end > this.length) end = this.length
                if (end <= start) return ''

                while (true) {
                    switch (encoding) {
                        case 'hex':
                            return hexSlice(this, start, end)

                        case 'utf8':
                        case 'utf-8':
                            return utf8Slice(this, start, end)

                        case 'ascii':
                            return asciiSlice(this, start, end)

                        case 'binary':
                            return binarySlice(this, start, end)

                        case 'base64':
                            return base64Slice(this, start, end)

                        case 'ucs2':
                        case 'ucs-2':
                        case 'utf16le':
                        case 'utf-16le':
                            return utf16leSlice(this, start, end)

                        default:
                            if (loweredCase) throw new TypeError('Unknown encoding: ' + encoding)
                            encoding = (encoding + '').toLowerCase()
                            loweredCase = true
                }
            }
            }

            Buffer.prototype.toString = function toString() {
                var length = this.length | 0
                if (length === 0) return ''
                if (arguments.length === 0) return utf8Slice(this, 0, length)
                return slowToString.apply(this, arguments)
            }

            Buffer.prototype.equals = function equals(b) {
                if (!Buffer.isBuffer(b)) throw new TypeError('Argument must be a Buffer')
                if (this === b) return true
                return Buffer.compare(this, b) === 0
            }

            Buffer.prototype.inspect = function inspect() {
                var str = ''
                var max = exports.INSPECT_MAX_BYTES
                if (this.length > 0) {
                    str = this.toString('hex', 0, max).match(/.{2}/g).join(' ')
                    if (this.length > max) str += ' ... '
            }
                return '<Buffer ' + str + '>'
            }

            Buffer.prototype.compare = function compare(b) {
                if (!Buffer.isBuffer(b)) throw new TypeError('Argument must be a Buffer')
                if (this === b) return 0
                return Buffer.compare(this, b)
            }

            Buffer.prototype.indexOf = function indexOf(val, byteOffset) {
                if (byteOffset > 0x7fffffff) byteOffset = 0x7fffffff
                else if (byteOffset < -0x80000000) byteOffset = -0x80000000
                byteOffset >>= 0

                if (this.length === 0) return -1
                if (byteOffset >= this.length) return -1

                // Negative offsets start from the end of the buffer
                if (byteOffset < 0) byteOffset = Math.max(this.length + byteOffset, 0)

                if (typeof val === 'string') {
                    if (val.length === 0) return -1 // special case: looking for empty string always fails
                    return String.prototype.indexOf.call(this, val, byteOffset)
                }
                if (Buffer.isBuffer(val)) {
                    return arrayIndexOf(this, val, byteOffset)
                }
                if (typeof val === 'number') {
                    if (Buffer.TYPED_ARRAY_SUPPORT && Uint8Array.prototype.indexOf === 'function') {
                        return Uint8Array.prototype.indexOf.call(this, val, byteOffset)
                }
                    return arrayIndexOf(this, [val], byteOffset)
                }

                function arrayIndexOf(arr, val, byteOffset) {
                    var foundIndex = -1
                    for (var i = 0; byteOffset + i < arr.length; i++) {
                        if (arr[byteOffset + i] === val[foundIndex === -1 ? 0 : i - foundIndex]) {
                            if (foundIndex === -1) foundIndex = i
                            if (i - foundIndex + 1 === val.length) return byteOffset + foundIndex
                        } else {
                            foundIndex = -1
                    }
                }
                    return -1
            }

                throw new TypeError('val must be string, number or Buffer')
            }

// `get` is deprecated
            Buffer.prototype.get = function get(offset) {
                console.log('.get() is deprecated. Access using array indexes instead.')
                return this.readUInt8(offset)
            }

// `set` is deprecated
            Buffer.prototype.set = function set(v, offset) {
                console.log('.set() is deprecated. Access using array indexes instead.')
                return this.writeUInt8(v, offset)
            }

            function hexWrite(buf, string, offset, length) {
                offset = Number(offset) || 0
                var remaining = buf.length - offset
                if (!length) {
                    length = remaining
                } else {
                    length = Number(length)
                    if (length > remaining) {
                    length = remaining
                }
                }

                // must be an even number of digits
                var strLen = string.length
                if (strLen % 2 !== 0) throw new Error('Invalid hex string')

                if (length > strLen / 2) {
                    length = strLen / 2
            }
                for (var i = 0; i < length; i++) {
                    var parsed = parseInt(string.substr(i * 2, 2), 16)
                    if (isNaN(parsed)) throw new Error('Invalid hex string')
                    buf[offset + i] = parsed
            }
                return i
            }

            function utf8Write(buf, string, offset, length) {
                return blitBuffer(utf8ToBytes(string, buf.length - offset), buf, offset, length)
            }

            function asciiWrite(buf, string, offset, length) {
                return blitBuffer(asciiToBytes(string), buf, offset, length)
            }

            function binaryWrite(buf, string, offset, length) {
                return asciiWrite(buf, string, offset, length)
            }

            function base64Write(buf, string, offset, length) {
                return blitBuffer(base64ToBytes(string), buf, offset, length)
            }

            function ucs2Write(buf, string, offset, length) {
                return blitBuffer(utf16leToBytes(string, buf.length - offset), buf, offset, length)
            }

            Buffer.prototype.write = function write(string, offset, length, encoding) {
                // Buffer#write(string)
                if (offset === undefined) {
                    encoding = 'utf8'
                    length = this.length
                    offset = 0
                    // Buffer#write(string, encoding)
                } else if (length === undefined && typeof offset === 'string') {
                    encoding = offset
                    length = this.length
                    offset = 0
                    // Buffer#write(string, offset[, length][, encoding])
                } else if (isFinite(offset)) {
                    offset = offset | 0
                    if (isFinite(length)) {
                        length = length | 0
                        if (encoding === undefined) encoding = 'utf8'
                } else {
                        encoding = length
                        length = undefined
                }
                    // legacy write(string, encoding, offset, length) - remove in v0.13
                } else {
                    var swap = encoding
                    encoding = offset
                    offset = length | 0
                    length = swap
                }

                var remaining = this.length - offset
                if (length === undefined || length > remaining) length = remaining

                if ((string.length > 0 && (length < 0 || offset < 0)) || offset > this.length) {
                    throw new RangeError('attempt to write outside buffer bounds')
                }

                if (!encoding) encoding = 'utf8'

                var loweredCase = false
                for (; ;) {
                    switch (encoding) {
                        case 'hex':
                            return hexWrite(this, string, offset, length)

                        case 'utf8':
                        case 'utf-8':
                            return utf8Write(this, string, offset, length)

                        case 'ascii':
                            return asciiWrite(this, string, offset, length)

                        case 'binary':
                            return binaryWrite(this, string, offset, length)

                        case 'base64':
                            // Warning: maxLength not taken into account in base64Write
                            return base64Write(this, string, offset, length)

                        case 'ucs2':
                        case 'ucs-2':
                        case 'utf16le':
                        case 'utf-16le':
                            return ucs2Write(this, string, offset, length)

                        default:
                            if (loweredCase) throw new TypeError('Unknown encoding: ' + encoding)
                            encoding = ('' + encoding).toLowerCase()
                            loweredCase = true
                }
            }
            }

            Buffer.prototype.toJSON = function toJSON() {
                return {
                    type: 'Buffer',
                    data: Array.prototype.slice.call(this._arr || this, 0)
            }
            }

            function base64Slice(buf, start, end) {
                if (start === 0 && end === buf.length) {
                    return base64.fromByteArray(buf)
                } else {
                    return base64.fromByteArray(buf.slice(start, end))
            }
            }

            function utf8Slice(buf, start, end) {
                end = Math.min(buf.length, end)
                var res = []

                var i = start
                while (i < end) {
                    var firstByte = buf[i]
                    var codePoint = null
                    var bytesPerSequence = (firstByte > 0xEF) ? 4
                        : (firstByte > 0xDF) ? 3
                        : (firstByte > 0xBF) ? 2
                        : 1

                    if (i + bytesPerSequence <= end) {
                        var secondByte, thirdByte, fourthByte, tempCodePoint

                        switch (bytesPerSequence) {
                            case 1:
                                if (firstByte < 0x80) {
                                    codePoint = firstByte
                                }
                                break
                            case 2:
                                secondByte = buf[i + 1]
                                if ((secondByte & 0xC0) === 0x80) {
                                    tempCodePoint = (firstByte & 0x1F) << 0x6 | (secondByte & 0x3F)
                                    if (tempCodePoint > 0x7F) {
                                        codePoint = tempCodePoint
                                }
                                }
                                break
                            case 3:
                                secondByte = buf[i + 1]
                                thirdByte = buf[i + 2]
                                if ((secondByte & 0xC0) === 0x80 && (thirdByte & 0xC0) === 0x80) {
                                    tempCodePoint = (firstByte & 0xF) << 0xC | (secondByte & 0x3F) << 0x6 | (thirdByte & 0x3F)
                                    if (tempCodePoint > 0x7FF && (tempCodePoint < 0xD800 || tempCodePoint > 0xDFFF)) {
                                        codePoint = tempCodePoint
                                }
                                }
                                break
                            case 4:
                                secondByte = buf[i + 1]
                                thirdByte = buf[i + 2]
                                fourthByte = buf[i + 3]
                                if ((secondByte & 0xC0) === 0x80 && (thirdByte & 0xC0) === 0x80 && (fourthByte & 0xC0) === 0x80) {
                                    tempCodePoint = (firstByte & 0xF) << 0x12 | (secondByte & 0x3F) << 0xC | (thirdByte & 0x3F) << 0x6 | (fourthByte & 0x3F)
                                    if (tempCodePoint > 0xFFFF && tempCodePoint < 0x110000) {
                                        codePoint = tempCodePoint
                                }
                                }
                    }
                    }

                    if (codePoint === null) {
                        // we did not generate a valid codePoint so insert a
                        // replacement char (U+FFFD) and advance only 1 byte
                        codePoint = 0xFFFD
                        bytesPerSequence = 1
                    } else if (codePoint > 0xFFFF) {
                        // encode to utf16 (surrogate pair dance)
                        codePoint -= 0x10000
                        res.push(codePoint >>> 10 & 0x3FF | 0xD800)
                        codePoint = 0xDC00 | codePoint & 0x3FF
                }

                    res.push(codePoint)
                    i += bytesPerSequence
            }

                return decodeCodePointsArray(res)
            }

// Based on http://stackoverflow.com/a/22747272/680742, the browser with
// the lowest limit is Chrome, with 0x10000 args.
// We go 1 magnitude less, for safety
            var MAX_ARGUMENTS_LENGTH = 0x1000

            function decodeCodePointsArray(codePoints) {
                var len = codePoints.length
                if (len <= MAX_ARGUMENTS_LENGTH) {
                    return String.fromCharCode.apply(String, codePoints) // avoid extra slice()
                }

                // Decode in chunks to avoid "call stack size exceeded".
                var res = ''
                var i = 0
                while (i < len) {
                    res += String.fromCharCode.apply(
                        String,
                        codePoints.slice(i, i += MAX_ARGUMENTS_LENGTH)
                    )
            }
                return res
            }

            function asciiSlice(buf, start, end) {
                var ret = ''
                end = Math.min(buf.length, end)

                for (var i = start; i < end; i++) {
                    ret += String.fromCharCode(buf[i] & 0x7F)
            }
                return ret
            }

            function binarySlice(buf, start, end) {
                var ret = ''
                end = Math.min(buf.length, end)

                for (var i = start; i < end; i++) {
                    ret += String.fromCharCode(buf[i])
            }
                return ret
            }

            function hexSlice(buf, start, end) {
                var len = buf.length

                if (!start || start < 0) start = 0
                if (!end || end < 0 || end > len) end = len

                var out = ''
                for (var i = start; i < end; i++) {
                    out += toHex(buf[i])
            }
                return out
            }

            function utf16leSlice(buf, start, end) {
                var bytes = buf.slice(start, end)
                var res = ''
                for (var i = 0; i < bytes.length; i += 2) {
                    res += String.fromCharCode(bytes[i] + bytes[i + 1] * 256)
            }
                return res
            }

            Buffer.prototype.slice = function slice(start, end) {
                var len = this.length
                start = ~~start
                end = end === undefined ? len : ~~end

                if (start < 0) {
                    start += len
                    if (start < 0) start = 0
                } else if (start > len) {
                    start = len
                }

                if (end < 0) {
                    end += len
                    if (end < 0) end = 0
                } else if (end > len) {
                    end = len
                }

                if (end < start) end = start

                var newBuf
                if (Buffer.TYPED_ARRAY_SUPPORT) {
                    newBuf = Buffer._augment(this.subarray(start, end))
                } else {
                    var sliceLen = end - start
                    newBuf = new Buffer(sliceLen, undefined)
                    for (var i = 0; i < sliceLen; i++) {
                        newBuf[i] = this[i + start]
                }
                }

                if (newBuf.length) newBuf.parent = this.parent || this

                return newBuf
            }

            /*
             * Need to make sure that buffer isn't trying to write out of bounds.
             */
            function checkOffset(offset, ext, length) {
                if ((offset % 1) !== 0 || offset < 0) throw new RangeError('offset is not uint')
                if (offset + ext > length) throw new RangeError('Trying to access beyond buffer length')
            }

            Buffer.prototype.readUIntLE = function readUIntLE(offset, byteLength, noAssert) {
                offset = offset | 0
                byteLength = byteLength | 0
                if (!noAssert) checkOffset(offset, byteLength, this.length)

                var val = this[offset]
                var mul = 1
                var i = 0
                while (++i < byteLength && (mul *= 0x100)) {
                    val += this[offset + i] * mul
            }

                return val
            }

            Buffer.prototype.readUIntBE = function readUIntBE(offset, byteLength, noAssert) {
                offset = offset | 0
                byteLength = byteLength | 0
                if (!noAssert) {
                    checkOffset(offset, byteLength, this.length)
                }

                var val = this[offset + --byteLength]
                var mul = 1
                while (byteLength > 0 && (mul *= 0x100)) {
                    val += this[offset + --byteLength] * mul
            }

                return val
            }

            Buffer.prototype.readUInt8 = function readUInt8(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 1, this.length)
                return this[offset]
            }

            Buffer.prototype.readUInt16LE = function readUInt16LE(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 2, this.length)
                return this[offset] | (this[offset + 1] << 8)
            }

            Buffer.prototype.readUInt16BE = function readUInt16BE(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 2, this.length)
                return (this[offset] << 8) | this[offset + 1]
            }

            Buffer.prototype.readUInt32LE = function readUInt32LE(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 4, this.length)

                return ((this[offset]) |
                    (this[offset + 1] << 8) |
                    (this[offset + 2] << 16)) +
                    (this[offset + 3] * 0x1000000)
            }

            Buffer.prototype.readUInt32BE = function readUInt32BE(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 4, this.length)

                return (this[offset] * 0x1000000) +
                    ((this[offset + 1] << 16) |
                    (this[offset + 2] << 8) |
                    this[offset + 3])
            }

            Buffer.prototype.readIntLE = function readIntLE(offset, byteLength, noAssert) {
                offset = offset | 0
                byteLength = byteLength | 0
                if (!noAssert) checkOffset(offset, byteLength, this.length)

                var val = this[offset]
                var mul = 1
                var i = 0
                while (++i < byteLength && (mul *= 0x100)) {
                    val += this[offset + i] * mul
            }
                mul *= 0x80

                if (val >= mul) val -= Math.pow(2, 8 * byteLength)

                return val
            }

            Buffer.prototype.readIntBE = function readIntBE(offset, byteLength, noAssert) {
                offset = offset | 0
                byteLength = byteLength | 0
                if (!noAssert) checkOffset(offset, byteLength, this.length)

                var i = byteLength
                var mul = 1
                var val = this[offset + --i]
                while (i > 0 && (mul *= 0x100)) {
                    val += this[offset + --i] * mul
            }
                mul *= 0x80

                if (val >= mul) val -= Math.pow(2, 8 * byteLength)

                return val
            }

            Buffer.prototype.readInt8 = function readInt8(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 1, this.length)
                if (!(this[offset] & 0x80)) return (this[offset])
                return ((0xff - this[offset] + 1) * -1)
            }

            Buffer.prototype.readInt16LE = function readInt16LE(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 2, this.length)
                var val = this[offset] | (this[offset + 1] << 8)
                return (val & 0x8000) ? val | 0xFFFF0000 : val
            }

            Buffer.prototype.readInt16BE = function readInt16BE(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 2, this.length)
                var val = this[offset + 1] | (this[offset] << 8)
                return (val & 0x8000) ? val | 0xFFFF0000 : val
            }

            Buffer.prototype.readInt32LE = function readInt32LE(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 4, this.length)

                return (this[offset]) |
                    (this[offset + 1] << 8) |
                    (this[offset + 2] << 16) |
                    (this[offset + 3] << 24)
            }

            Buffer.prototype.readInt32BE = function readInt32BE(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 4, this.length)

                return (this[offset] << 24) |
                    (this[offset + 1] << 16) |
                    (this[offset + 2] << 8) |
                    (this[offset + 3])
            }

            Buffer.prototype.readFloatLE = function readFloatLE(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 4, this.length)
                return ieee754.read(this, offset, true, 23, 4)
            }

            Buffer.prototype.readFloatBE = function readFloatBE(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 4, this.length)
                return ieee754.read(this, offset, false, 23, 4)
            }

            Buffer.prototype.readDoubleLE = function readDoubleLE(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 8, this.length)
                return ieee754.read(this, offset, true, 52, 8)
            }

            Buffer.prototype.readDoubleBE = function readDoubleBE(offset, noAssert) {
                if (!noAssert) checkOffset(offset, 8, this.length)
                return ieee754.read(this, offset, false, 52, 8)
            }

            function checkInt(buf, value, offset, ext, max, min) {
                if (!Buffer.isBuffer(buf)) throw new TypeError('buffer must be a Buffer instance')
                if (value > max || value < min) throw new RangeError('value is out of bounds')
                if (offset + ext > buf.length) throw new RangeError('index out of range')
            }

            Buffer.prototype.writeUIntLE = function writeUIntLE(value, offset, byteLength, noAssert) {
                value = +value
                offset = offset | 0
                byteLength = byteLength | 0
                if (!noAssert) checkInt(this, value, offset, byteLength, Math.pow(2, 8 * byteLength), 0)

                var mul = 1
                var i = 0
                this[offset] = value & 0xFF
                while (++i < byteLength && (mul *= 0x100)) {
                    this[offset + i] = (value / mul) & 0xFF
            }

                return offset + byteLength
            }

            Buffer.prototype.writeUIntBE = function writeUIntBE(value, offset, byteLength, noAssert) {
                value = +value
                offset = offset | 0
                byteLength = byteLength | 0
                if (!noAssert) checkInt(this, value, offset, byteLength, Math.pow(2, 8 * byteLength), 0)

                var i = byteLength - 1
                var mul = 1
                this[offset + i] = value & 0xFF
                while (--i >= 0 && (mul *= 0x100)) {
                    this[offset + i] = (value / mul) & 0xFF
            }

                return offset + byteLength
            }

            Buffer.prototype.writeUInt8 = function writeUInt8(value, offset, noAssert) {
                value = +value
                offset = offset | 0
                if (!noAssert) checkInt(this, value, offset, 1, 0xff, 0)
                if (!Buffer.TYPED_ARRAY_SUPPORT) value = Math.floor(value)
                this[offset] = (value & 0xff)
                return offset + 1
            }

            function objectWriteUInt16(buf, value, offset, littleEndian) {
                if (value < 0) value = 0xffff + value + 1
                for (var i = 0, j = Math.min(buf.length - offset, 2); i < j; i++) {
                    buf[offset + i] = (value & (0xff << (8 * (littleEndian ? i : 1 - i)))) >>>
                        (littleEndian ? i : 1 - i) * 8
            }
            }

            Buffer.prototype.writeUInt16LE = function writeUInt16LE(value, offset, noAssert) {
                value = +value
                offset = offset | 0
                if (!noAssert) checkInt(this, value, offset, 2, 0xffff, 0)
                if (Buffer.TYPED_ARRAY_SUPPORT) {
                this[offset] = (value & 0xff)
                    this[offset + 1] = (value >>> 8)
                } else {
                    objectWriteUInt16(this, value, offset, true)
            }
                return offset + 2
            }

            Buffer.prototype.writeUInt16BE = function writeUInt16BE(value, offset, noAssert) {
                value = +value
                offset = offset | 0
                if (!noAssert) checkInt(this, value, offset, 2, 0xffff, 0)
                if (Buffer.TYPED_ARRAY_SUPPORT) {
                    this[offset] = (value >>> 8)
                    this[offset + 1] = (value & 0xff)
                } else {
                    objectWriteUInt16(this, value, offset, false)
            }
                return offset + 2
            }

            function objectWriteUInt32(buf, value, offset, littleEndian) {
                if (value < 0) value = 0xffffffff + value + 1
                for (var i = 0, j = Math.min(buf.length - offset, 4); i < j; i++) {
                    buf[offset + i] = (value >>> (littleEndian ? i : 3 - i) * 8) & 0xff
            }
            }

            Buffer.prototype.writeUInt32LE = function writeUInt32LE(value, offset, noAssert) {
                value = +value
                offset = offset | 0
                if (!noAssert) checkInt(this, value, offset, 4, 0xffffffff, 0)
                if (Buffer.TYPED_ARRAY_SUPPORT) {
                    this[offset + 3] = (value >>> 24)
                    this[offset + 2] = (value >>> 16)
                    this[offset + 1] = (value >>> 8)
                    this[offset] = (value & 0xff)
                } else {
                    objectWriteUInt32(this, value, offset, true)
            }
                return offset + 4
            }

            Buffer.prototype.writeUInt32BE = function writeUInt32BE(value, offset, noAssert) {
                value = +value
                offset = offset | 0
                if (!noAssert) checkInt(this, value, offset, 4, 0xffffffff, 0)
                if (Buffer.TYPED_ARRAY_SUPPORT) {
                    this[offset] = (value >>> 24)
                    this[offset + 1] = (value >>> 16)
                    this[offset + 2] = (value >>> 8)
                    this[offset + 3] = (value & 0xff)
                } else {
                    objectWriteUInt32(this, value, offset, false)
            }
                return offset + 4
            }

            Buffer.prototype.writeIntLE = function writeIntLE(value, offset, byteLength, noAssert) {
                value = +value
                offset = offset | 0
                if (!noAssert) {
                    var limit = Math.pow(2, 8 * byteLength - 1)

                    checkInt(this, value, offset, byteLength, limit - 1, -limit)
            }

                var i = 0
                var mul = 1
                var sub = value < 0 ? 1 : 0
                this[offset] = value & 0xFF
                while (++i < byteLength && (mul *= 0x100)) {
                    this[offset + i] = ((value / mul) >> 0) - sub & 0xFF
            }

                return offset + byteLength
            }

            Buffer.prototype.writeIntBE = function writeIntBE(value, offset, byteLength, noAssert) {
                value = +value
                offset = offset | 0
                if (!noAssert) {
                    var limit = Math.pow(2, 8 * byteLength - 1)

                    checkInt(this, value, offset, byteLength, limit - 1, -limit)
                }

                var i = byteLength - 1
                var mul = 1
                var sub = value < 0 ? 1 : 0
                this[offset + i] = value & 0xFF
                while (--i >= 0 && (mul *= 0x100)) {
                    this[offset + i] = ((value / mul) >> 0) - sub & 0xFF
            }

                return offset + byteLength
            }

            Buffer.prototype.writeInt8 = function writeInt8(value, offset, noAssert) {
                value = +value
                offset = offset | 0
                if (!noAssert) checkInt(this, value, offset, 1, 0x7f, -0x80)
                if (!Buffer.TYPED_ARRAY_SUPPORT) value = Math.floor(value)
                if (value < 0) value = 0xff + value + 1
                this[offset] = (value & 0xff)
                return offset + 1
            }

            Buffer.prototype.writeInt16LE = function writeInt16LE(value, offset, noAssert) {
                value = +value
                offset = offset | 0
                if (!noAssert) checkInt(this, value, offset, 2, 0x7fff, -0x8000)
                if (Buffer.TYPED_ARRAY_SUPPORT) {
                this[offset] = (value & 0xff)
                    this[offset + 1] = (value >>> 8)
                } else {
                    objectWriteUInt16(this, value, offset, true)
            }
                return offset + 2
            }

            Buffer.prototype.writeInt16BE = function writeInt16BE(value, offset, noAssert) {
                value = +value
                offset = offset | 0
                if (!noAssert) checkInt(this, value, offset, 2, 0x7fff, -0x8000)
                if (Buffer.TYPED_ARRAY_SUPPORT) {
                    this[offset] = (value >>> 8)
                    this[offset + 1] = (value & 0xff)
                } else {
                    objectWriteUInt16(this, value, offset, false)
            }
                return offset + 2
            }

            Buffer.prototype.writeInt32LE = function writeInt32LE(value, offset, noAssert) {
                value = +value
                offset = offset | 0
                if (!noAssert) checkInt(this, value, offset, 4, 0x7fffffff, -0x80000000)
                if (Buffer.TYPED_ARRAY_SUPPORT) {
                    this[offset] = (value & 0xff)
                    this[offset + 1] = (value >>> 8)
                    this[offset + 2] = (value >>> 16)
                    this[offset + 3] = (value >>> 24)
                } else {
                    objectWriteUInt32(this, value, offset, true)
            }
                return offset + 4
            }

            Buffer.prototype.writeInt32BE = function writeInt32BE(value, offset, noAssert) {
                value = +value
                offset = offset | 0
                if (!noAssert) checkInt(this, value, offset, 4, 0x7fffffff, -0x80000000)
                if (value < 0) value = 0xffffffff + value + 1
                if (Buffer.TYPED_ARRAY_SUPPORT) {
                    this[offset] = (value >>> 24)
                    this[offset + 1] = (value >>> 16)
                    this[offset + 2] = (value >>> 8)
                    this[offset + 3] = (value & 0xff)
                } else {
                    objectWriteUInt32(this, value, offset, false)
            }
                return offset + 4
            }

            function checkIEEE754(buf, value, offset, ext, max, min) {
                if (value > max || value < min) throw new RangeError('value is out of bounds')
                if (offset + ext > buf.length) throw new RangeError('index out of range')
                if (offset < 0) throw new RangeError('index out of range')
            }

            function writeFloat(buf, value, offset, littleEndian, noAssert) {
                if (!noAssert) {
                    checkIEEE754(buf, value, offset, 4, 3.4028234663852886e+38, -3.4028234663852886e+38)
            }
                ieee754.write(buf, value, offset, littleEndian, 23, 4)
                return offset + 4
            }

            Buffer.prototype.writeFloatLE = function writeFloatLE(value, offset, noAssert) {
                return writeFloat(this, value, offset, true, noAssert)
            }

            Buffer.prototype.writeFloatBE = function writeFloatBE(value, offset, noAssert) {
                return writeFloat(this, value, offset, false, noAssert)
            }

            function writeDouble(buf, value, offset, littleEndian, noAssert) {
                if (!noAssert) {
                    checkIEEE754(buf, value, offset, 8, 1.7976931348623157E+308, -1.7976931348623157E+308)
            }
                ieee754.write(buf, value, offset, littleEndian, 52, 8)
                return offset + 8
            }

            Buffer.prototype.writeDoubleLE = function writeDoubleLE(value, offset, noAssert) {
                return writeDouble(this, value, offset, true, noAssert)
            }

            Buffer.prototype.writeDoubleBE = function writeDoubleBE(value, offset, noAssert) {
                return writeDouble(this, value, offset, false, noAssert)
            }

// copy(targetBuffer, targetStart=0, sourceStart=0, sourceEnd=buffer.length)
            Buffer.prototype.copy = function copy(target, targetStart, start, end) {
                if (!start) start = 0
                if (!end && end !== 0) end = this.length
                if (targetStart >= target.length) targetStart = target.length
                if (!targetStart) targetStart = 0
                if (end > 0 && end < start) end = start

                // Copy 0 bytes; we're done
                if (end === start) return 0
                if (target.length === 0 || this.length === 0) return 0

                // Fatal error conditions
                if (targetStart < 0) {
                    throw new RangeError('targetStart out of bounds')
                }
                if (start < 0 || start >= this.length) throw new RangeError('sourceStart out of bounds')
                if (end < 0) throw new RangeError('sourceEnd out of bounds')

                // Are we oob?
                if (end > this.length) end = this.length
                if (target.length - targetStart < end - start) {
                    end = target.length - targetStart + start
                }

                var len = end - start
                var i

                if (this === target && start < targetStart && targetStart < end) {
                    // descending copy from end
                    for (i = len - 1; i >= 0; i--) {
                        target[i + targetStart] = this[i + start]
                }
                } else if (len < 1000 || !Buffer.TYPED_ARRAY_SUPPORT) {
                    // ascending copy from start
                    for (i = 0; i < len; i++) {
                        target[i + targetStart] = this[i + start]
                    }
                } else {
                    target._set(this.subarray(start, start + len), targetStart)
            }

                return len
            }

// fill(value, start=0, end=buffer.length)
            Buffer.prototype.fill = function fill(value, start, end) {
                if (!value) value = 0
                if (!start) start = 0
                if (!end) end = this.length

                if (end < start) throw new RangeError('end < start')

                // Fill 0 bytes; we're done
                if (end === start) return
                if (this.length === 0) return

                if (start < 0 || start >= this.length) throw new RangeError('start out of bounds')
                if (end < 0 || end > this.length) throw new RangeError('end out of bounds')

                var i
                if (typeof value === 'number') {
                    for (i = start; i < end; i++) {
                        this[i] = value
                }
                } else {
                    var bytes = utf8ToBytes(value.toString())
                    var len = bytes.length
                    for (i = start; i < end; i++) {
                        this[i] = bytes[i % len]
                    }
            }

                return this
            }

            /**
             * Creates a new `ArrayBuffer` with the *copied* memory of the buffer instance.
             * Added in Node 0.12. Only available in browsers that support ArrayBuffer.
             */
            Buffer.prototype.toArrayBuffer = function toArrayBuffer() {
                if (typeof Uint8Array !== 'undefined') {
                    if (Buffer.TYPED_ARRAY_SUPPORT) {
                        return (new Buffer(this)).buffer
                } else {
                        var buf = new Uint8Array(this.length)
                        for (var i = 0, len = buf.length; i < len; i += 1) {
                            buf[i] = this[i]
                        }
                        return buf.buffer
                }
                } else {
                    throw new TypeError('Buffer.toArrayBuffer not supported in this browser')
            }
            }

// HELPER FUNCTIONS
// ================

            var BP = Buffer.prototype

            /**
             * Augment a Uint8Array *instance* (not the Uint8Array class!) with Buffer methods
             */
            Buffer._augment = function _augment(arr) {
                arr.constructor = Buffer
                arr._isBuffer = true

                // save reference to original Uint8Array set method before overwriting
                arr._set = arr.set

                // deprecated
                arr.get = BP.get
                arr.set = BP.set

                arr.write = BP.write
                arr.toString = BP.toString
                arr.toLocaleString = BP.toString
                arr.toJSON = BP.toJSON
                arr.equals = BP.equals
                arr.compare = BP.compare
                arr.indexOf = BP.indexOf
                arr.copy = BP.copy
                arr.slice = BP.slice
                arr.readUIntLE = BP.readUIntLE
                arr.readUIntBE = BP.readUIntBE
                arr.readUInt8 = BP.readUInt8
                arr.readUInt16LE = BP.readUInt16LE
                arr.readUInt16BE = BP.readUInt16BE
                arr.readUInt32LE = BP.readUInt32LE
                arr.readUInt32BE = BP.readUInt32BE
                arr.readIntLE = BP.readIntLE
                arr.readIntBE = BP.readIntBE
                arr.readInt8 = BP.readInt8
                arr.readInt16LE = BP.readInt16LE
                arr.readInt16BE = BP.readInt16BE
                arr.readInt32LE = BP.readInt32LE
                arr.readInt32BE = BP.readInt32BE
                arr.readFloatLE = BP.readFloatLE
                arr.readFloatBE = BP.readFloatBE
                arr.readDoubleLE = BP.readDoubleLE
                arr.readDoubleBE = BP.readDoubleBE
                arr.writeUInt8 = BP.writeUInt8
                arr.writeUIntLE = BP.writeUIntLE
                arr.writeUIntBE = BP.writeUIntBE
                arr.writeUInt16LE = BP.writeUInt16LE
                arr.writeUInt16BE = BP.writeUInt16BE
                arr.writeUInt32LE = BP.writeUInt32LE
                arr.writeUInt32BE = BP.writeUInt32BE
                arr.writeIntLE = BP.writeIntLE
                arr.writeIntBE = BP.writeIntBE
                arr.writeInt8 = BP.writeInt8
                arr.writeInt16LE = BP.writeInt16LE
                arr.writeInt16BE = BP.writeInt16BE
                arr.writeInt32LE = BP.writeInt32LE
                arr.writeInt32BE = BP.writeInt32BE
                arr.writeFloatLE = BP.writeFloatLE
                arr.writeFloatBE = BP.writeFloatBE
                arr.writeDoubleLE = BP.writeDoubleLE
                arr.writeDoubleBE = BP.writeDoubleBE
                arr.fill = BP.fill
                arr.inspect = BP.inspect
                arr.toArrayBuffer = BP.toArrayBuffer

                return arr
            }

            var INVALID_BASE64_RE = /[^+\/0-9A-Za-z-_]/g

            function base64clean(str) {
                // Node strips out invalid characters like \n and \t from the string, base64-js does not
                str = stringtrim(str).replace(INVALID_BASE64_RE, '')
                // Node converts strings with length < 2 to ''
                if (str.length < 2) return ''
                // Node allows for non-padded base64 strings (missing trailing ===), base64-js does not
                while (str.length % 4 !== 0) {
                    str = str + '='
            }
                return str
            }

            function stringtrim(str) {
                if (str.trim) return str.trim()
                return str.replace(/^\s+|\s+$/g, '')
            }

            function toHex(n) {
                if (n < 16) return '0' + n.toString(16)
                return n.toString(16)
            }

            function utf8ToBytes(string, units) {
                units = units || Infinity
                var codePoint
                var length = string.length
                var leadSurrogate = null
                var bytes = []

                for (var i = 0; i < length; i++) {
                    codePoint = string.charCodeAt(i)

                    // is surrogate component
                    if (codePoint > 0xD7FF && codePoint < 0xE000) {
                        // last char was a lead
                        if (!leadSurrogate) {
                            // no lead yet
                            if (codePoint > 0xDBFF) {
                                // unexpected trail
                                if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD)
                            continue
                            } else if (i + 1 === length) {
                                // unpaired lead
                            if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD)
                            continue
                        }

                            // valid lead
                            leadSurrogate = codePoint

                            continue
                        }

                        // 2 leads in a row
                        if (codePoint < 0xDC00) {
                        if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD)
                            leadSurrogate = codePoint
                            continue
                    }

                        // valid surrogate pair
                        codePoint = leadSurrogate - 0xD800 << 10 | codePoint - 0xDC00 | 0x10000
                    } else if (leadSurrogate) {
                        // valid bmp char, but last char was a lead
                        if ((units -= 3) > -1) bytes.push(0xEF, 0xBF, 0xBD)
                }

                    leadSurrogate = null

                    // encode utf8
                    if (codePoint < 0x80) {
                        if ((units -= 1) < 0) break
                        bytes.push(codePoint)
                    } else if (codePoint < 0x800) {
                    if ((units -= 2) < 0) break
                        bytes.push(
                            codePoint >> 0x6 | 0xC0,
                            codePoint & 0x3F | 0x80
                        )
                    } else if (codePoint < 0x10000) {
                        if ((units -= 3) < 0) break
                        bytes.push(
                            codePoint >> 0xC | 0xE0,
                            codePoint >> 0x6 & 0x3F | 0x80,
                            codePoint & 0x3F | 0x80
                        )
                    } else if (codePoint < 0x110000) {
                        if ((units -= 4) < 0) break
                        bytes.push(
                            codePoint >> 0x12 | 0xF0,
                            codePoint >> 0xC & 0x3F | 0x80,
                            codePoint >> 0x6 & 0x3F | 0x80,
                            codePoint & 0x3F | 0x80
                        )
                    } else {
                        throw new Error('Invalid code point')
                }
                }

                return bytes
            }

            function asciiToBytes(str) {
                var byteArray = []
                for (var i = 0; i < str.length; i++) {
                    // Node's code seems to be doing this and not & 0x7F..
                    byteArray.push(str.charCodeAt(i) & 0xFF)
            }
                return byteArray
            }

            function utf16leToBytes(str, units) {
                var c, hi, lo
                var byteArray = []
                for (var i = 0; i < str.length; i++) {
                    if ((units -= 2) < 0) break

                    c = str.charCodeAt(i)
                    hi = c >> 8
                    lo = c % 256
                    byteArray.push(lo)
                    byteArray.push(hi)
            }

                return byteArray
            }

            function base64ToBytes(str) {
                return base64.toByteArray(base64clean(str))
            }

            function blitBuffer(src, dst, offset, length) {
                for (var i = 0; i < length; i++) {
                    if ((i + offset >= dst.length) || (i >= src.length)) break
                    dst[i + offset] = src[i]
            }
                return i
            }

        }).call(this, typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})
    }, {"base64-js": 85, "ieee754": 86, "is-array": 87}],
    85: [function (require, module, exports) {
        var lookup = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

        ;
        (function (exports) {
            'use strict';

            var Arr = (typeof Uint8Array !== 'undefined')
                ? Uint8Array
                : Array

            var PLUS = '+'.charCodeAt(0)
            var SLASH = '/'.charCodeAt(0)
            var NUMBER = '0'.charCodeAt(0)
            var LOWER = 'a'.charCodeAt(0)
            var UPPER = 'A'.charCodeAt(0)
            var PLUS_URL_SAFE = '-'.charCodeAt(0)
            var SLASH_URL_SAFE = '_'.charCodeAt(0)

            function decode(elt) {
                var code = elt.charCodeAt(0)
                if (code === PLUS ||
                    code === PLUS_URL_SAFE)
                    return 62 // '+'
                if (code === SLASH ||
                    code === SLASH_URL_SAFE)
                    return 63 // '/'
                if (code < NUMBER)
                    return -1 //no match
                if (code < NUMBER + 10)
                    return code - NUMBER + 26 + 26
                if (code < UPPER + 26)
                    return code - UPPER
                if (code < LOWER + 26)
                    return code - LOWER + 26
            }

            function b64ToByteArray(b64) {
                var i, j, l, tmp, placeHolders, arr

                if (b64.length % 4 > 0) {
                    throw new Error('Invalid string. Length must be a multiple of 4')
            }

                // the number of equal signs (place holders)
                // if there are two placeholders, than the two characters before it
                // represent one byte
                // if there is only one, then the three characters before it represent 2 bytes
                // this is just a cheap hack to not do indexOf twice
                var len = b64.length
                placeHolders = '=' === b64.charAt(len - 2) ? 2 : '=' === b64.charAt(len - 1) ? 1 : 0

                // base64 is 4/3 + up to two characters of the original data
                arr = new Arr(b64.length * 3 / 4 - placeHolders)

                // if there are placeholders, only get up to the last complete 4 chars
                l = placeHolders > 0 ? b64.length - 4 : b64.length

                var L = 0

                function push(v) {
                    arr[L++] = v
                }

                for (i = 0, j = 0; i < l; i += 4, j += 3) {
                    tmp = (decode(b64.charAt(i)) << 18) | (decode(b64.charAt(i + 1)) << 12) | (decode(b64.charAt(i + 2)) << 6) | decode(b64.charAt(i + 3))
                    push((tmp & 0xFF0000) >> 16)
                    push((tmp & 0xFF00) >> 8)
                    push(tmp & 0xFF)
                }

                if (placeHolders === 2) {
                    tmp = (decode(b64.charAt(i)) << 2) | (decode(b64.charAt(i + 1)) >> 4)
                    push(tmp & 0xFF)
                } else if (placeHolders === 1) {
                    tmp = (decode(b64.charAt(i)) << 10) | (decode(b64.charAt(i + 1)) << 4) | (decode(b64.charAt(i + 2)) >> 2)
                    push((tmp >> 8) & 0xFF)
                    push(tmp & 0xFF)
                }

                return arr
            }

            function uint8ToBase64(uint8) {
                var i,
                    extraBytes = uint8.length % 3, // if we have 1 byte left, pad 2 bytes
                    output = "",
                    temp, length

                function encode(num) {
                    return lookup.charAt(num)
            }

                function tripletToBase64(num) {
                    return encode(num >> 18 & 0x3F) + encode(num >> 12 & 0x3F) + encode(num >> 6 & 0x3F) + encode(num & 0x3F)
                }

                // go through the array every three bytes, we'll deal with trailing stuff later
                for (i = 0, length = uint8.length - extraBytes; i < length; i += 3) {
                    temp = (uint8[i] << 16) + (uint8[i + 1] << 8) + (uint8[i + 2])
                    output += tripletToBase64(temp)
                }

                // pad the end with zeros, but make sure to not forget the extra bytes
                switch (extraBytes) {
                    case 1:
                        temp = uint8[uint8.length - 1]
                        output += encode(temp >> 2)
                        output += encode((temp << 4) & 0x3F)
                        output += '=='
                        break
                    case 2:
                        temp = (uint8[uint8.length - 2] << 8) + (uint8[uint8.length - 1])
                        output += encode(temp >> 10)
                        output += encode((temp >> 4) & 0x3F)
                        output += encode((temp << 2) & 0x3F)
                        output += '='
                        break
                }

                return output
            }

            exports.toByteArray = b64ToByteArray
            exports.fromByteArray = uint8ToBase64
        }(typeof exports === 'undefined' ? (this.base64js = {}) : exports))

    }, {}],
    86: [function (require, module, exports) {
        exports.read = function (buffer, offset, isLE, mLen, nBytes) {
            var e, m
            var eLen = nBytes * 8 - mLen - 1
            var eMax = (1 << eLen) - 1
            var eBias = eMax >> 1
            var nBits = -7
            var i = isLE ? (nBytes - 1) : 0
            var d = isLE ? -1 : 1
            var s = buffer[offset + i]

            i += d

            e = s & ((1 << (-nBits)) - 1)
            s >>= (-nBits)
            nBits += eLen
            for (; nBits > 0; e = e * 256 + buffer[offset + i], i += d, nBits -= 8) {
            }

            m = e & ((1 << (-nBits)) - 1)
            e >>= (-nBits)
            nBits += mLen
            for (; nBits > 0; m = m * 256 + buffer[offset + i], i += d, nBits -= 8) {
            }

            if (e === 0) {
                e = 1 - eBias
            } else if (e === eMax) {
                return m ? NaN : ((s ? -1 : 1) * Infinity)
            } else {
                m = m + Math.pow(2, mLen)
                e = e - eBias
            }
            return (s ? -1 : 1) * m * Math.pow(2, e - mLen)
        }

        exports.write = function (buffer, value, offset, isLE, mLen, nBytes) {
            var e, m, c
            var eLen = nBytes * 8 - mLen - 1
            var eMax = (1 << eLen) - 1
            var eBias = eMax >> 1
            var rt = (mLen === 23 ? Math.pow(2, -24) - Math.pow(2, -77) : 0)
            var i = isLE ? 0 : (nBytes - 1)
            var d = isLE ? 1 : -1
            var s = value < 0 || (value === 0 && 1 / value < 0) ? 1 : 0

            value = Math.abs(value)

            if (isNaN(value) || value === Infinity) {
                m = isNaN(value) ? 1 : 0
                e = eMax
            } else {
                e = Math.floor(Math.log(value) / Math.LN2)
                if (value * (c = Math.pow(2, -e)) < 1) {
                    e--
                    c *= 2
                }
                if (e + eBias >= 1) {
                    value += rt / c
            } else {
                    value += rt * Math.pow(2, 1 - eBias)
            }
                if (value * c >= 2) {
                    e++
                    c /= 2
                }

                if (e + eBias >= eMax) {
                    m = 0
                e = eMax
                } else if (e + eBias >= 1) {
                    m = (value * c - 1) * Math.pow(2, mLen)
                    e = e + eBias
            } else {
                    m = value * Math.pow(2, eBias - 1) * Math.pow(2, mLen)
                    e = 0
            }
        }

            for (; mLen >= 8; buffer[offset + i] = m & 0xff, i += d, m /= 256, mLen -= 8) {
            }

            e = (e << mLen) | m
            eLen += mLen
            for (; eLen > 0; buffer[offset + i] = e & 0xff, i += d, e /= 256, eLen -= 8) {
            }

            buffer[offset + i - d] |= s * 128
        }

    }, {}],
    87: [function (require, module, exports) {

        /**
         * isArray
         */

        var isArray = Array.isArray;

        /**
         * toString
         */

        var str = Object.prototype.toString;

        /**
         * Whether or not the given `val`
         * is an array.
         *
         * example:
         *
         *        isArray([]);
         *        // > true
         *        isArray(arguments);
         *        // > false
         *        isArray('');
         *        // > false
         *
         * @param {mixed} val
         * @return {bool}
         */

        module.exports = isArray || function (val) {
                return !!val && '[object Array]' == str.call(val);
            };

    }, {}],
    88: [function (require, module, exports) {
// Copyright Joyent, Inc. and other Node contributors.
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to permit
// persons to whom the Software is furnished to do so, subject to the
// following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
// NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
// DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
// USE OR OTHER DEALINGS IN THE SOFTWARE.

        function EventEmitter() {
            this._events = this._events || {};
            this._maxListeners = this._maxListeners || undefined;
        }

        module.exports = EventEmitter;

// Backwards-compat with node 0.10.x
        EventEmitter.EventEmitter = EventEmitter;

        EventEmitter.prototype._events = undefined;
        EventEmitter.prototype._maxListeners = undefined;

// By default EventEmitters will print a warning if more than 10 listeners are
// added to it. This is a useful default which helps finding memory leaks.
        EventEmitter.defaultMaxListeners = 10;

// Obviously not all Emitters should be limited to 10. This function allows
// that to be increased. Set to zero for unlimited.
        EventEmitter.prototype.setMaxListeners = function (n) {
            if (!isNumber(n) || n < 0 || isNaN(n))
                throw TypeError('n must be a positive number');
            this._maxListeners = n;
            return this;
        };

        EventEmitter.prototype.emit = function (type) {
            var er, handler, len, args, i, listeners;

            if (!this._events)
                this._events = {};

            // If there is no 'error' event listener then throw.
            if (type === 'error') {
                if (!this._events.error ||
                    (isObject(this._events.error) && !this._events.error.length)) {
                    er = arguments[1];
                    if (er instanceof Error) {
                        throw er; // Unhandled 'error' event
                }
                    throw TypeError('Uncaught, unspecified "error" event.');
            }
            }

            handler = this._events[type];

            if (isUndefined(handler))
                return false;

            if (isFunction(handler)) {
                switch (arguments.length) {
                    // fast cases
                    case 1:
                        handler.call(this);
                        break;
                    case 2:
                        handler.call(this, arguments[1]);
                        break;
                    case 3:
                        handler.call(this, arguments[1], arguments[2]);
                        break;
                    // slower
                    default:
                        len = arguments.length;
                        args = new Array(len - 1);
                        for (i = 1; i < len; i++)
                            args[i - 1] = arguments[i];
                        handler.apply(this, args);
            }
            } else if (isObject(handler)) {
                len = arguments.length;
                args = new Array(len - 1);
                for (i = 1; i < len; i++)
                    args[i - 1] = arguments[i];

                listeners = handler.slice();
                len = listeners.length;
                for (i = 0; i < len; i++)
                    listeners[i].apply(this, args);
            }

            return true;
        };

        EventEmitter.prototype.addListener = function (type, listener) {
            var m;

            if (!isFunction(listener))
                throw TypeError('listener must be a function');

            if (!this._events)
                this._events = {};

            // To avoid recursion in the case that type === "newListener"! Before
            // adding it to the listeners, first emit "newListener".
            if (this._events.newListener)
                this.emit('newListener', type,
                    isFunction(listener.listener) ?
                        listener.listener : listener);

            if (!this._events[type])
            // Optimize the case of one listener. Don't need the extra array object.
                this._events[type] = listener;
            else if (isObject(this._events[type]))
            // If we've already got an array, just append.
                this._events[type].push(listener);
            else
            // Adding the second element, need to change to array.
                this._events[type] = [this._events[type], listener];

            // Check for listener leak
            if (isObject(this._events[type]) && !this._events[type].warned) {
            var m;
                if (!isUndefined(this._maxListeners)) {
                    m = this._maxListeners;
                } else {
                    m = EventEmitter.defaultMaxListeners;
                }

                if (m && m > 0 && this._events[type].length > m) {
                    this._events[type].warned = true;
                    console.error('(node) warning: possible EventEmitter memory ' +
                        'leak detected. %d listeners added. ' +
                        'Use emitter.setMaxListeners() to increase limit.',
                        this._events[type].length);
                    if (typeof console.trace === 'function') {
                        // not supported in IE 10
                        console.trace();
                }
            }
            }

            return this;
        };

        EventEmitter.prototype.on = EventEmitter.prototype.addListener;

        EventEmitter.prototype.once = function (type, listener) {
            if (!isFunction(listener))
                throw TypeError('listener must be a function');

            var fired = false;

            function g() {
                this.removeListener(type, g);

                if (!fired) {
                    fired = true;
                    listener.apply(this, arguments);
            }
            }

            g.listener = listener;
            this.on(type, g);

            return this;
        };

// emits a 'removeListener' event iff the listener was removed
        EventEmitter.prototype.removeListener = function (type, listener) {
            var list, position, length, i;

            if (!isFunction(listener))
                throw TypeError('listener must be a function');

            if (!this._events || !this._events[type])
            return this;

            list = this._events[type];
            length = list.length;
            position = -1;

            if (list === listener ||
                (isFunction(list.listener) && list.listener === listener)) {
            delete this._events[type];
                if (this._events.removeListener)
                    this.emit('removeListener', type, listener);

            } else if (isObject(list)) {
                for (i = length; i-- > 0;) {
                    if (list[i] === listener ||
                        (list[i].listener && list[i].listener === listener)) {
                        position = i;
                        break;
                    }
                }

                if (position < 0)
                    return this;

                if (list.length === 1) {
                    list.length = 0;
                    delete this._events[type];
                } else {
                    list.splice(position, 1);
                }

                if (this._events.removeListener)
                    this.emit('removeListener', type, listener);
        }

            return this;
        };

        EventEmitter.prototype.removeAllListeners = function (type) {
            var key, listeners;

            if (!this._events)
                return this;

            // not listening for removeListener, no need to emit
            if (!this._events.removeListener) {
                if (arguments.length === 0)
                    this._events = {};
                else if (this._events[type])
                    delete this._events[type];
                return this;
        }

            // emit removeListener for all listeners on all events
            if (arguments.length === 0) {
                for (key in this._events) {
                    if (key === 'removeListener') continue;
                    this.removeAllListeners(key);
                }
                this.removeAllListeners('removeListener');
                this._events = {};
                return this;
        }

            listeners = this._events[type];

            if (isFunction(listeners)) {
                this.removeListener(type, listeners);
        } else {
                // LIFO order
                while (listeners.length)
                    this.removeListener(type, listeners[listeners.length - 1]);
            }
            delete this._events[type];

            return this;
        };

        EventEmitter.prototype.listeners = function (type) {
            var ret;
            if (!this._events || !this._events[type])
                ret = [];
            else if (isFunction(this._events[type]))
                ret = [this._events[type]];
            else
                ret = this._events[type].slice();
            return ret;
        };

        EventEmitter.listenerCount = function (emitter, type) {
            var ret;
            if (!emitter._events || !emitter._events[type])
                ret = 0;
            else if (isFunction(emitter._events[type]))
                ret = 1;
            else
                ret = emitter._events[type].length;
            return ret;
        };

        function isFunction(arg) {
            return typeof arg === 'function';
        }

        function isNumber(arg) {
            return typeof arg === 'number';
        }

        function isObject(arg) {
            return typeof arg === 'object' && arg !== null;
        }

        function isUndefined(arg) {
            return arg === void 0;
        }

    }, {}],
    89: [function (require, module, exports) {
        if (typeof Object.create === 'function') {
            // implementation from standard node.js 'util' module
            module.exports = function inherits(ctor, superCtor) {
                ctor.super_ = superCtor
                ctor.prototype = Object.create(superCtor.prototype, {
                    constructor: {
                        value: ctor,
                        enumerable: false,
                        writable: true,
                        configurable: true
                }
                });
            };
        } else {
            // old school shim for old browsers
            module.exports = function inherits(ctor, superCtor) {
                ctor.super_ = superCtor
                var TempCtor = function () {
                }
                TempCtor.prototype = superCtor.prototype
                ctor.prototype = new TempCtor()
                ctor.prototype.constructor = ctor
        }
        }

    }, {}],
    90: [function (require, module, exports) {
        /**
         * Determine if an object is Buffer
         *
         * Author:   Feross Aboukhadijeh <feross@feross.org> <http://feross.org>
         * License:  MIT
         *
         * `npm install is-buffer`
         */

        module.exports = function (obj) {
            return !!(obj != null &&
            (obj._isBuffer || // For Safari 5-7 (missing Object.prototype.constructor)
                (obj.constructor &&
                typeof obj.constructor.isBuffer === 'function' &&
                obj.constructor.isBuffer(obj))
            ))
        }

    }, {}],
    91: [function (require, module, exports) {
        module.exports = Array.isArray || function (arr) {
                return Object.prototype.toString.call(arr) == '[object Array]';
            };

    }, {}],
    92: [function (require, module, exports) {
// shim for using process in browser

        var process = module.exports = {};
        var queue = [];
        var draining = false;
        var currentQueue;
        var queueIndex = -1;

        function cleanUpNextTick() {
            draining = false;
            if (currentQueue.length) {
                queue = currentQueue.concat(queue);
            } else {
                queueIndex = -1;
        }
            if (queue.length) {
                drainQueue();
            }
        }

        function drainQueue() {
            if (draining) {
                return;
            }
            var timeout = setTimeout(cleanUpNextTick);
            draining = true;

            var len = queue.length;
            while (len) {
                currentQueue = queue;
                queue = [];
                while (++queueIndex < len) {
                    if (currentQueue) {
                        currentQueue[queueIndex].run();
                }
            }
                queueIndex = -1;
                len = queue.length;
        }
            currentQueue = null;
            draining = false;
            clearTimeout(timeout);
        }

        process.nextTick = function (fun) {
            var args = new Array(arguments.length - 1);
            if (arguments.length > 1) {
                for (var i = 1; i < arguments.length; i++) {
                    args[i - 1] = arguments[i];
            }
            }
            queue.push(new Item(fun, args));
            if (queue.length === 1 && !draining) {
                setTimeout(drainQueue, 0);
            }
        };

// v8 likes predictible objects
        function Item(fun, array) {
            this.fun = fun;
            this.array = array;
        }

        Item.prototype.run = function () {
            this.fun.apply(null, this.array);
        };
        process.title = 'browser';
        process.browser = true;
        process.env = {};
        process.argv = [];
        process.version = ''; // empty string to avoid regexp issues
        process.versions = {};

        function noop() {
        }

        process.on = noop;
        process.addListener = noop;
        process.once = noop;
        process.off = noop;
        process.removeListener = noop;
        process.removeAllListeners = noop;
        process.emit = noop;

        process.binding = function (name) {
            throw new Error('process.binding is not supported');
        };

        process.cwd = function () {
            return '/'
        };
        process.chdir = function (dir) {
            throw new Error('process.chdir is not supported');
        };
        process.umask = function () {
            return 0;
        };

    }, {}],
    93: [function (require, module, exports) {
        (function (global) {
            /*! https://mths.be/punycode v1.3.2 by @mathias */
            ;
            (function (root) {

                /** Detect free variables */
                var freeExports = typeof exports == 'object' && exports && !exports.nodeType && exports;
                var freeModule = typeof module == 'object' && module && !module.nodeType && module;
                var freeGlobal = typeof global == 'object' && global;
                if (
                    freeGlobal.global === freeGlobal ||
                    freeGlobal.window === freeGlobal ||
                    freeGlobal.self === freeGlobal
                ) {
                    root = freeGlobal;
                }

                /**
                 * The `punycode` object.
                 * @name punycode
                 * @type Object
                 */
                var punycode,

                    /** Highest positive signed 32-bit float value */
                    maxInt = 2147483647, // aka. 0x7FFFFFFF or 2^31-1

                    /** Bootstring parameters */
                    base = 36,
                    tMin = 1,
                    tMax = 26,
                    skew = 38,
                    damp = 700,
                    initialBias = 72,
                    initialN = 128, // 0x80
                    delimiter = '-', // '\x2D'

                    /** Regular expressions */
                    regexPunycode = /^xn--/,
                    regexNonASCII = /[^\x20-\x7E]/, // unprintable ASCII chars + non-ASCII chars
                    regexSeparators = /[\x2E\u3002\uFF0E\uFF61]/g, // RFC 3490 separators

                    /** Error messages */
                    errors = {
                        'overflow': 'Overflow: input needs wider integers to process',
                        'not-basic': 'Illegal input >= 0x80 (not a basic code point)',
                        'invalid-input': 'Invalid input'
                    },

                    /** Convenience shortcuts */
                    baseMinusTMin = base - tMin,
                    floor = Math.floor,
                    stringFromCharCode = String.fromCharCode,

                    /** Temporary variable */
                    key;

                /*--------------------------------------------------------------------------*/

                /**
                 * A generic error utility function.
                 * @private
                 * @param {String} type The error type.
                 * @returns {Error} Throws a `RangeError` with the applicable error message.
                 */
                function error(type) {
                    throw RangeError(errors[type]);
                }

                /**
                 * A generic `Array#map` utility function.
                 * @private
                 * @param {Array} array The array to iterate over.
                 * @param {Function} callback The function that gets called for every array
                 * item.
                 * @returns {Array} A new array of values returned by the callback function.
                 */
                function map(array, fn) {
                    var length = array.length;
                    var result = [];
                    while (length--) {
                        result[length] = fn(array[length]);
                }
                    return result;
                }

                /**
                 * A simple `Array#map`-like wrapper to work with domain name strings or email
                 * addresses.
                 * @private
                 * @param {String} domain The domain name or email address.
                 * @param {Function} callback The function that gets called for every
                 * character.
                 * @returns {Array} A new string of characters returned by the callback
                 * function.
                 */
                function mapDomain(string, fn) {
                    var parts = string.split('@');
                    var result = '';
                    if (parts.length > 1) {
                        // In email addresses, only the domain name should be punycoded. Leave
                        // the local part (i.e. everything up to `@`) intact.
                        result = parts[0] + '@';
                        string = parts[1];
                }
                    // Avoid `split(regex)` for IE8 compatibility. See #17.
                    string = string.replace(regexSeparators, '\x2E');
                    var labels = string.split('.');
                    var encoded = map(labels, fn).join('.');
                    return result + encoded;
                }

                /**
                 * Creates an array containing the numeric code points of each Unicode
                 * character in the string. While JavaScript uses UCS-2 internally,
                 * this function will convert a pair of surrogate halves (each of which
                 * UCS-2 exposes as separate characters) into a single code point,
                 * matching UTF-16.
                 * @see `punycode.ucs2.encode`
                 * @see <https://mathiasbynens.be/notes/javascript-encoding>
                 * @memberOf punycode.ucs2
                 * @name decode
                 * @param {String} string The Unicode input string (UCS-2).
                 * @returns {Array} The new array of code points.
                 */
                function ucs2decode(string) {
                    var output = [],
                        counter = 0,
                        length = string.length,
                        value,
                        extra;
                    while (counter < length) {
                        value = string.charCodeAt(counter++);
                        if (value >= 0xD800 && value <= 0xDBFF && counter < length) {
                            // high surrogate, and there is a next character
                            extra = string.charCodeAt(counter++);
                            if ((extra & 0xFC00) == 0xDC00) { // low surrogate
                                output.push(((value & 0x3FF) << 10) + (extra & 0x3FF) + 0x10000);
                        } else {
                                // unmatched surrogate; only append this code unit, in case the next
                                // code unit is the high surrogate of a surrogate pair
                            output.push(value);
                                counter--;
                        }
                        } else {
                            output.push(value);
                    }
                    }
                    return output;
                }

                /**
                 * Creates a string based on an array of numeric code points.
                 * @see `punycode.ucs2.decode`
                 * @memberOf punycode.ucs2
                 * @name encode
                 * @param {Array} codePoints The array of numeric code points.
                 * @returns {String} The new Unicode string (UCS-2).
                 */
                function ucs2encode(array) {
                    return map(array, function (value) {
                        var output = '';
                        if (value > 0xFFFF) {
                            value -= 0x10000;
                            output += stringFromCharCode(value >>> 10 & 0x3FF | 0xD800);
                            value = 0xDC00 | value & 0x3FF;
                        }
                        output += stringFromCharCode(value);
                    return output;
                    }).join('');
                }

                /**
                 * Converts a basic code point into a digit/integer.
                 * @see `digitToBasic()`
                 * @private
                 * @param {Number} codePoint The basic numeric code point value.
                 * @returns {Number} The numeric value of a basic code point (for use in
                 * representing integers) in the range `0` to `base - 1`, or `base` if
                 * the code point does not represent a value.
                 */
                function basicToDigit(codePoint) {
                    if (codePoint - 48 < 10) {
                        return codePoint - 22;
                }
                    if (codePoint - 65 < 26) {
                        return codePoint - 65;
                    }
                    if (codePoint - 97 < 26) {
                        return codePoint - 97;
                    }
                    return base;
                }

                /**
                 * Converts a digit/integer into a basic code point.
                 * @see `basicToDigit()`
                 * @private
                 * @param {Number} digit The numeric value of a basic code point.
                 * @returns {Number} The basic code point whose value (when used for
                 * representing integers) is `digit`, which needs to be in the range
                 * `0` to `base - 1`. If `flag` is non-zero, the uppercase form is
                 * used; else, the lowercase form is used. The behavior is undefined
                 * if `flag` is non-zero and `digit` has no uppercase form.
                 */
                function digitToBasic(digit, flag) {
                    //  0..25 map to ASCII a..z or A..Z
                    // 26..35 map to ASCII 0..9
                    return digit + 22 + 75 * (digit < 26) - ((flag != 0) << 5);
                }

                /**
                 * Bias adaptation function as per section 3.4 of RFC 3492.
                 * http://tools.ietf.org/html/rfc3492#section-3.4
                 * @private
                 */
                function adapt(delta, numPoints, firstTime) {
                    var k = 0;
                    delta = firstTime ? floor(delta / damp) : delta >> 1;
                    delta += floor(delta / numPoints);
                    for (/* no initialization */; delta > baseMinusTMin * tMax >> 1; k += base) {
                        delta = floor(delta / baseMinusTMin);
                }
                    return floor(k + (baseMinusTMin + 1) * delta / (delta + skew));
                }

                /**
                 * Converts a Punycode string of ASCII-only symbols to a string of Unicode
                 * symbols.
                 * @memberOf punycode
                 * @param {String} input The Punycode string of ASCII-only symbols.
                 * @returns {String} The resulting string of Unicode symbols.
                 */
                function decode(input) {
                    // Don't use UCS-2
                    var output = [],
                        inputLength = input.length,
                        out,
                        i = 0,
                        n = initialN,
                        bias = initialBias,
                        basic,
                        j,
                        index,
                        oldi,
                        w,
                        k,
                        digit,
                        t,
                        /** Cached calculation results */
                        baseMinusT;

                    // Handle the basic code points: let `basic` be the number of input code
                    // points before the last delimiter, or `0` if there is none, then copy
                    // the first basic code points to the output.

                    basic = input.lastIndexOf(delimiter);
                    if (basic < 0) {
                        basic = 0;
                }

                    for (j = 0; j < basic; ++j) {
                        // if it's not a basic code point
                        if (input.charCodeAt(j) >= 0x80) {
                            error('not-basic');
                    }
                        output.push(input.charCodeAt(j));
                }

                    // Main decoding loop: start just after the last delimiter if any basic code
                    // points were copied; start at the beginning otherwise.

                    for (index = basic > 0 ? basic + 1 : 0; index < inputLength; /* no final expression */) {

                        // `index` is the index of the next character to be consumed.
                        // Decode a generalized variable-length integer into `delta`,
                        // which gets added to `i`. The overflow checking is easier
                        // if we increase `i` as we go, then subtract off its starting
                        // value at the end to obtain `delta`.
                        for (oldi = i, w = 1, k = base; /* no condition */; k += base) {

                            if (index >= inputLength) {
                                error('invalid-input');
                        }

                            digit = basicToDigit(input.charCodeAt(index++));

                            if (digit >= base || digit > floor((maxInt - i) / w)) {
                                error('overflow');
                            }

                            i += digit * w;
                            t = k <= bias ? tMin : (k >= bias + tMax ? tMax : k - bias);

                            if (digit < t) {
                                break;
                            }

                            baseMinusT = base - t;
                            if (w > floor(maxInt / baseMinusT)) {
                                error('overflow');
                            }

                            w *= baseMinusT;

                        }

                        out = output.length + 1;
                        bias = adapt(i - oldi, out, oldi == 0);

                        // `i` was supposed to wrap around from `out` to `0`,
                        // incrementing `n` each time, so we'll fix that now:
                        if (floor(i / out) > maxInt - n) {
                            error('overflow');
                        }

                        n += floor(i / out);
                        i %= out;

                        // Insert `n` at position `i` of the output
                        output.splice(i++, 0, n);

                    }

                    return ucs2encode(output);
                }

                /**
                 * Converts a string of Unicode symbols (e.g. a domain name label) to a
                 * Punycode string of ASCII-only symbols.
                 * @memberOf punycode
                 * @param {String} input The string of Unicode symbols.
                 * @returns {String} The resulting Punycode string of ASCII-only symbols.
                 */
                function encode(input) {
                    var n,
                        delta,
                        handledCPCount,
                        basicLength,
                        bias,
                        j,
                        m,
                        q,
                        k,
                        t,
                        currentValue,
                        output = [],
                        /** `inputLength` will hold the number of code points in `input`. */
                        inputLength,
                        /** Cached calculation results */
                        handledCPCountPlusOne,
                        baseMinusT,
                        qMinusT;

                    // Convert the input in UCS-2 to Unicode
                    input = ucs2decode(input);

                    // Cache the length
                    inputLength = input.length;

                    // Initialize the state
                    n = initialN;
                    delta = 0;
                    bias = initialBias;

                    // Handle the basic code points
                    for (j = 0; j < inputLength; ++j) {
                        currentValue = input[j];
                        if (currentValue < 0x80) {
                            output.push(stringFromCharCode(currentValue));
                    }
                    }

                    handledCPCount = basicLength = output.length;

                    // `handledCPCount` is the number of code points that have been handled;
                    // `basicLength` is the number of basic code points.

                    // Finish the basic string - if it is not empty - with a delimiter
                    if (basicLength) {
                        output.push(delimiter);
                }

                    // Main encoding loop:
                    while (handledCPCount < inputLength) {

                        // All non-basic code points < n have been handled already. Find the next
                        // larger one:
                        for (m = maxInt, j = 0; j < inputLength; ++j) {
                            currentValue = input[j];
                            if (currentValue >= n && currentValue < m) {
                                m = currentValue;
                            }
                        }

                        // Increase `delta` enough to advance the decoder's <n,i> state to <m,0>,
                        // but guard against overflow
                        handledCPCountPlusOne = handledCPCount + 1;
                        if (m - n > floor((maxInt - delta) / handledCPCountPlusOne)) {
                            error('overflow');
                        }

                        delta += (m - n) * handledCPCountPlusOne;
                        n = m;

                    for (j = 0; j < inputLength; ++j) {
                        currentValue = input[j];

                        if (currentValue < n && ++delta > maxInt) {
                            error('overflow');
                        }

                        if (currentValue == n) {
                            // Represent delta as a generalized variable-length integer
                            for (q = delta, k = base; /* no condition */; k += base) {
                                t = k <= bias ? tMin : (k >= bias + tMax ? tMax : k - bias);
                                if (q < t) {
                                    break;
                                }
                                qMinusT = q - t;
                                baseMinusT = base - t;
                                output.push(
                                    stringFromCharCode(digitToBasic(t + qMinusT % baseMinusT, 0))
                                );
                                q = floor(qMinusT / baseMinusT);
                            }

                            output.push(stringFromCharCode(digitToBasic(q, 0)));
                            bias = adapt(delta, handledCPCountPlusOne, handledCPCount == basicLength);
                            delta = 0;
                            ++handledCPCount;
                        }
                    }

                        ++delta;
                        ++n;

                }
                    return output.join('');
                }

                /**
                 * Converts a Punycode string representing a domain name or an email address
                 * to Unicode. Only the Punycoded parts of the input will be converted, i.e.
                 * it doesn't matter if you call it on a string that has already been
                 * converted to Unicode.
                 * @memberOf punycode
                 * @param {String} input The Punycoded domain name or email address to
                 * convert to Unicode.
                 * @returns {String} The Unicode representation of the given Punycode
                 * string.
                 */
                function toUnicode(input) {
                    return mapDomain(input, function (string) {
                        return regexPunycode.test(string)
                            ? decode(string.slice(4).toLowerCase())
                            : string;
                    });
                }

                /**
                 * Converts a Unicode string representing a domain name or an email address to
                 * Punycode. Only the non-ASCII parts of the domain name will be converted,
                 * i.e. it doesn't matter if you call it with a domain that's already in
                 * ASCII.
                 * @memberOf punycode
                 * @param {String} input The domain name or email address to convert, as a
                 * Unicode string.
                 * @returns {String} The Punycode representation of the given domain name or
                 * email address.
                 */
                function toASCII(input) {
                    return mapDomain(input, function (string) {
                        return regexNonASCII.test(string)
                            ? 'xn--' + encode(string)
                            : string;
                    });
                }

                /*--------------------------------------------------------------------------*/

                /** Define the public API */
                punycode = {
                /**
                 * A string representing the current Punycode.js version number.
                 * @memberOf punycode
                 * @type String
                 */
                'version': '1.3.2',
                /**
                 * An object of methods to convert from JavaScript's internal character
                 * representation (UCS-2) to Unicode code points, and back.
                 * @see <https://mathiasbynens.be/notes/javascript-encoding>
                 * @memberOf punycode
                 * @type Object
                 */
                'ucs2': {
                    'decode': ucs2decode,
                    'encode': ucs2encode
                },
                    'decode': decode,
                    'encode': encode,
                    'toASCII': toASCII,
                    'toUnicode': toUnicode
                };

                /** Expose `punycode` */
                // Some AMD build optimizers, like r.js, check for specific condition patterns
                // like the following:
                if (
                    typeof define == 'function' &&
                    typeof define.amd == 'object' &&
                    define.amd
                ) {
                    define('punycode', function () {
                        return punycode;
                    });
                } else if (freeExports && freeModule) {
                    if (module.exports == freeExports) { // in Node.js or RingoJS v0.8.0+
                        freeModule.exports = punycode;
                    } else { // in Narwhal or RingoJS v0.7.0-
                        for (key in punycode) {
                            punycode.hasOwnProperty(key) && (freeExports[key] = punycode[key]);
                    }
                }
                } else { // in Rhino or a web browser
                    root.punycode = punycode;
                }

            }(this));

        }).call(this, typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})
    }, {}],
    94: [function (require, module, exports) {
// Copyright Joyent, Inc. and other Node contributors.
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to permit
// persons to whom the Software is furnished to do so, subject to the
// following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
// NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
// DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
// USE OR OTHER DEALINGS IN THE SOFTWARE.

        'use strict';

// If obj.hasOwnProperty has been overridden, then calling
// obj.hasOwnProperty(prop) will break.
// See: https://github.com/joyent/node/issues/1707
        function hasOwnProperty(obj, prop) {
            return Object.prototype.hasOwnProperty.call(obj, prop);
        }

        module.exports = function (qs, sep, eq, options) {
            sep = sep || '&';
            eq = eq || '=';
            var obj = {};

            if (typeof qs !== 'string' || qs.length === 0) {
            return obj;
            }

            var regexp = /\+/g;
            qs = qs.split(sep);

            var maxKeys = 1000;
            if (options && typeof options.maxKeys === 'number') {
                maxKeys = options.maxKeys;
            }

            var len = qs.length;
            // maxKeys <= 0 means that we should not limit keys count
            if (maxKeys > 0 && len > maxKeys) {
                len = maxKeys;
            }

            for (var i = 0; i < len; ++i) {
                var x = qs[i].replace(regexp, '%20'),
                    idx = x.indexOf(eq),
                    kstr, vstr, k, v;

                if (idx >= 0) {
                    kstr = x.substr(0, idx);
                    vstr = x.substr(idx + 1);
                } else {
                    kstr = x;
                    vstr = '';
                }

                k = decodeURIComponent(kstr);
                v = decodeURIComponent(vstr);

                if (!hasOwnProperty(obj, k)) {
                    obj[k] = v;
                } else if (isArray(obj[k])) {
                    obj[k].push(v);
                } else {
                    obj[k] = [obj[k], v];
                }
            }

            return obj;
        };

        var isArray = Array.isArray || function (xs) {
                return Object.prototype.toString.call(xs) === '[object Array]';
        };

    }, {}],
    95: [function (require, module, exports) {
// Copyright Joyent, Inc. and other Node contributors.
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to permit
// persons to whom the Software is furnished to do so, subject to the
// following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
// NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
// DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
// USE OR OTHER DEALINGS IN THE SOFTWARE.

        'use strict';

        var stringifyPrimitive = function (v) {
            switch (typeof v) {
                case 'string':
                    return v;

                case 'boolean':
                    return v ? 'true' : 'false';

                case 'number':
                    return isFinite(v) ? v : '';

                default:
                    return '';
            }
        };

        module.exports = function (obj, sep, eq, name) {
            sep = sep || '&';
            eq = eq || '=';
            if (obj === null) {
                obj = undefined;
            }

            if (typeof obj === 'object') {
                return map(objectKeys(obj), function (k) {
                    var ks = encodeURIComponent(stringifyPrimitive(k)) + eq;
                    if (isArray(obj[k])) {
                        return map(obj[k], function (v) {
                            return ks + encodeURIComponent(stringifyPrimitive(v));
                        }).join(sep);
                    } else {
                        return ks + encodeURIComponent(stringifyPrimitive(obj[k]));
                    }
                }).join(sep);

            }

            if (!name) return '';
            return encodeURIComponent(stringifyPrimitive(name)) + eq +
                encodeURIComponent(stringifyPrimitive(obj));
        };

        var isArray = Array.isArray || function (xs) {
                return Object.prototype.toString.call(xs) === '[object Array]';
        };

        function map(xs, f) {
            if (xs.map) return xs.map(f);
            var res = [];
            for (var i = 0; i < xs.length; i++) {
                res.push(f(xs[i], i));
            }
            return res;
        }

        var objectKeys = Object.keys || function (obj) {
            var res = [];
                for (var key in obj) {
                    if (Object.prototype.hasOwnProperty.call(obj, key)) res.push(key);
            }
            return res;
            };

    }, {}],
    96: [function (require, module, exports) {
        'use strict';

        exports.decode = exports.parse = require('./decode');
        exports.encode = exports.stringify = require('./encode');

    }, {"./decode": 94, "./encode": 95}],
    97: [function (require, module, exports) {
        module.exports = require("./lib/_stream_duplex.js")

    }, {"./lib/_stream_duplex.js": 98}],
    98: [function (require, module, exports) {
// a duplex stream is just a stream that is both readable and writable.
// Since JS doesn't have multiple prototypal inheritance, this class
// prototypally inherits from Readable, and then parasitically from
// Writable.

        'use strict';

        /*<replacement>*/
        var objectKeys = Object.keys || function (obj) {
                var keys = [];
                for (var key in obj) keys.push(key);
                return keys;
            }
        /*</replacement>*/


        module.exports = Duplex;

        /*<replacement>*/
        var processNextTick = require('process-nextick-args');
        /*</replacement>*/


        /*<replacement>*/
        var util = require('core-util-is');
        util.inherits = require('inherits');
        /*</replacement>*/

        var Readable = require('./_stream_readable');
        var Writable = require('./_stream_writable');

        util.inherits(Duplex, Readable);

        var keys = objectKeys(Writable.prototype);
        for (var v = 0; v < keys.length; v++) {
            var method = keys[v];
            if (!Duplex.prototype[method])
                Duplex.prototype[method] = Writable.prototype[method];
        }

        function Duplex(options) {
            if (!(this instanceof Duplex))
                return new Duplex(options);

            Readable.call(this, options);
            Writable.call(this, options);

            if (options && options.readable === false)
                this.readable = false;

            if (options && options.writable === false)
                this.writable = false;

            this.allowHalfOpen = true;
            if (options && options.allowHalfOpen === false)
                this.allowHalfOpen = false;

            this.once('end', onend);
        }

// the no-half-open enforcer
        function onend() {
            // if we allow half-open state, or if the writable side ended,
            // then we're ok.
            if (this.allowHalfOpen || this._writableState.ended)
                return;

            // no more data can be written.
            // But allow more writes to happen in this tick.
            processNextTick(onEndNT, this);
        }

        function onEndNT(self) {
            self.end();
        }

        function forEach(xs, f) {
            for (var i = 0, l = xs.length; i < l; i++) {
                f(xs[i], i);
        }
        }

    }, {
        "./_stream_readable": 100,
        "./_stream_writable": 102,
        "core-util-is": 103,
        "inherits": 89,
        "process-nextick-args": 104
    }],
    99: [function (require, module, exports) {
// a passthrough stream.
// basically just the most minimal sort of Transform stream.
// Every written chunk gets output as-is.

        'use strict';

        module.exports = PassThrough;

        var Transform = require('./_stream_transform');

        /*<replacement>*/
        var util = require('core-util-is');
        util.inherits = require('inherits');
        /*</replacement>*/

        util.inherits(PassThrough, Transform);

        function PassThrough(options) {
            if (!(this instanceof PassThrough))
                return new PassThrough(options);

            Transform.call(this, options);
        }

        PassThrough.prototype._transform = function (chunk, encoding, cb) {
            cb(null, chunk);
        };

    }, {"./_stream_transform": 101, "core-util-is": 103, "inherits": 89}],
    100: [function (require, module, exports) {
        (function (process) {
        'use strict';

            module.exports = Readable;

            /*<replacement>*/
            var processNextTick = require('process-nextick-args');
            /*</replacement>*/


        /*<replacement>*/
            var isArray = require('isarray');
            /*</replacement>*/


            /*<replacement>*/
            var Buffer = require('buffer').Buffer;
            /*</replacement>*/

            Readable.ReadableState = ReadableState;

            var EE = require('events').EventEmitter;

            /*<replacement>*/
            if (!EE.listenerCount) EE.listenerCount = function (emitter, type) {
                return emitter.listeners(type).length;
            };
            /*</replacement>*/


            /*<replacement>*/
            var Stream;
            (function () {
                try {
                    Stream = require('st' + 'ream');
                } catch (_) {
                } finally {
                    if (!Stream)
                        Stream = require('events').EventEmitter;
                }
            }())
            /*</replacement>*/

            var Buffer = require('buffer').Buffer;

            /*<replacement>*/
        var util = require('core-util-is');
        util.inherits = require('inherits');
        /*</replacement>*/


            /*<replacement>*/
            var debug = require('util');
            if (debug && debug.debuglog) {
                debug = debug.debuglog('stream');
            } else {
                debug = function () {
                };
        }
            /*</replacement>*/

            var StringDecoder;

            util.inherits(Readable, Stream);

            function ReadableState(options, stream) {
                var Duplex = require('./_stream_duplex');

                options = options || {};

                // object stream flag. Used to make read(n) ignore n and to
                // make all the buffer merging and length checks go away
                this.objectMode = !!options.objectMode;

                if (stream instanceof Duplex)
                    this.objectMode = this.objectMode || !!options.readableObjectMode;

                // the point at which it stops calling _read() to fill the buffer
                // Note: 0 is a valid value, means "don't call _read preemptively ever"
                var hwm = options.highWaterMark;
                var defaultHwm = this.objectMode ? 16 : 16 * 1024;
                this.highWaterMark = (hwm || hwm === 0) ? hwm : defaultHwm;

                // cast to ints.
                this.highWaterMark = ~~this.highWaterMark;

                this.buffer = [];
                this.length = 0;
                this.pipes = null;
                this.pipesCount = 0;
                this.flowing = null;
                this.ended = false;
                this.endEmitted = false;
                this.reading = false;

                // a flag to be able to tell if the onwrite cb is called immediately,
                // or on a later tick.  We set this to true at first, because any
                // actions that shouldn't happen until "later" should generally also
                // not happen before the first write call.
                this.sync = true;

                // whenever we return null, then we set a flag to say
                // that we're awaiting a 'readable' event emission.
                this.needReadable = false;
                this.emittedReadable = false;
                this.readableListening = false;

                // Crypto is kind of old and crusty.  Historically, its default string
                // encoding is 'binary' so we have to make this configurable.
                // Everything else in the universe uses 'utf8', though.
                this.defaultEncoding = options.defaultEncoding || 'utf8';

                // when piping, we only care about 'readable' events that happen
                // after read()ing all the bytes and not getting any pushback.
                this.ranOut = false;

                // the number of writers that are awaiting a drain event in .pipe()s
                this.awaitDrain = 0;

                // if true, a maybeReadMore has been scheduled
                this.readingMore = false;

                this.decoder = null;
                this.encoding = null;
                if (options.encoding) {
                    if (!StringDecoder)
                        StringDecoder = require('string_decoder/').StringDecoder;
                    this.decoder = new StringDecoder(options.encoding);
                    this.encoding = options.encoding;
            }
            }

            function Readable(options) {
                var Duplex = require('./_stream_duplex');

                if (!(this instanceof Readable))
                    return new Readable(options);

                this._readableState = new ReadableState(options, this);

                // legacy
                this.readable = true;

                if (options && typeof options.read === 'function')
                    this._read = options.read;

                Stream.call(this);
            }

// Manually shove something into the read() buffer.
// This returns true if the highWaterMark has not been hit yet,
// similar to how Writable.write() returns true if you should
// write() some more.
            Readable.prototype.push = function (chunk, encoding) {
                var state = this._readableState;

                if (!state.objectMode && typeof chunk === 'string') {
                    encoding = encoding || state.defaultEncoding;
                    if (encoding !== state.encoding) {
                        chunk = new Buffer(chunk, encoding);
                        encoding = '';
                }
                }

                return readableAddChunk(this, state, chunk, encoding, false);
            };

// Unshift should *always* be something directly out of read()
            Readable.prototype.unshift = function (chunk) {
                var state = this._readableState;
                return readableAddChunk(this, state, chunk, '', true);
            };

            Readable.prototype.isPaused = function () {
                return this._readableState.flowing === false;
            };

            function readableAddChunk(stream, state, chunk, encoding, addToFront) {
                var er = chunkInvalid(state, chunk);
                if (er) {
                    stream.emit('error', er);
                } else if (chunk === null) {
                    state.reading = false;
                    onEofChunk(stream, state);
                } else if (state.objectMode || chunk && chunk.length > 0) {
                    if (state.ended && !addToFront) {
                        var e = new Error('stream.push() after EOF');
                        stream.emit('error', e);
                    } else if (state.endEmitted && addToFront) {
                        var e = new Error('stream.unshift() after end event');
                        stream.emit('error', e);
                    } else {
                        if (state.decoder && !addToFront && !encoding)
                            chunk = state.decoder.write(chunk);

                        if (!addToFront)
                            state.reading = false;

                        // if we want the data now, just emit it.
                        if (state.flowing && state.length === 0 && !state.sync) {
                            stream.emit('data', chunk);
                            stream.read(0);
                    } else {
                            // update the buffer info.
                            state.length += state.objectMode ? 1 : chunk.length;
                            if (addToFront)
                                state.buffer.unshift(chunk);
                            else
                                state.buffer.push(chunk);

                            if (state.needReadable)
                                emitReadable(stream);
                    }

                        maybeReadMore(stream, state);
                }
                } else if (!addToFront) {
                    state.reading = false;
            }

                return needMoreData(state);
            }



// if it's past the high water mark, we can push in some more.
// Also, if we have no data yet, we can stand some
// more bytes.  This is to work around cases where hwm=0,
// such as the repl.  Also, if the push() triggered a
// readable event, and the user called read(largeNumber) such that
// needReadable was set, then we ought to push more, so that another
// 'readable' event will be triggered.
            function needMoreData(state) {
                return !state.ended &&
                    (state.needReadable ||
                    state.length < state.highWaterMark ||
                    state.length === 0);
            }

// backwards compatibility.
            Readable.prototype.setEncoding = function (enc) {
                if (!StringDecoder)
                    StringDecoder = require('string_decoder/').StringDecoder;
                this._readableState.decoder = new StringDecoder(enc);
                this._readableState.encoding = enc;
                return this;
            };

// Don't raise the hwm > 128MB
            var MAX_HWM = 0x800000;

            function roundUpToNextPowerOf2(n) {
                if (n >= MAX_HWM) {
                    n = MAX_HWM;
                } else {
                    // Get the next highest power of 2
                    n--;
                    for (var p = 1; p < 32; p <<= 1) n |= n >> p;
                    n++;
                }
                return n;
            }

            function howMuchToRead(n, state) {
                if (state.length === 0 && state.ended)
                    return 0;

                if (state.objectMode)
                    return n === 0 ? 0 : 1;

                if (n === null || isNaN(n)) {
                    // only flow one buffer at a time
                    if (state.flowing && state.buffer.length)
                        return state.buffer[0].length;
                    else
                        return state.length;
                }

                if (n <= 0)
                    return 0;

                // If we're asking for more than the target buffer level,
                // then raise the water mark.  Bump up to the next highest
                // power of 2, to prevent increasing it excessively in tiny
                // amounts.
                if (n > state.highWaterMark)
                    state.highWaterMark = roundUpToNextPowerOf2(n);

                // don't have that much.  return null, unless we've ended.
                if (n > state.length) {
                    if (!state.ended) {
                        state.needReadable = true;
                        return 0;
                } else {
                        return state.length;
                }
            }

                return n;
            }

// you can override either this method, or the async _read(n) below.
            Readable.prototype.read = function (n) {
                debug('read', n);
                var state = this._readableState;
                var nOrig = n;

                if (typeof n !== 'number' || n > 0)
                    state.emittedReadable = false;

                // if we're doing read(0) to trigger a readable event, but we
                // already have a bunch of data in the buffer, then just trigger
                // the 'readable' event and move on.
                if (n === 0 &&
                    state.needReadable &&
                    (state.length >= state.highWaterMark || state.ended)) {
                    debug('read: emitReadable', state.length, state.ended);
                    if (state.length === 0 && state.ended)
                        endReadable(this);
                else
                        emitReadable(this);
                    return null;
                }

                n = howMuchToRead(n, state);

                // if we've ended, and we're now clear, then finish it up.
                if (n === 0 && state.ended) {
                    if (state.length === 0)
                    endReadable(this);
                    return null;
                }

                // All the actual chunk generation logic needs to be
                // *below* the call to _read.  The reason is that in certain
                // synthetic stream cases, such as passthrough streams, _read
                // may be a completely synchronous operation which may change
                // the state of the read buffer, providing enough data when
                // before there was *not* enough.
                //
                // So, the steps are:
                // 1. Figure out what the state of things will be after we do
                // a read from the buffer.
                //
                // 2. If that resulting state will trigger a _read, then call _read.
                // Note that this may be asynchronous, or synchronous.  Yes, it is
                // deeply ugly to write APIs this way, but that still doesn't mean
                // that the Readable class should behave improperly, as streams are
                // designed to be sync/async agnostic.
                // Take note if the _read call is sync or async (ie, if the read call
                // has returned yet), so that we know whether or not it's safe to emit
                // 'readable' etc.
                //
                // 3. Actually pull the requested chunks out of the buffer and return.

                // if we need a readable event, then we need to do some reading.
                var doRead = state.needReadable;
                debug('need readable', doRead);

                // if we currently have less than the highWaterMark, then also read some
                if (state.length === 0 || state.length - n < state.highWaterMark) {
                    doRead = true;
                    debug('length less than watermark', doRead);
            }

                // however, if we've ended, then there's no point, and if we're already
                // reading, then it's unnecessary.
                if (state.ended || state.reading) {
                    doRead = false;
                    debug('reading or ended', doRead);
                }

                if (doRead) {
                    debug('do read');
                    state.reading = true;
                    state.sync = true;
                    // if the length is currently zero, then we *need* a readable event.
                    if (state.length === 0)
                        state.needReadable = true;
                    // call internal read method
                    this._read(state.highWaterMark);
                    state.sync = false;
                }

                // If _read pushed data synchronously, then `reading` will be false,
                // and we need to re-evaluate how much data we can return to the user.
                if (doRead && !state.reading)
                    n = howMuchToRead(nOrig, state);

                var ret;
                if (n > 0)
                    ret = fromList(n, state);
                else
                    ret = null;

                if (ret === null) {
                    state.needReadable = true;
                    n = 0;
            }

                state.length -= n;

                // If we have nothing in the buffer, then we want to know
                // as soon as we *do* get something into the buffer.
                if (state.length === 0 && !state.ended)
                    state.needReadable = true;

                // If we tried to read() past the EOF, then emit end on the next tick.
                if (nOrig !== n && state.ended && state.length === 0)
                    endReadable(this);

                if (ret !== null)
                    this.emit('data', ret);

                return ret;
            };

            function chunkInvalid(state, chunk) {
                var er = null;
                if (!(Buffer.isBuffer(chunk)) &&
                    typeof chunk !== 'string' &&
                    chunk !== null &&
                    chunk !== undefined && !state.objectMode) {
                    er = new TypeError('Invalid non-string/buffer chunk');
                }
                return er;
            }


            function onEofChunk(stream, state) {
                if (state.ended) return;
                if (state.decoder) {
                    var chunk = state.decoder.end();
                    if (chunk && chunk.length) {
                        state.buffer.push(chunk);
                        state.length += state.objectMode ? 1 : chunk.length;
                    }
                }
                state.ended = true;

                // emit 'readable' now to make sure it gets picked up.
                emitReadable(stream);
            }

// Don't emit readable right away in sync mode, because this can trigger
// another read() call => stack overflow.  This way, it might trigger
// a nextTick recursion warning, but that's not so bad.
            function emitReadable(stream) {
                var state = stream._readableState;
                state.needReadable = false;
                if (!state.emittedReadable) {
                    debug('emitReadable', state.flowing);
                    state.emittedReadable = true;
                    if (state.sync)
                        processNextTick(emitReadable_, stream);
                    else
                        emitReadable_(stream);
            }
            }

            function emitReadable_(stream) {
                debug('emit readable');
                stream.emit('readable');
                flow(stream);
            }


// at this point, the user has presumably seen the 'readable' event,
// and called read() to consume some data.  that may have triggered
// in turn another _read(n) call, in which case reading = true if
// it's in progress.
// However, if we're not ended, or reading, and the length < hwm,
// then go ahead and try to read some more preemptively.
            function maybeReadMore(stream, state) {
                if (!state.readingMore) {
                    state.readingMore = true;
                    processNextTick(maybeReadMore_, stream, state);
            }
            }

            function maybeReadMore_(stream, state) {
                var len = state.length;
                while (!state.reading && !state.flowing && !state.ended &&
                state.length < state.highWaterMark) {
                    debug('maybeReadMore read 0');
                    stream.read(0);
                    if (len === state.length)
                    // didn't get any data, stop spinning.
                        break;
                    else
                        len = state.length;
            }
                state.readingMore = false;
            }

// abstract method.  to be overridden in specific implementation classes.
// call cb(er, data) where data is <= n in length.
// for virtual (non-string, non-buffer) streams, "length" is somewhat
// arbitrary, and perhaps not very meaningful.
            Readable.prototype._read = function (n) {
                this.emit('error', new Error('not implemented'));
            };

            Readable.prototype.pipe = function (dest, pipeOpts) {
                var src = this;
                var state = this._readableState;

                switch (state.pipesCount) {
                    case 0:
                        state.pipes = dest;
                        break;
                    case 1:
                        state.pipes = [state.pipes, dest];
                        break;
                    default:
                        state.pipes.push(dest);
                        break;
                }
                state.pipesCount += 1;
                debug('pipe count=%d opts=%j', state.pipesCount, pipeOpts);

                var doEnd = (!pipeOpts || pipeOpts.end !== false) &&
                    dest !== process.stdout &&
                    dest !== process.stderr;

                var endFn = doEnd ? onend : cleanup;
                if (state.endEmitted)
                    processNextTick(endFn);
                else
                    src.once('end', endFn);

                dest.on('unpipe', onunpipe);
                function onunpipe(readable) {
                    debug('onunpipe');
                    if (readable === src) {
                        cleanup();
                }
                }

                function onend() {
                    debug('onend');
                    dest.end();
                }

                // when the dest drains, it reduces the awaitDrain counter
                // on the source.  This would be more elegant with a .once()
                // handler in flow(), but adding and removing repeatedly is
                // too slow.
                var ondrain = pipeOnDrain(src);
                dest.on('drain', ondrain);

                function cleanup() {
                    debug('cleanup');
                    // cleanup event handlers once the pipe is broken
                    dest.removeListener('close', onclose);
                    dest.removeListener('finish', onfinish);
                    dest.removeListener('drain', ondrain);
                    dest.removeListener('error', onerror);
                    dest.removeListener('unpipe', onunpipe);
                    src.removeListener('end', onend);
                    src.removeListener('end', cleanup);
                    src.removeListener('data', ondata);

                    // if the reader is waiting for a drain event from this
                    // specific writer, then it would cause it to never start
                    // flowing again.
                    // So, if this is awaiting a drain, then we just call it now.
                    // If we don't know, then assume that we are waiting for one.
                    if (state.awaitDrain &&
                        (!dest._writableState || dest._writableState.needDrain))
                        ondrain();
                }

                src.on('data', ondata);
                function ondata(chunk) {
                    debug('ondata');
                    var ret = dest.write(chunk);
                    if (false === ret) {
                        debug('false write response, pause',
                            src._readableState.awaitDrain);
                        src._readableState.awaitDrain++;
                        src.pause();
                }
                }

                // if the dest has an error, then stop piping into it.
                // however, don't suppress the throwing behavior for this.
                function onerror(er) {
                    debug('onerror', er);
                    unpipe();
                    dest.removeListener('error', onerror);
                    if (EE.listenerCount(dest, 'error') === 0)
                        dest.emit('error', er);
                }

                // This is a brutally ugly hack to make sure that our error handler
                // is attached before any userland ones.  NEVER DO THIS.
                if (!dest._events || !dest._events.error)
                    dest.on('error', onerror);
                else if (isArray(dest._events.error))
                    dest._events.error.unshift(onerror);
                else
                    dest._events.error = [onerror, dest._events.error];


                // Both close and finish should trigger unpipe, but only once.
                function onclose() {
                    dest.removeListener('finish', onfinish);
                    unpipe();
                }

                dest.once('close', onclose);
                function onfinish() {
                    debug('onfinish');
                    dest.removeListener('close', onclose);
                    unpipe();
                }

                dest.once('finish', onfinish);

                function unpipe() {
                    debug('unpipe');
                    src.unpipe(dest);
                }

                // tell the dest that it's being piped to
                dest.emit('pipe', src);

                // start the flow if it hasn't been started already.
                if (!state.flowing) {
                    debug('pipe resume');
                    src.resume();
                }

                return dest;
            };

            function pipeOnDrain(src) {
                return function () {
                    var state = src._readableState;
                    debug('pipeOnDrain', state.awaitDrain);
                    if (state.awaitDrain)
                        state.awaitDrain--;
                    if (state.awaitDrain === 0 && EE.listenerCount(src, 'data')) {
                        state.flowing = true;
                        flow(src);
                }
            };
            }


            Readable.prototype.unpipe = function (dest) {
                var state = this._readableState;

                // if we're not piping anywhere, then do nothing.
                if (state.pipesCount === 0)
                    return this;

                // just one destination.  most common case.
                if (state.pipesCount === 1) {
                    // passed in one, but it's not the right one.
                    if (dest && dest !== state.pipes)
                    return this;

                    if (!dest)
                        dest = state.pipes;

                    // got a match.
                    state.pipes = null;
                    state.pipesCount = 0;
                    state.flowing = false;
                    if (dest)
                        dest.emit('unpipe', this);
                    return this;
                }

                // slow case. multiple pipe destinations.

                if (!dest) {
                    // remove all.
                    var dests = state.pipes;
                    var len = state.pipesCount;
                    state.pipes = null;
                    state.pipesCount = 0;
                    state.flowing = false;

                    for (var i = 0; i < len; i++)
                        dests[i].emit('unpipe', this);
                    return this;
                }

                // try to find the right one.
                var i = indexOf(state.pipes, dest);
                if (i === -1)
                    return this;

                state.pipes.splice(i, 1);
                state.pipesCount -= 1;
                if (state.pipesCount === 1)
                    state.pipes = state.pipes[0];

                dest.emit('unpipe', this);

                return this;
            };

// set up data events if they are asked for
// Ensure readable listeners eventually get something
            Readable.prototype.on = function (ev, fn) {
                var res = Stream.prototype.on.call(this, ev, fn);

                // If listening to data, and it has not explicitly been paused,
                // then call resume to start the flow of data on the next tick.
                if (ev === 'data' && false !== this._readableState.flowing) {
                    this.resume();
                }

                if (ev === 'readable' && this.readable) {
                    var state = this._readableState;
                    if (!state.readableListening) {
                        state.readableListening = true;
                        state.emittedReadable = false;
                        state.needReadable = true;
                        if (!state.reading) {
                            processNextTick(nReadingNextTick, this);
                        } else if (state.length) {
                            emitReadable(this, state);
                    }
                }
            }

                return res;
            };
            Readable.prototype.addListener = Readable.prototype.on;

            function nReadingNextTick(self) {
                debug('readable nexttick read 0');
                self.read(0);
            }

// pause() and resume() are remnants of the legacy readable stream API
// If the user uses them, then switch into old mode.
            Readable.prototype.resume = function () {
                var state = this._readableState;
                if (!state.flowing) {
                    debug('resume');
                    state.flowing = true;
                    resume(this, state);
                }
                return this;
            };

            function resume(stream, state) {
                if (!state.resumeScheduled) {
                    state.resumeScheduled = true;
                    processNextTick(resume_, stream, state);
            }
            }

            function resume_(stream, state) {
                if (!state.reading) {
                    debug('resume read 0');
                    stream.read(0);
            }

                state.resumeScheduled = false;
                stream.emit('resume');
                flow(stream);
                if (state.flowing && !state.reading)
                    stream.read(0);
            }

            Readable.prototype.pause = function () {
                debug('call pause flowing=%j', this._readableState.flowing);
                if (false !== this._readableState.flowing) {
                    debug('pause');
                    this._readableState.flowing = false;
                    this.emit('pause');
            }
                return this;
            };

            function flow(stream) {
                var state = stream._readableState;
                debug('flow', state.flowing);
                if (state.flowing) {
                    do {
                        var chunk = stream.read();
                    } while (null !== chunk && state.flowing);
                }
            }

// wrap an old-style stream as the async data source.
// This is *not* part of the readable stream interface.
// It is an ugly unfortunate mess of history.
            Readable.prototype.wrap = function (stream) {
                var state = this._readableState;
                var paused = false;

                var self = this;
                stream.on('end', function () {
                    debug('wrapped end');
                    if (state.decoder && !state.ended) {
                        var chunk = state.decoder.end();
                        if (chunk && chunk.length)
                            self.push(chunk);
                    }

                    self.push(null);
                });

                stream.on('data', function (chunk) {
                    debug('wrapped data');
                    if (state.decoder)
                        chunk = state.decoder.write(chunk);

                    // don't skip over falsy values in objectMode
                    if (state.objectMode && (chunk === null || chunk === undefined))
                        return;
                    else if (!state.objectMode && (!chunk || !chunk.length))
                        return;

                    var ret = self.push(chunk);
                    if (!ret) {
                        paused = true;
                        stream.pause();
                    }
                });

                // proxy all the other methods.
                // important when wrapping filters and duplexes.
                for (var i in stream) {
                    if (this[i] === undefined && typeof stream[i] === 'function') {
                        this[i] = function (method) {
                            return function () {
                                return stream[method].apply(stream, arguments);
                            };
                        }(i);
                }
                }

                // proxy certain important events.
                var events = ['error', 'close', 'destroy', 'pause', 'resume'];
                forEach(events, function (ev) {
                    stream.on(ev, self.emit.bind(self, ev));
                });

                // when we try to consume some more bytes, simply unpause the
                // underlying stream.
                self._read = function (n) {
                    debug('wrapped _read', n);
                    if (paused) {
                        paused = false;
                        stream.resume();
                    }
            };

                return self;
            };



// exposed for testing purposes only.
            Readable._fromList = fromList;

// Pluck off n bytes from an array of buffers.
// Length is the combined lengths of all the buffers in the list.
            function fromList(n, state) {
                var list = state.buffer;
                var length = state.length;
                var stringMode = !!state.decoder;
                var objectMode = !!state.objectMode;
                var ret;

                // nothing in the list, definitely empty.
                if (list.length === 0)
                    return null;

                if (length === 0)
                    ret = null;
                else if (objectMode)
                    ret = list.shift();
                else if (!n || n >= length) {
                    // read it all, truncate the array.
                    if (stringMode)
                        ret = list.join('');
                    else
                        ret = Buffer.concat(list, length);
                    list.length = 0;
                } else {
                    // read just some of it.
                    if (n < list[0].length) {
                        // just take a part of the first list item.
                        // slice is the same for buffers and strings.
                        var buf = list[0];
                        ret = buf.slice(0, n);
                        list[0] = buf.slice(n);
                    } else if (n === list[0].length) {
                        // first list is a perfect match
                    ret = list.shift();
                    } else {
                        // complex case.
                        // we have enough to cover it, but it spans past the first buffer.
                    if (stringMode)
                        ret = '';
                    else
                        ret = new Buffer(n);

                        var c = 0;
                        for (var i = 0, l = list.length; i < l && c < n; i++) {
                        var buf = list[0];
                            var cpy = Math.min(n - c, buf.length);

                        if (stringMode)
                            ret += buf.slice(0, cpy);
                        else
                            buf.copy(ret, c, 0, cpy);

                            if (cpy < buf.length)
                                list[0] = buf.slice(cpy);
                            else
                                list.shift();

                            c += cpy;
                    }
                }
            }

                return ret;
            }

            function endReadable(stream) {
                var state = stream._readableState;

                // If we get here before consuming all the bytes, then that is a
                // bug in node.  Should never happen.
                if (state.length > 0)
                    throw new Error('endReadable called on non-empty stream');

                if (!state.endEmitted) {
                    state.ended = true;
                    processNextTick(endReadableNT, state, stream);
            }
            }

            function endReadableNT(state, stream) {
                // Check that we didn't get one last unshift.
                if (!state.endEmitted && state.length === 0) {
                    state.endEmitted = true;
                    stream.readable = false;
                    stream.emit('end');
            }
            }

            function forEach(xs, f) {
                for (var i = 0, l = xs.length; i < l; i++) {
                    f(xs[i], i);
            }
            }

            function indexOf(xs, x) {
                for (var i = 0, l = xs.length; i < l; i++) {
                    if (xs[i] === x) return i;
            }
                return -1;
            }

        }).call(this, require('_process'))
    }, {
        "./_stream_duplex": 98,
        "_process": 92,
        "buffer": 84,
        "core-util-is": 103,
        "events": 88,
        "inherits": 89,
        "isarray": 91,
        "process-nextick-args": 104,
        "string_decoder/": 111,
        "util": 83
    }],
    101: [function (require, module, exports) {
// a transform stream is a readable/writable stream where you do
// something with the data.  Sometimes it's called a "filter",
// but that's not a great name for it, since that implies a thing where
// some bits pass through, and others are simply ignored.  (That would
// be a valid example of a transform, of course.)
//
// While the output is causally related to the input, it's not a
// necessarily symmetric or synchronous transformation.  For example,
// a zlib stream might take multiple plain-text writes(), and then
// emit a single compressed chunk some time in the future.
//
// Here's how this works:
//
// The Transform stream has all the aspects of the readable and writable
// stream classes.  When you write(chunk), that calls _write(chunk,cb)
// internally, and returns false if there's a lot of pending writes
// buffered up.  When you call read(), that calls _read(n) until
// there's enough pending readable data buffered up.
//
// In a transform stream, the written data is placed in a buffer.  When
// _read(n) is called, it transforms the queued up data, calling the
// buffered _write cb's as it consumes chunks.  If consuming a single
// written chunk would result in multiple output chunks, then the first
// outputted bit calls the readcb, and subsequent chunks just go into
// the read buffer, and will cause it to emit 'readable' if necessary.
//
// This way, back-pressure is actually determined by the reading side,
// since _read has to be called to start processing a new chunk.  However,
// a pathological inflate type of transform can cause excessive buffering
// here.  For example, imagine a stream where every byte of input is
// interpreted as an integer from 0-255, and then results in that many
// bytes of output.  Writing the 4 bytes {ff,ff,ff,ff} would result in
// 1kb of data being output.  In this case, you could write a very small
// amount of input, and end up with a very large amount of output.  In
// such a pathological inflating mechanism, there'd be no way to tell
// the system to stop doing the transform.  A single 4MB write could
// cause the system to run out of memory.
//
// However, even in such a pathological case, only a single written chunk
// would be consumed, and then the rest would wait (un-transformed) until
// the results of the previous transformed chunk were consumed.

        'use strict';

        module.exports = Transform;

        var Duplex = require('./_stream_duplex');

        /*<replacement>*/
        var util = require('core-util-is');
        util.inherits = require('inherits');
        /*</replacement>*/

        util.inherits(Transform, Duplex);


        function TransformState(stream) {
            this.afterTransform = function (er, data) {
                return afterTransform(stream, er, data);
        };

            this.needTransform = false;
            this.transforming = false;
            this.writecb = null;
            this.writechunk = null;
        }

        function afterTransform(stream, er, data) {
            var ts = stream._transformState;
            ts.transforming = false;

            var cb = ts.writecb;

            if (!cb)
                return stream.emit('error', new Error('no writecb in Transform class'));

            ts.writechunk = null;
            ts.writecb = null;

            if (data !== null && data !== undefined)
                stream.push(data);

            if (cb)
                cb(er);

            var rs = stream._readableState;
            rs.reading = false;
            if (rs.needReadable || rs.length < rs.highWaterMark) {
                stream._read(rs.highWaterMark);
            }
        }


        function Transform(options) {
            if (!(this instanceof Transform))
                return new Transform(options);

            Duplex.call(this, options);

            this._transformState = new TransformState(this);

            // when the writable side finishes, then flush out anything remaining.
            var stream = this;

            // start out asking for a readable event once data is transformed.
            this._readableState.needReadable = true;

            // we have implemented the _read method, and done the other things
            // that Readable wants before the first _read call, so unset the
            // sync guard flag.
            this._readableState.sync = false;

            if (options) {
                if (typeof options.transform === 'function')
                    this._transform = options.transform;

                if (typeof options.flush === 'function')
                    this._flush = options.flush;
            }

            this.once('prefinish', function () {
                if (typeof this._flush === 'function')
                    this._flush(function (er) {
                        done(stream, er);
                    });
                else
                    done(stream);
            });
        }

        Transform.prototype.push = function (chunk, encoding) {
            this._transformState.needTransform = false;
            return Duplex.prototype.push.call(this, chunk, encoding);
        };

// This is the part where you do stuff!
// override this function in implementation classes.
// 'chunk' is an input chunk.
//
// Call `push(newChunk)` to pass along transformed output
// to the readable side.  You may call 'push' zero or more times.
//
// Call `cb(err)` when you are done with this chunk.  If you pass
// an error, then that'll put the hurt on the whole operation.  If you
// never call cb(), then you'll never get another chunk.
        Transform.prototype._transform = function (chunk, encoding, cb) {
            throw new Error('not implemented');
        };

        Transform.prototype._write = function (chunk, encoding, cb) {
            var ts = this._transformState;
            ts.writecb = cb;
            ts.writechunk = chunk;
            ts.writeencoding = encoding;
            if (!ts.transforming) {
                var rs = this._readableState;
                if (ts.needTransform ||
                    rs.needReadable ||
                    rs.length < rs.highWaterMark)
                    this._read(rs.highWaterMark);
            }
        };

// Doesn't matter what the args are here.
// _transform does all the work.
// That we got here means that the readable side wants more data.
        Transform.prototype._read = function (n) {
            var ts = this._transformState;

            if (ts.writechunk !== null && ts.writecb && !ts.transforming) {
                ts.transforming = true;
                this._transform(ts.writechunk, ts.writeencoding, ts.afterTransform);
            } else {
                // mark that we need a transform, so that any data that comes in
                // will get processed, now that we've asked for it.
                ts.needTransform = true;
        }
        };


        function done(stream, er) {
            if (er)
                return stream.emit('error', er);

            // if there's nothing in the write buffer, then that means
            // that nothing more will ever be provided
            var ws = stream._writableState;
            var ts = stream._transformState;

            if (ws.length)
                throw new Error('calling transform done when ws.length != 0');

            if (ts.transforming)
                throw new Error('calling transform done when still transforming');

            return stream.push(null);
        }

    }, {"./_stream_duplex": 98, "core-util-is": 103, "inherits": 89}],
    102: [function (require, module, exports) {
// A bit simpler than readable streams.
// Implement an async ._write(chunk, cb), and it'll handle all
// the drain event emission and buffering.

        'use strict';

        module.exports = Writable;

        /*<replacement>*/
        var processNextTick = require('process-nextick-args');
        /*</replacement>*/


        /*<replacement>*/
        var Buffer = require('buffer').Buffer;
        /*</replacement>*/

        Writable.WritableState = WritableState;


        /*<replacement>*/
        var util = require('core-util-is');
        util.inherits = require('inherits');
        /*</replacement>*/


        /*<replacement>*/
        var Stream;
        (function () {
            try {
                Stream = require('st' + 'ream');
            } catch (_) {
            } finally {
                if (!Stream)
                    Stream = require('events').EventEmitter;
            }
        }())
        /*</replacement>*/

        var Buffer = require('buffer').Buffer;

        util.inherits(Writable, Stream);

        function nop() {
        }

        function WriteReq(chunk, encoding, cb) {
            this.chunk = chunk;
            this.encoding = encoding;
            this.callback = cb;
            this.next = null;
        }

        function WritableState(options, stream) {
            var Duplex = require('./_stream_duplex');

            options = options || {};

            // object stream flag to indicate whether or not this stream
            // contains buffers or objects.
            this.objectMode = !!options.objectMode;

            if (stream instanceof Duplex)
                this.objectMode = this.objectMode || !!options.writableObjectMode;

            // the point at which write() starts returning false
            // Note: 0 is a valid value, means that we always return false if
            // the entire buffer is not flushed immediately on write()
            var hwm = options.highWaterMark;
            var defaultHwm = this.objectMode ? 16 : 16 * 1024;
            this.highWaterMark = (hwm || hwm === 0) ? hwm : defaultHwm;

            // cast to ints.
            this.highWaterMark = ~~this.highWaterMark;

            this.needDrain = false;
            // at the start of calling end()
            this.ending = false;
            // when end() has been called, and returned
            this.ended = false;
            // when 'finish' is emitted
            this.finished = false;

            // should we decode strings into buffers before passing to _write?
            // this is here so that some node-core streams can optimize string
            // handling at a lower level.
            var noDecode = options.decodeStrings === false;
            this.decodeStrings = !noDecode;

            // Crypto is kind of old and crusty.  Historically, its default string
            // encoding is 'binary' so we have to make this configurable.
            // Everything else in the universe uses 'utf8', though.
            this.defaultEncoding = options.defaultEncoding || 'utf8';

            // not an actual buffer we keep track of, but a measurement
            // of how much we're waiting to get pushed to some underlying
            // socket or file.
            this.length = 0;

            // a flag to see when we're in the middle of a write.
            this.writing = false;

            // when true all writes will be buffered until .uncork() call
            this.corked = 0;

            // a flag to be able to tell if the onwrite cb is called immediately,
            // or on a later tick.  We set this to true at first, because any
            // actions that shouldn't happen until "later" should generally also
            // not happen before the first write call.
            this.sync = true;

            // a flag to know if we're processing previously buffered items, which
            // may call the _write() callback in the same tick, so that we don't
            // end up in an overlapped onwrite situation.
            this.bufferProcessing = false;

            // the callback that's passed to _write(chunk,cb)
            this.onwrite = function (er) {
                onwrite(stream, er);
        };

            // the callback that the user supplies to write(chunk,encoding,cb)
            this.writecb = null;

            // the amount that is being written when _write is called.
            this.writelen = 0;

            this.bufferedRequest = null;
            this.lastBufferedRequest = null;

            // number of pending user-supplied write callbacks
            // this must be 0 before 'finish' can be emitted
            this.pendingcb = 0;

            // emit prefinish if the only thing we're waiting for is _write cbs
            // This is relevant for synchronous Transform streams
            this.prefinished = false;

            // True if the error was already emitted and should not be thrown again
            this.errorEmitted = false;
        }

        WritableState.prototype.getBuffer = function writableStateGetBuffer() {
            var current = this.bufferedRequest;
            var out = [];
            while (current) {
                out.push(current);
                current = current.next;
            }
            return out;
        };

        (function () {
            try {
                Object.defineProperty(WritableState.prototype, 'buffer', {
                    get: require('util-deprecate')(function () {
                        return this.getBuffer();
                    }, '_writableState.buffer is deprecated. Use ' +
                        '_writableState.getBuffer() instead.')
                });
            } catch (_) {
            }
        }());


        function Writable(options) {
            var Duplex = require('./_stream_duplex');

            // Writable ctor is applied to Duplexes, though they're not
            // instanceof Writable, they're instanceof Readable.
            if (!(this instanceof Writable) && !(this instanceof Duplex))
                return new Writable(options);

            this._writableState = new WritableState(options, this);

            // legacy.
            this.writable = true;

            if (options) {
                if (typeof options.write === 'function')
                    this._write = options.write;

                if (typeof options.writev === 'function')
                    this._writev = options.writev;
        }

            Stream.call(this);
        }

// Otherwise people can pipe Writable streams, which is just wrong.
        Writable.prototype.pipe = function () {
            this.emit('error', new Error('Cannot pipe. Not readable.'));
        };


        function writeAfterEnd(stream, cb) {
            var er = new Error('write after end');
            // TODO: defer error events consistently everywhere, not just the cb
            stream.emit('error', er);
            processNextTick(cb, er);
        }

// If we get something that is not a buffer, string, null, or undefined,
// and we're not in objectMode, then that's an error.
// Otherwise stream chunks are all considered to be of length=1, and the
// watermarks determine how many objects to keep in the buffer, rather than
// how many bytes or characters.
        function validChunk(stream, state, chunk, cb) {
            var valid = true;

            if (!(Buffer.isBuffer(chunk)) &&
                typeof chunk !== 'string' &&
                chunk !== null &&
                chunk !== undefined && !state.objectMode) {
                var er = new TypeError('Invalid non-string/buffer chunk');
                stream.emit('error', er);
                processNextTick(cb, er);
                valid = false;
        }
            return valid;
        }

        Writable.prototype.write = function (chunk, encoding, cb) {
            var state = this._writableState;
            var ret = false;

            if (typeof encoding === 'function') {
                cb = encoding;
                encoding = null;
            }

            if (Buffer.isBuffer(chunk))
                encoding = 'buffer';
            else if (!encoding)
                encoding = state.defaultEncoding;

            if (typeof cb !== 'function')
                cb = nop;

            if (state.ended)
                writeAfterEnd(this, cb);
            else if (validChunk(this, state, chunk, cb)) {
                state.pendingcb++;
                ret = writeOrBuffer(this, state, chunk, encoding, cb);
            }

            return ret;
        };

        Writable.prototype.cork = function () {
            var state = this._writableState;

            state.corked++;
        };

        Writable.prototype.uncork = function () {
            var state = this._writableState;

            if (state.corked) {
                state.corked--;

                if (!state.writing && !state.corked && !state.finished && !state.bufferProcessing &&
                    state.bufferedRequest)
                    clearBuffer(this, state);
            }
        };

        Writable.prototype.setDefaultEncoding = function setDefaultEncoding(encoding) {
            // node::ParseEncoding() requires lower case.
            if (typeof encoding === 'string')
                encoding = encoding.toLowerCase();
            if (!(['hex', 'utf8', 'utf-8', 'ascii', 'binary', 'base64',
                    'ucs2', 'ucs-2', 'utf16le', 'utf-16le', 'raw']
                    .indexOf((encoding + '').toLowerCase()) > -1))
                throw new TypeError('Unknown encoding: ' + encoding);
            this._writableState.defaultEncoding = encoding;
        };

        function decodeChunk(state, chunk, encoding) {
            if (!state.objectMode &&
                state.decodeStrings !== false &&
                typeof chunk === 'string') {
                chunk = new Buffer(chunk, encoding);
        }
            return chunk;
        }

// if we're already writing something, then just put this
// in the queue, and wait our turn.  Otherwise, call _write
// If we return false, then we need a drain event, so set that flag.
        function writeOrBuffer(stream, state, chunk, encoding, cb) {
            chunk = decodeChunk(state, chunk, encoding);

            if (Buffer.isBuffer(chunk))
                encoding = 'buffer';
            var len = state.objectMode ? 1 : chunk.length;

            state.length += len;

            var ret = state.length < state.highWaterMark;
            // we must ensure that previous needDrain will not be reset to false.
            if (!ret)
                state.needDrain = true;

            if (state.writing || state.corked) {
                var last = state.lastBufferedRequest;
                state.lastBufferedRequest = new WriteReq(chunk, encoding, cb);
                if (last) {
                    last.next = state.lastBufferedRequest;
            } else {
                    state.bufferedRequest = state.lastBufferedRequest;
            }
            } else {
                doWrite(stream, state, false, len, chunk, encoding, cb);
        }

            return ret;
        }

        function doWrite(stream, state, writev, len, chunk, encoding, cb) {
            state.writelen = len;
            state.writecb = cb;
            state.writing = true;
            state.sync = true;
            if (writev)
                stream._writev(chunk, state.onwrite);
            else
                stream._write(chunk, encoding, state.onwrite);
            state.sync = false;
        }

        function onwriteError(stream, state, sync, er, cb) {
            --state.pendingcb;
            if (sync)
                processNextTick(cb, er);
            else
                cb(er);

            stream._writableState.errorEmitted = true;
            stream.emit('error', er);
        }

        function onwriteStateUpdate(state) {
            state.writing = false;
            state.writecb = null;
            state.length -= state.writelen;
            state.writelen = 0;
        }

        function onwrite(stream, er) {
            var state = stream._writableState;
            var sync = state.sync;
            var cb = state.writecb;

            onwriteStateUpdate(state);

            if (er)
                onwriteError(stream, state, sync, er, cb);
            else {
                // Check if we're actually ready to finish, but don't emit yet
                var finished = needFinish(state);

                if (!finished && !state.corked && !state.bufferProcessing &&
                    state.bufferedRequest) {
                    clearBuffer(stream, state);
            }

                if (sync) {
                    processNextTick(afterWrite, stream, state, finished, cb);
                } else {
                    afterWrite(stream, state, finished, cb);
                }
        }
        }

        function afterWrite(stream, state, finished, cb) {
            if (!finished)
                onwriteDrain(stream, state);
            state.pendingcb--;
            cb();
            finishMaybe(stream, state);
        }

// Must force callback to be called on nextTick, so that we don't
// emit 'drain' before the write() consumer gets the 'false' return
// value, and has a chance to attach a 'drain' listener.
        function onwriteDrain(stream, state) {
            if (state.length === 0 && state.needDrain) {
                state.needDrain = false;
                stream.emit('drain');
        }
        }


// if there's something in the buffer waiting, then process it
        function clearBuffer(stream, state) {
            state.bufferProcessing = true;
            var entry = state.bufferedRequest;

            if (stream._writev && entry && entry.next) {
                // Fast case, write everything using _writev()
                var buffer = [];
                var cbs = [];
                while (entry) {
                    cbs.push(entry.callback);
                    buffer.push(entry);
                    entry = entry.next;
                }

                // count the one we are adding, as well.
                // TODO(isaacs) clean this up
                state.pendingcb++;
                state.lastBufferedRequest = null;
                doWrite(stream, state, true, state.length, buffer, '', function (err) {
                    for (var i = 0; i < cbs.length; i++) {
                        state.pendingcb--;
                        cbs[i](err);
                }
                });

                // Clear buffer
            } else {
                // Slow case, write chunks one-by-one
                while (entry) {
                    var chunk = entry.chunk;
                    var encoding = entry.encoding;
                    var cb = entry.callback;
                    var len = state.objectMode ? 1 : chunk.length;

                    doWrite(stream, state, false, len, chunk, encoding, cb);
                    entry = entry.next;
                    // if we didn't call the onwrite immediately, then
                    // it means that we need to wait until it does.
                    // also, that means that the chunk and cb are currently
                    // being processed, so move the buffer counter past them.
                    if (state.writing) {
                        break;
                    }
                }

                if (entry === null)
                state.lastBufferedRequest = null;
            }
            state.bufferedRequest = entry;
            state.bufferProcessing = false;
        }

        Writable.prototype._write = function (chunk, encoding, cb) {
            cb(new Error('not implemented'));
        };

        Writable.prototype._writev = null;

        Writable.prototype.end = function (chunk, encoding, cb) {
            var state = this._writableState;

            if (typeof chunk === 'function') {
                cb = chunk;
                chunk = null;
                encoding = null;
            } else if (typeof encoding === 'function') {
                cb = encoding;
                encoding = null;
            }

            if (chunk !== null && chunk !== undefined)
                this.write(chunk, encoding);

            // .end() fully uncorks
            if (state.corked) {
                state.corked = 1;
                this.uncork();
            }

            // ignore unnecessary end() calls.
            if (!state.ending && !state.finished)
                endWritable(this, state, cb);
        };


        function needFinish(state) {
            return (state.ending &&
            state.length === 0 &&
            state.bufferedRequest === null && !state.finished && !state.writing);
        }

        function prefinish(stream, state) {
            if (!state.prefinished) {
                state.prefinished = true;
                stream.emit('prefinish');
            }
        }

        function finishMaybe(stream, state) {
            var need = needFinish(state);
            if (need) {
                if (state.pendingcb === 0) {
                    prefinish(stream, state);
                    state.finished = true;
                    stream.emit('finish');
            } else {
                    prefinish(stream, state);
            }
        }
            return need;
        }

        function endWritable(stream, state, cb) {
            state.ending = true;
            finishMaybe(stream, state);
            if (cb) {
                if (state.finished)
                    processNextTick(cb);
                else
                    stream.once('finish', cb);
        }
            state.ended = true;
        }

    }, {
        "./_stream_duplex": 98,
        "buffer": 84,
        "core-util-is": 103,
        "events": 88,
        "inherits": 89,
        "process-nextick-args": 104,
        "util-deprecate": 105
    }],
    103: [function (require, module, exports) {
        (function (Buffer) {
// Copyright Joyent, Inc. and other Node contributors.
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to permit
// persons to whom the Software is furnished to do so, subject to the
// following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
// NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
// DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
// USE OR OTHER DEALINGS IN THE SOFTWARE.

// NOTE: These type checking functions intentionally don't use `instanceof`
// because it is fragile and can be easily faked with `Object.create()`.
            function isArray(ar) {
                return Array.isArray(ar);
            }

            exports.isArray = isArray;

            function isBoolean(arg) {
                return typeof arg === 'boolean';
            }

            exports.isBoolean = isBoolean;

            function isNull(arg) {
                return arg === null;
            }

            exports.isNull = isNull;

            function isNullOrUndefined(arg) {
                return arg == null;
            }

            exports.isNullOrUndefined = isNullOrUndefined;

            function isNumber(arg) {
                return typeof arg === 'number';
            }

            exports.isNumber = isNumber;

            function isString(arg) {
                return typeof arg === 'string';
            }

            exports.isString = isString;

            function isSymbol(arg) {
                return typeof arg === 'symbol';
            }

            exports.isSymbol = isSymbol;

            function isUndefined(arg) {
                return arg === void 0;
            }

            exports.isUndefined = isUndefined;

            function isRegExp(re) {
                return isObject(re) && objectToString(re) === '[object RegExp]';
            }

            exports.isRegExp = isRegExp;

            function isObject(arg) {
                return typeof arg === 'object' && arg !== null;
            }

            exports.isObject = isObject;

            function isDate(d) {
                return isObject(d) && objectToString(d) === '[object Date]';
            }

            exports.isDate = isDate;

            function isError(e) {
                return isObject(e) &&
                    (objectToString(e) === '[object Error]' || e instanceof Error);
            }

            exports.isError = isError;

            function isFunction(arg) {
                return typeof arg === 'function';
            }

            exports.isFunction = isFunction;

            function isPrimitive(arg) {
                return arg === null ||
                    typeof arg === 'boolean' ||
                    typeof arg === 'number' ||
                    typeof arg === 'string' ||
                    typeof arg === 'symbol' ||  // ES6 symbol
                    typeof arg === 'undefined';
            }

            exports.isPrimitive = isPrimitive;

            function isBuffer(arg) {
                return Buffer.isBuffer(arg);
            }

            exports.isBuffer = isBuffer;

            function objectToString(o) {
                return Object.prototype.toString.call(o);
            }
        }).call(this, {"isBuffer": require("/usr/local/lib/node_modules/browserify/node_modules/insert-module-globals/node_modules/is-buffer/index.js")})
    }, {"/usr/local/lib/node_modules/browserify/node_modules/insert-module-globals/node_modules/is-buffer/index.js": 90}],
    104: [function (require, module, exports) {
        (function (process) {
            'use strict';
            module.exports = nextTick;

            function nextTick(fn) {
                var args = new Array(arguments.length - 1);
                var i = 0;
                while (i < args.length) {
                    args[i++] = arguments[i];
            }
                process.nextTick(function afterTick() {
                    fn.apply(null, args);
                });
            }

        }).call(this, require('_process'))
    }, {"_process": 92}],
    105: [function (require, module, exports) {
        (function (global) {

            /**
             * Module exports.
             */

            module.exports = deprecate;

            /**
             * Mark that a method should not be used.
             * Returns a modified function which warns once by default.
             *
             * If `localStorage.noDeprecation = true` is set, then it is a no-op.
             *
             * If `localStorage.throwDeprecation = true` is set, then deprecated functions
             * will throw an Error when invoked.
             *
             * If `localStorage.traceDeprecation = true` is set, then deprecated functions
             * will invoke `console.trace()` instead of `console.error()`.
             *
             * @param {Function} fn - the function to deprecate
             * @param {String} msg - the string to print to the console when `fn` is invoked
             * @returns {Function} a new "deprecated" version of `fn`
             * @api public
             */

            function deprecate(fn, msg) {
                if (config('noDeprecation')) {
                    return fn;
            }

                var warned = false;

                function deprecated() {
                    if (!warned) {
                        if (config('throwDeprecation')) {
                            throw new Error(msg);
                        } else if (config('traceDeprecation')) {
                            console.trace(msg);
                        } else {
                            console.warn(msg);
                    }
                        warned = true;
                }
                    return fn.apply(this, arguments);
            }

                return deprecated;
            }

            /**
             * Checks `localStorage` for boolean values for the given `name`.
             *
             * @param {String} name
             * @returns {Boolean}
             * @api private
             */

            function config(name) {
                if (!global.localStorage) return false;
                var val = global.localStorage[name];
                if (null == val) return false;
                return String(val).toLowerCase() === 'true';
            }

        }).call(this, typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})
    }, {}],
    106: [function (require, module, exports) {
        module.exports = require("./lib/_stream_passthrough.js")

    }, {"./lib/_stream_passthrough.js": 99}],
    107: [function (require, module, exports) {
        var Stream = (function () {
            try {
                return require('st' + 'ream'); // hack to fix a circular dependency issue when used with browserify
            } catch (_) {
            }
        }());
        exports = module.exports = require('./lib/_stream_readable.js');
        exports.Stream = Stream || exports;
        exports.Readable = exports;
        exports.Writable = require('./lib/_stream_writable.js');
        exports.Duplex = require('./lib/_stream_duplex.js');
        exports.Transform = require('./lib/_stream_transform.js');
        exports.PassThrough = require('./lib/_stream_passthrough.js');

    }, {
        "./lib/_stream_duplex.js": 98,
        "./lib/_stream_passthrough.js": 99,
        "./lib/_stream_readable.js": 100,
        "./lib/_stream_transform.js": 101,
        "./lib/_stream_writable.js": 102
    }],
    108: [function (require, module, exports) {
        module.exports = require("./lib/_stream_transform.js")

    }, {"./lib/_stream_transform.js": 101}],
    109: [function (require, module, exports) {
        module.exports = require("./lib/_stream_writable.js")

    }, {"./lib/_stream_writable.js": 102}],
    110: [function (require, module, exports) {
// Copyright Joyent, Inc. and other Node contributors.
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to permit
// persons to whom the Software is furnished to do so, subject to the
// following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
// NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
// DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
// USE OR OTHER DEALINGS IN THE SOFTWARE.

        module.exports = Stream;

        var EE = require('events').EventEmitter;
        var inherits = require('inherits');

        inherits(Stream, EE);
        Stream.Readable = require('readable-stream/readable.js');
        Stream.Writable = require('readable-stream/writable.js');
        Stream.Duplex = require('readable-stream/duplex.js');
        Stream.Transform = require('readable-stream/transform.js');
        Stream.PassThrough = require('readable-stream/passthrough.js');

// Backwards-compat with node 0.4.x
        Stream.Stream = Stream;



// old-style streams.  Note that the pipe method (the only relevant
// part of this class) is overridden in the Readable class.

        function Stream() {
            EE.call(this);
        }

        Stream.prototype.pipe = function (dest, options) {
            var source = this;

            function ondata(chunk) {
                if (dest.writable) {
                    if (false === dest.write(chunk) && source.pause) {
                        source.pause();
                }
            }
            }

            source.on('data', ondata);

            function ondrain() {
                if (source.readable && source.resume) {
                    source.resume();
            }
            }

            dest.on('drain', ondrain);

            // If the 'end' option is not supplied, dest.end() will be called when
            // source gets the 'end' or 'close' events.  Only dest.end() once.
            if (!dest._isStdio && (!options || options.end !== false)) {
                source.on('end', onend);
                source.on('close', onclose);
            }

            var didOnEnd = false;

            function onend() {
                if (didOnEnd) return;
                didOnEnd = true;

                dest.end();
            }


            function onclose() {
                if (didOnEnd) return;
                didOnEnd = true;

                if (typeof dest.destroy === 'function') dest.destroy();
            }

            // don't leave dangling pipes when there are errors.
            function onerror(er) {
                cleanup();
                if (EE.listenerCount(this, 'error') === 0) {
                    throw er; // Unhandled stream error in pipe.
            }
            }

            source.on('error', onerror);
            dest.on('error', onerror);

            // remove all the event listeners that were added.
            function cleanup() {
                source.removeListener('data', ondata);
                dest.removeListener('drain', ondrain);

                source.removeListener('end', onend);
                source.removeListener('close', onclose);

                source.removeListener('error', onerror);
                dest.removeListener('error', onerror);

                source.removeListener('end', cleanup);
                source.removeListener('close', cleanup);

                dest.removeListener('close', cleanup);
            }

            source.on('end', cleanup);
            source.on('close', cleanup);

            dest.on('close', cleanup);

            dest.emit('pipe', source);

            // Allow for unix-like usage: A.pipe(B).pipe(C)
            return dest;
        };

    }, {
        "events": 88,
        "inherits": 89,
        "readable-stream/duplex.js": 97,
        "readable-stream/passthrough.js": 106,
        "readable-stream/readable.js": 107,
        "readable-stream/transform.js": 108,
        "readable-stream/writable.js": 109
    }],
    111: [function (require, module, exports) {
// Copyright Joyent, Inc. and other Node contributors.
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to permit
// persons to whom the Software is furnished to do so, subject to the
// following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
// NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
// DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
// USE OR OTHER DEALINGS IN THE SOFTWARE.

        var Buffer = require('buffer').Buffer;

        var isBufferEncoding = Buffer.isEncoding
            || function (encoding) {
                switch (encoding && encoding.toLowerCase()) {
                    case 'hex':
                    case 'utf8':
                    case 'utf-8':
                    case 'ascii':
                    case 'binary':
                    case 'base64':
                    case 'ucs2':
                    case 'ucs-2':
                    case 'utf16le':
                    case 'utf-16le':
                    case 'raw':
                        return true;
                    default:
                        return false;
            }
            }


        function assertEncoding(encoding) {
            if (encoding && !isBufferEncoding(encoding)) {
                throw new Error('Unknown encoding: ' + encoding);
        }
        }

// StringDecoder provides an interface for efficiently splitting a series of
// buffers into a series of JS strings without breaking apart multi-byte
// characters. CESU-8 is handled as part of the UTF-8 encoding.
//
// @TODO Handling all encodings inside a single object makes it very difficult
// to reason about this code, so it should be split up in the future.
// @TODO There should be a utf8-strict encoding that rejects invalid UTF-8 code
// points as used by CESU-8.
        var StringDecoder = exports.StringDecoder = function (encoding) {
            this.encoding = (encoding || 'utf8').toLowerCase().replace(/[-_]/, '');
            assertEncoding(encoding);
            switch (this.encoding) {
                case 'utf8':
                    // CESU-8 represents each of Surrogate Pair by 3-bytes
                    this.surrogateSize = 3;
                    break;
                case 'ucs2':
                case 'utf16le':
                    // UTF-16 represents each of Surrogate Pair by 2-bytes
                    this.surrogateSize = 2;
                    this.detectIncompleteChar = utf16DetectIncompleteChar;
                    break;
                case 'base64':
                    // Base-64 stores 3 bytes in 4 chars, and pads the remainder.
                    this.surrogateSize = 3;
                    this.detectIncompleteChar = base64DetectIncompleteChar;
                    break;
                default:
                    this.write = passThroughWrite;
                    return;
            }

            // Enough space to store all bytes of a single character. UTF-8 needs 4
            // bytes, but CESU-8 may require up to 6 (3 bytes per surrogate).
            this.charBuffer = new Buffer(6);
            // Number of bytes received for the current incomplete multi-byte character.
            this.charReceived = 0;
            // Number of bytes expected for the current incomplete multi-byte character.
            this.charLength = 0;
        };


// write decodes the given buffer and returns it as JS string that is
// guaranteed to not contain any partial multi-byte characters. Any partial
// character found at the end of the buffer is buffered up, and will be
// returned when calling write again with the remaining bytes.
//
// Note: Converting a Buffer containing an orphan surrogate to a String
// currently works, but converting a String to a Buffer (via `new Buffer`, or
// Buffer#write) will replace incomplete surrogates with the unicode
// replacement character. See https://codereview.chromium.org/121173009/ .
        StringDecoder.prototype.write = function (buffer) {
            var charStr = '';
            // if our last write ended with an incomplete multibyte character
            while (this.charLength) {
                // determine how many remaining bytes this buffer has to offer for this char
                var available = (buffer.length >= this.charLength - this.charReceived) ?
                this.charLength - this.charReceived :
                    buffer.length;

                // add the new bytes to the char buffer
                buffer.copy(this.charBuffer, this.charReceived, 0, available);
                this.charReceived += available;

                if (this.charReceived < this.charLength) {
                    // still not enough chars in this buffer? wait for more ...
                    return '';
            }

                // remove bytes belonging to the current character from the buffer
                buffer = buffer.slice(available, buffer.length);

                // get the character that was split
                charStr = this.charBuffer.slice(0, this.charLength).toString(this.encoding);

            // CESU-8: lead surrogate (D800-DBFF) is also the incomplete character
                var charCode = charStr.charCodeAt(charStr.length - 1);
            if (charCode >= 0xD800 && charCode <= 0xDBFF) {
                this.charLength += this.surrogateSize;
                charStr = '';
                continue;
            }
                this.charReceived = this.charLength = 0;

                // if there are no more bytes in this buffer, just emit our char
                if (buffer.length === 0) {
                    return charStr;
                }
                break;
            }

            // determine and set charLength / charReceived
            this.detectIncompleteChar(buffer);

            var end = buffer.length;
            if (this.charLength) {
                // buffer the incomplete character bytes we got
                buffer.copy(this.charBuffer, 0, buffer.length - this.charReceived, end);
                end -= this.charReceived;
            }

            charStr += buffer.toString(this.encoding, 0, end);

            var end = charStr.length - 1;
            var charCode = charStr.charCodeAt(end);
            // CESU-8: lead surrogate (D800-DBFF) is also the incomplete character
            if (charCode >= 0xD800 && charCode <= 0xDBFF) {
                var size = this.surrogateSize;
                this.charLength += size;
                this.charReceived += size;
                this.charBuffer.copy(this.charBuffer, size, 0, size);
                buffer.copy(this.charBuffer, 0, 0, size);
                return charStr.substring(0, end);
            }

            // or just emit the charStr
            return charStr;
        };

// detectIncompleteChar determines if there is an incomplete UTF-8 character at
// the end of the given buffer. If so, it sets this.charLength to the byte
// length that character, and sets this.charReceived to the number of bytes
// that are available for this character.
        StringDecoder.prototype.detectIncompleteChar = function (buffer) {
            // determine how many bytes we have to check at the end of this buffer
            var i = (buffer.length >= 3) ? 3 : buffer.length;

            // Figure out if one of the last i bytes of our buffer announces an
            // incomplete char.
            for (; i > 0; i--) {
                var c = buffer[buffer.length - i];

                // See http://en.wikipedia.org/wiki/UTF-8#Description

                // 110XXXXX
                if (i == 1 && c >> 5 == 0x06) {
                    this.charLength = 2;
                    break;
                }

                // 1110XXXX
                if (i <= 2 && c >> 4 == 0x0E) {
                    this.charLength = 3;
                    break;
            }

                // 11110XXX
                if (i <= 3 && c >> 3 == 0x1E) {
                    this.charLength = 4;
                    break;
            }
            }
            this.charReceived = i;
        };

        StringDecoder.prototype.end = function (buffer) {
            var res = '';
            if (buffer && buffer.length)
                res = this.write(buffer);

            if (this.charReceived) {
                var cr = this.charReceived;
                var buf = this.charBuffer;
                var enc = this.encoding;
                res += buf.slice(0, cr).toString(enc);
        }

            return res;
        };

        function passThroughWrite(buffer) {
            return buffer.toString(this.encoding);
        }

        function utf16DetectIncompleteChar(buffer) {
            this.charReceived = buffer.length % 2;
            this.charLength = this.charReceived ? 2 : 0;
        }

        function base64DetectIncompleteChar(buffer) {
            this.charReceived = buffer.length % 3;
            this.charLength = this.charReceived ? 3 : 0;
        }

    }, {"buffer": 84}],
    112: [function (require, module, exports) {
// Copyright Joyent, Inc. and other Node contributors.
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to permit
// persons to whom the Software is furnished to do so, subject to the
// following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
// NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
// DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
// USE OR OTHER DEALINGS IN THE SOFTWARE.

        var punycode = require('punycode');

        exports.parse = urlParse;
        exports.resolve = urlResolve;
        exports.resolveObject = urlResolveObject;
        exports.format = urlFormat;

        exports.Url = Url;

        function Url() {
            this.protocol = null;
            this.slashes = null;
            this.auth = null;
            this.host = null;
            this.port = null;
            this.hostname = null;
            this.hash = null;
            this.search = null;
            this.query = null;
            this.pathname = null;
            this.path = null;
            this.href = null;
        }

// Reference: RFC 3986, RFC 1808, RFC 2396

// define these here so at least they only have to be
// compiled once on the first module load.
        var protocolPattern = /^([a-z0-9.+-]+:)/i,
            portPattern = /:[0-9]*$/,

        // RFC 2396: characters reserved for delimiting URLs.
        // We actually just auto-escape these.
            delims = ['<', '>', '"', '`', ' ', '\r', '\n', '\t'],

        // RFC 2396: characters not allowed for various reasons.
            unwise = ['{', '}', '|', '\\', '^', '`'].concat(delims),

        // Allowed by RFCs, but cause of XSS attacks.  Always escape these.
            autoEscape = ['\''].concat(unwise),
        // Characters that are never ever allowed in a hostname.
        // Note that any invalid chars are also handled, but these
        // are the ones that are *expected* to be seen, so we fast-path
        // them.
            nonHostChars = ['%', '/', '?', ';', '#'].concat(autoEscape),
            hostEndingChars = ['/', '?', '#'],
            hostnameMaxLen = 255,
            hostnamePartPattern = /^[a-z0-9A-Z_-]{0,63}$/,
            hostnamePartStart = /^([a-z0-9A-Z_-]{0,63})(.*)$/,
        // protocols that can allow "unsafe" and "unwise" chars.
            unsafeProtocol = {
                'javascript': true,
                'javascript:': true
            },
        // protocols that never have a hostname.
            hostlessProtocol = {
                'javascript': true,
                'javascript:': true
            },
        // protocols that always contain a // bit.
            slashedProtocol = {
                'http': true,
                'https': true,
                'ftp': true,
                'gopher': true,
                'file': true,
                'http:': true,
                'https:': true,
                'ftp:': true,
                'gopher:': true,
                'file:': true
            },
            querystring = require('querystring');

        function urlParse(url, parseQueryString, slashesDenoteHost) {
            if (url && isObject(url) && url instanceof Url) return url;

            var u = new Url;
            u.parse(url, parseQueryString, slashesDenoteHost);
            return u;
        }

        Url.prototype.parse = function (url, parseQueryString, slashesDenoteHost) {
            if (!isString(url)) {
                throw new TypeError("Parameter 'url' must be a string, not " + typeof url);
        }

            var rest = url;

            // trim before proceeding.
            // This is to support parse stuff like "  http://foo.com  \n"
            rest = rest.trim();

            var proto = protocolPattern.exec(rest);
            if (proto) {
                proto = proto[0];
                var lowerProto = proto.toLowerCase();
                this.protocol = lowerProto;
                rest = rest.substr(proto.length);
            }

            // figure out if it's got a host
            // user@server is *always* interpreted as a hostname, and url
            // resolution will treat //foo/bar as host=foo,path=bar because that's
            // how the browser resolves relative URLs.
            if (slashesDenoteHost || proto || rest.match(/^\/\/[^@\/]+@[^@\/]+/)) {
                var slashes = rest.substr(0, 2) === '//';
                if (slashes && !(proto && hostlessProtocol[proto])) {
                    rest = rest.substr(2);
                    this.slashes = true;
            }
            }

            if (!hostlessProtocol[proto] &&
                (slashes || (proto && !slashedProtocol[proto]))) {

                // there's a hostname.
                // the first instance of /, ?, ;, or # ends the host.
                //
                // If there is an @ in the hostname, then non-host chars *are* allowed
                // to the left of the last @ sign, unless some host-ending character
                // comes *before* the @-sign.
                // URLs are obnoxious.
                //
                // ex:
                // http://a@b@c/ => user:a@b host:c
                // http://a@b?@c => user:a host:c path:/?@c

                // v0.12 TODO(isaacs): This is not quite how Chrome does things.
                // Review our test case against browsers more comprehensively.

                // find the first instance of any hostEndingChars
                var hostEnd = -1;
                for (var i = 0; i < hostEndingChars.length; i++) {
                    var hec = rest.indexOf(hostEndingChars[i]);
                    if (hec !== -1 && (hostEnd === -1 || hec < hostEnd))
                        hostEnd = hec;
                }

                // at this point, either we have an explicit point where the
                // auth portion cannot go past, or the last @ char is the decider.
                var auth, atSign;
                if (hostEnd === -1) {
                    // atSign can be anywhere.
                    atSign = rest.lastIndexOf('@');
                } else {
                    // atSign must be in auth portion.
                    // http://a@b/c@d => host:b auth:a path:/c@d
                    atSign = rest.lastIndexOf('@', hostEnd);
                }

                // Now we have a portion which is definitely the auth.
                // Pull that off.
                if (atSign !== -1) {
                    auth = rest.slice(0, atSign);
                    rest = rest.slice(atSign + 1);
                    this.auth = decodeURIComponent(auth);
                }

                // the host is the remaining to the left of the first non-host char
                hostEnd = -1;
                for (var i = 0; i < nonHostChars.length; i++) {
                    var hec = rest.indexOf(nonHostChars[i]);
                    if (hec !== -1 && (hostEnd === -1 || hec < hostEnd))
                        hostEnd = hec;
                }
                // if we still have not hit it, then the entire thing is a host.
                if (hostEnd === -1)
                    hostEnd = rest.length;

                this.host = rest.slice(0, hostEnd);
                rest = rest.slice(hostEnd);

                // pull out port.
                this.parseHost();

                // we've indicated that there is a hostname,
                // so even if it's empty, it has to be present.
                this.hostname = this.hostname || '';

                // if hostname begins with [ and ends with ]
                // assume that it's an IPv6 address.
                var ipv6Hostname = this.hostname[0] === '[' &&
                    this.hostname[this.hostname.length - 1] === ']';

                // validate a little.
                if (!ipv6Hostname) {
                    var hostparts = this.hostname.split(/\./);
                    for (var i = 0, l = hostparts.length; i < l; i++) {
                        var part = hostparts[i];
                        if (!part) continue;
                        if (!part.match(hostnamePartPattern)) {
                            var newpart = '';
                            for (var j = 0, k = part.length; j < k; j++) {
                                if (part.charCodeAt(j) > 127) {
                                    // we replace non-ASCII char with a temporary placeholder
                                    // we need this to make sure size of hostname is not
                                    // broken by replacing non-ASCII by nothing
                                    newpart += 'x';
                                } else {
                                    newpart += part[j];
                            }
                            }
                            // we test again with ASCII char only
                            if (!newpart.match(hostnamePartPattern)) {
                                var validParts = hostparts.slice(0, i);
                                var notHost = hostparts.slice(i + 1);
                                var bit = part.match(hostnamePartStart);
                                if (bit) {
                                    validParts.push(bit[1]);
                                    notHost.unshift(bit[2]);
                            }
                                if (notHost.length) {
                                    rest = '/' + notHost.join('.') + rest;
                                }
                                this.hostname = validParts.join('.');
                                break;
                        }
                    }
                }
                }

                if (this.hostname.length > hostnameMaxLen) {
                    this.hostname = '';
                } else {
                    // hostnames are always lower case.
                    this.hostname = this.hostname.toLowerCase();
                }

                if (!ipv6Hostname) {
                    // IDNA Support: Returns a puny coded representation of "domain".
                    // It only converts the part of the domain name that
                    // has non ASCII characters. I.e. it dosent matter if
                    // you call it with a domain that already is in ASCII.
                    var domainArray = this.hostname.split('.');
                    var newOut = [];
                    for (var i = 0; i < domainArray.length; ++i) {
                        var s = domainArray[i];
                        newOut.push(s.match(/[^A-Za-z0-9_-]/) ?
                        'xn--' + punycode.encode(s) : s);
                }
                    this.hostname = newOut.join('.');
                }

                var p = this.port ? ':' + this.port : '';
                var h = this.hostname || '';
                this.host = h + p;
                this.href += this.host;

                // strip [ and ] from the hostname
                // the host field still retains them, though
                if (ipv6Hostname) {
                    this.hostname = this.hostname.substr(1, this.hostname.length - 2);
                    if (rest[0] !== '/') {
                        rest = '/' + rest;
                }
            }
            }

            // now rest is set to the post-host stuff.
            // chop off any delim chars.
            if (!unsafeProtocol[lowerProto]) {

                // First, make 100% sure that any "autoEscape" chars get
                // escaped, even if encodeURIComponent doesn't think they
                // need to be.
                for (var i = 0, l = autoEscape.length; i < l; i++) {
                    var ae = autoEscape[i];
                    var esc = encodeURIComponent(ae);
                    if (esc === ae) {
                        esc = escape(ae);
                }
                    rest = rest.split(ae).join(esc);
            }
            }


            // chop off from the tail first.
            var hash = rest.indexOf('#');
            if (hash !== -1) {
                // got a fragment string.
                this.hash = rest.substr(hash);
                rest = rest.slice(0, hash);
            }
            var qm = rest.indexOf('?');
            if (qm !== -1) {
                this.search = rest.substr(qm);
                this.query = rest.substr(qm + 1);
                if (parseQueryString) {
                    this.query = querystring.parse(this.query);
            }
                rest = rest.slice(0, qm);
            } else if (parseQueryString) {
                // no query string, but parseQueryString still requested
                this.search = '';
                this.query = {};
            }
            if (rest) this.pathname = rest;
            if (slashedProtocol[lowerProto] &&
                this.hostname && !this.pathname) {
                this.pathname = '/';
            }

            //to support http.request
            if (this.pathname || this.search) {
                var p = this.pathname || '';
                var s = this.search || '';
                this.path = p + s;
            }

            // finally, reconstruct the href based on what has been validated.
            this.href = this.format();
            return this;
        };

// format a parsed object into a url string
        function urlFormat(obj) {
            // ensure it's an object, and not a string url.
            // If it's an obj, this is a no-op.
            // this way, you can call url_format() on strings
            // to clean up potentially wonky urls.
            if (isString(obj)) obj = urlParse(obj);
            if (!(obj instanceof Url)) return Url.prototype.format.call(obj);
            return obj.format();
        }

        Url.prototype.format = function () {
            var auth = this.auth || '';
            if (auth) {
                auth = encodeURIComponent(auth);
                auth = auth.replace(/%3A/i, ':');
                auth += '@';
        }

            var protocol = this.protocol || '',
                pathname = this.pathname || '',
                hash = this.hash || '',
                host = false,
                query = '';

            if (this.host) {
                host = auth + this.host;
            } else if (this.hostname) {
                host = auth + (this.hostname.indexOf(':') === -1 ?
                        this.hostname :
                    '[' + this.hostname + ']');
                if (this.port) {
                    host += ':' + this.port;
            }
            }

            if (this.query &&
                isObject(this.query) &&
                Object.keys(this.query).length) {
                query = querystring.stringify(this.query);
            }

            var search = this.search || (query && ('?' + query)) || '';

            if (protocol && protocol.substr(-1) !== ':') protocol += ':';

            // only the slashedProtocols get the //.  Not mailto:, xmpp:, etc.
            // unless they had them to begin with.
            if (this.slashes ||
                (!protocol || slashedProtocol[protocol]) && host !== false) {
                host = '//' + (host || '');
                if (pathname && pathname.charAt(0) !== '/') pathname = '/' + pathname;
            } else if (!host) {
                host = '';
            }

            if (hash && hash.charAt(0) !== '#') hash = '#' + hash;
            if (search && search.charAt(0) !== '?') search = '?' + search;

            pathname = pathname.replace(/[?#]/g, function (match) {
                return encodeURIComponent(match);
            });
            search = search.replace('#', '%23');

            return protocol + host + pathname + search + hash;
        };

        function urlResolve(source, relative) {
            return urlParse(source, false, true).resolve(relative);
        }

        Url.prototype.resolve = function (relative) {
            return this.resolveObject(urlParse(relative, false, true)).format();
        };

        function urlResolveObject(source, relative) {
            if (!source) return relative;
            return urlParse(source, false, true).resolveObject(relative);
        }

        Url.prototype.resolveObject = function (relative) {
            if (isString(relative)) {
                var rel = new Url();
                rel.parse(relative, false, true);
                relative = rel;
            }

            var result = new Url();
            Object.keys(this).forEach(function (k) {
                result[k] = this[k];
            }, this);

            // hash is always overridden, no matter what.
            // even href="" will remove it.
            result.hash = relative.hash;

            // if the relative url is empty, then there's nothing left to do here.
            if (relative.href === '') {
                result.href = result.format();
                return result;
            }

            // hrefs like //foo/bar always cut to the protocol.
            if (relative.slashes && !relative.protocol) {
                // take everything except the protocol from relative
                Object.keys(relative).forEach(function (k) {
                    if (k !== 'protocol')
                        result[k] = relative[k];
            });

                //urlParse appends trailing / to urls like http://www.example.com
                if (slashedProtocol[result.protocol] &&
                    result.hostname && !result.pathname) {
                    result.path = result.pathname = '/';
            }

                result.href = result.format();
                return result;
            }

            if (relative.protocol && relative.protocol !== result.protocol) {
                // if it's a known url protocol, then changing
                // the protocol does weird things
                // first, if it's not file:, then we MUST have a host,
                // and if there was a path
                // to begin with, then we MUST have a path.
                // if it is file:, then the host is dropped,
                // because that's known to be hostless.
                // anything else is assumed to be absolute.
                if (!slashedProtocol[relative.protocol]) {
                    Object.keys(relative).forEach(function (k) {
                        result[k] = relative[k];
                });
                result.href = result.format();
                return result;
            }

                result.protocol = relative.protocol;
                if (!relative.host && !hostlessProtocol[relative.protocol]) {
                    var relPath = (relative.pathname || '').split('/');
                    while (relPath.length && !(relative.host = relPath.shift()));
                    if (!relative.host) relative.host = '';
                    if (!relative.hostname) relative.hostname = '';
                    if (relPath[0] !== '') relPath.unshift('');
                    if (relPath.length < 2) relPath.unshift('');
                    result.pathname = relPath.join('/');
                } else {
                    result.pathname = relative.pathname;
            }
                result.search = relative.search;
                result.query = relative.query;
                result.host = relative.host || '';
                result.auth = relative.auth;
                result.hostname = relative.hostname || relative.host;
                result.port = relative.port;
                // to support http.request
                if (result.pathname || result.search) {
                    var p = result.pathname || '';
                    var s = result.search || '';
                    result.path = p + s;
            }
                result.slashes = result.slashes || relative.slashes;
                result.href = result.format();
                return result;
            }

            var isSourceAbs = (result.pathname && result.pathname.charAt(0) === '/'),
                isRelAbs = (
                    relative.host ||
                    relative.pathname && relative.pathname.charAt(0) === '/'
                ),
                mustEndAbs = (isRelAbs || isSourceAbs ||
                (result.host && relative.pathname)),
                removeAllDots = mustEndAbs,
                srcPath = result.pathname && result.pathname.split('/') || [],
                relPath = relative.pathname && relative.pathname.split('/') || [],
                psychotic = result.protocol && !slashedProtocol[result.protocol];

            // if the url is a non-slashed url, then relative
            // links like ../.. should be able
            // to crawl up to the hostname, as well.  This is strange.
            // result.protocol has already been set by now.
            // Later on, put the first path part into the host field.
            if (psychotic) {
                result.hostname = '';
                result.port = null;
                if (result.host) {
                    if (srcPath[0] === '') srcPath[0] = result.host;
                    else srcPath.unshift(result.host);
            }
                result.host = '';
                if (relative.protocol) {
                    relative.hostname = null;
                    relative.port = null;
                    if (relative.host) {
                        if (relPath[0] === '') relPath[0] = relative.host;
                        else relPath.unshift(relative.host);
                }
                    relative.host = null;
            }
                mustEndAbs = mustEndAbs && (relPath[0] === '' || srcPath[0] === '');
            }

            if (isRelAbs) {
                // it's absolute.
                result.host = (relative.host || relative.host === '') ?
                    relative.host : result.host;
                result.hostname = (relative.hostname || relative.hostname === '') ?
                    relative.hostname : result.hostname;
                result.search = relative.search;
                result.query = relative.query;
                srcPath = relPath;
                // fall through to the dot-handling below.
            } else if (relPath.length) {
                // it's relative
                // throw away the existing file, and take the new path instead.
                if (!srcPath) srcPath = [];
                srcPath.pop();
                srcPath = srcPath.concat(relPath);
                result.search = relative.search;
                result.query = relative.query;
            } else if (!isNullOrUndefined(relative.search)) {
                // just pull out the search.
                // like href='?foo'.
                // Put this after the other two cases because it simplifies the booleans
            if (psychotic) {
                result.hostname = result.host = srcPath.shift();
                //occationaly the auth can get stuck only in host
                //this especialy happens in cases like
                //url.resolveObject('mailto:local1@domain1', 'local2@domain2')
                var authInHost = result.host && result.host.indexOf('@') > 0 ?
                    result.host.split('@') : false;
                if (authInHost) {
                    result.auth = authInHost.shift();
                    result.host = result.hostname = authInHost.shift();
                }
            }
                result.search = relative.search;
                result.query = relative.query;
                //to support http.request
            if (!isNull(result.pathname) || !isNull(result.search)) {
                result.path = (result.pathname ? result.pathname : '') +
                    (result.search ? result.search : '');
            }
            result.href = result.format();
            return result;
            }

            if (!srcPath.length) {
                // no path at all.  easy.
                // we've already handled the other stuff above.
                result.pathname = null;
                //to support http.request
                if (result.search) {
                    result.path = '/' + result.search;
                } else {
                    result.path = null;
            }
                result.href = result.format();
                return result;
            }

            // if a url ENDs in . or .., then it must get a trailing slash.
            // however, if it ends in anything else non-slashy,
            // then it must NOT get a trailing slash.
            var last = srcPath.slice(-1)[0];
            var hasTrailingSlash = (
            (result.host || relative.host) && (last === '.' || last === '..') ||
            last === '');

            // strip single dots, resolve double dots to parent dir
            // if the path tries to go above the root, `up` ends up > 0
            var up = 0;
            for (var i = srcPath.length; i >= 0; i--) {
                last = srcPath[i];
                if (last == '.') {
                    srcPath.splice(i, 1);
                } else if (last === '..') {
                    srcPath.splice(i, 1);
                    up++;
                } else if (up) {
                    srcPath.splice(i, 1);
                    up--;
                }
        }

            // if the path is allowed to go above the root, restore leading ..s
            if (!mustEndAbs && !removeAllDots) {
                for (; up--; up) {
                    srcPath.unshift('..');
                }
        }

            if (mustEndAbs && srcPath[0] !== '' &&
                (!srcPath[0] || srcPath[0].charAt(0) !== '/')) {
                srcPath.unshift('');
        }

            if (hasTrailingSlash && (srcPath.join('/').substr(-1) !== '/')) {
                srcPath.push('');
        }

            var isAbsolute = srcPath[0] === '' ||
                (srcPath[0] && srcPath[0].charAt(0) === '/');

            // put the host back
            if (psychotic) {
                result.hostname = result.host = isAbsolute ? '' :
                    srcPath.length ? srcPath.shift() : '';
                //occationaly the auth can get stuck only in host
                //this especialy happens in cases like
                //url.resolveObject('mailto:local1@domain1', 'local2@domain2')
                var authInHost = result.host && result.host.indexOf('@') > 0 ?
                    result.host.split('@') : false;
                if (authInHost) {
                    result.auth = authInHost.shift();
                    result.host = result.hostname = authInHost.shift();
                }
        }

            mustEndAbs = mustEndAbs || (result.host && srcPath.length);

            if (mustEndAbs && !isAbsolute) {
                srcPath.unshift('');
            }

            if (!srcPath.length) {
                result.pathname = null;
                result.path = null;
            } else {
                result.pathname = srcPath.join('/');
            }

            //to support request.http
            if (!isNull(result.pathname) || !isNull(result.search)) {
                result.path = (result.pathname ? result.pathname : '') +
                    (result.search ? result.search : '');
            }
            result.auth = relative.auth || result.auth;
            result.slashes = result.slashes || relative.slashes;
            result.href = result.format();
            return result;
        };

        Url.prototype.parseHost = function () {
            var host = this.host;
            var port = portPattern.exec(host);
            if (port) {
                port = port[0];
                if (port !== ':') {
                    this.port = port.substr(1);
                }
                host = host.substr(0, host.length - port.length);
            }
            if (host) this.hostname = host;
        };

        function isString(arg) {
            return typeof arg === "string";
        }

        function isObject(arg) {
            return typeof arg === 'object' && arg !== null;
        }

        function isNull(arg) {
            return arg === null;
        }

        function isNullOrUndefined(arg) {
            return arg == null;
        }

    }, {"punycode": 93, "querystring": 96}],
    113: [function (require, module, exports) {
        module.exports = function isBuffer(arg) {
            return arg && typeof arg === 'object'
                && typeof arg.copy === 'function'
                && typeof arg.fill === 'function'
                && typeof arg.readUInt8 === 'function';
        }
    }, {}],
    114: [function (require, module, exports) {
        (function (process, global) {
// Copyright Joyent, Inc. and other Node contributors.
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to permit
// persons to whom the Software is furnished to do so, subject to the
// following conditions:
//
// The above copyright notice and this permission notice shall be included
// in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
// OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN
// NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
// DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
// OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE
// USE OR OTHER DEALINGS IN THE SOFTWARE.

            var formatRegExp = /%[sdj%]/g;
            exports.format = function (f) {
                if (!isString(f)) {
                    var objects = [];
                    for (var i = 0; i < arguments.length; i++) {
                        objects.push(inspect(arguments[i]));
                }
                    return objects.join(' ');
                }

                var i = 1;
                var args = arguments;
                var len = args.length;
                var str = String(f).replace(formatRegExp, function (x) {
                    if (x === '%%') return '%';
                    if (i >= len) return x;
                    switch (x) {
                        case '%s':
                            return String(args[i++]);
                        case '%d':
                            return Number(args[i++]);
                        case '%j':
                            try {
                                return JSON.stringify(args[i++]);
                            } catch (_) {
                                return '[Circular]';
                            }
                        default:
                            return x;
                }
                });
                for (var x = args[i]; i < len; x = args[++i]) {
                    if (isNull(x) || !isObject(x)) {
                        str += ' ' + x;
                    } else {
                        str += ' ' + inspect(x);
                    }
                }
                return str;
            };


// Mark that a method should not be used.
// Returns a modified function which warns once by default.
// If --no-deprecation is set, then it is a no-op.
            exports.deprecate = function (fn, msg) {
                // Allow for deprecating things in the process of starting up.
                if (isUndefined(global.process)) {
                    return function () {
                        return exports.deprecate(fn, msg).apply(this, arguments);
                    };
                }

                if (process.noDeprecation === true) {
                    return fn;
                }

                var warned = false;

                function deprecated() {
                    if (!warned) {
                        if (process.throwDeprecation) {
                            throw new Error(msg);
                        } else if (process.traceDeprecation) {
                            console.trace(msg);
                        } else {
                            console.error(msg);
                        }
                        warned = true;
                    }
                    return fn.apply(this, arguments);
                }

                return deprecated;
            };


            var debugs = {};
            var debugEnviron;
            exports.debuglog = function (set) {
                if (isUndefined(debugEnviron))
                    debugEnviron = process.env.NODE_DEBUG || '';
                set = set.toUpperCase();
                if (!debugs[set]) {
                    if (new RegExp('\\b' + set + '\\b', 'i').test(debugEnviron)) {
                        var pid = process.pid;
                        debugs[set] = function () {
                            var msg = exports.format.apply(exports, arguments);
                            console.error('%s %d: %s', set, pid, msg);
                    };
                    } else {
                        debugs[set] = function () {
                        };
                }
                }
                return debugs[set];
            };


            /**
             * Echos the value of a value. Trys to print the value out
             * in the best way possible given the different types.
             *
             * @param {Object} obj The object to print out.
             * @param {Object} opts Optional options object that alters the output.
             */
            /* legacy: obj, showHidden, depth, colors*/
            function inspect(obj, opts) {
                // default options
                var ctx = {
                    seen: [],
                    stylize: stylizeNoColor
            };
                // legacy...
                if (arguments.length >= 3) ctx.depth = arguments[2];
                if (arguments.length >= 4) ctx.colors = arguments[3];
                if (isBoolean(opts)) {
                // legacy...
                    ctx.showHidden = opts;
                } else if (opts) {
                    // got an "options" object
                    exports._extend(ctx, opts);
            }
                // set default options
                if (isUndefined(ctx.showHidden)) ctx.showHidden = false;
                if (isUndefined(ctx.depth)) ctx.depth = 2;
                if (isUndefined(ctx.colors)) ctx.colors = false;
                if (isUndefined(ctx.customInspect)) ctx.customInspect = true;
                if (ctx.colors) ctx.stylize = stylizeWithColor;
                return formatValue(ctx, obj, ctx.depth);
            }

            exports.inspect = inspect;


// http://en.wikipedia.org/wiki/ANSI_escape_code#graphics
            inspect.colors = {
                'bold': [1, 22],
                'italic': [3, 23],
                'underline': [4, 24],
                'inverse': [7, 27],
                'white': [37, 39],
                'grey': [90, 39],
                'black': [30, 39],
                'blue': [34, 39],
                'cyan': [36, 39],
                'green': [32, 39],
                'magenta': [35, 39],
                'red': [31, 39],
                'yellow': [33, 39]
            };

// Don't use 'blue' not visible on cmd.exe
            inspect.styles = {
                'special': 'cyan',
                'number': 'yellow',
                'boolean': 'yellow',
                'undefined': 'grey',
                'null': 'bold',
                'string': 'green',
                'date': 'magenta',
                // "name": intentionally not styling
                'regexp': 'red'
            };


            function stylizeWithColor(str, styleType) {
                var style = inspect.styles[styleType];

                if (style) {
                    return '\u001b[' + inspect.colors[style][0] + 'm' + str +
                        '\u001b[' + inspect.colors[style][1] + 'm';
                } else {
                return str;
            }
            }


            function stylizeNoColor(str, styleType) {
                return str;
            }


            function arrayToHash(array) {
                var hash = {};

                array.forEach(function (val, idx) {
                    hash[val] = true;
                });

                return hash;
            }


            function formatValue(ctx, value, recurseTimes) {
                // Provide a hook for user-specified inspect functions.
                // Check that value is an object with an inspect function on it
                if (ctx.customInspect &&
                    value &&
                    isFunction(value.inspect) &&
                        // Filter out the util module, it's inspect function is special
                    value.inspect !== exports.inspect &&
                        // Also filter out any prototype objects using the circular check.
                    !(value.constructor && value.constructor.prototype === value)) {
                    var ret = value.inspect(recurseTimes, ctx);
                    if (!isString(ret)) {
                        ret = formatValue(ctx, ret, recurseTimes);
                }
                    return ret;
                }

                // Primitive types cannot have properties
                var primitive = formatPrimitive(ctx, value);
                if (primitive) {
                    return primitive;
                }

                // Look up the keys of the object.
                var keys = Object.keys(value);
                var visibleKeys = arrayToHash(keys);

                if (ctx.showHidden) {
                    keys = Object.getOwnPropertyNames(value);
                }

                // IE doesn't make error fields non-enumerable
                // http://msdn.microsoft.com/en-us/library/ie/dww52sbt(v=vs.94).aspx
                if (isError(value)
                    && (keys.indexOf('message') >= 0 || keys.indexOf('description') >= 0)) {
                    return formatError(value);
                }

                // Some type of object without properties can be shortcutted.
                if (keys.length === 0) {
                if (isFunction(value)) {
                    var name = value.name ? ': ' + value.name : '';
                    return ctx.stylize('[Function' + name + ']', 'special');
                }
                if (isRegExp(value)) {
                    return ctx.stylize(RegExp.prototype.toString.call(value), 'regexp');
                }
                if (isDate(value)) {
                    return ctx.stylize(Date.prototype.toString.call(value), 'date');
                }
                if (isError(value)) {
                    return formatError(value);
                }
                }

                var base = '', array = false, braces = ['{', '}'];

                // Make Array say that they are Array
                if (isArray(value)) {
                    array = true;
                    braces = ['[', ']'];
                }

                // Make functions say that they are functions
                if (isFunction(value)) {
                    var n = value.name ? ': ' + value.name : '';
                    base = ' [Function' + n + ']';
                }

                // Make RegExps say that they are RegExps
                if (isRegExp(value)) {
                    base = ' ' + RegExp.prototype.toString.call(value);
                }

                // Make dates with properties first say the date
                if (isDate(value)) {
                    base = ' ' + Date.prototype.toUTCString.call(value);
                }

                // Make error with message first say the error
                if (isError(value)) {
                    base = ' ' + formatError(value);
                }

                if (keys.length === 0 && (!array || value.length == 0)) {
                    return braces[0] + base + braces[1];
                }

                if (recurseTimes < 0) {
                    if (isRegExp(value)) {
                        return ctx.stylize(RegExp.prototype.toString.call(value), 'regexp');
                } else {
                        return ctx.stylize('[Object]', 'special');
                }
            }

                ctx.seen.push(value);

                var output;
                if (array) {
                    output = formatArray(ctx, value, recurseTimes, visibleKeys, keys);
                } else {
                    output = keys.map(function (key) {
                        return formatProperty(ctx, value, recurseTimes, visibleKeys, key, array);
                });
            }

                ctx.seen.pop();

                return reduceToSingleString(output, base, braces);
            }


            function formatPrimitive(ctx, value) {
                if (isUndefined(value))
                    return ctx.stylize('undefined', 'undefined');
                if (isString(value)) {
                    var simple = '\'' + JSON.stringify(value).replace(/^"|"$/g, '')
                            .replace(/'/g, "\\'")
                            .replace(/\\"/g, '"') + '\'';
                    return ctx.stylize(simple, 'string');
                }
                if (isNumber(value))
                    return ctx.stylize('' + value, 'number');
                if (isBoolean(value))
                    return ctx.stylize('' + value, 'boolean');
                // For some reason typeof null is "object", so special case here.
                if (isNull(value))
                    return ctx.stylize('null', 'null');
            }


            function formatError(value) {
                return '[' + Error.prototype.toString.call(value) + ']';
            }


            function formatArray(ctx, value, recurseTimes, visibleKeys, keys) {
                var output = [];
                for (var i = 0, l = value.length; i < l; ++i) {
                    if (hasOwnProperty(value, String(i))) {
                        output.push(formatProperty(ctx, value, recurseTimes, visibleKeys,
                            String(i), true));
                    } else {
                        output.push('');
                    }
                }
                keys.forEach(function (key) {
                    if (!key.match(/^\d+$/)) {
                        output.push(formatProperty(ctx, value, recurseTimes, visibleKeys,
                            key, true));
                    }
                });
                return output;
            }


            function formatProperty(ctx, value, recurseTimes, visibleKeys, key, array) {
                var name, str, desc;
                desc = Object.getOwnPropertyDescriptor(value, key) || {value: value[key]};
                if (desc.get) {
                    if (desc.set) {
                        str = ctx.stylize('[Getter/Setter]', 'special');
                    } else {
                        str = ctx.stylize('[Getter]', 'special');
                    }
                } else {
                    if (desc.set) {
                        str = ctx.stylize('[Setter]', 'special');
                    }
                }
                if (!hasOwnProperty(visibleKeys, key)) {
                    name = '[' + key + ']';
                }
                if (!str) {
                    if (ctx.seen.indexOf(desc.value) < 0) {
                        if (isNull(recurseTimes)) {
                            str = formatValue(ctx, desc.value, null);
                    } else {
                            str = formatValue(ctx, desc.value, recurseTimes - 1);
                    }
                        if (str.indexOf('\n') > -1) {
                            if (array) {
                                str = str.split('\n').map(function (line) {
                                    return '  ' + line;
                                }).join('\n').substr(2);
                        } else {
                                str = '\n' + str.split('\n').map(function (line) {
                                        return '   ' + line;
                                    }).join('\n');
                        }
                    }
                    } else {
                        str = ctx.stylize('[Circular]', 'special');
                }
                }
                if (isUndefined(name)) {
                    if (array && key.match(/^\d+$/)) {
                        return str;
                }
                    name = JSON.stringify('' + key);
                    if (name.match(/^"([a-zA-Z_][a-zA-Z_0-9]*)"$/)) {
                        name = name.substr(1, name.length - 2);
                        name = ctx.stylize(name, 'name');
                    } else {
                        name = name.replace(/'/g, "\\'")
                            .replace(/\\"/g, '"')
                            .replace(/(^"|"$)/g, "'");
                        name = ctx.stylize(name, 'string');
                    }
            }

                return name + ': ' + str;
            }


            function reduceToSingleString(output, base, braces) {
                var numLinesEst = 0;
                var length = output.reduce(function (prev, cur) {
                    numLinesEst++;
                    if (cur.indexOf('\n') >= 0) numLinesEst++;
                    return prev + cur.replace(/\u001b\[\d\d?m/g, '').length + 1;
                }, 0);

                if (length > 60) {
                    return braces[0] +
                        (base === '' ? '' : base + '\n ') +
                        ' ' +
                        output.join(',\n  ') +
                        ' ' +
                        braces[1];
            }

                return braces[0] + base + ' ' + output.join(', ') + ' ' + braces[1];
            }


// NOTE: These type checking functions intentionally don't use `instanceof`
// because it is fragile and can be easily faked with `Object.create()`.
            function isArray(ar) {
                return Array.isArray(ar);
            }

            exports.isArray = isArray;

            function isBoolean(arg) {
                return typeof arg === 'boolean';
            }

            exports.isBoolean = isBoolean;

            function isNull(arg) {
                return arg === null;
            }

            exports.isNull = isNull;

            function isNullOrUndefined(arg) {
                return arg == null;
            }

            exports.isNullOrUndefined = isNullOrUndefined;

            function isNumber(arg) {
                return typeof arg === 'number';
            }

            exports.isNumber = isNumber;

            function isString(arg) {
                return typeof arg === 'string';
            }

            exports.isString = isString;

            function isSymbol(arg) {
                return typeof arg === 'symbol';
            }

            exports.isSymbol = isSymbol;

            function isUndefined(arg) {
                return arg === void 0;
            }

            exports.isUndefined = isUndefined;

            function isRegExp(re) {
                return isObject(re) && objectToString(re) === '[object RegExp]';
            }

            exports.isRegExp = isRegExp;

            function isObject(arg) {
                return typeof arg === 'object' && arg !== null;
            }

            exports.isObject = isObject;

            function isDate(d) {
                return isObject(d) && objectToString(d) === '[object Date]';
            }

            exports.isDate = isDate;

            function isError(e) {
                return isObject(e) &&
                    (objectToString(e) === '[object Error]' || e instanceof Error);
            }

            exports.isError = isError;

            function isFunction(arg) {
                return typeof arg === 'function';
            }

            exports.isFunction = isFunction;

            function isPrimitive(arg) {
                return arg === null ||
                    typeof arg === 'boolean' ||
                    typeof arg === 'number' ||
                    typeof arg === 'string' ||
                    typeof arg === 'symbol' ||  // ES6 symbol
                    typeof arg === 'undefined';
            }

            exports.isPrimitive = isPrimitive;

            exports.isBuffer = require('./support/isBuffer');

            function objectToString(o) {
                return Object.prototype.toString.call(o);
            }


            function pad(n) {
                return n < 10 ? '0' + n.toString(10) : n.toString(10);
            }


            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep',
                'Oct', 'Nov', 'Dec'];

// 26 Feb 16:19:34
            function timestamp() {
                var d = new Date();
                var time = [pad(d.getHours()),
                    pad(d.getMinutes()),
                    pad(d.getSeconds())].join(':');
                return [d.getDate(), months[d.getMonth()], time].join(' ');
            }


// log is just a thin wrapper to console.log that prepends a timestamp
            exports.log = function () {
                console.log('%s - %s', timestamp(), exports.format.apply(exports, arguments));
            };


            /**
             * Inherit the prototype methods from one constructor into another.
             *
             * The Function.prototype.inherits from lang.js rewritten as a standalone
             * function (not on Function.prototype). NOTE: If this file is to be loaded
             * during bootstrapping this function needs to be rewritten using some native
             * functions as prototype setup using normal JavaScript does not work as
             * expected during bootstrapping (see mirror.js in r114903).
             *
             * @param {function} ctor Constructor function which needs to inherit the
             *     prototype.
             * @param {function} superCtor Constructor function to inherit prototype from.
             */
            exports.inherits = require('inherits');

            exports._extend = function (origin, add) {
                // Don't do anything if add isn't an object
                if (!add || !isObject(add)) return origin;

                var keys = Object.keys(add);
                var i = keys.length;
                while (i--) {
                    origin[keys[i]] = add[keys[i]];
            }
                return origin;
            };

            function hasOwnProperty(obj, prop) {
                return Object.prototype.hasOwnProperty.call(obj, prop);
            }

        }).call(this, require('_process'), typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})
    }, {"./support/isBuffer": 113, "_process": 92, "inherits": 89}]
}, {}, [82]);

