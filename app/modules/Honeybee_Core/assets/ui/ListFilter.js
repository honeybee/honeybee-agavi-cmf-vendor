define([
    "Honeybee_Core/Widget",
], function(Widget) {
    var default_options = {
        prefix: 'Honeybee_Core/ui/ListFilter',
        listen_on_local_control: true,
        excluded_inputs_selector: 'form[name="jumpToPage"] input',
        target_form_selector: '#search_form',
        target_form_filter_list_selector: '.search__additional-inputs',
        filter_control_selector: '[name="filter[{FILTER_NAME}]"]', // forms control in the whole document
        trigger_selector: '.hb-list-filter__trigger',
        quick_label_selector: '.hb-list-filter__quick-label',
        quick_clear_selector: '.hb-list-filter__clear',
        filter_toggle_selector: '.hb-list-filter__toggle'
    };

    var translations = {
        'quick_label': '{FILTER_NAME}: {VALUE}',
        'quick_label.title': 'Show/Hide filter',
        'quick_clear': 'x',
        'quick_clear.description': 'Clear filter'
    };

    function ListFilter(dom_element, options)
    {
        var self = this;

        this.init(dom_element, default_options);
        this.addOptions(options);

        this.translations = $.extend({}, translations, this.options.translations);

        this.id = _.snakeCase(this.options.filter_id || this.$widget.data('hbFilterId'));
        if (!this.id) {
            console.error(this.prefix + ' - Cannot initialize a filter with invalid identifier.');
            return;
        }
        // note: 'name' falling back to 'id' can cause problems with names containing dots (attribute paths, ES multifields, etc)
        this.name = this.options.filter_name || this.$widget.data('hbFilterName') || this.id;
        // target form contains the active/inactive filter control
        this.$target_form = $(this.options.target_form_selector);
        if (this.$target_form.length === 0) {
            this.$target_form = this.$widget.find('form');
            if (this.$target_form.length === 0) {
                this.logError('Cannot find a form for the list-filters.');
                return;
            }
        }
        this.filter_control_selector = this.options.filter_control_selector.replace('{FILTER_NAME}', this.name);
        this.$target_control = this.$target_form.find(this.filter_control_selector);
        // target input must always exist (disabled: filter inactive; enabled: filter active)
        if (this.$target_control.length === 0) {
            this.$target_control = $('<input type="hidden" />')
                .attr('name', 'filter[' + this.name + ']')
                .prop('disabled', true)
                .appendTo(this.$target_form.find(this.options.target_form_filter_list_selector));
        }
        // can have a local control to trigger changes to target filter control
        this.$default_control = this.$widget.find(this.filter_control_selector);

        this.addListeners();
    };

    ListFilter.prototype = new Widget();
    ListFilter.prototype.constructor = ListFilter;

    ListFilter.prototype.addListeners = function() {
        var self = this;

        // commands for this filter
        jsb.whenFired('LIST_FILTER_' + this.id.toUpperCase() + ':ACTION', function(values, event_name) {
            switch(values.action) {
                case 'ADD_LIST_FILTER':
                    self.activate();
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

        // quick control
        this.$widget.on('click', this.options.filter_toggle_selector, function(e) {
            // remove no-js toggling behavior
            // toggle() runs more code than just toggling filter visibility
            e.preventDefault();
            self.toggle();
        });
        this.$widget.on('click', this.options.quick_clear_selector, function(e) {
            self.clear();
        });

        // target form
        this.$target_control.on('change', function(e) {
            self.onTargetChange($(this).val());
        });

        // if default control is present update target control, when changed
        if (this.$target_control.length > 0 && this.$default_control.length > 0 && !this.$default_control.is(this.$target_control)) {
            this.$default_control.on('change', function(e) {
                var value = $(this).val();
                self.setTargetControl(value, 'silent');
                self.onTargetChange(value);
            });
        }

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
            // this.$widget.find(selectors.trigger).not($trigger)
            //     .filter(function() { return $(this).is($trigger) === false; })
            //     .prop('checked', false);
            this.$default_control.focus();
        }

        return this;
    };

    ListFilter.prototype.activate = function() {
        if (!this.isActive()) {
            this.$target_control.prop('disabled', false);
        }
        this.$widget.addClass('hb-list-filter--active');

        return this;
    };

    ListFilter.prototype.clear = function() {
        this.$target_control.prop('disabled', true);
        this.$widget.removeClass('hb-list-filter--active');
        jsb.fireEvent('LIST_FILTER:CLEARED', { filter_id: self.id });

        return this;
    };

    ListFilter.prototype.setQuickLabel = function(value) {
        // Label: quick_label, <filter>.quick_label
        // Label Title: quick_label.title, <filter>.quick_label.title
        // Translated Value: <filter>.quick_label.value_<value>
        var translated_value, quick_label, quick_label_title;
        value = value || this.$target_control.val() || '...';

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
    }

    ListFilter.prototype.setTargetControl = function(value, silent) {
        this.$target_control.val(value);
        if (!silent) {
            this.$target_control.change();
        }
        return this;
    };

    ListFilter.prototype.onTargetChange = function(value) {
        var $update_inputs;
        // propagate change to same-name controls on the document
        $update_inputs = $(this.filter_control_selector)
            .not(this.$target_control)
            .not(this.options.excluded_inputs_selector);
        // trigger change (and prevent loop, if default control is present)
        $update_inputs.not(this.$default_control).change();

        this.setQuickLabel(value);

    };

    ListFilter.prototype.isActive = function() {
        return this.$target_control.prop('disabled') !== true;
    };

    return ListFilter;
});
