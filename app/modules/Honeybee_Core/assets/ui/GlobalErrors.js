define([
    "Honeybee_Core/Widget",
    "lodash"
], function(Widget, _) {

    var default_options = {
        prefix: "Honeybee_Core/ui/GlobalErrors",
        offset: 0,
        css: {
            'top': 0,
            'width': '100%',
            'left': 0
        },
        selector: undefined, // e.g. '.hb-errors__fields',
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

        $(window).on('resize.' + this.prefix, _.debounce(function() { self.onResize(); }, this.options.debounce));

        // handle click events on error messages and focus the respective input element
        $(document).on('click.' + this.prefix, '.hb-errors__fields .error.specific label', function(ev) {
            var $target = $(ev.target);
            var elm_id = $target.closest('.error.specific').data('field-id');
            var $elm = $('#' + elm_id);
            if ($elm.length > 0) {
                jsb.fireEvent('TABS:OPEN_TAB_THAT_CONTAINS', { 'element_id': elm_id });
            }
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
            if (!data.$clone) {
                // TODO deep clone with events and data? display:none the original element?
                data.$clone = $el.clone().css({
                    position: 'fixed',
                    top: data.top
                }).addClass('fixed-to-top').appendTo('body');

                $el.css("visibility", "hidden");

                this.onResize($el);
            } else {
                data.$clone.css({
                    'display': 'block'
                });
            }
        } else {
            if (data.$clone) {
                data.$clone.css({
                    'display': 'none'
                });
                $el.css("visibility", "visible");
            }
        }
    };

    // recalculate dimensions of the hidden element and update the clone dimensions if necessary
    GlobalErrors.prototype.onResize = function($elem) {
        var $el = $elem || this.$fix;

        var data = $el.data(this.options.datakey);

        if (data && data.$clone) {
            var offset = $el.offset();
            data.offsetTop = offset.top;
            data.$clone.css(this.options.css);
            // TODO do this? option?
            //data.$clone.css({
            //    left: offset.left,
            //    width: $el.width()
            //});
        }
    };

    return GlobalErrors;
});
