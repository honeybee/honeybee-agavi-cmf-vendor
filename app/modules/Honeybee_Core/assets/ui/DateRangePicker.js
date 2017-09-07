/*
    TODO:
    - get range parts. Use the renderer? Will need to compose them anyway later via JS
    - try to avoid using date values: it won't reselect the value, few seconds (or days) after the POST
    OPTIONAL:
    - add '+' button to allow to add multiple range limits?
*/
define([
    "Honeybee_Core/Widget",
    "Honeybee_Core/ui/SelectBox",
    "Honeybee_Core/ui/DatePicker",
    "moment"
], function(Widget, SelectBox, DatePicker, moment) {
    var default_options = {
        prefix: 'Honeybee_Core/ui/DateRangePicker',
        default_control_selector: '.date-range-picker__input [name]',
        limit_input_template_selector: '[data-hb-template=datepicker]',
        custom_controls_selector: '.date-range-picker__custom', // @todo Try to not have it configurable
        custom_text: 'Custom: {VALUE}',
        default_custom_value: 'range(gte:now)',
        date_picker_config: {},
        period_map: {
            // map elastic syntax to moment syntax
            // https://www.elastic.co/guide/en/elasticsearch/reference/5.3/common-options.html#date-math
            // https://momentjs.com/docs/#/manipulating/add/
            'year': 'years',
            'y': 'y',
            'month': 'months',
            'M': 'M',
            'week': 'weeks',
            'w': 'w',
            'day': 'days',
            'd': 'd',
            'hour': 'hours',
            'h': 'h',
            'minute': 'minutes',
            'm': 'm'
        }
    };

    function DateRangePicker(dom_element, options) {
        var self = this;

        this.init(dom_element, _.merge({}, default_options, options));

        this.$default_control = this.$widget.find(this.options.default_control_selector);
        this.$custom = this.$widget.find(this.options.custom_controls_selector);

        var regex_periods = Object.keys(this.options.period_map).join('|');
        this.date_math_regex = new RegExp('([+-])(\\d+)\\s*(' + regex_periods + ')', 'g');
        this.range_limits = [];

        this.options.date_picker_config.onSetSelectedDate = this.onSelectDate;

        this.initUi();
    };

    DateRangePicker.prototype = new Widget();
    DateRangePicker.prototype.constructor = DateRangePicker;

    DateRangePicker.prototype.initUi = function() {
        this.initSelect();
        this.initLimits();
        this.addListeners();
        // this.updateCustom();
    };

    DateRangePicker.prototype.addListeners = function() {
        var self = this;

        this.$widget.on('change', this.options.default_control_selector, function(e) {
            if (self.$default_control.find(':selected').is('.date-range-picker__input-custom')) {
                self.showCustom(true);
            } else {
                self.showCustom(false);
            }
        });

        this.$custom.on('change', '.date-range-picker__range-limit :input', function(e) {
            self.updateCustom();
        });

        this.$custom.on('click', this.options.custom_controls_selector + '-controls', function(e) {
            var $target = $(e.target);
            if ($target.is(self.options.custom_controls_selector + '-add')) {
                self.addLimit();
            }
            if ($target.is(self.options.custom_controls_selector + '-remove')) {
                if (self.removeLimit()) {
                    self.updateCustom();
                }
            }
        });
    };

    DateRangePicker.prototype.updateCustom = function(silent) {
        if (!this.$default_control.find(':selected').is('.date-range-picker__input-custom'))
            return;

        var custom_value = this.buildCustomValue();
        this.$default_control.find('.date-range-picker__input-custom').val(custom_value);
        this.$default_control.val(custom_value) // or chain .prop('selected', true) to the previous line

        if (!silent) {
            this.$default_control.change();    // @todo Verify it works also with selectize
        }
    };

    DateRangePicker.prototype.initSelect = function() {
        var current_value, choice_options, is_custom_value, custom_value, custom_text, $custom_option;

        // add custom value, if not already provided
        if (this.$default_control.find('.date-range-picker__input-custom').length === 0) {
            choice_options = $(this.$default_control[0].options).map(function() { return this.value; }).get();
            current_value = this.$default_control.data('jsCurrentValue');

            is_custom_value = current_value && $.inArray(current_value, choice_options) === -1;
            custom_value = is_custom_value ? current_value : this.options.default_custom_value;
            custom_text = this.options.custom_text.replace('{VALUE}', current_value);   // @todo custom_text should use translation + value
            $custom_option = $(new Option(custom_text, custom_value, is_custom_value, is_custom_value))
                .addClass('date-range-picker__input-custom');

            this.$default_control.append($custom_option);
        }

        // @todo selectize control
        //       (atm doesn't work, as library doesn't retain class .date-range-picker__input-custom on the custom option)
        // this.select_box = new SelectBox(this.$widget, this.options.selectize_config);
    };

    DateRangePicker.prototype.initLimits = function() {
        var self = this;
        var range_limits = this.options.date_range_values || this.$widget.data('hbDateRangeValues');
        if (range_limits.length === 0) {
            // no support for empty ranges; add at least one picker
            range_limits.push({ comparator: 'gte', comparand: 'now'});
        }
        range_limits.forEach(function(limit) {
            self.addLimit(limit.comparator, limit.comparand);
        });
    };

    DateRangePicker.prototype.addLimit = function(comparator, value) {
        comparator = comparator || 'lte';
        value = this.validateLimitValue(value) || value;

        // clone template
        var limit_template = this.$widget.find('.date-range-picker__templates')
            .find(this.options.limit_input_template_selector)
            .html();
        var limit_element = $.parseHTML(limit_template);
        this.$custom.find(this.options.custom_controls_selector + '-values').append(limit_element);

        var $limit_element = $(limit_element);
        var $comparator = $limit_element.find('.date-range-picker__comparator :input');

        // set limit values
        $limit_element.find('.datepicker-input').val(value);
        $comparator.val(comparator);

        this.range_limits.push({
            'comparator': $comparator,
            'widget': new DatePicker(limit_element, this.options.date_picker_config)
        });
    };

    DateRangePicker.prototype.removeLimit = function() {
        if (this.range_limits.length < 2)
            return false;

        this.range_limits.pop()
            .comparator
            .closest('.date-range-picker__range-limit')
            .remove();

        return true;
    }

    DateRangePicker.prototype.buildCustomValue = function() {
        var self = this;
        var range_value = '';

        this.range_limits.forEach(function(limit) {
            var picker = limit.widget.getPicker();
            var valid_value = self.validateLimitValue(picker.getOutputElement().val());
            if (valid_value) {
                range_value += limit.comparator.val() + ':' + valid_value + ',';
            }
        });

        return range_value ? 'range(' + range_value + ')' : this.options.default_custom_value;
    };

    DateRangePicker.prototype.validateLimitValue = function(value) {
        var match, now, period;
        if (!_.isString(value)) {
            return false
        }
        if (!moment(value, moment.ISO_8601, true).isValid()) {
            now = moment();
            if (value === 'now') {
                value = now.toISOString();
            } else if (match = this.date_math_regex.exec(value)) {
                period = this.options.period_map[match[3]];
                if (match[1] === '+') {
                    value = now.add(match[2], period).toISOString();
                } else {
                    value = now.subtract(match[2], period).toISOString();
                }
            } else {
                return false;
            }
        }
        return value;
    };

    DateRangePicker.prototype.showCustom = function(show) {
        if (!!show) {
            this.$custom.show('fast');
        } else {
            this.$custom.hide('fast');
        }
    };

    DateRangePicker.prototype.getLimits = function() {
        return this.range_limits;
    }

    // DatetimeLocaPicker doesn't trigger change event, but provides a change callback
    // @todo Remove if behaviour gets fixed

    // @todo Make DatePicker trigger 'change'. (should change the library and stop using callbacks?)

    DateRangePicker.prototype.onSelectDate = function(e) {
        this.picker.getOutputElement().change();
    };

    return DateRangePicker;
});
