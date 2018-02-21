define([
    "Honeybee_Core/Widget",
    "jquery"
], function(Widget, $) {

    var default_options = {
        prefix: "Honeybee_Core/ui/UserWidget",
    };

    function UserWidget(dom_element, options) {
        var self = this;

        this.init(dom_element, default_options);
        this.addOptions(options);
        this.$bg_area = this.$widget.find('.user-area__background');

        if (this.$bg_area.length === 0) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }
        this.startBgImageSlideShow();
    }

    UserWidget.prototype = new Widget();
    UserWidget.prototype.constructor = UserWidget;

    UserWidget.prototype.startBgImageSlideShow = function() {
        var i = 1, css = '', slide_show_pos, $bg_tiles;
        for (; i < this.options.bg_images.length; i++) {
            css = 'style="background-image: url('+this.options.bg_images[i]+')"';
            this.$bg_area.prepend($('<div class="user-area__background_tile" ' + css + '></div>'));
            slide_show_pos = i;
        }

        $bg_tiles = this.$bg_area.children();

        if ($bg_tiles.length !== 0) {
            setInterval(function() {
                $($bg_tiles[slide_show_pos]).removeClass('active');
                slide_show_pos++;
                if (slide_show_pos >= this.options.bg_images.length) {
                    slide_show_pos = 0;
                }
                $($bg_tiles[slide_show_pos]).addClass('active');
            }.bind(this), 10000);
        }
    };

    return UserWidget;
});
