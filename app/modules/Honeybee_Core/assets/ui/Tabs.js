define([
    "Honeybee_Core/Widget"
], function(Widget) {

    "use strict";

    var default_options = {
        prefix: "Honeybee_Core/ui/Tabs",
    };

    function Tabs(dom_element, options) {
        this.init(dom_element, default_options);
        this.addOptions(options);

        if (this.$widget.length === 0) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }

        var self = this;

        /**
         * highlighting of currently open tabs
         */
        self.$widget.on("click focus blur", ".hb-tabs__trigger", function(ev) {
            /* setTimout 0 to defer the event.
             * otherwise the :focus selector will not find the newly focused
             * element if it gets focus via tab-key because the browser has not finished
             * applying the new focus state.
             */
            setTimeout(function() {
                self.selectTab();
                self.focusTab();
            }, 0);
        });

        jsb.whenFired('TABS:OPEN_TAB_THAT_CONTAINS', function(values, event_name) {
            if (values.element_id) {
                // $(id) would need escaping for ids with dots…
                self.openTabThatContains(document.getElementById(values.element_id));
            }
        });

        jsb.whenFired('TABS:UPDATE_ERROR_BUBBLES', function(values, event_name) {
            self.updateErrorBubbles();
        });
        self.updateErrorBubbles();

        if (window.location.hash) {
            self.openTab(window.location.hash.substr(1));
        } else {
            self.selectTab();
        }

        $(window).on('hashchange', function(ev) {
            self.openTab(window.location.hash.substr(1));
        });
    }

    Tabs.prototype = new Widget();
    Tabs.prototype.constructor = Tabs;

    Tabs.prototype.openTabThatContains = function(element) {
        var $panels = this.$widget.find('> .hb-tabs__content > .hb-tabs__panel');
        var $panel = $panels.has(element);
        if ($panel.length > 0) {
            $panel.prev('.hb-tabs__trigger').prop("checked", true);
            this.selectTab();
        }
    };

    Tabs.prototype.openTab = function(tab_id) {
        var trigger = document.getElementById(tab_id);
        if (trigger && $(trigger).hasClass('hb-tabs__trigger')) {
            $(trigger).prop("checked", true);
            this.selectTab();
        }
    };

    Tabs.prototype.selectTab = function() {
        var active_id = this.$widget.find('> .hb-tabs__content > .hb-tabs__trigger:checked').attr('id');
        var $selected_label = this.$widget.find("> .hb-tabs__header label[for='"+active_id+"']");

        this.$widget.find('> .hb-tabs__header label').removeClass('selected');
        $selected_label.addClass('selected');
        if (active_id) {
            window.location.hash = active_id;
        }
    };

    Tabs.prototype.focusTab = function() {
        var focus_id = this.$widget.find('> hb-tabs__content > .hb-tabs__trigger:focus').attr('id');
        var $focus_label = this.$widget.find("> .hb-tabs__header label[for='"+focus_id+"']");
        this.$widget.find('> .hb-tabs__header label').removeClass('focus');
        $focus_label.addClass('focus');
    };

    Tabs.prototype.updateErrorBubbles = function() {
        var $toggles = this.$widget.find('> .hb-tabs__header > .hb-tabs__toggles > .hb-tabs__toggle');
        $toggles.each(function(idx, elem) {
            var $toggle = $(elem);
            var trigger_id = $toggle.find('label').attr('for');
            var trigger = document.getElementById(trigger_id); // $(id) would mean we need to escape the id for jquery
            if (trigger) {
                var invalid_inputs = [];
                $(trigger).next('.hb-tabs__panel').find('.invalid,:invalid').filter(":input").each(
                    function(idx, invalid_input) {
                        if ($(invalid_input).parents('.hb-entity-templates').length === 0) {
                            invalid_inputs.push(invalid_input);
                        }
                    }
                );
                if (invalid_inputs.length > 0) {
                    // put number of invalid inputs into a error-bubble span into the tab toggle
                    var $bubble = $toggle.find('.error-bubble');
                    if ($bubble.length > 0) {
                        // update bubble error count
                        $bubble.text(invalid_inputs.length);
                    } else {
                        // create bubble with error count
                        var $label = $toggle.children('label');
                        var span = document.createElement('span');
                        span.appendChild(document.createTextNode(invalid_inputs.length));
                        span.setAttribute('class', 'error-bubble');
                        $toggle.children('label').append(span);
                    }
                } else {
                    // no erroneous inputs -> remove error bubble from tab toggle
                    $toggle.find('.error-bubble').remove();
                }
            }
        });
    };

    return Tabs;
});
