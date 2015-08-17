define([
    "Honeybee_Core/Widget"
], function(Widget) {

    "use strict";

    var default_options = {
        prefix: 'Core/NotificationWidget',
        channel: null,
        dsn: null
    };

    var NotificationWidget = function(dom_element, options) {
        this.init(dom_element, default_options);
        this.addOptions(options);
        this.logDebug(this.options);

        this.connect();
    };

    NotificationWidget.prototype = new Widget();
    NotificationWidget.prototype.constructor = NotificationWidget;

    NotificationWidget.prototype.connect = function() {
        var that = this;

        this.connection = new WebSocket(this.options.dsn);

        this.connection.onopen = function(event) {
            that.logTrace("Connection established!", event);
        };

        this.connection.onmessage = function(event) {
            that.onMessageReceived(JSON.parse(event.data));
        };
    };

    NotificationWidget.prototype.onMessageReceived = function(message) {
        this.logDebug('Received message', message);
    };

    return NotificationWidget;

}, function (err) {
    // err has err.requireType (timeout, nodefine, scripterror)
    // and err.requireModules (an array of module Ids/paths)
});
