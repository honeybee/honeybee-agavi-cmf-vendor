define([
    "Honeybee_Core/ui/EmbeddedEntityList",
    "selectize"
], function(EmbeddedEntityList) {

    var default_options = {
        prefix: "Honeybee_Core/ui/ReferenceEntityList"
    };

    function ReferenceEntityList(dom_element, options) {
        if (dom_element) {
            EmbeddedEntityList.call(this, dom_element, _.merge({}, default_options, options));
        }
        this.options.remove_label = this.options.remove_label || "×";
        this.options.remove_title = this.options.remove_title || "Remove";
        this.options.remove_button_class = this.options.remove_button_class || "remove";
    }

    ReferenceEntityList.prototype = new EmbeddedEntityList();
    ReferenceEntityList.prototype.constructor = ReferenceEntityList;

    ReferenceEntityList.prototype.loadSuggestions = function(query, callback) {
        if (!query.length) return callback();

        $.ajax({
            url: this.buildSuggestUrl(query, this.getActiveReferenceType()),
            type: 'GET',
            dataType: 'json',
            error: function() { callback(); },
            success: function(res) { callback(res.data); }
        });
    };

    ReferenceEntityList.prototype.onItemAdded = function(entity_identifer) {
        this.appendEntityReference(
            this.$select[0].selectize.options[entity_identifer],
            this.getActiveReferenceType()
        );
    };

    ReferenceEntityList.prototype.onItemRemoved = function(ref_id) {
        if (this.options.inline_mode === true) {
            this.$entities_list.empty();
            this.cloneItem(this.templates[this.getActiveReferenceType()]);
            this.$entities_list.find('> .hb-embed-item > .hb-embed-item__content > .hb-embed-actions').remove();
            this.purgeSelectizeQueryCache();
        } else {
            var item_query = ".attribute_value_referenced_identifier input[value='{REF_ID}']".replace('{REF_ID}', ref_id);
            this.$entities_list.find('> li').filter(function() {
                return $(item_query, this).length > 0;
            }).remove();
        }
    };

    ReferenceEntityList.prototype.appendEntityReference = function(reference_embed_data, type_prefix) {
        var self = this;
        var attribute_name = self.$widget.find('.hb-field__value input[type=hidden]').prop('name');

        jsb.fireEvent('WIDGET:BUSY_LOADING', {
            'type': 'start',
            'attribute_name': attribute_name
        });

        $.ajax({
            url: this.buildRenderUrl(type_prefix),
            type: 'POST',
            dataType: 'html',
            data: this.buildRenderPostData(reference_embed_data, type_prefix),
            error: function() {
                self.logError("An unexpected error occured while rendering reference-embed serverside.", arguments);
            },
            success: function(html_item) {
                if (self.options.inline_mode === true) {
                    self.$entities_list.html(html_item);
                } else {
                    self.$entities_list.append(html_item);
                }
                self.registerItem(self.$entities_list.find('> li:last-child'));
            },
            complete: function() {
                jsb.fireEvent('WIDGET:BUSY_LOADING', {
                    'type': 'stop',
                    'attribute_name': attribute_name
                });
            }
        });
    };

    ReferenceEntityList.prototype.buildSuggestUrl = function(query, type_prefix) {
        return this.getSuggestOptionsFor(type_prefix).suggest_url.replace('%7BQUERY%7D', encodeURIComponent(query));
    };

    ReferenceEntityList.prototype.buildRenderUrl = function(type_prefix) {
        var suggest_options = this.getSuggestOptionsFor(type_prefix);
        var parent_group_parts = this.getParentGroupParts(true);
        var input_group_parts = (parent_group_parts.length === 0)
            ? this.options.input_group
            : _.clone(parent_group_parts);
        input_group_parts.push(this.options.fieldname, this.cur_item_index + 1);

        var embed_path_parts = [ type_prefix + '[0]', this.options.fieldname ];
        var parent_items = this.$widget.parents('.hb-embed-item');
        var i, $parent_item;
        for (i = 0; i < parent_items.length; i++) {
            $parent_item = $(parent_items[i]);
            embed_path_parts.push($parent_item.data('embed-type') + '[0]');
            // pop-off next group, which is the embed index (statically set to 0 above)
            parent_group_parts.pop();
            // then the next pop gives the fieldname
            embed_path_parts.push(parent_group_parts.pop());
        }
        embed_path_parts.reverse();

        return suggest_options.render_uri_tpl.replace(
            encodeURIComponent('{EMBED_PATH}'),
            embed_path_parts.join('.')
        ) + '?input_group=' + input_group_parts.join(',');
    };

    ReferenceEntityList.prototype.buildRenderPostData = function(reference_embed_data, type_prefix) {
        var embed_data = { '@type': type_prefix };
        var parent_items = this.$widget.parents('.hb-embed-item');
        var parent_group_parts = this.getParentGroupParts();
        var i, prop, $parent_item;
        var prefill_data = next_data = {};

        for (prop in reference_embed_data) {
            if (reference_embed_data.hasOwnProperty(prop) && prop !== 'identifier') {
                embed_data[prop] = reference_embed_data[prop];
            } else if (prop === 'identifier') {
                embed_data.referenced_identifier = reference_embed_data[prop];
            }
        }

        prefill_data[this.options.fieldname] = [ embed_data ];
        for (i = 0; i < parent_items.length; i++) {
            $parent_item = $(parent_items[i]);
            prefill_data['@type'] = $parent_item.data('embed-type');
            next_data = {};
            parent_group_parts.pop(); // pop-off next group, which is the embed index
            next_data[parent_group_parts.pop()] = [ prefill_data ]; // then the next pop gives the fieldname
            prefill_data = next_data;
        }

        return prefill_data;
    };

    ReferenceEntityList.prototype.fetchReferencedEntityIdentifierFromEmbed = function($item) {
        var $embed_identifier_input = $item.find('.attribute_value_referenced_identifier input');
        if ($embed_identifier_input.length !== 1) {
            return false;
        }

        return $embed_identifier_input.val() || false;
    };

    ReferenceEntityList.prototype.getActiveReferenceType = function() {
        // @todo need to introspect the dropdown to figure out the currently active type for multi references
        var $active_type = this.$widget.find('> .hb-field__content > .hb-embed-type-selector .activity').first();
        return $active_type.attr('href').replace('#', '');
    };

    ReferenceEntityList.prototype.getSuggestOptionsFor = function(type_prefix) {
        if (!this.options.suggest_options[type_prefix]) {
            throw "Missing display_fields settings for type " + type_prefix + " within given suggest_options.";
        }
        return this.options.suggest_options[type_prefix];
    };

    ReferenceEntityList.prototype.purgeSelectizeQueryCache = function() {
        // we need to get selectize to reload here, as it's caching our suggest requests
        // @see https://github.com/brianreavis/selectize.js/issues/704
    };

    ReferenceEntityList.prototype.buildSelectForInitalValues = function() {
        var $select = $("<select></select>");
        $select.attr("multiple", true);
        _.each(this.options.initial_value, function(select_value) {
            var $option = $("<option></option>");
            $option.attr("value", select_value.value);
            $option.text(select_value.label);
            $option.attr("selected", true);
            $select.append($option);
        });

        return $select;
    };

    //
    // EmbeddedEntityList overrides
    //

    ReferenceEntityList.prototype.initUi = function() {
        var self = this;
        var suggest_options = this.getSuggestOptionsFor(this.getActiveReferenceType());

        this.$select = this.buildSelectForInitalValues();
        this.$widget.find('> .hb-field__content > .hb-autocomplete').replaceWith(this.$select);
        this.$select.selectize({
            maxItems: this.options.max_count,
            minItems: this.options.min_count,
            hideSelected: true,
            highlight: true,
            persist: false,
            create: false,
            valueField: suggest_options.value_field,
            labelField: suggest_options.suggest_field,
            searchField: suggest_options.suggest_field,
            plugins: {
                "remove_button": {
                    label: this.options.remove_label,
                    title: this.options.remove_title,
                    className: this.options.remove_button_class
                }
            },
            load: this.loadSuggestions.bind(this),
            onItemAdd: this.onItemAdded.bind(this),
            onItemRemove: this.onItemRemoved.bind(this)
        });

        if (this.options.isReadonly === true) {
            this.$select[0].selectize.disable();
        }
    };

    ReferenceEntityList.prototype.handleAction = function(event, $target_item) {
        var reference_id = this.fetchReferencedEntityIdentifierFromEmbed($target_item);
        var $action = $(event.currentTarget);
        if ($action.hasClass('hb-embed-action__delete')) {
            this.$select[0].selectize.removeItem(reference_id);
            this.purgeSelectizeQueryCache();
        }
        EmbeddedEntityList.prototype.handleAction.call(this, event, $target_item);
    };

    return ReferenceEntityList;
});
