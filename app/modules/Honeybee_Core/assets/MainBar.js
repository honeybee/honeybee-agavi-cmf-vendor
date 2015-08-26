define([
    "Honeybee_Core/Widget",
], function(Widget) {

    "use strict";

    var default_options = {
        "acts_as_radiogroup": true,
        "nested": [
            {
                "foo": "foo"
            },
            {
                "bar": "bar"
            }
        ]
    };

    var MainBar = function(dom_element, options) {
        this.init(dom_element, default_options);
        this.addOptions(options);

        this.selectmode = false;

        this.$triggers = this.$widget.find('.mainbar__trigger');
        this.$batch_mode_trigger = this.$widget.find('#mainbar__trigger-batch');

        if (!this.$batch_mode_trigger || !this.$triggers) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }

        if (this.options.acts_as_radiogroup) {
            this.actsAsRadioGroup();
        }

        this.logDebug(this.getPrefix() + " behaviour applied. Options:", this.options);

        this.initListeners();

        jsb.fireEvent('MainBar::CREATED', {
            "id": 6,
            "date": new Date().toString(),
            "name": this.getPrefix(),
            "options": this.options
        });

    };

    MainBar.prototype = new Widget();
    MainBar.prototype.constructor = MainBar;

    MainBar.prototype.getPrefix = function() {
        return 'Core/MainBar';
    }

    // only one MainBar menu should be open at a time
    MainBar.prototype.actsAsRadioGroup = function() {
        var that = this;

        this.$triggers.on('click', function(ev) {
            that.$triggers.filter(':checked').not(this).removeAttr('checked'); // uncheck all other checked triggers

            if (this.id === 'mainbar__trigger-batch' && this.checked === true) {
                that.selectmode = true;
                $('body').removeClass('selectmode').addClass('selectmode');
            } else {
                that.selectmode = false;
                $('body').removeClass('selectmode');
            }

        });
    }

    MainBar.prototype.initListeners = function() {
        var that = this;

        $(".list tbody" ).on("click", 'tr', function(ev) {
            if (that.selectmode) {
                //if (ev.target.type !== 'checkbox') {
                if (ev.target.tagName !== 'INPUT' && ev.target.tagName !== 'LABEL'
                    && ev.target.tagName !== 'A' && ev.target.tagName !== 'BUTTON') {
                    $(this).find('input[type=checkbox].list__toggleEntry').click();
                }
            }
        });

        this.$entry_checkboxes = $('.list__toggleEntry');
        this.$toggle_all_checkbox = $('.list__toggleAll');

        this.$toggle_all_checkbox.on('click', function(ev) {
            that.$entry_checkboxes.prop('checked', this.checked);
            update_checkbox_status(ev);
        });

        var update_checkbox_status = function(ev) {
            if (that.$entry_checkboxes.length ===  $('.list__toggleEntry:checked').length) {
                that.$select_all_toggle.removeClass('hb-icon-checkmark-2').addClass('hb-icon-checkmark');
                that.$toggle_all_checkbox.prop('checked', true);
            } else {
                that.$select_all_toggle.removeClass('hb-icon-checkmark').addClass('hb-icon-checkmark-2');
                that.$toggle_all_checkbox.prop('checked', false);
            }
        };

        this.$select_all_toggle = $('.select-all-toggle');
        this.$select_all_toggle.on('click', function(ev) {
            ev.preventDefault();
            that.$toggle_all_checkbox.trigger('click');
        });

        $('.invert-all-selected-toggle').on('click', function(ev) {
            ev.preventDefault();
            that.$entry_checkboxes.each(function(index, elem) {
                elem.checked = !elem.checked;
            });
            update_checkbox_status(ev);
        });

        this.$entry_checkboxes.on('change', function(ev) {
            update_checkbox_status(ev);
        });
    }

    return MainBar;

}, function (err) {
    // err has err.requireType (timeout, nodefine, scripterror)
    // and err.requireModules (an array of module Ids/paths)
});

