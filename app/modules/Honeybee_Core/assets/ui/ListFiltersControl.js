define([
    "Honeybee_Core/Widget"
], function(Widget) {
    var default_options = {
        prefix: 'Honeybee_Core/ui/ListFiltersControl',
        activity_map_selector: '.activity-map',
        toggle_selector: '.base-dropdown__toggle',
        dropdown_selector: '.base-dropdown__more',
        filter_trigger_selector: '.activity',
        list_fiter_list_selector: '.hb-list-filters__list'
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
            var filter_name = $target.attr('href').replace('#', '');

            self.addFilter(filter_name);
        });

        this.$activity_map.on('cli')
    };

    ListFiltersControl.prototype.addFilter = function(filter_name) {
        var $filter_list = $(this.options.list_fiter_list_selector);
        var filter_selector = '[data-hb-filter-name="' + filter_name + '"]';
        if ($filter_list.find(filter_selector).length === 0) {
            // clone template
            var filter_template = $('#list_filter_templates')
                .find(filter_selector)
                .html();
            $filter_list.append($.parseHTML(filter_template));
            // execute jsb
            jsb.applyBehaviour($filter_list.get(0));
        } else {
            jsb.fireEvent('LIST_FILTER_' + _.snakeCase(filter_name).toUpperCase() + ':ACTION', { action: 'TOGGLE_FILTER' } );
        }
    };

    ListFiltersControl.prototype.clearAllFilters = function() {
        jsb.fireEvent('LIST_FILTER:ACTION', { action: 'CLEAR' });
    };

    return ListFiltersControl;
});
