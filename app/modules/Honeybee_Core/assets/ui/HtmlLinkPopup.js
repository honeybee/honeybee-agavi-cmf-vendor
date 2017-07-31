define([
    "Honeybee_Core/Widget",
    "magnific-popup"
], function(Widget, mfp) {

    var default_options = {
        prefix: 'Honeybee_Core/ui/HtmlLinkPopup',
        triggerSelector: '.htmllink-popup-trigger',
        previewSelector: '.htmllink-popup-preview a',
        inputHrefSelector: '.htmllink__href',
        inputTextSelector: '.htmllink__text',
        inputTitleSelector: '.htmllink__title',
        inputTargetSelector: '.htmllink__target',
        inputRelSelector: '.htmllink__rel',
        inputHreflangSelector: '.htmllink__hreflang',
        inputDownloadSelector: '.htmllink__download',
        btnAcceptSelector: '.htmllink-popup__accept',
        btnCancelSelector: '.htmllink-popup__cancel',
        btnResetSelector: '.htmllink-popup__reset',
        btnClearSelector: '.htmllink-popup__clear',
        mfp: {
            type: 'inline',
            focus: '.htmllink__href',
            disableOn: null,
            preloader: false,
            removalDelay: 0,
            mainClass: 'htmllink-actual-popup',
            // enableEscapeKey: true,
            // closeMarkup: '<button title="%title%" type="button" class="mfp-close">&#215;</button>',
            // tClose: 'Close (Esc)',
            showCloseBtn: false,
            modal: true
        }
    };

    function HtmlLinkPopup(dom_element, options) {
        var that = this;
        this.init(dom_element, default_options);
        this.addOptions(options);

        this.$trigger = this.$widget.find(this.options.triggerSelector).first();
        this.$preview = this.$widget.find(this.options.previewSelector).first();
        this.$input_href = this.$widget.find(this.options.inputHrefSelector).first();
        this.$input_text = this.$widget.find(this.options.inputTextSelector).first();
        this.$input_title = this.$widget.find(this.options.inputTitleSelector).first();
        this.$input_target = this.$widget.find(this.options.inputTargetSelector).first();
        this.$input_rel = this.$widget.find(this.options.inputRelSelector).first();
        this.$input_hreflang = this.$widget.find(this.options.inputHreflangSelector).first();
        this.$input_download = this.$widget.find(this.options.inputDownloadSelector).first();
        this.$btn_accept = this.$widget.find(this.options.btnAcceptSelector).first();
        this.$btn_cancel = this.$widget.find(this.options.btnCancelSelector).first();
        this.$btn_reset = this.$widget.find(this.options.btnResetSelector).first();
        this.$btn_clear = this.$widget.find(this.options.btnClearSelector).first();

        if (this.$trigger.length === 0 && this.$input_href.length === 0) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }

        this.initial = {
            href: this.$input_href.val(),
            text: this.$input_text.val(),
            title: this.$input_title.val(),
            target: this.$input_target.prop('checked') === true ? this.$input_target.val() : '',
            rel: this.$input_rel.val(),
            hreflang: this.$input_hreflang.val(),
            download: this.$input_download.prop('checked') === true
        };

        this.default_mfp_settings = {
            callbacks: {
                beforeOpen: function() {
                    that.storeInputValues();
                    // when elemened is focused, some mobile browsers in some cases zoom in
                    if ($(window).width() < 1024) {
                        this.st.focus = false;
                    } else {
                        this.st.focus = that.options.inputHrefSelector;
                    }
                }
            }
        };

        this.mfp_settings = _.merge({}, this.default_mfp_settings, this.options.mfp);

        this.$trigger.magnificPopup(this.mfp_settings);

        this.$btn_accept.on('click', function(ev) {
            ev.preventDefault();
            that.$trigger.magnificPopup('close');
            that.$trigger.focus();
            that.updatePreview();
        });

        this.$btn_cancel.on('click', function(ev) {
            ev.preventDefault();
            that.$trigger.magnificPopup('close');
            that.$trigger.focus();
            that.restoreInputValues();
            that.updatePreview();
        });

        this.$btn_reset.on('click', function(ev) {
            ev.preventDefault();
            that.resetInputsToInitialValues();
            that.$input_href.focus();
            that.updatePreview();
        });

        this.$btn_clear.on('click', function(ev) {
            ev.preventDefault();
            that.clearInputs();
            that.$input_href.focus();
            that.updatePreview();
        });

        this.updatePreview();
    }

    HtmlLinkPopup.prototype = new Widget();
    HtmlLinkPopup.prototype.constructor = HtmlLinkPopup;

    HtmlLinkPopup.prototype.storeInputValues = function() {
        this.orig = {
            href: this.$input_href.val(),
            text: this.$input_text.val(),
            title: this.$input_title.val(),
            target: this.$input_target.prop('checked') === true ? this.$input_target.val() : '',
            rel: this.$input_rel.val(),
            hreflang: this.$input_hreflang.val(),
            download: this.$input_download.prop('checked') === true
        };
    }

    HtmlLinkPopup.prototype.restoreInputValues = function() {
        this.$input_href.val(this.orig.href);
        this.$input_text.val(this.orig.text);
        this.$input_title.val(this.orig.title);
        this.$input_target.val(this.orig.target);
        this.$input_target.prop('checked', this.orig.target === '_blank');
        this.$input_rel.val(this.orig.rel);
        this.$input_hreflang.val(this.orig.hreflang);
        this.$input_download.prop('checked', this.orig.download);
    }

    HtmlLinkPopup.prototype.updatePreview = function() {
        this.$preview.prop('href', this.$input_href.val());
        var text = this.$input_text.val();
        if (text.length > 0) {
            this.$preview.text(this.$input_text.val());
        } else {
            this.$preview.text(this.$input_href.val());
        }
        this.$preview.prop('title', this.$input_title.val());
        this.$preview.prop('hreflang', this.$input_hreflang.val());
        if (this.$input_download.prop('checked')) {
            this.$preview.attr('download', 'download');
        } else {
            this.$preview.removeAttr('download');
        }
    }

    HtmlLinkPopup.prototype.resetInputsToInitialValues = function() {
        this.$input_href.val(this.initial.href);
        this.$input_text.val(this.initial.text);
        this.$input_title.val(this.initial.title);
        this.$input_target.val(this.initial.target);
        this.$input_target.prop('checked', this.initial.target === '_blank');
        this.$input_rel.val(this.initial.rel);
        this.$input_hreflang.val(this.initial.hreflang);
        this.$input_download.prop('checked', this.initial.download);
    }

    HtmlLinkPopup.prototype.clearInputs = function() {
        this.$input_href.val('');
        this.$input_text.val('');
        this.$input_title.val('');
        this.$input_target.val('');
        this.$input_target.prop('checked', false);
        this.$input_rel.val('');
        this.$input_hreflang.val('');
        this.$input_download.prop('checked', false);
    }

    return HtmlLinkPopup;
});
