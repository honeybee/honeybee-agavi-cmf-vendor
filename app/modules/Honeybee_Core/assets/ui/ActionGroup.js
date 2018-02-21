define([
    "Honeybee_Core/Widget",
    "jquery",
    "jsb"
], function(Widget, $, jsb) {

    "use strict";

    var available_modes = {
        "dropdown": "actsAsDropdown",
        "toggle": "actsAsToggle",
        "default": "actsAsDefault"
    };

    var default_options = {
        "mode": "default",
        "prefix": "ActionGroup"
    };

    var ActionGroup = function(dom_element, options) {
        this.init(dom_element, default_options);
        this.addOptions(options);

        this.$toggle = this.$widget.find('.ag__toggle');
        this.$trigger = this.$widget.find('.ag__trigger');

        if (this.$trigger.length === 0 || this.$toggle.length === 0) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }

        this[available_modes[this.options.mode]]();

        this.logDebug("Behaviour applied. Options:", this.options);

        this.initListeners();

        jsb.fireEvent('ActionGroup::CREATED', {
            "id": 6,
            "date": +new Date(),
            "name": this.getPrefix(),
            "options": this.options
        });

    };

    // ActionGroup extends Widget
    ActionGroup.prototype = new Widget();
    ActionGroup.prototype.constructor = ActionGroup;

    ActionGroup.prototype.actsAsDefault = function() {
    };

    ActionGroup.prototype.actsAsDropdown = function() {
        var that = this;
        that.$widget.find('.ag__more .action').on('click', function(ev) {
            ev.preventDefault();
            var $action = $(this);
            jsb.fireEvent(that.getPrefix() + "::MORE_ACTION_CLICKED", {
                "widget": that,
                "action": $action
            });
        });
    };

    // uncheck trigger and add 'active' class only on clicked list item
    // always set text on dropdown label to the clicked action's text
    ActionGroup.prototype.actsAsToggle = function() {
        var that = this;
        that.$widget.find('.ag__more .action').on('click', function(ev) {
            ev.preventDefault();
            that.$trigger.removeAttr('checked');
            that.$trigger.prop('checked', false);
            var $action = $(this);
            $action.parent('li').addClass('active').siblings().removeClass('active');
            var new_text = $action.text();
            var old_text = that.$toggle.text();
            that.$toggle.text(new_text);
            jsb.fireEvent(that.getPrefix() + "::TOGGLE_TEXT_CHANGED", {
                "from": old_text,
                "to": new_text,
                "action": $action
            });
        });
    };

    ActionGroup.prototype.initListeners = function() {
        var that = this;
        var on_what = new RegExp('^' + this.escapeRegExp(this.getPrefix()) + '.*$');
        this.off_handler = jsb.on(
            on_what,
            function(values, event_name) {
                that.logDebug(event_name, values);
            }
        );
    };

    /* call parent method:
    ActionGroup.prototype.fooBar = function() {
        Widget.prototype.fooBar.apply(this);
    }*/

    return ActionGroup;

}, function (err) {
    // err has err.requireType (timeout, nodefine, scripterror)
    // and err.requireModules (an array of module Ids/paths)
});

