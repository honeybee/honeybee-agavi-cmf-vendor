define([
    "Honeybee_Core/ui/ListFilter"
], function(ListFilter) {

    var default_options = {
        prefix: "Honeybee_Core/ui/list-filter/ChoiceListFilter"
    };

    function ChoiceListFilter(dom_element, options) {
        ListFilter.call(this, dom_element, _.merge({}, default_options, options));
    };

    ChoiceListFilter.prototype = new ListFilter();
    ChoiceListFilter.prototype.constructor = ChoiceListFilter;

    ChoiceListFilter.prototype.setQuickLabel = function() {
        var $control = this.getControl();
        var control_val = $control.val();
        var control_text = $control.find(':selected').text();
        var value = this.translations['value_' + control_val] ? control_val : control_text;

        ListFilter.prototype.setQuickLabel.call(this, value);

        return this;
    };

    return ChoiceListFilter;
});
