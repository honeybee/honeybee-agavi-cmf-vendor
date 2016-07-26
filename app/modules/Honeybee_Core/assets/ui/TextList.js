define([
    "Honeybee_Core/Widget",
    "selectize"
], function(Widget) {

    var default_options = {
        prefix: "Honeybee_Core/ui/TextList",
    };

    function TextList(dom_element, options) {
        var self = this;

        this.init(dom_element, default_options);
        this.addOptions(options);

        this.options.max_count = this.options.max_count;
        this.options.min_count = this.options.min_count || Number(this.isRequired());
        this.options.remove_label = this.options.remove_label || "-";

        this.isReadable = !(this.isReadonly() || this.isDisabled());
        this.$input_element = this.$widget.find(".input-text-list");

        if (this.$input_element.length === 0) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }

        this.buildSelectize();
    }

    TextList.prototype = new Widget();
    TextList.prototype.constructor = TextList;

    TextList.prototype.buildSelectize = function() {
        var $select = $("<select></select>");
        var $text_input = this.$input_element.find("input[type=text]");
        var $item_labels = this.$input_element.children("label");

        $select.attr("multiple", true);
        $select.attr("name", $text_input.attr("name"));
        $select.prop("disabled", !this.isReadable);
        $select.prop("required", this.isRequired());

        $item_labels.each(function() {
            var $label = $(this);
            var $checkbox = $label.children("input");
            var $option = $("<option></option>");

            $option.attr("value", $checkbox.attr("value"));
            $option.text($label.children("span").text());
            $option.attr("selected", $checkbox.attr("checked"));

            $select.append($option);
        });

        $item_labels.remove();

        this.$widget.find('.input-text-list__box').replaceWith($select);

        var selectize_config = {
            maxItems: this.options.max_count,
            minItems: this.options.min_count,
            plugins: {
                "remove_button": {
                    label: this.options.remove_label
                }
            },
            create: this.isReadable && this.options.allowed_values && this.options.allowed_values.length === 0,
            render: {
                option_create: function(data, escape) {
                    return '<div class="create">+ <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                }
            },
            onChange: this.updateItems,
            onInitialize: this.updateItems
        };

        // if there's a set of allowed tags they should be removed on backspace instead of characters being removed
        if (this.options.allowed_values && this.options.allowed_values.length === 0) {
            selectize_config['plugins']['restore_on_backspace'] = {};
        }

        $select.selectize(selectize_config);
    };

    TextList.prototype.updateItems = function() {
        if(this.items.length < this.settings.minItems) {
            this.$control_input.prop('required', true);
            this.isInvalid = true;
            console.log('The number of items must be at least ' + this.settings.minItems);
        } else {
            this.$control_input.prop('required', false);
            this.isInvalid = false;
        }
        this.refreshClasses();
    };

    return TextList;
});
