define([
    "Honeybee_Core/Widget",
    "Honeybee_Core/ui/CharCounter",
    "squire",
    "dompurify",
    "magnific-popup",
], function(Widget, CharCounter, Squire, DOMPurify) {

    // only allow the following tags when pasting contents into the richtext editor
    var ALLOWED_TAGS_ON_PASTE = [
        // HTML
        'a','b','i','u','s','br','ul','ol','li',
        // Text
        '#text'
    ];

    // only allow the following tags when pasting contents into the richtext editor
    var ALLOWED_ATTRS_ON_PASTE = [
        'href', 'target', 'title', 'lang', 'rel'
    ];

    // forbid all other tags when pasting contents into the richtext editor
    var FORBIDDEN_TAGS_ON_PASTE = [
        // HTML
        'span','abbr','acronym','p','div','em','strong','sub','sup','strike','hr','h1','h2','h3','h4','h5','h6','address','area','article','aside','audio','bdi','bdo','big','blink','blockquote','body','button','canvas','caption','center','cite','code','col','colgroup','content','data','datalist','dd','decorator','del','details','dfn','dir','dl','dt','element','fieldset','figcaption','figure','font','footer','form','head','header','hgroup','html','img','input','ins','kbd','label','legend','main','map','mark','marquee','menu','menuitem','meter','nav','nobr','optgroup','option','output','pre','progress','q','rp','rt','ruby','samp','section','select','shadow','source','spacer','strike','style','summary','table','tbody','td','template','textarea','tfoot','th','thead','time','tr','track','tt','var','video','wbr',
        // SVG
        'svg','altglyph','altglyphdef','altglyphitem','animatecolor','animatemotion','animatetransform','circle','clippath','defs','desc','ellipse','filter','font','g','glyph','glyphref','hkern','image','line','lineargradient','marker','mask','metadata','mpath','path','pattern','polygon','polyline','radialgradient','rect','stop','switch','symbol','text','textpath','title','tref','tspan','view','vkern',
        // SVG Filters
        'feBlend','feColorMatrix','feComponentTransfer','feComposite','feConvolveMatrix','feDiffuseLighting','feDisplacementMap','feFlood','feFuncA','feFuncB','feFuncG','feFuncR','feGaussianBlur','feMerge','feMergeNode','feMorphology','feOffset','feSpecularLighting','feTile','feTurbulence',
        // MathML
        'math','menclose','merror','mfenced','mfrac','mglyph','mi','mlabeledtr','mmuliscripts','mn','mo','mover','mpadded','mphantom','mroot','mrow','ms','mpspace','msqrt','mystyle','msub','msup','msubsup','mtable','mtd','mtext','mtr','munder','munderover',
    ];

    // forbid all other attributes when pasting contents into the richtext editor
    var FORBIDDEN_ATTRS_ON_PASTE = [
        // HTML
        'accept','action','align','alt','autocomplete','background','bgcolor','border','cellpadding','cellspacing','checked','cite','class','clear','color','cols','colspan','coords','datetime','default','dir','disabled','download','enctype','face','for','headers','height','hidden','high','hreflang','id','ismap','label','list','loop', 'low','max','maxlength','media','method','min','multiple','name','noshade','novalidate','nowrap','open','optimum','pattern','placeholder','poster','preload','pubdate','radiogroup','readonly','required','rev','reversed','rows','rowspan','spellcheck','scope','selected','shape','size','span','srclang','start','src','step','style','summary','tabindex','type','usemap','valign','value','width','xmlns',
        // SVG
        'accent-height','accumulate','additivive','alignment-baseline','ascent','attributename','attributetype','azimuth','basefrequency','baseline-shift','begin','bias','by','clip','clip-path','clip-rule','color','color-interpolation','color-interpolation-filters','color-profile','color-rendering','cx','cy','d','dx','dy','diffuseconstant','direction','display','divisor','dur','edgemode','elevation','end','fill','fill-opacity','fill-rule','filter','flood-color','flood-opacity','font-family','font-size','font-size-adjust','font-stretch','font-style','font-variant','font-weight','fx', 'fy','g1','g2','glyph-name','glyphref','gradientunits','gradienttransform','image-rendering','in','in2','k','k1','k2','k3','k4','kerning','keypoints','keysplines','keytimes','lengthadjust','letter-spacing','kernelmatrix','kernelunitlength','lighting-color','local','marker-end','marker-mid','marker-start','markerheight','markerunits','markerwidth','maskcontentunits','maskunits','max','mask','mode','min','numoctaves','offset','operator','opacity','order','orient','orientation','origin','overflow','paint-order','path','pathlength','patterncontentunits','patterntransform','patternunits','points','preservealpha','r','rx','ry','radius','refx','refy','repeatcount','repeatdur','restart','result','rotate','scale','seed','shape-rendering','specularconstant','specularexponent','spreadmethod','stddeviation','stitchtiles','stop-color','stop-opacity','stroke-dasharray','stroke-dashoffset','stroke-linecap','stroke-linejoin','stroke-miterlimit','stroke-opacity','stroke','stroke-width','surfacescale','targetx','targety','transform','text-anchor','text-decoration','text-rendering','textlength','u1','u2','unicode','values','viewbox','visibility','vert-adv-y','vert-origin-x','vert-origin-y','word-spacing','wrap','writing-mode','xchannelselector','ychannelselector','x','x1','x2','y','y1','y2','z','zoomandpan',
        // MathML
        'accent','accentunder','bevelled','close','columnsalign','columnlines','columnspan','denomalign','depth','display','displaystyle','fence','frame','largeop','length','linethickness','lspace','lquote','mathbackground','mathcolor','mathsize','mathvariant','maxsize','minsize','movablelimits','notation','numalign','open','rowalign','rowlines','rowspacing','rowspan','rspace','rquote','scriptlevel','scriptminsize','scriptsizemultiplier','selection','separator','separators','stretchy','subscriptshift','supscriptshift','symmetric','voffset',
        // XML
        'xlink:href','xml:id','xlink:title','xml:space','xmlns:xlink'
    ];

    var default_options = {
        prefix: "Honeybee_Core/ui/HtmlRichTextEditor",
        counter_enabled: false,
        // the original textarea element that will be hidden
        textarea_selector: 'textarea',
        hide_textarea: true,
        // element that will be used as editor for the textarea content
        editor_selector: '.editor-hrte',
        // Squire options to use
        squire_config: {
            blockTag: 'DIV',
            blockAttributes: {
                'class': 'hb-paragraph'
            },
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
            FORBID_TAGS: FORBIDDEN_TAGS_ON_PASTE,
            FORBID_ATTR: FORBIDDEN_ATTRS_ON_PASTE,
            ALLOW_TAGS: ALLOWED_TAGS_ON_PASTE,
            ALLOWED_ATTR: ALLOWED_ATTRS_ON_PASTE,
            ALLOW_DATA_ATTR: false,
            DATA_URI_TAGS: []
        },
        // DOMPurify options to use upon syncing the changed HTML back to the original (hidden) textarea
        dompurify_sync_config: {
            WHOLE_DOCUMENT: false,
            RETURN_DOM: false,
            RETURN_DOM_FRAGMENT: false,
            FORBID_TAGS: [ 'style' ],
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

        this.$editor = this.$widget.find(this.options.editor_selector).first();
        if (this.$editor.length === 0) {
            this.$editor = $(this.options.editor_selector);
        }

        if (this.$textarea.length !== 1 || this.$editor.length !== 1) {
            this.logError(this.getPrefix() + " behaviour not applied as expected DOM doesn't match.");
            this.$widget.find('.editor-menu,.editor-hrte').hide();
            return;
        }

        // switch the label of the textarea to hide to the wysiwyg contenteditable element
        // hint: this doesn't work ATM as browsers and specs don't see contenteditable as controls
        // if (!_.isString(this.$editor.id)) {
        //     this.$editor.prop('id', 'hrte-' + this.getRandomString());
        // }
        // this.$textarea_label = $('label[for="'+this.$textarea.prop('id')+'"]');
        // this.$textarea_label.prop('for', this.$editor.prop('id'));

        this.canUndo = false;
        this.canRedo = false;
        this.isFocussed = false;
        this.maxlength = this.$textarea.prop('maxLength');
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
                highlight: function() { return that.$widget.hasClass('editor--autogrow'); },
                enable: function() { return true; }
            },
            empty: {
                $btn: this.$widget.find('[data-editor-action="empty"]'),
                highlight: function() { return false; },
                enable: function() { console.log(that.editor.getHTML());return that.editor.getHTML() !== ''; }
            }
        };

        // save editor instance
        this.editor = this.createSquireInstance();

        // add counter to editor
        if (this.options.counter_enabled === true) {
            this.initCounter();
        }

        // set initial content of squire editor
        this.validate(this.$textarea.val());
        this.editor.setHTML(this.$textarea.val());
        this.editor.fireEvent('input');

        // show textarea this widget syncs with
        if (this.options.hide_textarea === true) {
            this.$textarea.hide();
        }

        if (this.readonly) {
            this.editor.getRoot().setAttribute('contenteditable', false);

            _.forOwn(this.buttons, function(btn, name) {
                btn.$btn.prop('disabled', true);
                btn.enable = function() { return false; };
            });

            this.$widget.addClass('editor--readonly');  // IE doesn't support :read-only selector

            return;
        }

        // this.$editor.on('click', function(ev) {
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

        this.$widget.show();
    }

    HtmlRichTextEditor.prototype = new Widget();
    HtmlRichTextEditor.prototype.constructor = HtmlRichTextEditor;

    HtmlRichTextEditor.prototype.createSquireInstance = function() {
        var that = this;

        // init Squire instance
        var editor = new Squire(this.$editor[0], this.options.squire_config);

        // sync content to textarea on input
        editor.addEventListener('input', function(ev) {
            var sanitized_html = that.sanitize(that.editor.getHTML());
            // the editor won't prevent from typing, once reached the textarea maxlength
            if (that.validate(sanitized_html)) {
                that.$textarea.val(sanitized_html);
                if (that.options.trigger_textarea_change_event) {
                    that.$textarea.change();
                }
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
        if (sanitized_html === '<div class="hb-paragraph"><br></div>') {
            sanitized_html = ''; // strip squire leftover when there's no real content
        }
        return sanitized_html;
    };

    HtmlRichTextEditor.prototype.validate = function(text)  {
        var valid = true;

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

    HtmlRichTextEditor.prototype.initCounter = function() {
        // @todo Count length of content stripped of markup?
        var counter_config = this.options.counter_config || {};
        counter_config.getTargetVal = this.editor._getHTML.bind(this.editor);

        this.char_counter = new CharCounter(this.$editor[0], counter_config);
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

        if (this.$widget.hasClass('editor--autogrow')) {
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

    // default sanitization method used for squire - uses different config for pasting content into the editor
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
            this.$widget.toggleClass('editor--autogrow');
            if (this.$widget.hasClass('editor--autogrow')) {
                this.buttons.autogrow.$btn.addClass('active');
            } else {
                this.buttons.autogrow.$btn.removeClass('active');
            }
        } else if (action === 'empty') {
            if (this.validate('')) {
                editor._setHTML('');
                editor.fireEvent('input')
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
