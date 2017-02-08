define([
    "Honeybee_Core/Widget",
    "squire",
    "dompurify",
    "magnific-popup"
], function(Widget, Squire, DOMPurify) {

    var default_options = {
        prefix: "Honeybee_Core/ui/HtmlRichTextEditor",
        count_template: '{COUNT}',
        // the original textarea element that will be hidden
        textarea_selector: 'textarea',
        hide_textarea: true,
        // element that will be used as editor for the textarea content
        editor_input_selector: '.editor-hrte',
        // Squire options to use
        squire_config: {
            blockTag: 'BLOCK', // Squire default is 'DIV', 'BLOCK' is used as dompurify will remove that automatically
            blockAttributes: null,
            tagAttributes: {
                ul: null,
                ol: null,
                li: null,
                a: null
            }
        },
        // DOMPurify options to use upon editor.setHTML call (via sanitizeToDOMFragment function)
        dompurify_config: {
            WHOLE_DOCUMENT: false,
            RETURN_DOM: true,
            RETURN_DOM_FRAGMENT: true,
            FORBID_TAGS: [],
            FORBID_ATTR: []
        },
        // DOMPurify options to use when pasting untrusted things from clipboard (via sanitizeToDOMFragment function)
        dompurify_paste_config: {
            WHOLE_DOCUMENT: false,
            RETURN_DOM: true,
            RETURN_DOM_FRAGMENT: true,
            FORBID_TAGS: ['style'],
            FORBID_ATTR: ['style']
        },
        // DOMPurify options to use upon syncing the changed HTML back to the original (hidden) textarea
        dompurify_sync_config: {
            WHOLE_DOCUMENT: false,
            RETURN_DOM: false,
            RETURN_DOM_FRAGMENT: false,
            FORBID_TAGS: ['BLOCK'],
            FORBID_ATTR: []
        }
    };

    function HtmlRichTextEditor(dom_element, options) {
        var that = this;

        if (!('contentEditable' in document.body) || !DOMPurify.isSupported) {
            return;
        }

        // feeling lucky from here on as windows Phone, IE<9, iOS<8 etc. are buggy or non-working

        this.init(dom_element, default_options);
        this.addOptions(options);

        // a custom sanitization function may be specified for initial setHTML call and pasting content
        if (typeof this.options.sanitizeToDOMFragment !== 'function') {
            this.options.squire_config.sanitizeToDOMFragment = function(html, is_paste, squire_instance) {
                return that.sanitizeToDOMFragment(html, is_paste, squire_instance);
            };
        }

        this.$textarea = this.$widget.find(this.options.textarea_selector).first();
        if (this.$textarea.length === 0) {
            this.$textarea = $(this.options.textarea_selector);
        }

        this.$editor_input = this.$widget.find(this.options.editor_input_selector).first();
        if (this.$editor_input.length === 0) {
            $(this.options.editor_input_selector);
        }

        if (this.$textarea.length !== 1 || this.$editor_input.length !== 1) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            return;
        }

        // switch the label of the textarea to hide to the wysiwyg contenteditable element
        // hint: this doesn't work ATM as browsers and specs don't see contenteditable as controls
        // if (!_.isString(this.$editor_input.id)) {
        //     this.$editor_input.prop('id', 'hrte-' + this.getRandomString());
        // }
        // this.$textarea_label = $('label[for="'+this.$textarea.prop('id')+'"]');
        // this.$textarea_label.prop('for', this.$editor_input.prop('id'));

        this.canUndo = false;
        this.canRedo = false;
        this.isFocussed = false;
        this.maxlength = this.$textarea.prop('maxLength');
        this.$editor_count = this.$widget.find('.editor-count');
        this.readonly = this.options.isReadonly || this.options.isDisabled || this.$textarea.prop('readonly');

        this.buttons = {
            bold: {
                $btn: this.$widget.find('[data-editor-action="bold"]'),
                highlight: function() { return that.isBold(); },
                enable: function() { return that.isBold() || that.editor.getSelectedText() !== ''; }
            },
            italic: {
                $btn: this.$widget.find('[data-editor-action="italic"]'),
                highlight: function() { return that.isItalic(); },
                enable: function() { return that.isItalic() || that.editor.getSelectedText() !== ''; }
            },
            underline: {
                $btn: this.$widget.find('[data-editor-action="underline"]'),
                highlight: function() { return that.isUnderlined(); },
                enable: function() { return that.isUnderlined() || that.editor.getSelectedText() !== ''; }
            },
            strike: {
                $btn: this.$widget.find('[data-editor-action="strike"]'),
                highlight: function() { return that.isStriked(); },
                enable: function() { return that.isStriked() || that.editor.getSelectedText() !== ''; }
            },
            ul: {
                $btn: this.$widget.find('[data-editor-action="ul"]'),
                highlight: function() { return that.isUnorderedList(); },
                enable: function() { return that.isFocussed; }
            },
            ol: {
                $btn: this.$widget.find('[data-editor-action="ol"]'),
                highlight: function() { return that.isOrderedList(); },
                enable: function() { return that.isFocussed; }
            },
            link: {
                $btn: this.$widget.find('[data-editor-action="link"]'),
                highlight: function() { return that.isLink(); },
                enable: function() { return that.isLink() || that.editor.getSelectedText() !== ''; }
            },
            unlink: {
                $btn: this.$widget.find('[data-editor-action="unlink"]'),
                highlight: function() { return false; },
                enable: function() { return that.isLink(); }
            },
            undo: {
                $btn: this.$widget.find('[data-editor-action="undo"]'),
                highlight: function() { return false; },
                enable: function() { return that.canUndo; }
            },
            redo: {
                $btn: this.$widget.find('[data-editor-action="redo"]'),
                highlight: function() { return false; },
                enable: function() { return that.canRedo; }
            },
            autogrow: {
                $btn: this.$widget.find('[data-editor-action="autogrow"]'),
                highlight: function() { return that.$editor.hasClass('editor--autogrow'); },
                enable: function() { return true; }
            },
        };

        this.$editor = this.$editor_input.closest('.editor');

        // save editor instance
        this.editor = this.createSquireInstance();

        // set initial content of squire editor
        this.validate(this.$textarea.val());
        this.editor.setHTML(this.$textarea.val());

        // hide textarea this widget syncs with
        if (this.options.hide_textarea === true) {
            this.$textarea.hide();
        }

        if (this.readonly) {
            this.editor.getRoot().setAttribute('contenteditable', false);

            for (var button_name in this.buttons) {
                if (this.buttons.hasOwnProperty(button_name)) {
                    this.buttons[button_name].$btn.prop('disabled', true);
                    this.buttons[button_name].enable = function() { return false; };
                }
            }

            this.$editor.addClass('editor--readonly');  // IE doesn't support :read-only selector

            return;
        }

        // this.$editor_input.on('click', function(ev) {
        //     that.updateUI();
        // });

        // format content on action button clicks
        this.$widget.on('click', '[data-editor-action]', function(ev) {
            that.handleAction(this, ev);
        });

        // prepare link button to open a popup dialog
        this.$link = null;
        this.$editor_popup_link = this.$widget.find('.editor-popup-link').first();
        var editor_popup_link_id = 'hrte-popup-' + this.getRandomString();
        this.$editor_popup_link.prop('id', editor_popup_link_id);
        this.buttons.link.$btn.data('mfpSrc', '#'+editor_popup_link_id);
        this.buttons.link.$btn.magnificPopup({
            preloader: false,
            items: [
                {
                    type: 'inline',
                    src: '#'+that.$editor_popup_link.prop('id'),
                    focus: '.editor-popup-link__input-url',
                    modal: true,
                }
            ],
            callbacks: {
                beforeOpen: function() {
                    var range = that.editor.getSelection().cloneRange();
                    var path = that.editor.getPath();
                    var link = null;
                    var is_link = new RegExp('(?:^|>)A\\b');
                    if (is_link.test(path)) {
                        // cursor is most likely within a link
                        link = range.commonAncestorContainer.parentElement;
                        // as use might have selected only a part of the link we select the whole link
                        // to be consistent with only having the cursor inside the link text
                        that.editor.getSelection().selectNode(link);
                    } else if (path === '(selection)' || path === 'BLOCK') {
                        // user selected some text that may include no or multiple links
                        $(range.commonAncestorContainer).find('a').each(function(idx, elm) {
                            if (elm.tagName === 'A' && Squire.isNodeContainedInRange(range, elm, true)) {
                                link = elm;
                            }
                        });
                    }
                    // add found link to dialog so user may change what was typed before
                    if (link !== null) {
                        var $link = $(link);
                        that.$editor_popup_link.find('.editor-popup-link__input-url').first().val($link.prop('href') || '');
                        that.$editor_popup_link.find('.editor-popup-link__input-title').first().val($link.prop('title') || '');
                        var target = $link.prop('target');
                        if (target.search(/_blank/i) >= 0) {
                            that.$editor_popup_link.find('.editor-popup-link__input-target').first().prop('checked', true);
                        } else {
                            that.$editor_popup_link.find('.editor-popup-link__input-target').first().prop('checked', false);
                        }
                    }
                }
            }
        });

        // link dialog accept button
        this.$widget.find('.editor-popup__accept').on('click', function(ev) {
            var url = that.$editor_popup_link.find('.editor-popup-link__input-url').first().val();
            var title = that.$editor_popup_link.find('.editor-popup-link__input-title').first().val();
            var open_in_blank = that.$editor_popup_link.find('.editor-popup-link__input-target').first().prop('checked');
            var url_attributes = {
                'title': title,
                'target': open_in_blank ? '_blank' : '',
                'rel': open_in_blank ? 'noopener noreferrer' : '',
            };
            that.editor.makeLink(url, url_attributes);
            that.buttons.link.$btn.magnificPopup('close');
            that.editor.focus();
            ev.preventDefault();
        });

        // link dialog cancel button
        this.$widget.find('.editor-popup__cancel').on('click', function(ev) {
            that.buttons.link.$btn.magnificPopup('close');
            ev.preventDefault();
        });

        // initial ui update to have all buttons in their proper displayed state
        this.updateUI();

        this.$editor.show();
    }

    HtmlRichTextEditor.prototype = new Widget();
    HtmlRichTextEditor.prototype.constructor = HtmlRichTextEditor;

    HtmlRichTextEditor.prototype.createSquireInstance = function() {
        var that = this;

        // init Squire instance
        var editor = new Squire(this.$editor_input[0], this.options.squire_config);

        // sync content to textarea on input
        editor.addEventListener('input', function(ev) {
            var sanitized_html = that.sanitize(that.editor.getHTML());
            if (that.validate(sanitized_html)) {
                that.$textarea.val(sanitized_html);
            }
        });

        editor.addEventListener('undoStateChange', function(ev) {
            that.canUndo = ev.canUndo;
            that.canRedo = ev.canRedo;
            that.updateUI();
        });

        editor.addEventListener('focus', function(ev) {
            that.isFocussed = true;
        });

        editor.addEventListener('blur', function(ev) {
            that.isFocussed = false;
        });

        // editor.addEventListener('pathChange', function(ev) {
        //     //that.updateUI();
        // });
        // 
        // editor.addEventListener('keyup', function(ev) {
        //     // that.updateUI();
        // });
        // 
        // editor.addEventListener('select', function(ev) {
        //     // that.updateUI();
        // });

        return editor;
    };

    HtmlRichTextEditor.prototype.sanitize = function(text) {
        var sanitized_html = DOMPurify.sanitize(text, this.options.dompurify_sync_config);
        // remove trailing <br> element after DIV block removal
        return sanitized_html.replace(/<br\>$/, '');
    }

    HtmlRichTextEditor.prototype.validate = function(text)  {
        var valid = true;
        this.$editor_count.html(this.options.count_template.replace(/{COUNT}/, text.length));

        if (this.maxlength !== -1 && text.length > this.maxlength) {
            valid = false;
        }

        if (valid) {
            this.$textarea.removeClass('invalid');
        } else {
            this.$textarea.addClass('invalid');
        }
        jsb.fireEvent('TABS:UPDATE_ERROR_BUBBLES');

        return valid;
    };

    HtmlRichTextEditor.prototype.updateUI = function() {
        var undo = this.buttons.undo;
        if (undo.enable()) {
            undo.$btn.prop('disabled', false);
        } else {
            undo.$btn.prop('disabled', true);
        }

        var redo = this.buttons.redo;
        if (redo.enable()) {
            redo.$btn.prop('disabled', false);
        } else {
            redo.$btn.prop('disabled', true);
        }

        if (this.$editor.hasClass('editor--autogrow')) {
            this.buttons.autogrow.$btn.addClass('active');
        } else {
            this.buttons.autogrow.$btn.removeClass('active');
        }

        // _.forOwn(this.buttons, function(btn, name) {
        //     if (btn.enable && btn.enable()) {
        //         btn.$btn.prop('disabled', false);
        //     } else {
        //         btn.$btn.prop('disabled', true);
        //     }
        //     if (btn.highlight()) {
        //         btn.$btn.addClass('active');
        //     } else {
        //         btn.$btn.removeClass('active');
        //     }
        // });
    };

    HtmlRichTextEditor.prototype.sanitizeToDOMFragment = function(html, is_paste, squire_instance) {
        var dompurify_config = is_paste ? this.options.dompurify_paste_config : this.options.dompurify_config;
        var fragment = DOMPurify.sanitize(html, dompurify_config);

        if (!fragment) {
            fragment = squire_instance.getDocument().createDocumentFragment();
        }

        return fragment;
    };

    HtmlRichTextEditor.prototype.handleAction = function(action_element, ev) {
        var that = this;
        var $action = $(action_element);
        var action = $action.data('editorAction');
        var editor = this.editor;

        // current command AND state under current path/cursor
        var cur = {
            isBold: action === 'bold' && this.queryCommandState('B'),
            isItalic: action === 'italic' && this.queryCommandState('I'),
            isUnderline: action === 'underline' && this.queryCommandState('U'),
            isStrike: action === 'strike' && this.queryCommandState('S'),
            isOrderedList: action === 'ol' && this.queryCommandState('OL'),
            isUnorderedList: action === 'ul' && this.queryCommandState('UL'),
            isUnlink: action === 'unlink' && this.queryCommandState('A')
        };

        if (cur.isBold |
            cur.isItalic |
            cur.isUnderline |
            cur.isStrike |
            cur.isOrderedList |
            cur.isUnorderedList |
            cur.isUnlink
        ) {
            if (cur.isBold) {
                editor.removeBold();
            }
            if (cur.isItalic) {
                editor.removeItalic();
            }
            if (cur.isUnderline) {
                editor.removeUnderline();
            }
            if (cur.isStrike) {
                editor.removeStrikethrough();
            }
            if (cur.isOrderedList || cur.isUnorderedList) {
                editor.removeList();
            }
            if (cur.isUnlink) {
                editor.removeLink();
            }
        } else if (action === 'link') {
            // do nothing as a dialog will be shown to the user to add/change the url etc.
        } else if (action === 'autogrow') {
            this.$editor.toggleClass('editor--autogrow');
            if (this.$editor.hasClass('editor--autogrow')) {
                this.buttons.autogrow.$btn.addClass('active');
            } else {
                this.buttons.autogrow.$btn.removeClass('active');
            }
        } else {
            switch (action) {
                case 'strike':
                    editor.strikethrough();
                    break;
                case 'ol':
                    editor.makeOrderedList();
                    break;
                case 'ul':
                    editor.makeUnorderedList();
                    break;
                case 'unlink':
                    editor.removeLink();
                    break;
                default:
                    that.execCommand(action);
                    break;
            }
            editor.focus();
        }
    };

    HtmlRichTextEditor.prototype.getSelectedText = function () {
        return this.editor.getSelectedText();
    };

    HtmlRichTextEditor.prototype.execCommand = function(command, arg) {
        var editor = this.editor;
        if (this.editor && this.editor[command]) {
            this.editor[command](arg);
        } else {
            throw new Error('Unknown Squire command.');
        }

        return this;
    };

    HtmlRichTextEditor.prototype.queryCommandState = function(tag) {
        var regexp = new RegExp('(?:^|>)' + tag + '\\b');
        var path = this.editor.getPath();
        return path === '(selection)' ? this.editor.hasFormat(tag) : regexp.test(path);
    };

    HtmlRichTextEditor.prototype.isBold = function() {
        return this.queryCommandState('B');
    };

    HtmlRichTextEditor.prototype.isItalic = function() {
        return this.queryCommandState('I');
    };

    HtmlRichTextEditor.prototype.isUnderlined = function() {
        return this.queryCommandState('U');
    };

    HtmlRichTextEditor.prototype.isStriked = function() {
        return this.queryCommandState('S');
    };

    HtmlRichTextEditor.prototype.isList = function() {
        return this.isUnorderedList() || this.isOrderedList();
    };

    HtmlRichTextEditor.prototype.isUnorderedList = function() {
        return this.queryCommandState('UL');
    };

    HtmlRichTextEditor.prototype.isOrderedList = function() {
        return this.queryCommandState('OL');
    };

    HtmlRichTextEditor.prototype.isLink = function() {
        return this.queryCommandState('A');
    };

    return HtmlRichTextEditor;
});
