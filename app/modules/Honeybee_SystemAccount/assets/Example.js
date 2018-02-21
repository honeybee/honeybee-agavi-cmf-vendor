define([
    "jquery",
    "jsb"
], function($, jsb) {

    "use strict";

    var Example = function(dom_element, options) {
        console.log("User/Example", options);
        dom_element.textContent = 'User ' + options.name;
    };

    return Example;
});
