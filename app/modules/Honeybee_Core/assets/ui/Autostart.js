define([
    "jquery",
    "magnific-popup"
], function() {

    appendRequiredAttributeToAllDataRequiredFields();
    handleFlyoutSpecifics();


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

});
