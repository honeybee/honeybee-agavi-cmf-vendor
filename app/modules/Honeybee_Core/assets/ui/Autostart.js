define([
    "Honeybee_Core/Config",
    "jquery",
    "jsb"
], function(config) {

    appendRequiredAttributeToAllDataRequiredFields();
    handleFlyoutSpecifics();
    handleLoadingState(
        config.get('widgets.handle_loading', false),
        config.get('widgets.loading_classname', 'hb-js-widget--busy'),
        config.get('widgets.loading_release_time', 10000)
    );

    function appendRequiredAttributeToAllDataRequiredFields() {
        $('[data-required]').each(function() {
            $(this).prop('required', true);
            $(this).removeData('required');
            $(this).removeAttr('data-required');
        });
    };

    function handleFlyoutSpecifics() {
        /**
        * handle click events to close flyouts when user clicks
        * somewhere else
        */
        $(document).on("click", function(ev) {
            var $target = $(ev.target);
            var $this = $(this);
            if ($target.closest("label.hb-js-flyout-toggle").length > 0) {
                //do nothing, this is the click on the checkbox's label
                //there will be another click event on the checkbox after this one.
            } else {
                if ($target.hasClass("hb-js-flyout-trigger")) {
                    $("input.hb-js-flyout-trigger").not($target).prop('checked', false);
                } else {
                    $("input.hb-js-flyout-trigger").prop('checked', false);
                }
            }
        });

        /**
        * prevent the toggle areas of dropdowns to look like two seperate rectangles
        * by highlighting them together
        */
        $(document).on("mouseover", ".hb-js-flyout-toggle", function(ev) {
            $(this).siblings(".hb-js-flyout-toggle").addClass("hover");
        });
        $(document).on("mouseout", ".hb-js-flyout-toggle", function(ev) {
            $(this).siblings(".hb-js-flyout-toggle").removeClass("hover");
        });
    };

    function handleLoadingState(enabled, loading_class, release_time) {
        var loading_class = loading_class || 'hb-js-widget--busy';
        var release_time = release_time || 5000;
        var busy_state_timeout = null;
        var busy_state = {};

        function setLoadingState() {
            $('body').addClass(loading_class);
        }

        function releaseLoadingState() {
            $('body').removeClass(loading_class);
        }

        // release loading state after a period of time
        function resetLoadingStateTimer() {
            if (busy_state_timeout) {
                clearTimeout(busy_state_timeout);
            } else {
                busy_state_timeout = setTimeout(
                    function() {
                        releaseLoadingState(loading_class);
                    },
                    release_time
                );
            }
        }

        if (!enabled) {
            return;
        }

        jsb.whenFired('WIDGET:BUSY_LOADING', function(values, event_name) {
            if (!values.attribute_name) {
                return;
            }
            var busy_state_count = 0;

            // manage counters stack
            if (values.type === 'start') {
                if (isNaN(busy_state[values.attribute_name])) {
                    busy_state[values.attribute_name] = 0;
                }
                busy_state[values.attribute_name]++;

                resetLoadingStateTimer(release_time);
            } else {
                // type: 'stop'
                if (!isNaN(busy_state[values.attribute_name])) {
                    busy_state[values.attribute_name]--;
                }
            }

            for (var attribute_busy_stack in busy_state) {
                busy_state_count += busy_state[attribute_busy_stack];
            }
            if (busy_state_count > 0) {
                setLoadingState(loading_class);
            } else {
                releaseLoadingState(loading_class);
            }
        });
    }
});
