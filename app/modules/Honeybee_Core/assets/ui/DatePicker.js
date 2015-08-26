define([
    "Honeybee_Core/Widget",
    "ldsh!Honeybee_Core/lib/calendar.tmpl",
    "Honeybee_Core/lib/DatetimeLocalPicker"
], function(Widget, calendars_tmpl, dtlp) {

    var default_options = {
        prefix: "Honeybee_Core/ui/DatePicker",
        locale: "de",
        direction: "ltr",
        displayMode: "table",
        displayFormat: "L LTS"
    };

    function DatePicker(dom_element, options) {
        var self = this;

        if (typeof options !== "object") {
            options = {};
        }

        if (!options.locale) {
            options.locale = $('html').data('moment-locale-identifier') || $("html").attr("lang") || default_options.locale;
        }

        if (!options.direction) {
            options.direction = $('html').attr('dir') || default_options.direction;
        }

        this.init(dom_element, default_options);
        this.addOptions(options);

        this.initial_picker_settings = {
            inputElement: this.$picker_input_element,
            toggleElement: this.$picker_toggle_element,
            templates: {
                calendars: calendars_tmpl
            },
            cssClasses: {
                inputInvalid: "invalid"
            },
            locale: options.locale,
            direction: options.direction,
            hideOnSet: true,
            minWeeksPerMonth: 6,
            //numberOfMonths: -2,
            //numberOfMonths: {
            //    before: 1,
            //    after: 1
            //},
            //constraints: {
            //    minDate: '2010-01-01',
            //    maxDate: '2030-05-14T13:59:59.999'
            //},
            //disableWeekends: false,
            //disabledDates: [
            //    new Date('2015-01-20'),
            //    '2015-01-27',
            //    function (date) {
            //        if (date.date()%14 === 0) {
            //            return true;
            //        }
            //        return false;
            //    }
            //],
            //onSetCurrentDate: onSet,
            //onSetSelectedDate: onSelect,
            //onShow: onWhatever,
            //onHide: onWhatever,
            //onBeforeShow: onWhatever,
            //onBeforeHide: onWhatever,
            onDraw: this.fitToContent.bind(this),
            debug: false
        };

        var datepicker_input_selector = this.$widget.data('picker-input') || '.datepicker-input';
        var datepicker_toggle_selector = this.$widget.data('picker-toggle') || '.datepicker-toggle';

        // main input element of the datetime picker
        this.$picker_input_element = this.$widget.find(datepicker_input_selector).first();
        if (this.$picker_input_element.length > 0) {
            this.initial_picker_settings.inputElement = this.$picker_input_element;
        } else {
            console.error('No input element given for datepicker.');
        }

        // optional toggle element to show/hide the picker
        this.$picker_toggle_element = this.$widget.find(datepicker_toggle_selector);
        if (this.$picker_toggle_element.length > 0) {
            this.initial_picker_settings.toggleElement = this.$picker_toggle_element;
        }

        this.initial_picker_settings.displayFormat = this.options.display_format || this.$widget.data('displayFormat') || default_options.displayFormat;
        this.initial_picker_settings.defaultDisplayMode = this.options.display_mode || this.$widget.data('displayMode') || default_options.displayMode;

        // create/attach datepicker
        this.picker = new dtlp(this.initial_picker_settings);

        if(this.isReadonly()) {
            this.picker.unbind();
        }

        window.pickers = window.pickers || [];
        window.pickers.push(this.picker);
    }

    DatePicker.prototype = new Widget();
    DatePicker.prototype.constructor = DatePicker;

    DatePicker.prototype.fitToContent = function() {
        var $datepicker = this.$widget.find(".datepicker");

        if ($datepicker.hasClass('calendar--list')) {
            var $picker_content = this.$widget.find(".datepicker__content");
            var $calendars = this.$widget.find(".calendars");
            var $body = $calendars.children(".calendars__body");
            var $inner = $body.children(".calendars__body_inner");
            var $header = $calendars.children(".calendars__header");

            $picker_content.height($inner.height() + $header.height());

            //now the browser will reflow and $picker_content will have a new actual height
            //according to its min/max-height.
            $body.height($picker_content.height() - $header.height());

            var new_width = $inner.width();

            new_width += 15; // adjust for vertical scrollbars width in list mode, magic number m(

            // now shrink the widht from the really high inital CSS value down to fit the content
            $picker_content.width(new_width);
        }
    };

    return DatePicker;
});
