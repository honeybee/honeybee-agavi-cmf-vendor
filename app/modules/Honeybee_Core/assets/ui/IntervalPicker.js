define([
    "Honeybee_Core/Widget",
    "jsb"
], function(Widget, jsb) {
    var default_options = {
        prefix: 'Honeybee_Core/ui/IntervalPicker',
        // input_value_selector: '.default-interval-picker__input',
        input_choice_selector: '.input-choice select',
        // input_choice_selector: '.default-interval-picker__quick-selection',
        // form_selector: '.default-interval-picker__form',
        // custom_controls_selector: '.default-interval-picker__custom',
        picker_template_selector: '.datepicker-controls-wrapper-template',
        date_input_selector: '.datepicker-input',
        interval_input_value_template: 'range(gte:{{FROM}},lte:{{TO}})',
        from_input_value_template: 'range(gte:{{FROM}})',
        to_input_value_template: 'range(lte:{{TO}})',
        translations_prefix: 'default'
    };

    function IntervalPicker(dom_element, options)
    {
        var self = this;

        this.init(dom_element, default_options);
        this.addOptions(options);

        // this.options.input_choice_hide_class = this.options.input_choice_selector.replace('.','') + '--hidden';
        // this.options.custom_controls_hide_class = this.options.custom_controls_selector.replace('.','') + '--hidden';

        this.$input_choice = this.$widget.find(this.options.input_choice_selector);
        // this.form = this.$widget.find(this.options.form_selector);
        // this.custom = this.$widget.find(this.options.custom_controls_selector);
        this.$input_date_template = this.$widget.find(this.options.picker_template_selector);
        // this.input_source = this.form.find(this.options.input_value_selector);

        // this.input_choice.removeClass(this.options.input_choice_hide_class);

        this.initCustomChoice();
    };

    IntervalPicker.prototype = new Widget();
    IntervalPicker.prototype.constructor = IntervalPicker;

    IntervalPicker.prototype.initCustomChoice = function() {
        var self = this;

return false;

        // create input for target value
        $('input').attr({
                type: 'hidden',
                name: this.input_choice.attr('name'),
                value: this.input_choice.data('jsValue')
            })
            .appendTo(this.$widget);
        this.input_choice.removeAttr('name');
        // create additional select option
        $('<option />').attr('value', 'custom')
            .text(this.options.translations['{{prefix}}_picker_custom'])
            .appendTo(this.input_choice);
        // create From/To date inputs
        this.$from_date = this.$input_date_template
            .clone()
            .removeClass(this.options.picker_template_selector.replace('.',''))
            .addClass('jsb_ jsb_Bo_Tb/ui/DatePicker')               // @todo Use cmf-DatePicker; find a way to retrieve picker instance
            .data('jsb', JSON.stringify(this.options.from_date_options || {}))
            .appendTo(this.custom);
        this.$to_date = this.$from_date.clone()
            .appendTo(this.custom);
        // init date-pickers
        this.$from_date
            .addClass('interval-picker__from')
            .data('jsb', JSON.stringify(this.options.from_date_options || {}))
            .find(this.options.date_input_selector)
                .val(this.$widget.data('jsFrom'));
        this.$to_date
            .addClass('interval-picker__to')
            .data('jsb', JSON.stringify(this.options.to_date_options || {}))
            .find(this.options.date_input_selector)
                .val(this.$widget.data('jsTo'));
        jsb.applyBehaviour(this.form);

        // jsb.whenFired('Jsb::BEHAVIOURS_APPLIED', function(e) {
            self.addFormListeners();
            self.$input_choice.find('select').attr('disabled', false);
            // show eventual values already loaded
            if (self.$input_choice.find('select').val() === 'custom') {
                self.toggleCustomInterval();
            }
        // });

        self.$input_date_template.remove();
        $('<span class="close-custom activity">x</span>').appendTo(self.$custom);
        // self.input_source.attr('required', null).hide();
        self.$from_date.show();
        self.$to_date.show();
    };

    IntervalPicker.prototype.addFormListeners = function() {
        var self = this;

        this.input_choice.find('select').change(function(e) {
            var value = $(this).val();
            if (value === 'custom') {
                self.toggleCustomInterval();
            } else if (value.length !== 0) {
                self.from_date[0].date_picker.setDate(value);
                self.to_date[0].date_picker.setDate('');
            } else {
                self.from_date[0].date_picker.setDate('');
                self.to_date[0].date_picker.setDate('');
            }
        });

        this.$widget.on('submit.' + this.prefix /* random id to distinguish from other instances */, function(e) {
            var from, to;

            e.preventDefault();
            e.stopPropagation();

            if (self.input_choice.find('select').val() === '') {
                // no filter. reset.
                self.from_date[0].date_picker.setDate('');
                self.to_date[0].date_picker.setDate('');
                self.input_source.attr('disabled', true);
            } else {
                // check values
                from = self.from_date.find(self.options.date_input_selector).val();
                to = self.to_date.find(self.options.date_input_selector).val();
                if (from.length === 0 && to.length === 0) {
                    console.error(self.prefix + ': at least one date must be specified');
                    return false;
                }
                self.input_source.attr('disabled', false);
                self.input_source.val(self.buildIntervalValue(from, to));
            }
            $(this).off('submit.' + self.prefix);
            $(this).submit();
        });

        this.$custom.find('.close-custom').click(function(e) {
            self.toggleCustomInterval();
            self.$input_choice.find('select').val('');
        })
    };

    IntervalPicker.prototype.getIntervalValues = function(value) {
        if (to.length === 0) {
            return this.options.from_input_value_template.replace('{{FROM}}', from);
        }
        if (from.length === 0) {
            return this.options.to_input_value_template.replace('{{TO}}', to);
        }

        return this.options.interval_input_value_template
            .replace('{{FROM}}', from)
            .replace('{{TO}}', to);
    };

    // IntervalPicker.prototype.buildIntervalValue = function(from, to) {
    //     if (to.length === 0) {
    //         return this.options.from_input_value_template.replace('{{FROM}}', from);
    //     }
    //     if (from.length === 0) {
    //         return this.options.to_input_value_template.replace('{{TO}}', to);
    //     }

    //     return this.options.interval_input_value_template
    //         .replace('{{FROM}}', from)
    //         .replace('{{TO}}', to);
    // };

    // IntervalPicker.prototype.toggleCustomInterval = function() {
    //     this.custom.toggleClass(this.options.custom_controls_hide_class);
    //     this.input_choice.toggleClass(this.options.input_choice_hide_class);
    // };

    return IntervalPicker;
});
