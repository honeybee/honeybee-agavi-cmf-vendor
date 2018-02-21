define([
    "Honeybee_Core/ui/ListFilter",
    "Honeybee_Core/ui/DatePicker",
    "Honeybee_Core/lib/DatetimeLocalPicker",
    "lodash",
    "jquery",
    "jsb"
], function(ListFilter, DatePicker, DatetimeLocalPicker, _, $, jsb) {

    var default_options = {
        prefix: "Honeybee_Core/ui/list-filter/DatePickerListFilter",
        date_picker_selector: ".hb-list-filter__datepicker",
        quick_label_date_display_format: "ll"
    };

    function DatePickerListFilter(dom_element, options) {
        ListFilter.call(this, dom_element, _.merge({}, default_options, options));

        var date_picker_element = this.$widget.find(this.options.date_picker_selector).get(0);
        var date_picker_options = _.merge({}, this.options.date_picker_config);
        date_picker_options.onSetSelectedDate = this.onSelectDate;

        this.date_picker_widget = new DatePicker(date_picker_element, date_picker_options);
        this.date_picker = this.date_picker_widget.getPicker();
        if (!this.date_picker_widget || !(this.date_picker instanceof DatetimeLocalPicker)) {
            console.log('There was a problem with initialising the DatePicker. Please check the DOM or the provided settings.');
            return;
        }
    }

    DatePickerListFilter.prototype = new ListFilter();
    DatePickerListFilter.prototype.constructor = DatePickerListFilter;

    // ListFilter overrides

    // Format date when displaying it in the quick-control label
    DatePickerListFilter.prototype.setQuickLabel = function() {
        var $date_picker_input = this.date_picker.getInputElement();
        if ($date_picker_input.length !== 0) {
            value = $date_picker_input.val();
            if (this.date_picker.isValidDate(value)) {
                value = this.date_picker
                    .parseDate(value)
                    .local()
                    .format(this.options.quick_label_date_display_format);
            }
        }

        ListFilter.prototype.setQuickLabel.call(this, value);

        return this;
    };

    // DatetimeLocaPicker doesn't trigger change event, but provides a change callback
    // @todo Remove if behaviour gets fixed
    DatePickerListFilter.prototype.onSelectDate = function(e) {
        this.picker.getOutputElement().change();
    };

    return DatePickerListFilter;
});
