define([
    "Honeybee_Core/Widget",
    "lodash"
], function(Widget, _) {

    var default_options = {
        prefix: "Honeybee_Core/ui/GlobalErrors",
        offset: 0,
        selector: undefined, // what to fix-to-top? e.g. '.hb-errors__fields' – defaults to widget element
        debounce: 50, // milliseconds
        datakey: 'globalerrors'
    };

    function GlobalErrors(dom_element, options) {
        this.init(dom_element, default_options);
        this.addOptions(options);

        // use the widget element as the one to fix-to-top or the given selector from options
        this.$fix = this.$widget;
        if (this.options.selector) {
            this.$fix = this.$widget.find(this.options.selector).first();
        }

        if (this.$fix.length !== 1) {
            this.logError('Given selector does not match an element.');
            return;
        }

        var data = this.$fix.data(this.options.datakey);
        if (!data) {
            data = {
                offsetTop: this.$fix.offset().top,
                top: parseInt(this.options.offset || 0, 10)
            };
            this.$fix.data(this.options.datakey, data);
        }

        this.onScroll();
        this.attachEventHandlers();
        this.enableSpecificErrorSwitching();
    };

    GlobalErrors.prototype = new Widget();
    GlobalErrors.prototype.constructor = GlobalErrors;

    GlobalErrors.prototype.attachEventHandlers = function() {
        var self = this;

        $(window).on(
            'scroll.' + this.prefix + ' orientationchange.' + this.prefix,
            _.debounce(
                function() { self.onScroll(); },
                this.options.debounce
            )
        );

        // handle click events on error messages and focus the respective input element
        $(document).on('click.' + this.prefix, '.hb-errors__fields .error.specific label', function(ev) {
            var $target = $(ev.target);
            var elm_id = $target.closest('.error.specific').data('field-id');
            var $elm = $(document.getElementById(elm_id));
            if ($elm.length > 0) {
                jsb.fireEvent('TABS:OPEN_TAB_THAT_CONTAINS', { 'element_id': elm_id });
                jsb.fireEvent('ENTITY_LIST:OPEN_ENTITY_THAT_CONTAINS', { 'element_id': elm_id });
            }
        });

        $(document).on('invalid.' + this.prefix, function(ev) {
            jsb.fireEvent('TABS:UPDATE_ERROR_BUBBLES');
        });

        // other candidate events: input, copy, paste, cut etc.
        $(document).on('change.' + this.prefix, function(ev) {
            // only update when turning valid or invalid? track it? … if ($(ev.target).is(':invalid')) {}
            jsb.fireEvent('TABS:UPDATE_ERROR_BUBBLES');
        });
    };

    // depending on scroll position of $elem switch to position:fixed for clone of that element
    GlobalErrors.prototype.onScroll = function($elem) {
        var $el = $elem || this.$fix;

        var data = $el.data(this.options.datakey);
        if (!data || !$el.is(':visible')) {
            // not an element we're interested in ot the element might be hidden via display:none or similar
            // :visible => visibility:hidden/opacity:0 are considered visible as layout space is used
            return;
        }

        var partly_hidden = $(window).scrollTop() >= (data.offsetTop - data.top);
        if (partly_hidden) {
            $el.addClass('fixed-to-top').css({
                top: data.top
            });
        } else {
            $el.removeClass('fixed-to-top');
        }
    };

    // prev/next links to switch through errors and focus the invliad input element
    GlobalErrors.prototype.enableSpecificErrorSwitching = function() {
        var self = this;

        this.$error_list = this.$widget.find('.hb-errors__fields');

        this.$errors = this.$error_list.children('.error');
        if (this.$errors.length < 2) {
            // only show the item, but no prev/next switch when there's only one error message
            if (this.$errors.length === 1) {
                this.$errors.addClass('is-visible');
            }
            return;
        }

        this.current_idx = 0;

        this.$error_switch = this.$widget.find('.hb-errors__switch');
        this.$error_switch.show();

        this.$count_current = this.$error_switch.find('.hb-errors__switch-count-current');
        this.$count_current.text(1);

        this.$count_total = this.$error_switch.find('.hb-errors__switch-count-total');
        this.$count_total.text(this.$errors.length);

        this.$errors.removeClass('is-visible');
        $(this.$errors.get(this.current_idx)).addClass('is-visible');

        $(document).on('click.' + this.prefix, '.hb-errors__switch-link', function(ev) {
            self.$errors.removeClass('is-visible');

            var $target = $(ev.target);

            if ($target.hasClass('hb-errors__switch-link-prev')) {
                self.current_idx--;
                if (self.current_idx < 0) {
                    self.current_idx = self.$errors.length - 1;
                }
            } else if ($target.hasClass('hb-errors__switch-link-next')) {
                self.current_idx++;
                if (self.current_idx > (self.$errors.length - 1)) {
                    self.current_idx = 0;
                }
            }

            self.$count_current.text(self.current_idx + 1);

            var $error = $(self.$errors.get(self.current_idx));
            $error.addClass('is-visible').find('label[for]').trigger('click');

            jsb.fireEvent('GLOBALERRORS:SHOW_FIELD_ERROR', { 'current': self.current_idx, 'element': $error });
        });
    };

    GlobalErrors.prototype.removeEventHandlers = function() {
        $(window).off('.' + this.prefix);
        $(document).off('.' + this.prefix);
    };

    return GlobalErrors;
});
