define([
    "Honeybee_Core/ui/EmbeddedEntityList",
    "jsb",
    "selectize",
    "jquery",
    "lodash",
    "Honeybee_Core/ui/SelectizePlugins"
], function(EmbeddedEntityList, jsb, selectize, $, _) {

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

        this.$select[0].selectize.$control.addClass('loading');
        $.ajax({
            url: this.buildSuggestUrl(query, this.getActiveReferenceType()),
            type: 'GET',
            dataType: 'json',
            error: function() { callback(); },
            success: function(res) { callback(res.data); },
            complete: function () {
                this.$select[0].selectize.$control.removeClass('loading');
            }.bind(this)
        });
    };

    ReferenceEntityList.prototype.onItemAdded = function(entity_identifer) {
        // selectize won't trigger 'item_remove' when replacing in a single-item control
        if (this.options.max_count === 1) {
            this.$entities_list.find('> li').remove();
        }

        this.appendEntityReference(
            this.$select[0].selectize.options[entity_identifer],
            this.getActiveReferenceType()
        );
    };

    ReferenceEntityList.prototype.onItemRemoved = function(ref_id) {
        if (this.options.inline_mode === true) {
            this.$entities_list.empty();
            this.cloneItem(this.templates[this.getActiveReferenceType()]); // inline-mode always has an item of each type
            this.$entities_list.find('> .hb-embed-item > .hb-embed-item__content > .hb-embed-actions').remove();
            this.purgeSelectizeQueryCache();
        } else {
            var item_query = ".attribute_value_referenced_identifier input[value='{REF_ID}']".replace('{REF_ID}', ref_id);
            this.$entities_list.find('> li').filter(function() {
                return $(item_query, this).length > 0;
            }).remove();
            this.removeEntityPlaceholder(ref_id);
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
            beforeSend: function() {
                if (self.options.inline_mode === false) {
                    // appendEntityPlaceholder increments the cur_item_index
                    self.appendEntityPlaceholder(reference_embed_data.identifier);
                }
            },
            error: function() {
                self.removeEntityPlaceholder(reference_embed_data.identifier);
                self.logError("An unexpected error occured while rendering reference-embed serverside.", arguments);
            },
            success: function(html_item) {
                if (self.options.inline_mode === true) {
                    self.$entities_list.html(html_item);
                } else {
                    self.replaceEntityPlaceholder(reference_embed_data.identifier, html_item);
                }
                // don't increment index (appendEntityPlaceholder already did it)
                self.registerItem(self.$entities_list.find('> li:last-child'), false);
            },
            complete: function() {
                jsb.fireEvent('WIDGET:BUSY_LOADING', {
                    'type': 'stop',
                    'attribute_name': attribute_name
                });
            }
        });
    };

    ReferenceEntityList.prototype.appendEntityPlaceholder = function(entity_identifer) {
        var $placeholder = this.cloneItem(this.templates[this.getActiveReferenceType()], true);
        if (!$placeholder) {
            throw Error('Unable to clone item.');
        }
        $placeholder.addClass('hb-embed-item-placeholder');
        $placeholder.attr('data-placeholder-identifier', entity_identifer);
        $placeholder.data('placeholder-identifier', entity_identifer);

        return $placeholder;
    };

    ReferenceEntityList.prototype.replaceEntityPlaceholder = function(entity_identifer, html_item) {
        var placeholder_query = '.hb-embed-item-placeholder[data-placeholder-identifier="{REF_ID}"]'.replace('{REF_ID}', entity_identifer);
        var $placeholder = this.$entities_list.find('> li').filter(placeholder_query);
        var $new_item = $(html_item).filter('.hb-embed-item');
        var placeholder_input_group = $placeholder.attr('data-input-group');
        var new_item_input_group = $new_item.attr('data-input-group');
        // replace placeholder of the corresponding request
        if (new_item_input_group === placeholder_input_group) {
            return $placeholder.replaceWith($new_item);
        }

        return false;
    };

    ReferenceEntityList.prototype.removeEntityPlaceholder = function(entity_identifer) {
        var placeholder_query = '.hb-embed-item-placeholder[data-placeholder-identifier="{REF_ID}"]'.replace('{REF_ID}', entity_identifer);
        var $item = this.$entities_list.find('> li').filter(placeholder_query).remove();

        return $item;
    };

    ReferenceEntityList.prototype.buildSuggestUrl = function(query, type_prefix) {
        return this.getSuggestOptionsFor(type_prefix).suggest_url.replace('%7BQUERY%7D', encodeURIComponent(query));
    };

    ReferenceEntityList.prototype.buildRenderUrl = function(type_prefix) {
        var suggest_options = this.getSuggestOptionsFor(type_prefix);
        var parent_group_parts = this.getParentGroupParts(true);
        var input_group_parts = (parent_group_parts.length === 0)
            ? _.clone(this.options.input_group)
            : _.clone(parent_group_parts);
        input_group_parts.push(this.options.fieldname, this.cur_item_index);

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
        // 'required' relevant for initialisation; after could be removed/ignored
        $select.attr('required', this.options.isRequired);
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
        this.$widget.removeClass('invalid'); // selectize will revalidate
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
                },
                "function_override": {
                    functions: {
                        refreshValidityState: _.curry(this.customRefreshValidityState)(this)
                    }
                }
            },
            load: this.loadSuggestions.bind(this),
            onItemAdd: this.onItemAdded.bind(this),
            onItemRemove: this.onItemRemoved.bind(this),
            onBlur: this.updateUi.bind(this, false),
            onFocus: this.updateUi.bind(this, false)
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

    ReferenceEntityList.prototype.updateValidity = function(invalid) {
        var invalid = invalid || this.isInvalid();
        jsb.fireEvent('TABS:UPDATE_ERROR_BUBBLES');

        return invalid;
    }

    // ReferenceEntityList.prototype.isInvalid = function() {
    //     // Additional validation goes here
    //     return EmbeddedEntityList.prototype.isInvalid.call(this);;
    // };


    //
    // Selectize function overrides
    //  (this = selectize)
    //

    ReferenceEntityList.prototype.customRefreshValidityState = function(context, original) {
        // fix code from original selectize method:
        // - use custom validation
        // - use just input caret to mark validation; ignore valdidation of orgiginal input
        var invalid, message;

        invalid = context.isInvalid.call(context);
        context.updateValidity.call(context, invalid);

        this.isInvalid = invalid;
        this.$control_input.prop('required', invalid);
        this.$input.prop('required', false);

        // report validation message
        if (invalid) {
            validity_message = 'Minimum: ' + ~~context.options.min_count + '. Maximum: ' + ~~context.options.max_count;
        } else {
            validity_message = '';
        }
        if (this.$control_input[0].setCustomValidity) {
            this.$control_input[0].setCustomValidity(validity_message);
        } else if (validity_message.length) {
            console.warn(context.getPrefix() + ' - ' + validity_message);
        }
    };

    return ReferenceEntityList;
});
