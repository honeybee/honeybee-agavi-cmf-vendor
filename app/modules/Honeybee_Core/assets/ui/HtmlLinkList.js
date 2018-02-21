define([
    "Honeybee_Core/Widget",
    "jsb",
    "jquery"
], function(Widget, jsb, $) {

    var default_options = {
        prefix: 'Honeybee_Core/ui/HtmlLinkList',
        listSelector: '.htmllinklist',
        btnAddSelector: '.htmllink-add',
        handleSelector: '.htmllink-handle',
        placeholderSelector: '.htmllinklist__item.sortable-placeholder',
        listItemSelector: '.htmllinklist__item',
        emptyLinkSelector: '.htmllinklist__item.empty',
        templateSelector: '.htmllinklist-template',
        classListItem: 'htmllinklist__item',
        classUseHandle: 'use-handle',
        useHandle: false
    };

    function HtmlLinkList(dom_element, options) {
        var that = this;
        this.init(dom_element, default_options);
        this.addOptions(options);

        this.$list = this.$widget.find(this.options.listSelector).first();
        this.$btn_add = this.$widget.find(this.options.btnAddSelector).first();

        if (this.$btn_add.length === 0 || this.$list.length === 0) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }

        if (this.options.useHandle) {
            this.$list.addClass(this.options.classUseHandle);
        }

        this.$widget.find(this.options.emptyLinkSelector).remove();

        this.$template = this.$widget.find(this.options.templateSelector).first();
        this.$placeholder = this.$widget.find(this.options.placeholderSelector).first();
        this.$placeholder.detach();

        this.$items = this.$list.children();
        this.idx = this.$list.children().length;
        this.template = this.$template.html();

        this.$template.remove();

        this.$btn_add.on('click', function(ev) {
            ev.preventDefault();
            that.addNewLink();
        });

        var accept_filter = {'field_name': that.options.field_name};
        jsb.on('HTMLLINKPOPUP:REMOVED', accept_filter, function(payload) {
            if (payload && payload['widget_element']) {
                var $li = payload['widget_element'].parents(that.options.listItemSelector);
                $li.remove();
                that.makeSortable();
            }
        });

        this.makeSortable();
    }

    HtmlLinkList.prototype = new Widget();
    HtmlLinkList.prototype.constructor = HtmlLinkList;

    HtmlLinkList.prototype.addNewLink = function() {
        this.idx = this.idx+1;
        var html = this.template;
        html = this.replaceAll(html, this.options.grouped_base_path+'[IDX]', this.options.grouped_base_path+'['+this.idx+']');
        html = this.replaceAll(html, this.options.field_id+'IDX', this.options.field_id+'IDX'+this.idx);
        this.$list.append(html);
        jsb.applyBehaviour(this.$list.get(0)); // HtmlLinkPopup
        this.makeSortable();
    }

    HtmlLinkList.prototype.replaceAll = function(str, find, replace) {
        return str.replace(new RegExp(this.escapeRegExp(find), 'g'), replace);
    }

    HtmlLinkList.prototype.makeSortable = function() {
        var that = this;
        var isHandle = false;
        var $dragging = null;
        // this.$items = this.$items.add(this.$list.children());
        this.$items = this.$list.children();

        if (this.options.useHandle) {
            this.$items.find(this.options.handleSelector).mousedown(function() {
                isHandle = true;
            }).mouseup(function() {
                isHandle = false;
            });
        }

        this.$items.attr('draggable', 'true');

        this.$items.on('dragstart.htmllinklistdnd', function(ev) {
            if (that.options.useHandle && !isHandle) {
                return false;
            }
            isHandle = false;
            var dt = ev.originalEvent.dataTransfer;
            dt.effectAllowed = 'move';
            dt.setData('Text', 'none');
            $dragging = $(this);
            $dragging.addClass('sortable-dragging').index();
        });

        this.$items.on('dragend.htmllinklistdnd', function() {
            if (!$dragging) {
                return;
            }
            $dragging.removeClass('sortable-dragging').show();
            that.$placeholder.detach();
            $dragging = null;
        });

        this.$items.add(this.$placeholder).on('dragover.htmllinklistdnd dragenter.htmllinklistdnd drop.htmllinklistdnd', function(ev) {
            if (!that.$items.is($dragging)) {
                return true;
            }
            if (ev.type == 'drop') {
                ev.stopPropagation();
                that.$placeholder.after($dragging);
                $dragging.trigger('dragend.htmllinklistdnd');
                return false;
            }
            ev.preventDefault();
            ev.originalEvent.dataTransfer.dropEffect = 'move';
            var li = this;
            var $li = $(li);
            if (that.$items.is(li)) {
                $dragging.hide();
                var method = that.$placeholder.index() < $li.index() ? 'after' : 'before';
                $li[method](that.$placeholder);
            } else if (!that.$placeholder.is(li) && !$li.children().length) {
                that.$placeholder.detach();
                $li.append(that.$placeholder);
            }
            return false;
        });
    }

    return HtmlLinkList;
});
