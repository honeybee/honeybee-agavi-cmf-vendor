define([
    "Honeybee_Core/Widget",
], function(Widget) {
    var default_options = {
        prefix: 'Honeybee_Core/ui/ListFilter',
        excluded_inputs_selector: 'form[name="jumpToPage"] input',
        filter_selector: '.hb-list-filter',
        trigger_selector: '.hb-list-filter__trigger',
        quick_label_selector: '.hb-list-filter__quick-label',
        quick_clear_selector: '.hb-list-filter__clear',
        filter_toggle_selector: '.hb-list-filter__toggle',
        translations: {
            'quick_label': '{FILTER_NAME}: {VALUE}',
            'quick_label.title': 'Show/Hide filter',
            'quick_clear': 'x',
            'quick_clear.description': 'Clear filter'
        }
    };

    function ListFilter(dom_element, options)
    {
        var self = this;
        if (!dom_element) {
            return;
        }
        this.init(dom_element, _.merge({}, default_options, options));
        this.jsb_off_handler = [];

        this.translations = _.merge({}, this.options.translations);

        this.id = _.snakeCase(this.options.filter_id || this.$widget.data('hbFilterId'));
        if (!this.id) {
            this.logError(this.prefix + ' - Cannot initialize a filter with invalid identifier.');
            return;
        }
        // note: 'name' falling back to 'id' can cause problems with names containing dots (attribute paths, ES multifields, etc)
        this.name = this.options.filter_name || this.$widget.data('hbFilterName') || this.id;
        this.filter_control_selector = '[name="filter[' + this.name + ']"]';
        this.$control = this.$widget.find(this.filter_control_selector);    // @todo Don't rely on DOM state. Get control on-the-fly
        if (this.$control.length === 0) {
            this.logError(this.prefix + ' - Cannot initialize a filter as no control has been found.');
            return;
        }

        this.addListeners();
    };

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

        this.$widget.on('click', this.options.filter_toggle_selector, function(e) {
            // remove no-js toggling behavior
            // toggle() runs more code than just toggling filter visibility
            e.preventDefault();
            self.toggle();
        });
        this.$widget.on('click', this.options.quick_clear_selector, function(e) {
            self.clear();
        });

        return this;
    };

    ListFilter.prototype.addCommandListeners = function() {
        var self = this;
        var off_handler;

        // commands for this filter
        jsb.whenFired('LIST_FILTER_' + this.id.toUpperCase() + ':ACTION', function(values, event_name) {
            switch(values.action) {
                case 'TOGGLE_FILTER':
                    self.toggle(values.show);
                    break;
                default:
                    self.logWarn('ListFilter action not recognized.');
            }
        });

        // commands for all the filters
        jsb.whenFired('LIST_FILTER:ACTION', function(values, event_name) {
            switch(values.action) {
                case 'TOGGLE':
                    values.exclude = values.exclude || [ self.id ]; // if not defined exclude self.id, to prevent loops
                    if (values.exclude.indexOf(self.id) !== -1) {
                        return;
                    }
                    self.toggle(values.show);
                    break;
                case 'CLEAR':
                    if (values.exclude && values.exclude.indexOf(self.id) !== -1) {
                        return;
                    }
                    self.clear();
                    break;
                default:
                    self.logWarn('ListFilter action not recognized.');
            }
        });

        return this;
    };

    ListFilter.prototype.addControlListeners = function() {
        var self = this;

        this.$widget.on('change', this.filter_control_selector, function(e) {
            self.onControlChange.call(self, $(this).val());
        });

        return this;
    };

    ListFilter.prototype.toggle = function(show) {
        var self = this;
        var $trigger = this.$widget.find(this.options.trigger_selector);

        // can lazy load widgets just when filter is shown
        var $lazy_widgets = this.$widget.find('.jsb__');
        $lazy_widgets.each(function(idx, el) {
            $(this).removeClass('jsb__').addClass('jsb_');
            // so lazy..
            if (idx === $lazy_widgets.length) {
                jsb.applyBehavrious(this.$widget.get(0));
            }
        });

        // toggle filter
        $trigger.prop('checked', function(idx, val) {
            return typeof show !== 'undefined' ? show : !val;
        });
        // if shown, hide other filters
        if ($trigger.prop('checked') === true) {
            jsb.fireEvent(
                'LIST_FILTER:ACTION',
                {
                    action: 'TOGGLE',
                    show: false,
                    exclude: [ self.id ]
                }
            );
            this.$control.focus();
        }

        return this;
    };

    ListFilter.prototype.clear = function() {
        this.$widget.closest(this.options.filter_selector).remove();
        jsb.fireEvent('LIST_FILTER:CLEARED', { filter_id: this.id });

        delete this;
    };

    ListFilter.prototype.setQuickLabel = function(value, default_value) {
        // Label: quick_label, <filter>.quick_label
        // Label Title: quick_label.title, <filter>.quick_label.title
        // Translated Value: <filter>.quick_label.value_<value>
        var translated_value, quick_label, quick_label_title;
        value = value || this.$control.val() || default_value || '...';

        translated_value = this.translations['quick_label_value_' + value] || value;
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

    ListFilter.prototype.setControl = function(value, silent) {
        this.$control.val(value);
        if (!silent) {
            this.$control.change();
        }
        return this;
    };

    ListFilter.prototype.onControlChange = function(value) {
        this.setQuickLabel(value);
    };

    return ListFilter;
});
