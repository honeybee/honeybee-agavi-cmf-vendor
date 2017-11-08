define([
    "Honeybee_Core/ui/ListFilter",
    "selectize"
], function(ListFilter) {

    var default_options = {
        prefix: "Honeybee_Core/ui/list-filter/TextListListFilter",
        join_values: false,
        input_allowed: true,
        allow_empty_option: true,
        string_delimiter: ','
    };

    function TextListListFilter(dom_element, options) {
        var $dom_element, filter_name, options;
        $dom_element = $(dom_element);
        options = _.merge({}, default_options, options);

        // before initialisation, use []-notation in filter-control name, if value must be handled as array
        if (!options.join_values) {
            filter_name = options.filter_name || $dom_element.data('hbFilterName');
            options.control_name = options.control_name || 'filter[' + filter_name + '][]';
            options.control_selector = '.input-text-list__input [name="' + options.control_name + '"]';
        }

        ListFilter.call(this, dom_element, options);

        this.$input_element = this.$widget.find('.input-text-list');
        if (this.$input_element.length === 0) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }
        this.allowed_options = this.getAllowedOptions();
        this.buildSelectize();
    }

    TextListListFilter.prototype = new ListFilter();
    TextListListFilter.prototype.constructor = TextListListFilter;

    TextListListFilter.prototype.getAllowedOptions = function() {
        if (this.options.allowed_values && this.options.allowed_values.length > 0) {
            return this.options.allowed_values;
        }
        var allowed_options = [];
        var $item_labels = this.$input_element.find("label");

        $item_labels.each(function() {
            var $input = $(this).find('input');
            var allowed_option = {
                text: $(this).find('span').text(),
                value: $input.val()
            };
            allowed_options.push(allowed_option);
        });

        return allowed_options;
    };

    TextListListFilter.prototype.buildSelectize = function() {
        var $source_control, $select_control, current_values, selectize_config;

        if (!this.options.join_values) {
            // select multiple to submit multiple values
            $source_control = this.getControl();
            $select_control = $('<select multiple>');
            $select_control.attr({
                id: $source_control.attr('id'),
                name: $source_control.attr('name'),
                class: $source_control.attr('class')
            });
            this.getControl().replaceWith($select_control);
        }

        current_values = this.$input_element.find("input.text-list__item--checked")
            .map(function(input) {
                return this.value;
            })
            .get();

        selectize_config = {
            options: this.allowed_options,
            items: current_values,
            searchField: [ 'text' ],
            delimiter: this.options.string_delimiter,
            maxItems: this.options.max_count,
            plugins: {
                'remove_button': {
                    label: this.options.remove_label,
                    title: this.options.remove_title,
                    className: this.options.remove_button_class
                },
                'restore_on_backspace': {}
            },
            create: this.options.input_allowed,
            render: {
                option_create: function(data, escape) {
                    return '<div class="create">+ <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                }
            },
            onFocus: this.focusControl
        };
        this.getControl().selectize(selectize_config);

        // replace controls with selectized input
        this.$widget.find('.input-text-list__box').replaceWith(this.$widget.find('.input-text-list__input'));
    };

    TextListListFilter.prototype.getControlValues = function() {
        var $control = this.getControl();
        var current_values = $control.val() || [];

        if (current_values.length && !$control.is('select')) {
            current_values = current_values.split(this.options.string_delimiter);
        }
        return current_values;
    };

    TextListListFilter.prototype.setQuickLabel = function() {
        var self = this;
        var control_values = this.getControlValues();
        var join_string = this.options.string_delimiter + ' ';
        var control_value_translations = control_values.map(function(value) {
            return self.translations['value_' + value] || value;
        });
        ListFilter.prototype.setQuickLabel.call(this, control_value_translations.join(join_string));

        return this;
    };

    return TextListListFilter;
});
