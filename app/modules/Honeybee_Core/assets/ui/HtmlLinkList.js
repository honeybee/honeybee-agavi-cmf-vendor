define([
    "Honeybee_Core/Widget"
], function(Widget) {

    var default_options = {
        prefix: 'Honeybee_Core/ui/HtmlLinkList',
        btnAddSelector: '.htmllink-add'
    };

    function HtmlLinkList(dom_element, options) {
        var that = this;
        this.init(dom_element, default_options);
        this.addOptions(options);

        this.$btn_add = this.$widget.find(this.options.btnAddSelector).first();

        if (this.$btn_add === 0) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }

        this.$btn_add.on('click', function(ev) {
            ev.preventDefault();
            // add new entry
            console.log(that.$widget.find('.htmllink'));
        });

        var only_accept_where = {'field_name': that.options.field_name};
        // console.log(only_accept_where);
        jsb.on('HTMLLINKPOPUP:REMOVED', only_accept_where, function(values, event_name) {
            console.log(values, event_name);
        });
    }

    HtmlLinkList.prototype = new Widget();
    HtmlLinkList.prototype.constructor = HtmlLinkList;

    HtmlLinkList.prototype.storeInputValues = function() {
    }

    HtmlLinkList.prototype.restoreInputValues = function() {
    }

    return HtmlLinkList;
});
