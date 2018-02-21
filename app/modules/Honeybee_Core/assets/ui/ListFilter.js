define([
    "Honeybee_Core/Widget",
    "jsb",
    "jquery",
    "lodash"
], function(Widget, jsb, $, _) {

    "use strict";

    var default_options = {
        prefix: 'Honeybee_Core/ui/ListFilter',
        excluded_inputs_selector: 'form[name="jumpToPage"] input',
        // filter_selector: '.hb-list-filter',
        trigger_selector: '.hb-list-filter__trigger',
        quick_label_selector: '.hb-list-filter__quick-label',
        quick_clear_selector: '.hb-list-filter__clear',
        toggle_selector: '.hb-list-filter__toggle',
        translations: {
            'quick_label': '{FILTER_NAME}: {VALUE}',
            'quick_label_title': 'Show/Hide filter',
            'quick_clear': 'x',
            'quick_clear_title': 'Clear filter'
        }
    };

    function ListFilter(dom_element, options)
    {
        var self = this;
        if (!dom_element) {
            return;
        }
        this.init(dom_element, _.merge({}, default_options, options));

        this.translations = _.merge({}, this.$widget.data('hbTranslations'), this.options.translations);

        this.id = this.options.filter_id || this.$widget.data('hbFilterId');
        this.name = this.options.filter_name || this.$widget.data('hbFilterName');
        if (!this.name || !this.id) {
            this.logError(this.prefix + ' - Cannot initialize a filter with invalid name or id.');
            return;
        }
        this.control_name = this.options.control_name || 'filter[' + this.name + ']';
        this.control_selector = this.options.control_selector || '[name="' + this.control_name + '"]';

        this.addListeners();
    }

    ListFilter.prototype = new Widget();
    ListFilter.prototype.constructor = ListFilter;

    ListFilter.prototype.addListeners = function() {
        var self = this;

        this.addQuickControlListeners();
        this.addCommandListeners();
        this.addControlListeners();

        return this;
    };

    ListFilter.prototype.addQuickControlListeners = function() {
        var self = this;

        this.$widget.on('click', this.options.toggle_selector, function(e) {
            // remove no-js toggling behavior
            // toggle() runs more code than just toggling filter visibility
            e.preventDefault();
            self.toggle.call(self);
        });
        this.$widget.on('click', this.options.quick_clear_selector, function(e) {
            self.clear.call(self);
        });

        return this;
    };

    ListFilter.prototype.addCommandListeners = function() {
        var self = this;

        // commands for this filter
        jsb.whenFired('LIST_FILTER:' + this.id.toUpperCase() + ':ACTION', function(values, event_name) {
            switch(values.action) {
                case 'TOGGLE_FILTER':
                    self.toggle.call(self, values.show, values.silent);
                    break;
                case 'CLEAR_FILTER':
                    self.clear.call(self);
                    break;
                default:
                    self.logWarn('ListFilter action not recognized.');
            }
        }).dontLeak(this);

        return this;
    };

    ListFilter.prototype.addControlListeners = function() {
        var self = this;

        this.$widget.on('change', this.control_selector, function(e) {
            self.onControlChange.call(self, $(e.target).val());
        });

        return this;
    };

    ListFilter.prototype.toggle = function(show, silent) {
        var self = this;
        var $trigger = this.$widget.find(this.options.trigger_selector);

        // can lazy load widgets just when filter is shown
        var $lazy_widgets = this.$widget.find('.jsb__');
        $lazy_widgets.each(function(idx, el) {
            $(this).removeClass('jsb__').addClass('jsb_');
            // so lazy..
            if (idx === $lazy_widgets.length) {
                jsb.applyBehavrious(self.$widget.get(0));
            }
        });

        // toggle filter
        $trigger.prop('checked', function(idx, val) {
            return typeof show !== 'undefined' ? show : !val;
        });
        // if shown, hide other filters
        if (!silent && $trigger.prop('checked') === true) {
            jsb.fireEvent(
                'LIST_FILTER:ACTION',
                {
                    action: 'TOGGLE_ALL',
                    show: false,
                    silent: true,
                    exclude: [ self.id ]
                }
            );
        }
        if (show) {
            this.getControl().focus();
        }

        return this;
    };

    ListFilter.prototype.clear = function() {
        this.$widget.html('').remove();
        jsb.fireEvent('Jsb::REMOVED_INSTANCE', this);
        jsb.fireEvent('LIST_FILTER:CLEARED', { filter: this.id });
    };

    ListFilter.prototype.setQuickLabel = function(value, default_value) {
        // Label: quick_label, <filter>.quick_label
        // Label Title: quick_label_title, <filter>.quick_label_title
        // Translated Value: <filter>.value_<value>
        var translated_value, quick_label, quick_label_title;
        value = value || this.getControl().val() || default_value || '...';

        translated_value = this.translations['value_' + value] || value;
        quick_label = this.translations['quick_label'];
        quick_label_title = this.translations['quick_label_title'];

        if (quick_label) {
            value = quick_label.replace('{VALUE}', translated_value);
        }
        value = value.replace('{FILTER_NAME}', this.name);
        this.$widget.find(this.options.quick_label_selector)
            .attr('title', quick_label_title)
            .html(value);

        return this;
    };

    ListFilter.prototype.getControl = function() {
        return this.$widget.find(this.control_selector);
    };

    ListFilter.prototype.setControl = function(value, silent) {
        this.getControl().val(value);
        if (!silent) {
            this.getControl().change();
        }
        return this;
    };

    ListFilter.prototype.onControlChange = function(value) {
        this.setQuickLabel(value);
    };

    return ListFilter;
});
