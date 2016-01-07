define([
    "Honeybee_Core/Widget",
], function(Widget) {

    var default_options = {
        prefix: "Honeybee_Core/ui/GlobalErrors",
        offset: 0,
        css: {
            'top': 0,
            'width': '100%',
            'left': 0
        },
        datakey: 'globalerrors'
    };

    function GlobalErrors(dom_element, options) {
        this.init(dom_element, default_options);
        this.addOptions(options);

        var data = this.$widget.data(this.options.datakey);
        if (!data) {
            data = {
                offsetTop: this.$widget.offset().top,
                top: parseInt(this.options.offset || 0, 10)
            };
            this.$widget.data(this.options.datakey, data);
        }

        this.onScroll(this.$widget);
        this.attachEventHandlers();
    };

    GlobalErrors.prototype = new Widget();
    GlobalErrors.prototype.constructor = GlobalErrors;

    GlobalErrors.prototype.attachEventHandlers = function() {
        var self = this;
        $(window).on('scroll.' + this.prefix, function() { self.onScroll(self.$widget); });
        $(window).on('orientationchange.' + this.prefix, function() { self.onScroll(self.$widget); });
        $(window).on('resize.' + this.prefix, function() { self.onResize(self.$widget); });


        /**
         * handle click events on error messages and focus the respective input element
         */
        $(document).on('click', '.hb-errors__fields .error.specific label', function(ev) {
            var $target = $(ev.target);
            var elm_id = $target.closest('.error.specific').data('field-id');
            var $elm = $('#' + elm_id);
            if ($elm.length > 0) {
                jsb.fireEvent('GLOBALERRORS:CLICKED_LABEL_FOR_ELEMENT', { 'element_id': elm_id });
            }
        });

    };

    // depending on scroll position of $el switch to position:fixed for clone of that element
    GlobalErrors.prototype.onScroll = function($el) {
        var data = $el.data(this.options.datakey);
        if (!data) {
            return;
        }

        var partly_hidden = $(window).scrollTop() >= (data.offsetTop - data.top);
        if (partly_hidden) {
            if (!data.$clone) {
                // TODO deep clone with events and data? display:none the original element?
                data.$clone = $el.clone().css({
                    position: 'fixed',
                    top: data.top
                })
                .addClass('fixed-to-top')
                .appendTo('body');

                $el.css("visibility", "hidden");

                this.onResize($el);
            } else {
                data.$clone.css({
                    'display': 'block'}
                );
            }
        } else {
            if (data.$clone) {
                data.$clone.css({
                    'display': 'none'}
                );
                $el.css("visibility", "visible");
            }
        }
    };

    // recalculate dimensions of the hidden element and update the clone dimensions if necessary
    GlobalErrors.prototype.onResize = function($el) {
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
