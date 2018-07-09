define([
    "selectize",
    "jquery"
], function(selectize, $) {

    var default_options = {
        prefix: "Honeybee_Core/ui/SelectizePlugins",
    };

    /**
     * Allow to override/extend any public method of Selectize
     *
     * Options:
     * - functions => object
     *     {
     *         "original_function_name": function (original_function, context, args...)
     *         {
     *             ... overriding function code ...
     *
     *             // original_function: partially applied already by the plugin
     *             // context: usually the execution context object (your widget)
     *                         it can be partially applied too when providing the plugin options
     *                          e.g.  _.curry(callback)(context)  or  _.partial(callback, context, arg1)
     *             // this: selectize instance
     *             // call parent: original_function.call(this);
     *
     *         }
     *     }
     */
    selectize.define('function_override', function(options) {
        var selctz = this;
        $.each(options.functions, function(func_name, override_func) {
            if ($.isFunction(override_func)) {
                selctz[func_name] = (function() {
                    var original = selctz[func_name];
                    return override_func.bind(selctz, original);
                })();
            }
        });
    });
});
