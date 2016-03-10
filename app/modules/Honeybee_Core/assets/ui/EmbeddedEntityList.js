define([
    "Honeybee_Core/Widget"
], function(Widget) {

    var default_options = {
        prefix: "Honeybee_Core/ui/EmbeddedEntityList",
        embed_tpl_selector: '> .hb-field__value > .hb-entity-templates > .hb-embed-item'
    };

    function EmbeddedEntityList(dom_element, options) {
        var self = this;
        if (!dom_element) {
            return;
        }

        this.init(dom_element, _.merge({}, default_options, options));
        this.is_valid = false;
        this.cur_item_index = -1;
        this.templates = this.loadEmbedTemplates();
        this.$entities_list = this.$widget.find('> .hb-field__value > .hb-entity-list:not(.hb-entity-templates)');
        this.$entities_list.find('> li').each(function(idx, item) {
            self.registerItem($(item));
        });
        this.initUi();
    }

    EmbeddedEntityList.prototype = new Widget();
    EmbeddedEntityList.prototype.constructor = EmbeddedEntityList;

    EmbeddedEntityList.prototype.initUi = function() {
        var self = this;

        jsb.whenFired('ENTITY_LIST:OPEN_ENTITY_THAT_CONTAINS', function(values, event_name) {
            if (values.element_id) {
                // $(id) would need escaping for ids with dots…
                self.openItemThatContains(document.getElementById(values.element_id));
            }
        });

        if (this.isWriteable()) {
            this.registerEmbedTypeSelector();
        }
        this.registerItemsHighlighting();
        this.updateUi();

        $('body').addClass('hb-js-embedded-entity-list--is_loaded');
    };

    EmbeddedEntityList.prototype.registerItemsHighlighting = function(element) {
        if ($('body.hb-js-embedded-entity-list--is_loaded').length) {
            return;
        }
        // manage highlighting of list items
        $(document).on('focus', ':input, label, a', function(ev){
            $item = $(ev.target).parents('.hb-embed-item').first();
            $('.hb-embed-item').not($item).removeClass('hb-js-embed-item--is_focused')
            $item.addClass('hb-js-embed-item--is_focused');
        })
    }

    EmbeddedEntityList.prototype.openItemThatContains = function(element) {
        if(!element) {
            this.logError('Please, provide an element to search for.');
            return;
        }
        var $items = this.$widget.find('> .hb-field__value > .hb-entity-list > .hb-embed-item');
        var $item = $items.has(element);
        if ($item.length > 0) {
            this.expandItem($item);
        }
    };

    EmbeddedEntityList.prototype.expandItem = function(item) {
        $target_item = this.$widget.find(item);
        $target_item.find('> .hb-embed-item__trigger').prop("checked", true);
    }

    EmbeddedEntityList.prototype.collapseItem = function(item) {
        $target_item = this.$widget.find(item);
        $target_item.find('> .hb-embed-item__trigger').prop("checked", false);
    }

    EmbeddedEntityList.prototype.registerEmbedTypeSelector = function() {
        var self = this;
        this.$widget.find('> .hb-field__content > .hb-embed-type-selector .activity').on('click', function(event) {
            var embed_type = $(event.currentTarget).attr('href').replace('#', '');
            if (self.templates[embed_type]) {
                self.cloneItem(self.templates[embed_type]);
            } else {
                self.logError('Cannot find template.');
            }
            return false;
        });
    };

    EmbeddedEntityList.prototype.loadEmbedTemplates = function() {
        var self = this;
        var templates = {};
        this.$widget.find(this.options.embed_tpl_selector).each(function(idx, template) {
            var $template = $(template).remove();
            var tpl_name = $(template).data('embed-type');
            if (tpl_name) {
                $template.find('> :input[name$="[__template]"]').remove();
                templates[tpl_name] = $template;
                self.cur_item_index++;
            }
        });

        return templates;
    };

    EmbeddedEntityList.prototype.cloneItem = function($item) {
        if(!this.isClonable()) {
            this.logDebug('Cloning is not allowed. The limit of items could have been reached.');
            return false;
        }
        var self = this;
        var $clone = $item.clone();
        var input_group = $clone.data('input-group');
        var fixed_input_group = input_group.replace(/\[(\d+)\]$/, function(matches) {
            return "[" + self.cur_item_index + "]";
        });

        this.$entities_list.append($clone);

        $clone.find('.attribute_value_identifier input').val('');
        $clone.removeAttr('data-hb-item-identifier');
        $clone.attr('data-input-group', fixed_input_group);
        $clone.data('input-group', fixed_input_group);
        $clone.find(':input').each(function(idx, input) {
            var $input = $(input);
            var input_name = $input.attr('name');
            if (input_name) {
                $input.attr('name', $input.attr('name').replace(input_group, fixed_input_group));
            }
        });

        this.registerItem($clone);
        this.updateUi();

        return $clone;
    };

    EmbeddedEntityList.prototype.registerItem = function($item) {
        var self = this;
        this.cur_item_index++;

        $item.find('.jsb__').each(function(idx, behavior_el) {
            if ($(behavior_el).parents('.hb-entity-templates').length === 0) {
                $(behavior_el).removeClass('jsb__').addClass('jsb_');
            }
        });
        jsb.applyBehaviour($item);
        $item.find('> .hb-embed-item__header > .hb-embed-item__controls > .hb-embed-actions .hb-embed-action').on('click', function(event) {
            self.handleAction(event, $item);
            // prevent double event triggering when using label+input inside hb-embed-action
            event.preventDefault();
        });

        $item.find('.hb-embed-item__trigger').first().on('change', function(event) {
            $item.toggleClass('hb-embed-item--expanded');
        });

        // unique id for the collapse/expand CSS behaviour for the current item and inner ones
        $item.find('.hb-embed-item')
            .addBack()
            .each(function(index, el) {
                var $inner_item = $(el);
                trigger_id = $inner_item.data('input-group') + '-' + Math.floor(Math.random() * 100000);
                $inner_item.find('label.hb-embed-item__toggle').attr('for', trigger_id);
                $inner_item.find('input[type=checkbox].hb-embed-item__trigger').attr('id', trigger_id);
            });
    };

    EmbeddedEntityList.prototype.handleAction = function(event, $target_item) {
        var $action = $(event.currentTarget);
        if (!this.isWriteable()) {
            return false;
        }

        if ($action.hasClass('hb-embed-action__up')) {
            $target_item.insertBefore($target_item.prev());
        } else if ($action.hasClass('hb-embed-action__down')) {
            $target_item.insertAfter($target_item.next());
        } else if ($action.hasClass('hb-embed-action__duplicate')) {
            this.cloneItem($target_item);
        } else if ($action.hasClass('hb-embed-action__delete')) {
            $target_item.remove();
        } else {
            this.logDebug('EmbeddedEntityList.handleAction -> could not be resolve to a valid action.')
        }
        this.updateUi();
    };

    EmbeddedEntityList.prototype.updateUi = function() {
        if(this.isClonable()) {
            this.$widget.find('> .hb-embed-item__header > .hb-embed-item__controls > .hb-embed-actions .hb-action__add-embed').removeClass('visuallyhidden');
        } else {
            this.$widget.find('> .hb-embed-item__header > .hb-embed-item__controls > .hb-embed-actions .hb-action__add-embed').addClass('visuallyhidden');
        }

        if(this.options.min_count !== null &&
            this.$entities_list.find('> .hb-embed-item').length < this.options.min_count
        ) {
            this.is_valid = false;
        } else {
            this.is_valid = true;
        }
    };

    EmbeddedEntityList.prototype.isClonable = function() {
        return !this.options.max_count || this.$entities_list.find('> .hb-embed-item').length < this.options.max_count;
    }

    EmbeddedEntityList.prototype.isWriteable = function() {
        // @todo as soon as we have explicit support for !writable, please use here
        return !(this.isReadonly() || this.isDisabled());
    }

    EmbeddedEntityList.prototype.getParentGroupPath = function() {
        var $parent_item = this.$widget.parents('.hb-embed-item').first();

        return $parent_item ? $parent_item.data('input-group') || '' : '';
    };

    EmbeddedEntityList.prototype.getParentGroupParts = function(include_form_group) {
        include_form_group = include_form_group || false;
        var input_group_parts = [];
        var $parent_item = this.$widget.parents('.hb-embed-item').first();
        var parent_input_group = this.getParentGroupPath();
        if (parent_input_group.length > 0) {
            input_group_parts = parent_input_group.match(/([\w_\d]+)(\[([\w_\d])\])*/ig);
            if (!include_form_group) {
                input_group_parts.shift(); // shift off form-group e.g. edit, create etc.
            }
        }

        return input_group_parts;
    };

    return EmbeddedEntityList;
});
