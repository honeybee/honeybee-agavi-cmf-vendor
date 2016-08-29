define([
    "Honeybee_Core/Widget",
    "magnific-popup"
], function(Widget) {

    var default_options = {
        prefix: "Honeybee_Core/ui/AssetList"
    };

    function uploadFile(input_name, file, target_url, progress_cb, end_cb) {
        var fd = new FormData();
        fd.append(input_name, file);

        jsb.fireEvent('WIDGET:BUSY_LOADING', {
            'type': 'start',
            'attribute_name': input_name
        });

        var xhr = new XMLHttpRequest();
        xhr.open('POST', target_url, true);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                var percentComplete = (e.loaded / e.total) * 100;
                progress_cb(percentComplete);
            }
        };

        xhr.onload = function() {
            var resp;

            if (this.getResponseHeader('Content-Type').indexOf('application/json') !== 0) {
                end_cb(new Error('Server response Content-Type is not application/json'), null);
            } else {
                try {
                    resp = JSON.parse(this.response);
                    if (this.status == 200) {
                        end_cb(null, resp);
                    } else if (this.status == 400) {
                        end_cb(new Error("Server response is not 200 OK."), resp);
                    }
                } catch (e) {
                    end_cb(new Error('Error while decoding JSON response!'), null);
                }
            }

            jsb.fireEvent('WIDGET:BUSY_LOADING', {
                'type': 'stop',
                'attribute_name': input_name
            });
        };

        xhr.send(fd);
    }

    function AssetList(dom_element, options) {
        var self = this;

        this.init(dom_element, default_options);
        this.addOptions(options);

        this.isReadable = !(this.isReadonly() || this.isDisabled());

        this.id = this.$widget.data('id');
        this.form_name = this.$widget.data('form-name');
        this.dummy_items = ['.newitem'];
        this.dropzone_selector = '.assetlist--dropzone';

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

        this.current_index = -1;

        if (this.isReadable) {
            this.bindDropEvents();
            this.bindClickEvents();
            this.bindMultipleInput();

            this.$widget.find('.assetlist-tabs__content > .assetlist__item').each(function(i, item) {
                var $item = $(item);
                self.bindItemFileInput($item);
                self.current_index++;
            });
        }

        this.$popup_trigger = this.$widget.find('.hb-field-label__name');
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
                }
            }
        }

        self.updateItemsCount();
        this.updatePopupItems();

        this.$widget.addClass('widget-initialized');

        this.$widget.on("click", ".assetlist__thumb", function(ev) {
            var $li = $(this);
            var item_index = $li.index();

            // console.log(self.id, item_index, self.popup_options, self.$popup_trigger);
                self.$popup_trigger.magnificPopup('open'); // why does …('open', idx) not work?
                self.$popup_trigger.magnificPopup('goTo', item_index-1);
        });

        this.$widget.on("click", ".assetlist__thumb-control.remove", function(ev) {
            ev.preventDefault();
            ev.stopPropagation();

            var $target = $(ev.target);
            var $thumb_item = $target.parents('.assetlist__thumb').first();
            var index = $thumb_item.index();
            var $next = $thumb_item.next();

            var items = self.$widget.find(".assetlist-tabs__content > .assetlist__item");
            var thumbs = self.$widget.find(".assetlist-tabs__toggles > .assetlist__thumb");

            items.eq(index).remove();
            thumbs.eq(index).remove();

            // TODO reindex the form fields to prevent gaps and update self.current_index
            // the FormPopulationFilter uses the index provided here to repopulate
            // while the rendering of the assets on serverside reindexes from 0-n without gaps

            self.updatePopupItems();
            self.updateItemsCount();
        });
    }

    AssetList.prototype = new Widget();
    AssetList.prototype.constructor = AssetList;

    AssetList.prototype.updateItemsCount = function() {
        $items = this.$widget.find('.assetlist-tabs__toggle');
        if (this.$widget.find('.assetlist-tabs__toggle').length - this.dummy_items.length) {
            this.$widget.addClass('has-items');
            this.$widget.removeClass('is-empty');
        } else {
            this.$widget.addClass('is-empty');
            this.$widget.removeClass('has-items');
        }
    }

    AssetList.prototype.updatePopupItems = function() {
        var items = [];

        this.$widget.find('.assetlist__asset').each(function(idx, item) {
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

    AssetList.prototype.bindClickEvents = function() {
        var self = this;
    };

    AssetList.prototype.bindItemFileInput = function($item) {
        var self = this;
        var item_id = $item.attr("data-item-id");
        // this.logDebug('bindItemFileInput', item_id, $item);
        $input = $item.find(".assetlist__asset-input");
        $input.addClass("visuallyhidden");
        $input.removeAttr('name');
        $input.on("change", function(ev) {
            var $input = $(this);
            var files = $input[0].files;
            // self.logDebug('input onChange:', files, $input);
            if (files.length == 1) {
                if ($item.hasClass("newitem")) {
                    self.appendAssetFile(files[0]);
                } else {
                    self.replaceAssetFile(item_id, files[0]);
                }
            } else {
                self.logDebug('Multiple files uploaded to input?', $input.id || '');
            }
        });
    };

    AssetList.prototype.bindMultipleInput = function() {
        var self = this;

        var $multi = this.$widget.find(".hb-assetlist__input-multiple");
        var $multiinput = this.$widget.find(".hb-assetlist__input-multiple-trigger");
        var $multilabel = this.$widget.find(".hb-assetlist__input-multiple-label");
        var newid = this.getRandomString();
        $multiinput.attr('id', newid);
        $multilabel.attr('for', newid);
        $multilabel.removeClass("hide");

        $multiinput.on("change", function(ev) {
            self.appendMultipleFiles(this.files);
        });
    };

    AssetList.prototype.bindDropEvents = function() {
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
            $target = $(ev.target);
            $parent_dropzone = $target.closest(self.dropzone_selector, self.$widget);

            if (self.dragevents.length === 0)
            {
                // self.logDebug('INITIAL DRAGENTER');
                //self.dropbox.addClass('dragging');
                $body.addClass('dragging');
                self.$widget.find('.dragover, .dragout').removeClass('dragover dragout');
            }

            if ($parent_dropzone.length) {
                $parent_dropzone.removeClass('dragout').addClass('dragover');
            }

            self.dragevents.push(ev.target); // add the target element to our stack

            ev.preventDefault();
            ev.stopPropagation();
            return false;
        });

        $body.on("dragover", function(ev) {
            // stop the event to notify browser, that a drop operation may be possible here
            $target = $(ev.target);
            $parent_dropzone = $target.closest(self.dropzone_selector, self.$widget);
            if ($parent_dropzone.length) {
                ev.originalEvent.dataTransfer.dropEffect = 'copy';
            }

            ev.preventDefault();
            ev.stopPropagation();
            return false;
        });

        $body.on('dragleave', function(ev) {
            // self.logDebug('dragleave', ev.target);
            $target = $(ev.target);
            $parent_dropzone = $target.closest(self.dropzone_selector, self.$widget);
            $last_entered = $(self.dragevents[self.dragevents.length - 1]);

            if($last_entered.closest(self.dropzone_selector, self.$widget).length === 0) {
                $parent_dropzone.removeClass('dragover').addClass('dragout');
            }

            for (var i = self.dragevents.length; i--; i) {
                if (self.dragevents[i] === ev.target) self.dragevents.splice(i, 1); // remove the target element from our stack
            }

            if (self.dragevents.length === 0) {
                // self.logDebug('FINAL DRAGLEAVE');
                $body.removeClass('dragging');
                self.$widget.find('.dragover, .dragout').removeClass('dragover dragout');
            }
        });

        $body.on('dragend', function(ev) {
            // self.logDebug('dragend', ev.target);
            $body.removeClass('dragging');
            self.$widget.find('.dragover, .dragout').removeClass('dragover dragout');
        });

        this.$widget.on("drop", function(ev) {
            // self.logDebug("list drop", ev.target);
            ev.stopPropagation();
            ev.preventDefault();

            self.dragevents = []; // re-init
            $body.removeClass('dragging');
            self.$widget.find('.dragover, .dragout').removeClass('dragover dragout');

            var files = ev.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                // console.log("dropped multiple files on the asset list", files.length, ev);
                self.appendMultipleFiles(files);
            }
        });

        this.$widget.on("drop", ".assetlist__thumb-img", function(ev) {
            // self.logDebug("replacement drop", ev.target);
            ev.stopPropagation();
            ev.preventDefault();

            self.dragevents = []; // re-init
            $body.removeClass('dragging');
            self.$widget.find('.dragover, .dragout').removeClass('dragover dragout');

            var $item = $(this).parents(".assetlist__thumb").first();
            var item_id = $item.attr("data-item-id");
            // self.logDebug('dropOnThumb?', item_id, '$this=', $(this), 'item=', $item);
            var files = ev.originalEvent.dataTransfer.files;
            if (files.length == 1) {
                console.log("dropped 1 file on an asset item", item_id);
                if ($item.hasClass("newitem")) {
                    self.appendAssetFile(files[0]);
                } else {
                    self.replaceAssetFile(item_id, files[0]);
                }
            }
        });
    };

    AssetList.prototype.appendMultipleFiles = function(files) {
        files = Array.prototype.slice.call(files);

        _.forEach(files, function(f) {
            this.appendAssetFile(f);
        }.bind(this));
    };

    AssetList.prototype.uploadFile = function(file, progress_cb, end_cb) {
        var self = this;
        uploadFile(self.upload_input_name, file, self.upload_url, progress_cb.bind(self), end_cb.bind(self));
    };

    AssetList.prototype.replaceAssetFile = function(item_id, file) {
        var self = this;
        self.uploadFile(file, function(progress) {
            self.logDebug("replaceAssetFile progress", progress);
        }, function(err, response_json){
            if (err) {
                self.updatePopupItems();
                self.logDebug("replaceAssetFile upload error", err, response_json);
                if (response_json) {
                    var msg = self.options.upload_error_msg || 'File was not accepted by the server.';
                    msg += "\n\n";
                    $.each(response_json.messages, function(idx, error_msg) {
                        msg += '- '+error_msg+"\n";
                    });
                    window.alert(msg);
                }
                return;
            }

            self.logDebug('upload complete for replaceAssetFile', response_json, 'item_id=', item_id);

            var $thumb = self.$widget.find(".assetlist__thumb[data-item-id="+item_id+"] .assetlist__thumb-img");
            $thumb.attr("src", response_json.file.download_url||'');

            var $item = self.$widget.find(".assetlist__asset[data-item-id="+item_id+"]");
            $item.find(".assetlist__asset-img").attr("src", response_json.file.download_url||'')
            $item.find(".assetlist__asset-location").val(response_json.file.location||'');
            $item.find(".assetlist__asset-filename").val(response_json.file.filename);
            $item.find(".assetlist__asset-filesize").val(response_json.file.filesize);
            $item.find(".assetlist__asset-mimetype").val(response_json.file.mimetype);

            self.updatePopupItems();
        });
    };

    AssetList.prototype.appendAssetFile = function(file) {
        var self = this;

        // self.logDebug('Appending file', file);

        // create a new thumbnail
        var $thumbitem_tpl = self.$widget.find(".assetlist__thumb.newitem"); // LI
        var $newthumbitem = $thumbitem_tpl.clone();
        //$newthumbitem.find(".assetlist__thumb-img").attr('src', response_json.file.download_url||'');
        $newthumbitem.removeClass("newitem").addClass('hb-js-uploading');

        var $progress = $newthumbitem.find('progress').first()[0];
        $progress.value=0;
        $newthumbitem.appendTo(self.$widget.find('.assetlist-tabs__toggles'));
        self.updateItemsCount();

        self.uploadFile(
            file,
            function(progress) {
                console.log("progress", progress);
                $progress.value=progress;
            }, function(err, response_json){
                if (err) {
                    $newthumbitem.remove();
                    self.updateItemsCount();
                    self.logDebug("appendAssetFile upload error", err, response_json);
                    if (response_json) {
                        var msg = self.options.upload_error_msg || 'File was not accepted by the server.';
                        msg += "\n\n";
                        $.each(response_json.messages, function(idx, error_msg) {
                            msg += '- '+error_msg+"\n";
                        });
                        window.alert(msg);
                    }
                    return;
                }

                self.current_index++;

                self.logDebug('upload complete – new index='+self.current_index, response_json);

                // create a new asset inputs docfragment
                // TODO create and move the radio input of the template item around as well
                var $item_tpl = self.$widget.find(".assetlist__item.newitem").first();
                var input_name_prefix = $item_tpl.data('grouped-base-path');
                var $newitem = $item_tpl.clone();
                $newitem.find(".assetlist__asset-location").val(response_json.file.location||'');
                $newitem.find(".assetlist__asset-img").attr('src', response_json.file.download_url||'');

                $newitem.find(".assetlist__asset-filename").val(response_json.file.filename);
                $newitem.find(".assetlist__asset-filesize").val(response_json.file.filesize);
                $newitem.find(".assetlist__asset-mimetype").val(response_json.file.mimetype);

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
                $newitem.appendTo(self.$widget.find('.assetlist-tabs__content'));

                // create a new thumbnail
                //var $thumbitem_tpl = self.$widget.find(".assetlist__thumb.newitem"); // LI
                //var $newthumbitem = $thumbitem_tpl.clone();

                // prevent flickering while loading after setting the new 'src'
                $newthumbitem.load(function() {
                    $newthumbitem.removeClass('hb-js-uploading');
                });
                $newthumbitem.find(".assetlist__thumb-img").attr('src', response_json.file.download_url||'');
                $newthumbitem.find('progress').remove();
                //$newthumbitem.removeClass("newitem");
                //$newthumbitem.appendTo(self.$widget.find('.assetlist-tabs__toggles'));

                // update popup items to contain the new asset
                self.updatePopupItems();
            }
        );
    };

    return AssetList;
});
