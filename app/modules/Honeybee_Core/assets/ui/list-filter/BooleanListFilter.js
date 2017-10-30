define([
    "Honeybee_Core/ui/ListFilter"
], function(ListFilter) {

    var default_options = {
        prefix: "Honeybee_Core/ui/list-filter/BooleanListFilter",
        control_label_selector: 'input:checked ~ .hb-list-filter__choice-label'
    };

    function BooleanListFilter(dom_element, options) {
        ListFilter.call(this, dom_element, _.merge({}, default_options, options));
    };

    BooleanListFilter.prototype = new ListFilter();
    BooleanListFilter.prototype.constructor = BooleanListFilter;

    BooleanListFilter.prototype.setQuickLabel = function(value) {
        var $control = this.getControl();
        var control_val = $control.filter(':checked').val();
        var value = this.translations['value_' + control_val]
            ? control_val
            : this.$widget.find(this.options.control_label_selector).text();

        ListFilter.prototype.setQuickLabel.call(this, value);

        return this;
    };

    return BooleanListFilter;
});
