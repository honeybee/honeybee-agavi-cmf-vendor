define([
    "Honeybee_Core/Widget"
], function(Widget) {

    "use strict";

    var default_options = {
        prefix: 'Honeybee_Core/ui/ListFiltersController',
        activity_map_selector: '.activity-map',
        toggle_selector: '.base-dropdown__toggle',
        dropdown_selector: '.base-dropdown__more',
        filter_trigger_selector: '.activity',
        list_filter_list_selector: '.hb-list-filters__list',
        list_filter_selector: '.hb-list-filter'
    };

    function ListFiltersController(dom_element, options)
    {
        var self = this;

        this.init(dom_element, default_options);
        this.addOptions(options);

        this.$activity_map = this.$widget.find(this.options.activity_map_selector);

        this.addListeners();
    };

    ListFiltersController.prototype = new Widget();
    ListFiltersController.prototype.constructor = ListFiltersController;

    ListFiltersController.prototype.addListeners = function() {
        var self = this;
        var $keypressed = [];

        $('body').keydown(function(e) { if (e.altKey) { self.$activity_map.addClass('destructive'); }});
        $('body').keyup(function(e) { if (!e.altKey) { self.$activity_map.removeClass('destructive'); }});

        // clear filters
        this.$activity_map.on('click', this.options.toggle_selector, function(e) {
            if (e.altKey) {
                e.preventDefault();
                self.clearAllFilters.call(self);
            }
        });

        // add filters
        this.$activity_map.on('click', this.options.filter_trigger_selector, function(e) {
            e.preventDefault();
            var $target = $(e.target);
            var filter_id = $target.attr('href').replace('#', '');

            self.addFilter.call(self, filter_id);
        });

        // commands
        jsb.whenFired('LIST_FILTER:ACTION', function(values, event_name) {
            switch(values.action) {
                case 'TOGGLE_ALL':
                    self.toggleAllFilters.call(self, values.exclude, !'show', 'silent');
                    break;
                case 'CLEAR_ALL':
                    self.clearAllFilters.call(self);
                    break;
                default:
                    self.logWarn('ListFilter action not recognized.');
            }
        });
    };

    ListFiltersController.prototype.addFilter = function(filter_id) {
        var $filter_list = $(this.options.list_filter_list_selector);
        var filter_selector = '[data-hb-filter-id="' + filter_id + '"]';
        var $filter_template;

        if ($filter_list.find(filter_selector).length === 0) {
            // clone template
            var $filter_template = $('#list_filter_templates').find(filter_selector);
            if ($filter_template.length === 0) {
                this.logWarn('Unable to add "' + filter_id + '" filter');
            }
            $filter_list.append($.parseHTML($filter_template.html()));
            // execute jsb
            jsb.applyBehaviour($filter_list.get(0));
        }
        jsb.fireEvent('LIST_FILTER:' + filter_id.toUpperCase() + ':ACTION', { action: 'TOGGLE_FILTER', show: true } );
    };

    ListFiltersController.prototype.clearAllFilters = function(exclude) {
        this.getLoadedFilters(exclude).forEach(function(filter_id, idx) {
            jsb.fireEvent('LIST_FILTER:' + filter_id.toUpperCase() + ':ACTION', { action: 'CLEAR_FILTER' });
        });
    };

    ListFiltersController.prototype.toggleAllFilters = function(exclude, show, silent) {
        var self = this;
        show = show || false;
        silent = silent !== true;
        var values = { action: 'TOGGLE_FILTER', show: show, silent: silent };
        this.getLoadedFilters(exclude).forEach(function(filter_id, idx) {
            jsb.fireEvent('LIST_FILTER:' + filter_id.toUpperCase() + ':ACTION', values);
        });
    };

    ListFiltersController.prototype.getLoadedFilters = function(exclude) {
        var self = this;
        exclude = exclude || [];
        return $(this.options.list_filter_list_selector)
            .find(this.options.list_filter_selector).map(function() {
                var filter_id = $(this).data('hbFilterId');
                if (exclude.indexOf(filter_id) === -1) {
                    return filter_id;
                }
            })
            .get();
    };

    return ListFiltersController;
});
