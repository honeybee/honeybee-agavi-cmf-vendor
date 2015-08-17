define([
    "Honeybee_Core/Widget"
], function(Widget) {

    var default_options = {
        prefix: "Honeybee_Core/ui/Image",
    };

    function replaceFileInputWithTextInput($file) {
        var $text = $('<input type="text" class="hide" />');
        $text.attr("name", $file.attr("name"));
        $text.attr("class", $file.attr("class"));
        $text.addClass("hide");
        console.log("replacing file input", $file.attr("id"));
        $file.attr("name", "");
        $file.attr("class", "hide");
        $text.insertAfter($file);

        return $text;
    }

    function fakeUpload(cb) {
        var progress = 0;
        var interval = setInterval(function() {
            progress += 20;

            if (progress >= 100) {
                clearInterval(interval);
            }

            cb(null, progress);
        }, 200);
    }

    function uploadFile(file, cb) {
        var fd = new FormData();
        fd.append("fake_upload_file", file);
        // These extra params aren't necessary but show that you can include other data.
        fd.append("fake_image_upload", true);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/', true);

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                var percentComplete = (e.loaded / e.total) * 100;
                cb(null, percentComplete);
            }
        };

        xhr.onload = function() {
            if (this.status == 200) {
                var resp = JSON.parse(this.response);

                cb(null, 100, resp.path);
            } else {
                cb(new Error("Server error"), null);
            }
        };

        xhr.send(fd);
    }

    function Image(dom_element, options) {

        console.log("Honeybee_Core/Image");
        var self = this;
        this.$dom_element = $(dom_element);
        this.$input = this.$dom_element.find(".image-input__input");
        this.name = this.$input.attr("name");
        this.$image = this.$dom_element.find(".image-input__image");
        this.$pick = this.$dom_element.find(".image-input__pick");
        this.$filename = this.$dom_element.find(".image-input__filename");
        this.$progress = this.$dom_element.find(".image-input__progress");

        if (typeof options !== "object") {
            options = {};
        }

        this.$input.on("change", function(ev) {
            console.log("change", this.files);

            self.handleNewFile(this.files[0]);
        });

        this.$image.on("dragenter", function(ev) {
            ev.stopPropagation();
            ev.preventDefault();
        });

        this.$image.on("dragover", function(ev) {
            ev.stopPropagation();
            ev.preventDefault();
        });

        this.$image.on("drop", function(ev) {
            var files = ev.originalEvent.dataTransfer.files;
            if (files.length === 1) {
                ev.stopPropagation();
                ev.preventDefault();
                console.log("dropped 1 file", ev);
                //self.$input[0].files = ev.originalEvent.dataTransfer.files;
                self.handleNewFile(ev.originalEvent.dataTransfer.files[0]);
            }
        });

        this.$text_input = replaceFileInputWithTextInput(this.$input);

        this.$pick.removeClass("hide");
        this.$filename.removeClass("hide");

        this.$pick.on("click", function() {
            self.$input.click();
        });

        this.$filename.on("click", function() {
            self.$input.click();
        });

        this.positionProgressBar();

        jsb.on(this.name + ":new_file", function(data) {
            console.log("Image: new file", data.file);
            self.handleNewFile(data.file);
        });
    }

    Image.prototype = new Widget();
    Image.prototype.constructor = Image;

    Image.prototype.showImageFile = function(file) {
        var self = this;

        self.$filename.text(file.name);

        var reader = new FileReader();
        reader.onload = function (e) {
            self.$image.attr('src', e.target.result);
            self.positionProgressBar();
        };
        reader.readAsDataURL(file);
    };

    Image.prototype.handleNewFile = function(file) {
        console.log("handleNewFile");
        var self = this;
        this.showImageFile(file);
        uploadFile(file, function(err, progress, url) {
            console.log("progress: " + progress);
            self.updateProgressBar(progress);

            if (progress >= 100) {
                self.$text_input.val("random_id_" + Math.floor(Math.random() * 10000));
                self.$image.attr("src", url);
            }
        });
    };

    Image.prototype.positionProgressBar = function() {
        var offset = this.$image.offset();
        var image_rect = {
            x: offset.left,
            y: offset.top,
            w: this.$image.width(),
            h: this.$image.height()
        };

        this.$progress.width(image_rect.w * 0.8);
        this.$progress.css("top", (image_rect.y + image_rect.h / 2 - this.$progress.height() / 2) + "px");
        this.$progress.css("left", (image_rect.x + image_rect.w * 0.1) + "px");
    };

    Image.prototype.updateProgressBar = function(value) {
        var self = this;
        this.$progress.removeClass("hide");
        this.$progress.children("div").css("width", value + "%");

        if (value >= 100) {
            setTimeout(function(){
                self.$progress.addClass("hide");
            }, 200);
        }
    };

    return Image;
});


