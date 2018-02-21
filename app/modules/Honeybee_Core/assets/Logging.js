if (typeof(console) === "undefined") {
    var empty_function = function() {};

    console = {};
    console.log = empty_function;
    console.error = empty_function;
    console.info = empty_function;
    console.debug = empty_function;
    console.trace = empty_function;
} else {
    if (typeof console.log === 'object') {
        // IE fix
        console.trace = function() {
            console.log(arguments);
        };
    } else {
        console.trace = function() {
            console.log.apply(this, arguments);
        };
    }
}

define([
    "Honeybee_Core/Config"
], function(config) {

    "use strict";

    var __log = function(prefix, log_type, args) {
        var i = 0;

        var logging_exclude_length = Logging.exclude.length;
        for (i = 0; i < logging_exclude_length; i++) {
            if (prefix.match(Logging.exclude[i])) {
                return;
            }
        }

        var logging_include_length = Logging.include.length;
        for (i = 0; i < logging_include_length; i++) {
            if (prefix.match(Logging.include[i])) {
                var args_two = Array.prototype.slice.apply(args);
                args_two.unshift("[" + prefix + "]");
                console[log_type].apply(console, args_two);
                return;
            }
        }
    };

    var Logging = function() {
    };

    Logging.prototype.log = function() {
        if (Logging.level >= Logging.LEVEL_TRACE) {
            __log(this.log_prefix, 'log', arguments);
        }
    };

    Logging.prototype.logTrace = function() {
        if (Logging.tracing_enabled || (Logging.level >= Logging.LEVEL_TRACE)) {
            __log(this.log_prefix, 'trace', arguments);
        }
    };

    Logging.prototype.logDebug = function() {
        if (Logging.level >= Logging.LEVEL_DEBUG) {
            __log(this.log_prefix, 'log', arguments);
        }
    };

    Logging.prototype.logInfo = function() {
        if (Logging.level >= Logging.LEVEL_INFO) {
            __log(this.log_prefix, 'info', arguments);
        }
    };

    Logging.prototype.logWarn = function() {
        if (Logging.level >= Logging.LEVEL_WARN) {
            __log(this.log_prefix, 'info', arguments);
        }
    };

    Logging.prototype.logError = function() {
        if (Logging.level >= Logging.LEVEL_ERROR) {
            __log(this.log_prefix, 'error', arguments);
        }
    };

    Logging.prototype.addTracing = function(included_names, excluded_names) {
        var that = this;
        var excluded_logging_names = ['log', 'logTrace', 'logError', 'logDebug', 'logWarn', 'logInfo', 'addTracing'];
        excluded_names = excluded_names || [];

        if (!included_names) {
            included_names = [];
            for (var key in this) {
                if (typeof this[key] === 'function' && excluded_names.indexOf(key) === -1) {
                    if (excluded_logging_names.indexOf(key) === -1) {
                        included_names.push(key);
                    }
                }
            }
        }

        included_names.forEach(function(function_name) {
            var original_function = that[function_name];
            that[function_name] = function() {
                that.logTrace(function_name, arguments);
                return original_function.apply(that, arguments);
            };
        });
    };

    Logging.applyLogging = function(instance, class_name, add_tracing) {
        var logging_functions = ['log', 'logTrace', 'logError', 'logDebug', 'logWarn', 'logInfo', 'addTracing'];
        instance.log_prefix = class_name;

        for (var i = 0; i < logging_functions.length; i++) {
            var function_name = logging_functions[i];
            instance[function_name] = instance[function_name] || this.prototype[function_name];
        }

        if ((typeof add_tracing !== "undefined" && add_tracing) || config.get('logging.enable_tracing', false)) {
            Logging.tracing_enabled = true;
            instance.addTracing();
        }
    };

    Logging.LEVEL_ALL = 127;
    Logging.LEVEL_TRACE = 6;
    Logging.LEVEL_LOG = 5;
    Logging.LEVEL_DEBUG = 5;
    Logging.LEVEL_INFO = 4;
    Logging.LEVEL_WARN = 3;
    Logging.LEVEL_ERROR = 2;
    Logging.LEVEL_FATAL = 1;
    Logging.LEVEL_OFF = 0;

    Logging.tracing_enabled = false;

    try {
        Logging.level = config.get('logging.log_level', Logging.LEVEL_ALL);
    } catch(err) {
        Logging.level = Logging.LEVEL_ALL;
    }

    Logging.include = [
        /.*/
    ];

    Logging.exclude = [
        // /^SomePattern/
    ];

    return Logging;

}, function (err) {
    // err has err.requireType (timeout, nodefine, scripterror)
    // and err.requireModules (an array of module ids/paths)
});

