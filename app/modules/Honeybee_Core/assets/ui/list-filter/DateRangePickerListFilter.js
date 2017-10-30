define([
    "Honeybee_Core/ui/ListFilter",
    "Honeybee_Core/ui/DateRangePicker"
], function(ListFilter, DateRangePicker) {
    var default_options = {
        prefix: 'Honeybee_Core/ui/list-filter/DateRangePickerListFilter',
        date_range_picker_selector: '.date-range-picker',
        quick_label_date_display_format: 'll',
        translations: {
            quick_label_range_limit: '{COMPARATOR} {COMPARAND}, ',
            'quick_label_comparator_gte': 'from',
            'quick_label_comparator_lte': 'to'
        },
        date_range_picker_config: {
            custom_text: 'Custom range'
        }
    };

    function DateRangePickerListFilter(dom_element, options) {
        ListFilter.call(this, dom_element, _.merge({}, default_options, options));

        var $range_picker = this.$widget.find(this.options.date_range_picker_selector);
        if (!$range_picker.length) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }

        date_range_picker_config = this.options.date_range_picker_config;
        date_range_picker_config.default_custom_value = this.options.default_range_value || date_range_picker_config.default_custom_value;
        date_range_picker_config.custom_text = this.translations.picker_custom || date_range_picker_config.custom_text;

        this.range_picker = new DateRangePicker($range_picker.get(0), date_range_picker_config);

        // update quick-label
        this.getControl().change();
    };

    DateRangePickerListFilter.prototype = new ListFilter();
    DateRangePickerListFilter.prototype.constructor = DateRangePickerListFilter;

    DateRangePickerListFilter.prototype.setQuickLabel = function(value) {
        var self = this;
        if (this.range_picker) {
            this.getQuickLabelValue(value);
        } else {
            // callback could be triggered when range_picker is not yet ready
            var interval = setInterval(function() {
                if (self.range_picker) {
                    clearInterval(interval);
                    self.getQuickLabelValue(value);
                }
            }, 1000);
        }

        return this;
    };

    DateRangePickerListFilter.prototype.getQuickLabelValue = function(value) {
        var self = this;
        var selected_choice = this.getControl().find(':selected');
        value = '';

        if (selected_choice.is('.date-range-picker__input-custom')) {
            this.range_picker.getLimits().forEach(function(limit) {
                value += self.buildQuickLabelLimit(limit);
            });
            // trim right
            value = value.replace(/[\,\s]+$/, '');
            if (value.length === 0) {
                value = selected_choice.text();
            };
        } else {
            // @todo When translated values support is complete, rely on it rather than the select-option text
            value = selected_choice.text();
        }

        ListFilter.prototype.setQuickLabel.call(this, value);

        return this;
    };

    DateRangePickerListFilter.prototype.buildQuickLabelLimit = function(limit) {
        var limit_comparator = limit.comparator.find(':selected').val();
        var comparator_text = this.translations['quick_label_comparator_' + limit_comparator] || limit_comparator;
        var limit_picker = limit.widget.getPicker();
        var picker_value = limit_picker.getOutputElement().val();
        if (!picker_value || !limit_picker.isValidDate(picker_value))
            return '';

        var formatted_date = limit_picker.getSelectedDate()
            .local()
            .format(this.options.quick_label_date_display_format);

        return this.translations.quick_label_range_limit
            .replace('{COMPARATOR}', comparator_text)
            .replace('{COMPARAND}', formatted_date);
    };

    return DateRangePickerListFilter;
});
