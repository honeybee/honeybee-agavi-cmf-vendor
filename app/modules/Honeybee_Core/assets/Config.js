define([], function() {

    "use strict";

    var Config = function() {
        this.values = {
            "logging.enable_tracing": false,
            "logging.log_level": 127, // ALL = 127, NONE = 0, ERROR = 2, DEBUG = 5
            "widgets.handle_loading": true,
            "widgets.loading_classname": "hb-js-widget--busy"
        };

        this.load("html");
    };

    Config.prototype.setValues = function(values) {
        this.values = values;
    };

    Config.prototype.get = function(key, default_value) {
        var value = this.values[key];

        if (typeof value === "undefined" && !default_value) {
            throw new Error('[Config] Key "' + key + '" is undefined (or not whitelisted?).');
        }

        if (value || value == 0) {
            return value;
        } else {
            return default_value;
        }
    };

    Config.prototype.load = function(config_selector) {
        var config_element = document.querySelector(config_selector);

        if (config_element && config_element.getAttribute('data-config-js')) {
            var config = JSON.parse(config_element.dataset.configJs);
        }

        for (var setting in config) {
            this.values[setting] = config[setting];
        }
    };

    return new Config();

}, function (err) {
    // err has err.requireType (timeout, nodefine, scripterror)
    // and err.requireModules (an array of module ids/paths)
});
