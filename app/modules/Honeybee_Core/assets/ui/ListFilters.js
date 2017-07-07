define([
    "Honeybee_Core/Widget"
], function(Widget) {
    var default_options = {
        prefix: 'Honeybee_Core/ui/ListFilters',
        // filter_selector: '.hb-list-filter',
        quick_controls_selector: '.hb-list-filters__quick-controls',
        details_trigger_selector: '.hb-list-filters__trigger',
        quick_class: 'hb-list-filters__quick',
        quick_clear_class: 'hb-list-filters__clear'
    };

    function ListFilters(dom_element, options)
    {
        var self = this;

        this.init(dom_element, default_options);
        this.addOptions(options);

        this.$quick_controls = this.$widget.find(this.options.quick_controls_selector);
        this.$details_trigger = this.$widget.find(this.options.details_trigger_selector)

        this.addListeners();
    };

    ListFilters.prototype = new Widget();
    ListFilters.prototype.constructor = ListFilters;

    ListFilters.prototype.addListeners = function() {
        var self = this;

        this.$quick_controls.on('click', function(e) {
            var $target = $(e.target);

            if ($target.hasClass(self.options.quick_class)) {
                self.$details_trigger.prop('checked', true);    // display filters detail
            }
            if ($target.hasClass(self.options.quick_clear_class)) {
console.log($target.data('filter'));
                self.clearFilter($target);
            }
            // var $target_input = self.$widget.find('#' + $target_label.attr('for'));
            

            // var expand_target_selector = '.' + $target_label.data('jsExpandTarget');
            // var $filter = $target_label.closest(self.options.filter_selector);
            // $filter.find(expand_target_selector).toggle('fast');
        });
    };

    ListFilters.prototype.clearFilter = function(clear_button) {
        var self = this;
        var $clear_button = $(clear_button);

        filter_name = $clear_button.data('filter');
        $filter = this.$widget.find('.hb-list-filters__filter_{{FILTER_NAME}}'.replace('{{FILTER_NAME}}', filter_name));
console.log(filter_name, $filter);
        $filter.remove();
        $clear_button.closest('.' + this.options.quick_class).remove();
    };

    // ListFilters.prototype.toggleAttr = function(el, attr) {
    //     $(el).attr(attr, function(i, val) { return !val; })
    // };

    return ListFilters;
});
