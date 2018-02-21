define([
    "Honeybee_Core/Widget",
    "jquery",
    "jsb"
], function(Widget, $, jsb) {

    /* By default the widget expects to be applied to a input control supporting val().
       If applied to other elements provide target_selector and getTargetVal.

        @todo: count can differ depending on the browser counting "\r|\n|\r\n" as newline
    */

    var default_options = {
        prefix: 'Honeybee_Core/ui/CharCounter',
        template: '{COUNT}',
        show_counter: true
    };

    function CharCounter(dom_element, options) {
        var that = this;

        this.init(dom_element, default_options);
        this.addOptions(options);

        if (this.options.target_selector) {
            this.$target = this.$widget.find(this.options.target_selector);
        } else {
            this.$target = this.$widget;
        }
        this.valid_length = this.options.valid_length || this.$target.prop('maxLength') || this.$target.data('maxlength');

        if (this.options.counter_selector) {
            this.$counter = this.$widget.find(this.options.counter_selector).hide();
        } else {
            this.$counter = $('<div class="hb-counter"></div>').insertAfter(this.$widget).hide();
        }

        if (this.$target.length !== 1 || this.$counter.length !== 1) {
            return;
        }

        if (typeof this.options.getTargetVal === 'function') {
            this.getTargetVal = this.options.getTargetVal;
        }

        this.updateCount();

        this.$target.on('input', this.onTargetInput.bind(this));

        if (this.options.show_counter === true) {
            this.$counter.show();
        }

        dom_element.char_counter = this;
    }

    CharCounter.prototype = new Widget();
    CharCounter.prototype.constructor = CharCounter;

    CharCounter.prototype.updateCount = function(target_value) {
        target_value = target_value || this.getTargetVal();

        this.$counter.html(this.options.template.replace(/{COUNT}/, target_value.length));
        this.$counter.attr('data-count', target_value.length);

        this.checkTargetValidity(target_value);
    };

    CharCounter.prototype.setValidity = function(valid) {
        if (valid) {
            this.$counter.removeClass('hb-counter--invalid');
        } else {
            this.$counter.addClass('hb-counter--invalid');
        }
    };

    CharCounter.prototype.getCount = function() {
        return this.$counter.data('count') || 0;
    };

    CharCounter.prototype.getTarget = function() {
        return this.$target;
    };

    CharCounter.prototype.getCounter = function() {
        return this.$counter;
    };

    CharCounter.prototype.getTargetVal = function() {
        return this.$target.val();
    };

    CharCounter.prototype.checkTargetValidity = function(target_value) {
        if (isNaN(this.valid_length)) {
            return;
        }
        target_value = target_value || this.getTargetVal();

        if (this.valid_length !== -1 && target_value.length > this.valid_length) {
            this.setValidity(false);
        } else {
            this.setValidity(true);
        }
    };

    CharCounter.prototype.onTargetInput = function(e) {
        this.updateCount();
    };

    return CharCounter;
});
