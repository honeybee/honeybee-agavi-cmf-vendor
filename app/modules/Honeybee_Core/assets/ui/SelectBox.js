define([
    "Honeybee_Core/Widget",
    "selectize"
], function(Widget) {

    var default_options = {
        prefix: "Honeybee_Core/ui/SelectBox",
    };

    function SelectBox(dom_element, options) {
        this.init(dom_element, default_options);
        this.addOptions(options);

        this.$select = this.$widget.find("select");
        var settings = {};

        if (this.$select.length === 0) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }
        settings['allowEmptyOption'] = this.options.allow_empty_option || false;

        this.$select.selectize(settings);
    }

    SelectBox.prototype = new Widget();
    SelectBox.prototype.constructor = SelectBox;

    return SelectBox;
});

