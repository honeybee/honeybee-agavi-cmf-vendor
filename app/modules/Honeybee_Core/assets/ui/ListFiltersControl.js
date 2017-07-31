define([
    "Honeybee_Core/Widget"
], function(Widget) {
    var default_options = {
        prefix: 'Honeybee_Core/ui/ListFiltersControl',
        activity_map_selector: '.activity-map',
        toggle_selector: '.base-dropdown__toggle',
        dropdown_selector: '.base-dropdown__more',
        filter_trigger_selector: '.activity'
    };

    function ListFiltersControl(dom_element, options)
    {
        var self = this;

        this.init(dom_element, default_options);
        this.addOptions(options);

        this.$activity_map = this.$widget.find(this.options.activity_map_selector);

        this.addListeners();
    };

    ListFiltersControl.prototype = new Widget();
    ListFiltersControl.prototype.constructor = ListFiltersControl;

    ListFiltersControl.prototype.addListeners = function() {
        var self = this;
        var $keypressed = [];

        $('body').keydown(function(e) { if (e.altKey) { self.$activity_map.addClass('destructive'); }});
        $('body').keyup(function(e) { if (!e.altKey) { self.$activity_map.removeClass('destructive'); }});

        this.$activity_map.on('click', this.options.toggle_selector, function(e) {
            if (e.altKey) {
                e.preventDefault();
                self.clearAllFilters();
            }
        });

        this.$activity_map.on('click', this.options.filter_trigger_selector, function(e) {
            e.preventDefault();
            var $target = $(e.target);
            var filter_id = $target.attr('href').replace('#', '');

            self.addFilter(filter_id);
        });

        this.$activity_map.on('cli')
    };

    ListFiltersControl.prototype.addFilter = function(filter_id) {
        jsb.fireEvent('LIST_FILTER_' + filter_id.toUpperCase() + ':ACTION', { action: 'ADD_LIST_FILTER' } );
    };

    ListFiltersControl.prototype.clearAllFilters = function() {
        jsb.fireEvent('LIST_FILTER:ACTION', { action: 'CLEAR' });
    };

    return ListFiltersControl;
});
