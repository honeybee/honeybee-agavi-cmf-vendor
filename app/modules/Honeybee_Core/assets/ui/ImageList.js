define([
    "Honeybee_Core/Widget",
    "Honeybee_Core/lib/selectrect",
    "magnific-popup"
], function(Widget, selectrect) {

    var default_options = {
        prefix: "Honeybee_Core/ui/ImageList"
    };

    function uploadFile(input_name, file, target_url, progress, end) {
        var fd = new FormData();
        fd.append(input_name, file);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', target_url, true);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                var percentComplete = (e.loaded / e.total) * 100;
                progress(percentComplete);
            }
        };

        xhr.onload = function() {
            if (this.status == 200) {
                if (this.getResponseHeader('Content-Type').indexOf('application/json') !== 0) {
                    end(new Error('Server response Content-Type is not application/json'), null);
                }

                var resp = JSON.parse(this.response);
                end(null, resp);
            } else {
                end(new Error("Server error"), null);
            }
        };

        xhr.send(fd);
    }

    function ImageList(dom_element, options) {
        var self = this;

        this.init(dom_element, default_options);
        this.addOptions(options);

        this.isReadable = !(this.isReadonly() || this.isDisabled());

        this.id = this.$widget.data('id');
        this.form_name = this.$widget.data('form-name');
        this.aoi_selectrect = {};

        this.resource_type_name = this.$widget.data('resource-type-name');
        this.resource_type_prefix = this.$widget.data('resource-type-prefix');
        this.resource_identifier = this.$widget.data('resource-identifier');

        this.attribute_path = this.$widget.data('attribute-path');
        this.upload_input_name = this.$widget.data('upload-input-name') || 'uploadform['+self.attribute_path+']'; // uploadform[gallery]
        // this.logDebug('UploadInputName:', this.upload_input_name, self.attribute_path);
        this.upload_url = this.$widget.data('upload-url');

        if (!this.upload_url || !this.attribute_path) {
            this.logDebug('No upload url or attribute path given.');
            return;
        }

/*
        this.initEvents();
        this.initPopupSupport();
        this.initDragAndDropSupport();
*/

        this.bindKeyEvents();

        this.current_index = 0;

        if (this.isReadable) {
            this.bindDropEvents();
            this.bindClickEvents();
            this.bindMultipleInput();

            this.$widget.find('.imagelist-tabs__content > .imagelist__item').each(function(i, item) {
                var $item = $(item);
                self.bindItemFileInput($item);
                self.current_index++;
            });
        }

        this.$popup_trigger = this.$widget.find('.hb-field__label');
        this.grouped_field_name = this.$widget.data('grouped-field-name');

        this.popup_options = {
            type: 'inline',
            midClick: true,
            prependTo: self.$widget.find('.popup-content-here'),
            gallery: {
                enabled: true,
                navigateByImgClick: false
            },
            callbacks: {
                open: function() {
                    // fires when this exact popup is opened, "this" refers to the magnific popup object
                },
                change: function() {
                    self.cancelAoi();
                    // fires when content changes, "this" refers to the magnific popup object
                },
                beforeClose: function() {
                    self.cancelAoi();
                }
            }
        }

        this.updatePopupItems();

        this.$widget.find('.imagelist').addClass('widget-initialized');

        this.$widget.on("click", ".imagelist__thumb", function(ev) {
            var $li = $(this);
            var item_index = $li.index();

            // console.log(self.id, item_index, self.popup_options, self.$popup_trigger);
                self.$popup_trigger.magnificPopup('open'); // why does …('open', idx) not work?
                self.$popup_trigger.magnificPopup('goTo', item_index-1);
                /*
            if (item_index > 0) {
                // edit image details in popup
                self.$popup_trigger.magnificPopup('open'); // why does …('open', idx) not work?
                self.$popup_trigger.magnificPopup('goTo', item_index-1);
            } else if (item_index === 0) {
                // upload a new image
                // TODO trigger the multiple file upload field?
                var item_id = $li.data('item-id');
                var $item = self.$widget.find('.imagelist__item[data-item-id='+item_id+'] .imagelist__image-input');
                $item.trigger('click');
            }*/
        });

        this.$widget.on("click", ".imagelist__thumb-control.remove", function(ev) {
            ev.preventDefault();
            ev.stopPropagation();

            var $target = $(ev.target);
            var $thumb_item = $target.parents('.imagelist__thumb').first();
            var index = $thumb_item.index();
            var $next = $thumb_item.next();

            var items = self.$widget.find(".imagelist-tabs__content > .imagelist__item");
            var thumbs = self.$widget.find(".imagelist-tabs__toggles > .imagelist__thumb");

            items.eq(index).remove();
            thumbs.eq(index).remove();

            $next.find('.imagelist__thumb-control.move').first().focus();
        });
    }

    ImageList.prototype = new Widget();
    ImageList.prototype.constructor = ImageList;

    ImageList.prototype.updatePopupItems = function() {
        var items = [];

        this.$widget.find('.imagelist__image').each(function(idx, item) {
            var $item = $(item);
            if (!$item.hasClass('newitem')) {
                items.push({
                    src: $item,
                    type: 'inline'
                });
            } else {
                // newitem is the template and should not be available in the popup
            }
        });

        this.popup_options.items = items;
        this.$popup_trigger.magnificPopup(this.popup_options);
    };

    ImageList.prototype.bindClickEvents = function() {
        var self = this;

        this.$widget.on("click", ".imagelist__thumb", function(ev) {
            self.cancelAoi();
        });

        this.$widget.on("click", ".imagelist__thumb .move", function(ev) {
            ev.preventDefault();
            ev.stopPropagation();

            var $target = $(ev.target);
            var $thumb_item = $target.parents('.imagelist__thumb').first();
            var index = $thumb_item.index();
            var $next = $thumb_item.next();

            if ($target.hasClass("right") && !$next.hasClass("newitem")) {
                self.moveItem(index, index + 1);
            } else if ($target.hasClass("left") && index > 0) {
                self.moveItem(index, index - 1);
            }

            $target.focus();
        });

        this.$widget.on("click", ".imagelist__image-aoi-trigger", function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            self.selectAoi($(ev.target));
            $(this).addClass("hide").
                siblings(".imagelist__image-aoi-accept").removeClass("hide").
                siblings(".imagelist__image-aoi-cancel").removeClass("hide");
        });

        this.$widget.on("click", ".imagelist__image-aoi-accept", function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            self.acceptAoi();
            $(this).addClass("hide").
                siblings(".imagelist__image-aoi-trigger").removeClass("hide").
                siblings(".imagelist__image-aoi-cancel").addClass("hide");
        });

        this.$widget.on("click", ".imagelist__image-aoi-cancel", function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            self.cancelAoi();
            $(this).addClass("hide").
                siblings(".imagelist__image-aoi-trigger").removeClass("hide").
                siblings(".imagelist__image-aoi-accept").addClass("hide");
        });
    };

    ImageList.prototype.bindKeyEvents = function() {
        var self = this;

        this.$widget.on("keydown", ".move", function(ev) {
            if (ev.shiftKey && ev.keyCode >= 37 && ev.keyCode <= 40) {
                ev.preventDefault();
                ev.stopPropagation();
            }

            var $target = $(ev.target);
            var $item = $target.parents(".imagelist__thumb").first();
            var index = $item.index();
            var $next = $item.next();
            var direction;

            switch (ev.keyCode) {
                case 39:
                case 40:
                    direction = 'forward';
                    break;
                case 37:
                case 38:
                    direction = 'backward';
                    break;
                default: return;
            }

            if (ev.shiftKey && self.isReadable) {
                if (direction == "forward" && !$next.hasClass("newitem")) {// && $next.hasClass("item")) {
                    self.moveItem(index, index + 1);
                } else if (direction == "backward" && index > 0) {
                    self.moveItem(index, index - 1);
                }
            }

            $target.focus();
        });
    };

    ImageList.prototype.bindItemFileInput = function($item) {
        var self = this;
        var item_id = $item.attr("data-item-id");
        // this.logDebug('bindItemFileInput', item_id, $item);
        $input = $item.find(".imagelist__image-input");
        $input.addClass("visuallyhidden");
        $input.removeAttr('name');
        $input.on("change", function(ev) {
            var $input = $(this);
            var files = $input[0].files;
            // self.logDebug('input onChange:', files, $input);
            if (files.length == 1) {
                if ($item.hasClass("newitem")) {
                    self.appendImageFile(files[0]);
                } else {
                    self.replaceImageFile(item_id, files[0]);
                }
            } else {
                self.logDebug('Multiple files uploaded to input?', $input.id || '');
            }
        });
    };

    ImageList.prototype.bindMultipleInput = function() {
        var self = this;

        var $multi = this.$widget.find(".imagelist__input-multiple");
        var $multiinput = this.$widget.find(".imagelist__input-multiple-trigger");
        var $multilabel = this.$widget.find(".imagelist__input-multiple-label");
        var newid = this.getRandomString();
        $multiinput.attr('id', newid);
        $multilabel.attr('for', newid);
        $multilabel.removeClass("hide");

        $multiinput.on("change", function(ev) {
            self.appendMultipleFiles(this.files);
        });
    };

    ImageList.prototype.bindDropEvents = function() {
        var self = this;

        /*
         * We have to count the dragenter and dragleave events as it is otherwise not easy to know if
         * users stopped their dragging operation by e.g. leaving our current browser window.
         */
        this.dragevents = [];

        /*
         * Available drag related events in browsers are:
         *
         * dragstart, dragenter, dragover, dragleave, drop, dragend
         *
         * We need to stop dragenter and dragover events to indicate drop zones
         * as browsers will otherwise trigger their default behaviours (which
         * usually results in opening the dropped file in the current window)
         */
        $body = $(':root');

        $body.on("dragenter", function(ev) {
            // self.logDebug('dragenter', ev.target);
            if (self.dragevents.length === 0)
            {
                // self.logDebug('INITIAL DRAGENTER');
                //self.dropbox.addClass('dragging');
                $body.addClass('dragging');
            }

            self.dragevents.push(ev.target); // add the target element to our stack

            $target = $(ev.target);
            if ($target.hasClass('imagelist-tabs__toggles') || $target.hasClass('imagelist__thumb-img')) {
                $target.addClass('dragover');
            }

            ev.preventDefault();
            ev.stopPropagation();
            return false;
        });

        $body.on("dragover", function(ev) {
            // stop the event to notify browser, that a drop operation may be possible here

            $target = $(ev.target);

            if ($target.hasClass('imagelist-tabs__toggles') || $target.hasClass('imagelist__thumb-img')) {
                ev.originalEvent.dataTransfer.dropEffect = 'copy';
            }

            ev.preventDefault();
            ev.stopPropagation();
            return false;
        });

        $body.on('dragleave', function(ev) {
            // self.logDebug('dragleave', ev.target);

            $target = $(ev.target);
            $target.removeClass('dragover');

            for (var i = self.dragevents.length; i--; i) {
                if (self.dragevents[i] === ev.target) self.dragevents.splice(i, 1); // remove the target element from our stack
            }

            if (self.dragevents.length === 0) {
                // self.logDebug('FINAL DRAGLEAVE');
                $body.removeClass('dragging');
            }
        });

        $body.on('dragend', function(ev) {
            // self.logDebug('dragend', ev.target);
            $body.removeClass('dragging');
        });

        this.$widget.on("drop", function(ev) {
            // self.logDebug("list drop", ev.target);
            ev.stopPropagation();
            ev.preventDefault();

            self.dragevents = []; // re-init
            $body.removeClass('dragging');

            var files = ev.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                // console.log("dropped multiple files on the image list", files.length, ev);
                self.appendMultipleFiles(files);
            }
        });

        this.$widget.on("drop", ".imagelist__thumb-img", function(ev) {
            // self.logDebug("replacement drop", ev.target);
            ev.stopPropagation();
            ev.preventDefault();

            self.dragevents = []; // re-init
            $body.removeClass('dragging');

            var $item = $(this).parents(".imagelist__thumb").first();
            var item_id = $item.attr("data-item-id");
            // self.logDebug('dropOnThumb?', item_id, '$this=', $(this), 'item=', $item);
            var files = ev.originalEvent.dataTransfer.files;
            if (files.length == 1) {
                console.log("dropped 1 file on an image item", item_id);
                if ($item.hasClass("newitem")) {
                    self.appendImageFile(files[0]);
                } else {
                    self.replaceImageFile(item_id, files[0]);
                }
            }
        });
    };

    ImageList.prototype.appendMultipleFiles = function(files) {
        files = Array.prototype.slice.call(files);

        _.forEach(files, function(f) {
            this.appendImageFile(f);
        }.bind(this));
    };

    ImageList.prototype.uploadFile = function(file, progress, end) {
        var self = this;
        uploadFile(self.upload_input_name, file, self.upload_url, progress.bind(self), end.bind(self));
    };

    ImageList.prototype.replaceImageFile = function(item_id, file) {
        var self = this;
        self.uploadFile(file, function(progress) {
            console.log("progress", progress);
        }, function(err, response_json){
            if (err) {
                console.log("upload error", err, response_json);
                self.updatePopupItems();
                return;
            }

            self.logDebug('upload complete for replaceImageFile', response_json, 'item_id=', item_id);

            var $thumb = self.$widget.find(".imagelist__thumb[data-item-id="+item_id+"] .imagelist__thumb-img");
            $thumb.attr("src", response_json.file.download_url||'');

            var $item = self.$widget.find(".imagelist__image[data-item-id="+item_id+"]");
            $item.find(".imagelist__image-img").attr("src", response_json.file.download_url||'')
            $item.find(".imagelist__image-location").val(response_json.file.location||'');

            self.updatePopupItems();
            self.cancelAoi();
        });
    };

    ImageList.prototype.appendImageFile = function(file) {
        var self = this;

        // self.logDebug('Appending file', file);


        // create a new thumbnail
        var $thumbitem_tpl = self.$widget.find(".imagelist__thumb.newitem"); // LI
        var $newthumbitem = $thumbitem_tpl.clone();
        //$newthumbitem.find(".imagelist__thumb-img").attr('src', response_json.file.download_url||'');
        $newthumbitem.removeClass("newitem").addClass('hb-js-uploading');

        var $progress = $newthumbitem.find('progress').first()[0];
        $progress.value=0;
        $newthumbitem.appendTo(self.$widget.find('.imagelist-tabs__toggles'));

        self.uploadFile(file, function(progress) {
            console.log("progress", progress);
            $progress.value=progress;
        }, function(err, response_json){
            if (err) {
                $newthumbitem.remove();
                console.log("upload error", err);
                return;
            }

            self.current_index++;

            self.logDebug('upload complete – new index='+self.current_index, response_json);

            // create a new image inputs docfragment
            // TODO create and move the radio input of the template item around as well
            var $item_tpl = self.$widget.find(".imagelist__item.newitem").first();
            var input_name_prefix = $item_tpl.data('grouped-base-path');
            var $newitem = $item_tpl.clone();
            $newitem.find(".imagelist__image-location").val(response_json.file.location||'');
            $newitem.find(".imagelist__image-img").attr('src', response_json.file.download_url||'');
            $newitem.removeClass('newitem').find('.newitem').removeClass('newitem'); // the clone is not a template item
            self.bindItemFileInput($newitem);

            // all named input elements from the template need to get new names with the new index
            $newitem.find(':input[name]').each(function(idx, input) {
                var $input = $(input);
                var new_input_name_prefix = input_name_prefix.replace(/\[(\d+)\]$/, function(matches) {
                    return "[" + self.current_index + "]";
                });
                $input.attr('name', $input.attr('name').replace(input_name_prefix, new_input_name_prefix));
            });
            $newitem.appendTo(self.$widget.find('.imagelist-tabs__content'));

            // create a new thumbnail
            //var $thumbitem_tpl = self.$widget.find(".imagelist__thumb.newitem"); // LI
            //var $newthumbitem = $thumbitem_tpl.clone();
            $newthumbitem.find(".imagelist__thumb-img").attr('src', response_json.file.download_url||'');
            $newthumbitem.removeClass('hb-js-uploading');
            $newthumbitem.find('progress').remove();
            //$newthumbitem.removeClass("newitem");
            //$newthumbitem.appendTo(self.$widget.find('.imagelist-tabs__toggles'));

            // update popup items to contain the new image
            self.updatePopupItems();
            self.cancelAoi();
        });
    };

    ImageList.prototype.moveItem = function(from, to) {
        // TODO move the radio inputs of the items around as well
        var items = this.$widget.find(".imagelist-tabs__content > .imagelist__item");
        var thumbs = this.$widget.find(".imagelist-tabs__toggles > .imagelist__thumb");

        if (to < from) {
            items.eq(from).insertBefore(items.eq(to));
            thumbs.eq(from).insertBefore(thumbs.eq(to));
        } else if (from < to) {
            items.eq(from).insertAfter(items.eq(to));
            thumbs.eq(from).insertAfter(thumbs.eq(to));
        }

        this.updatePopupItems();
    };

    ImageList.prototype.acceptAoi = function() {
        // this.logDebug(this.aoi_selectrect.selection);
        this.aoi_selectrect.$input.val(this.aoi_selectrect.selection);
        this.cancelAoi();
    };

    ImageList.prototype.cancelAoi = function() {
        if (this.aoi_selectrect.rect) {
            this.aoi_selectrect.rect.cancel();
        }
        this.aoi_selectrect = {};
        this.$widget.find(".imagelist__image-aoi").addClass("hide");
        this.$widget.find(".imagelist__image-aoi-trigger").removeClass("hide");
    };

    ImageList.prototype.selectAoi = function($btn) {
        var self = this;
        this.cancelAoi();
        var $item = $btn.closest('.imagelist__image');
        var $img =  $item.find(".imagelist__image-img");
        var $aoi_input = $item.find(".imagelist__aoi-input");
        var aoi = $aoi_input.val();
        try {
            aoi = JSON.parse(aoi);
            aoi = {
                x: aoi[0],
                y: aoi[1],
                w: aoi[2],
                h: aoi[3]
            };
        } catch (e) {
            aoi = undefined;
        }
        this.aoi_selectrect = {
            rect: selectrect($img[0], aoi),
            selection: {},
            $input: $aoi_input
        };
        this.aoi_selectrect.rect.onUpdate(function(aoi) {
            self.aoi_selectrect.selection = JSON.stringify([
                aoi.x,
                aoi.y,
                aoi.w,
                aoi.h
            ]);
        });
    };

    return ImageList;
});

