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
        this.$validity_input = this.$widget.find('> .hb-field__value > .hb-field__list-validity');
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
        if (this.isWriteable()) {
            this.registerEmbedTypeSelector();
        }
        this.updateUi();
    };

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
        $item.find('> .hb-embed-actions .hb-embed-action').on('click', function(event) {
            self.handleAction(event, $item);
            return false;
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
            this.$widget.find('> .hb-embed-actions .hb-action__add-embed').removeClass('visuallyhidden');
        } else {
            this.$widget.find('> .hb-embed-actions .hb-action__add-embed').addClass('visuallyhidden');
        }

        if(this.options.min_count !== null &&
            this.$entities_list.find('> .hb-embed-item').length < this.options.min_count
        ) {
            this.$validity_input.prop('checked', false);
        } else {
            this.$validity_input.prop('checked', true);
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
