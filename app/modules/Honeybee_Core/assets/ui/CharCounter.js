define([
    'Honeybee_Core/Widget'
], function(Widget) {

    /* By default the widget expects to be applied to a input control supporting val().
       If applied to other elements provide target_selector and getTargetVal.

        @todo: count can differ depending on the browser counting "\r|\n|\r\n" as newline
    */

    var default_options = {
        prefix: 'Honeybee_Core/ui/CharCounter',
        template: '{COUNT}',
        register_autoupdate: true,
        show_counter: true
    };

    function CharCounter(dom_element, options) {
        var that = this;
        var $scope = $;

        this.init(dom_element, default_options);
        this.addOptions(options);

        if (this.options.target_selector) {
            this.$target = this.$widget.find(this.options.target_selector);
        } else {
            this.$target = this.$widget;
        }

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

        if (this.options.register_autoupdate !== false) {
            this.$target.on('input', function(e) {
                that.updateCount();
            });
        }

        this.updateCount();
        if (this.options.show_counter === true) {
            this.$counter.show();
        }

        dom_element.char_counter = this;
    }

    CharCounter.prototype = new Widget();
    CharCounter.prototype.constructor = CharCounter;

    CharCounter.prototype.updateCount = function() {
        var val = this.getTargetVal();
        this.$counter.html(this.options.template.replace(/{COUNT}/, val.length));
        this.$counter.attr('data-count', val.length);
    };

    CharCounter.prototype.setValidity = function(valid) {
        if (valid) {
            this.$counter.removeClass('hb-counter--invalid');
        } else {
            this.$counter.addClass('hb-counter--invalid');
        }
    }

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
    }

    return CharCounter;
});
