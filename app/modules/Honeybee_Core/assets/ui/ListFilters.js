define([
    "Honeybee_Core/Widget"
], function(Widget) {

    "use strict";

    var default_options = {
        prefix: 'Honeybee_Core/ui/ListFilters',
    };

    var translations = {
        'quick_label': '{FILTER_ID}: {VALUE}',
        'quick_label.title': 'Show/Hide filter',
        'quick_clear': 'x',
        'quick_clear.description': 'Clear filter'
    };

    // selectors
    var selectors = {
        form: '#search_form',
        form_controls: '.search__additional-inputs',
        input_with_id: 'input[name="filter[{FILTER_ID}]"]',
        filter_list: '.hb-list-filters__list',
        filter_with_id: '[data-filter-id="{FILTER_ID}"]',
        quick_control: '.hb-list-filters__quick',
        quick_control_list: '.hb-list-filters__quick-controls',
        quick_control_label: '.hb-list-filters__quick-label',
        quick_control_with_id: '[data-filter-id="{FILTER_ID}"]',
        template: 'script[type="text/template"]',
        trigger: '.hb-list-filters__trigger',
        filter_toggle_class: 'hb-list-filter__toggle',
        quick_clear_class: 'hb-list-filters__clear'
    };

    function ListFilters(dom_element, options)
    {
        var self = this;

        this.templates = {};

        this.init(dom_element, default_options);
        this.addOptions(options);

        translations = $.extend(this.options.translations, translations);

        this.$form = this.$widget.find(selectors.form);
        if (this.$form.length === 0) {
            this.$form = $(selectors.form);
        }
        if (this.$form.length === 0) {
            this.logError('Cannot find a form for the list-filters.');
            return;
        }
        this.$form_controls = this.$form.find(selectors.form_controls);
        this.$quick_controls = this.$widget.find(selectors.quick_control_list);
        this.$filters = this.$widget.find(selectors.filter_list);

        this.$widget.find(selectors.template).each(function(index, el) {
            self.templates[$(this).data('templateName')] = $(this).remove().html();
        });

        this.addListeners();

        // remove no-js behavior
        this.$quick_controls.on('click', 'label', function(e) {
            // toggleFilter() runs more code than just toggling filter visibility
            e.preventDefault();
        });
    };

    ListFilters.prototype = new Widget();
    ListFilters.prototype.constructor = ListFilters;

    ListFilters.prototype.addListeners = function() {
        var self = this;

        jsb.whenFired('LIST_FILTERS:ACTION', function(values, event_name) {
            switch(values.action) {
                case 'ADD_LIST_FILTER_WITH_ID':
                    if (self.isFilterLoaded(values.filter_id)) {
                        self.toggleFilter(values.filter_id);
                    } else {
                        self.addFilter(values.filter_id);
                    }
                    break;
                case 'TOGGLE_LIST_FILTER_WITH_ID':
                    self.toggleFilter(values.filter_id);
                    break;
                case 'SET_FILTER_INPUT_TO_VALUE':
                    self.setInput(values.filter_id, values.input_value);
                    break;
                case 'SET_FILTER_QUICK_CONTROL_LABEL_TO_VALUE':
                    self.setQuickLabel(values.filter_id, values.label_value);
                    break;
                case 'CLEAR_LIST_FILTER_WITH_ID':
                    self.clearFilter(values.filter_id);
                    break;
                case 'CLEAR_ALL_FILTERS':
                    self.$form_controls.find('input[name^="filter["]').each(function(idx, el) {
                        var matched_filter_id = $(this).attr('name').match(/^filter\[(\w+)\]$/);
                        if (matched_filter_id) {
                            self.clearFilter(matched_filter_id[1]);
                        }
                    });
                    break;
                default:
                    self.logWarn('ListFilters action not recognized.');
            };
        });

        // form inputs
        this.$form.on('change', 'input[name^="filter["]', function(e) {
            var $target = $(e.target);
            var matched_filter_id = $target.attr('name').match(/^filter\[(\w+)\]$/);
            var filter_id;

            if (matched_filter_id) {
                filter_id = matched_filter_id[1];
                self.setQuickLabel(filter_id, $(this).val());
            }
        });

        // quick controls
        this.$quick_controls.on('click', function(e) {
            var $target = $(e.target);
            var filter_id = $target.closest(selectors.quick_control).data('filterId');

            if ($target.hasClass(selectors.filter_toggle_class)) {
                self.toggleFilter(filter_id)
            }
            if ($target.hasClass(selectors.quick_clear_class)) {
                self.clearFilter(filter_id);
            }
        });

        // update list with values from filters' bounded-input
        // Tip: it listens on data-attribute (.attr), not data-property (.data)
        this.$filters.on('change', '[data-bound-filter-id]', function(e) {
            var $target = $(e.target);
            jsb.fireEvent(
                'LIST_FILTERS:ACTION',
                {
                    action: 'SET_FILTER_INPUT_TO_VALUE',
                    filter_id: $target.data('boundFilterId'),
                    input_value: $target.val()
                }
            );
        });

        return this;
    };

    ListFilters.prototype.addFilter = function(id) {
        // add markup
        if (!this.isFilterLoaded(id)) {
            // quick control
            // @todo Support custom content via template. Atm eventual modifications are done, if needed, by custom filers.
            var quick_control_content = this.templates['quick-control_default_content'];
            this.cloneTemplate('quick-control', id, quick_control_content)
                .appendTo(this.$quick_controls);
            this.setQuickLabel(id);

            // filter
            var filter_content = this.templates[id + '_filter'];
            var $filter = this.cloneTemplate('filter-list-item', id, filter_content)
                .appendTo(this.$filters);

            // form input
            $('<input type="hidden" />')
                .attr('name', 'filter[' + id + ']')
                .appendTo(this.$form.find(selectors.form_controls));

            jsb.applyBehaviour($filter.get(0));
        }

        this.toggleFilter(id);

        return this;
    };

    ListFilters.prototype.cloneTemplate = function(template_name, filter_id, content) {
        var clone;
        content = content || '';

        if (!this.templates[template_name]) {
            this.logError('Template "' + template_name + '" does not exist.');
            return $();
        }
        clone = this.templates[template_name]
                .replace(/{CONTENT}/g, content)
                .replace(/{FILTER_ID}/g, filter_id);

        return $(clone);
    }

    ListFilters.prototype.setInput = function(filter_id, value, silent) {
        if (!filter_id) {
            return this.logWarn('Invalid filter id (' + filter_id + ')');
        }
        var $input = this.getInput(filter_id)
        $input.val(value)
        if (!silent) {
            $input.change();
        }

        return this;
    };

    ListFilters.prototype.setQuickLabel = function(filter_id, value) {
        // Label: quick_label, <filter>.quick_label
        // Label Title: quick_label.title, <filter>.quick_label.title
        // Translated Value: quick_label.value_<value>, <filter>.quick_label.value_<value>
        // @todo Test translated value
        var translated_value, quick_control_label, quick_control_label_title;

        if (!filter_id) {
            return this.logWarn('Invalid filter id (' + filter_id + ')');
        }
        value = value || this.getInput(filter_id).val() || '...';

        translated_value = this.translate('quick_label_value', filter_id, value) || value;
        quick_control_label = this.translate('quick_label', filter_id, value);
        quick_control_label_title = this.translate('quick_label.title', filter_id, value);

        if (quick_control_label) {
            value = quick_control_label.replace('{VALUE}', translated_value);
        }
        value = value.replace('{FILTER_ID}', filter_id);    // do I really need tech-info in the value?....
        this.getQuickControl(filter_id)
            .find(selectors.quick_control_label)
            .attr('title', quick_control_label_title)
            .html(value);

        return this;
    }

    ListFilters.prototype.translate = function(key, filter_id, value) {
        var filter_value_key, filter_key;
        // e.g: quick_label, category.quick_label.value_CatA, category.quick_label

        if (filter_id) {
            filter_key = filter_id + '.' + key;
            if (value) {
                filter_value_key = filter_id + '.' + key + '.value_' + value;
                if (translations[filter_value_key]) {
                    return translations[filter_value_key];
                } else {
                    if (translations[filter_key]) {
                        return translations[filter_key];
                    }
                }
            } else if (translations[filter_key]) {
                return translations[filter_key];
            }
        } else if (value) {
            return value;
        }
        if (translations[key]) {
            return translations[key]
        };

        // return key;
    };

    ListFilters.prototype.clearFilter = function(id) {
        if (!id) {
            return this.logWarn('Invalid filter id (' + id + ')');
        }
        this.getQuickControl(id).remove();
        this.getFilter(id)
            .trigger('clear_filter', { filter_id: id })
            .remove();
        this.getInput(id).remove();

        return this;
    };

    ListFilters.prototype.toggleFilter = function(id) {
        if (!id) {
            return this.logWarn('Invalid filter id (' + id + ')');
        }
        var $filter = this.getFilter(id);
        var $trigger;

        // can lazy load widgets just when are shown
        $filter.find('.jsb__').each(function() {
            $(this).removeClass('jsb__').addClass('jsb_');
            jsb.applyBehaviour($(this).parent().get(0));
        });

        // show one filter at once
        $trigger = $filter.siblings().closest(selectors.trigger);
        $trigger.prop('checked', function(idx, val) { return !val; });
        this.$filters.find(selectors.trigger)
            .filter(function() { return $(this).is($trigger) === false; })
            .prop('checked', false);

        return this;
    };

    ListFilters.prototype.isFilterLoaded = function(id) {
        return this.$form_controls.find(selectors.input_with_id.replace('{FILTER_ID}', id)).length !== 0;
    };

    ListFilters.prototype.getQuickControl = function(id) {
        return this.$quick_controls.find(selectors.quick_control_with_id.replace('{FILTER_ID}', id));
    };

    ListFilters.prototype.getFilter = function(id) {
        return this.$filters.find(selectors.filter_with_id.replace('{FILTER_ID}', id));
    };

    ListFilters.prototype.getInput = function(id) {
        return this.$form.find(selectors.input_with_id.replace('{FILTER_ID}', id));
    };

    return ListFilters;
});
