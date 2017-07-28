define([
    "Honeybee_Core/Widget",
    "magnific-popup"
], function(Widget, mfp) {

    var default_options = {
        prefix: 'Honeybee_Core/ui/HtmlLinkPopup',
        triggerSelector: '.htmllink-popup-trigger',
        hrefInputSelector: '.htmllink__href',
        acceptBtnSelector: '.htmllink-popup__accept',
        mfp: {
            type: 'inline',
            focus: '.htmllink__href';
            disableOn: null,
            preloader: false,
            removalDelay: 0,
            enableEscapeKey: false,
            mainClass: 'htmllink-actual-popup',
            showCloseBtn: false,
            closeMarkup: ''
            //closeMarkup: '<button title="Dialog schließen" type="button" class="mfp-close">Schließen &#215;</button>',
        }
    };

    function HtmlLinkPopup(dom_element, options) {
        var that = this;
        this.init(dom_element, default_options);
        this.addOptions(options);

        this.$trigger = this.$widget.find(this.options.triggerSelector).first();
        this $href_input = this.$widget.find(this.options.hrefInputSelector).first();
        this $accept_btn = this.$widget.find(this.options.acceptBtnSelector).first();
        if (this.$trigger.length === 0) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }

        this.original_trigger_text = this.$trigger.text();

        this.default_mfp_settings = {
            callbacks: {
                beforeOpen: function() {
                    // when elemened is focused, some mobile browsers in some cases zoom in
                    if ($(window).width() < 1024) {
                        this.st.focus = false;
                    } else {
                        this.st.focus = that.options.hrefInputSelector;
                    }
                }
            }
        };

        this.mfp_settings = _.merge({}, this.default_mfp_settings, this.options.mfp);

        this.$trigger.magnificPopup(this.mfp_settings);

        this.$accept_btn.on('click', function(ev) {
            that.$trigger.magnificPopup('close');
            that.$trigger.focus();
            var href = that.$href_input.val();
            if (href === '') {
                that.$trigger.text(that.original_trigger_text);
            } else {
                that.$trigger.text(href);
            }
            ev.preventDefault();
        });
    }

    HtmlLinkPopup.prototype = new Widget();
    HtmlLinkPopup.prototype.constructor = HtmlLinkPopup;

    return HtmlLinkPopup;
});
