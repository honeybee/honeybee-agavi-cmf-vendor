;(function (factory) {
    // register as anonymous module, AMD style
    if (typeof define === 'function' && define.amd) {
        define(
            [
                'jquery',
                'lodash',
                'moment'
            ],
            factory,
            function (error) {
                // error has err.requireType (timeout, nodefine, scripterror)
                // and error.requireModules (an array of module ids/paths)
            }
        );
    }
}(function ($, _, moment) {
    'use strict';

    var default_settings = {
        inputElement: null, // used for typing / modifying manually
        outputElement: null, // output selected date to that input element
        toggleElement: null, // element that toggles visibility of the picker
        pickerElement: null,
        //utcOffsetElement: null,

        locale: 'en',
        direction: 'ltr', //isRTL: false,
        parseStrict: true,
        hideOnSet: false,
        showOnFocus: false,
        showOnAutofocus: true,

        // must be given per locale as moment doesn't include this
        // see https://github.com/moment/moment/issues/1947
        // see https://en.wikipedia.org/wiki/Workweek_and_weekend
        weekendDays: [6, 0], // based on 0 to 6 (Sunday to Saturday)
        notationWeekdays: '_weekdaysMin', // moment.localeData()[…]

        // positive integer of weeks to render for a calendar month;
        // anything lower than necessary number of weeks will be ignored
        minWeeksPerMonth: 6,

        // number of months to display at once; integer value or object
        // - negative integer values will set the number of months before
        // - positive integer values will set the number of months after
        numberOfMonths: {
            before: 0,
            after: 0
        },

        inputFormats: [
            moment.ISO_8601,
            // ISO formats
            'YYYY-MM-DD[T]HH:mm:ss.SSSZ',
            'YYYY-MM-DD[T]HH:mm:ss.SSS[Z]',
            'YYYY-MM-DD[T]HH:mm:ssZ',
            'YYYY-MM-DD[T]HH:mm:ss[Z]',
            'YYYY-MM-DD',
            // DE style formats
            'DD.MM.YYYY HH:mm:ss.SSSZ',
            'DD.MM.YYYY HH:mm:ss.SSS',
            'DD.MM.YYYY HH:mm:ss',
            'DD.MM.YYYY HH:mm',
            'DD.MM.YYYY',
            // UK style formats
            'DD/MM/YYYY HH:mm:ss.SSSZ',
            'DD/MM/YYYY HH:mm:ss.SSS',
            'DD/MM/YYYY HH:mm:ss',
            'DD/MM/YYYY HH:mm',
            'DD/MM/YYYY',
            // US style formats (will only match when the above won't match or the US format is longer etc.
            'MM/DD/YYYY HH:mm:ss.SSSZ',
            'MM/DD/YYYY HH:mm:ss.SSS',
            'MM/DD/YYYY HH:mm:ss',
            'MM/DD/YYYY HH:mm',
            'MM/DD/YYYY',
            // local verbose formats
            'DD. MMMM YYYY HH:mm:ss.SSS',
            'DD. MMMM YYYY HH:mm:ss',
            'DD. MMMM YYYY HH:mm',
            // local verbose formats with weekday
            'dddd, DD. MMMM YYYY HH:mm:ss.SSS',
            'dddd, DD. MMMM YYYY HH:mm:ss',
            'dddd, DD. MMMM YYYY HH:mm'
        ],

        //displayFormat: 'YYYY-MM-DD HH:mm:ss',
        displayFormat: 'L LTS',

        outputFormat: 'YYYY-MM-DD[T]HH:mm:ss.SSSZ',
        output: {
            forceHours: null,
            forceMinutes: null,
            forceSeconds: null,
            forceMilliseconds: null
        },

        constraints: {
            minDate: null, // must be new Date(…) or a string compatible to inputFormats
            maxDate: null, // must be new Date(…) or a string compatible to inputFormats
            // alternative way (all 4 must be specified):
            minMonth: 0,
            minYear: 1800,
            maxMonth: 11,
            maxYear: 2100,
        },

        disableWeekends: false,
        disabledDates: [],

        autoFitViewport: true, // decrease font-size to fit the calendar into the viewport

        onBeforeShow: null,
        onBeforeHide: null,
        onBeforeDraw: null,
        onShow: null,
        onHide: null,
        onDraw: null,
        onSetCurrentDate: null,
        onSetSelectedDate: null,

        defaultDisplayMode: 'table',
        displayModeMap: {
            table: 'table', // CSS display: table => table => cssClasses.displayMode.table
            block: 'list'   // CSS display: block => list => cssClasses.displayMode.list
        },

        templates: {
            picker: '<div class="datepicker"><div class="datepicker__content"></div></div>',
            calendars: ''
            //calendarHeader: '<span class="calendar-title"><%- date.format("MMMM YYYY") %></span>',
            //calendarWeekday: '…',
            //calendarWeek: '…',
            //calendarDay: '…',
            //calendarFooter: '…',
        },

        //cssPrefix: 'dtlp-',
        cssClasses: {
            displayMode: {
                table: 'calendar--table',
                list: 'calendar--list'
            },

            dayName: 'weekday',
            weekend: 'weekend',
            week: 'week',
            weekNumber: 'calendar-week',
            weekExcess: 'week--excess',
            day: 'day',
            dayExcess: 'day--excess',
            dayPrevMonth: 'day--prev-month',
            dayNextMonth: 'day--next-month',

            isVisible: 'is-visible',
            isDisabled: 'day--disabled',
            isSelected: 'day--selected',
            isCurrent: 'day--current',
            isToday: 'day--today',
            isEmpty: 'is-empty',
            inputInvalid: 'is-invalid',

            selectToday: 'select-today',
            selectCurrent: 'select-current',

            setDay: 'set-day',

            gotoPrevMonth: 'goto-prev-month',
            gotoNextMonth: 'goto-next-month',
            gotoPrevYear: 'goto-prev-year',
            gotoNextYear: 'goto-next-year',

            picker: 'datepicker',
            pickerContent: 'datepicker__content',

            calendars: 'calendars',
            calendarsHeader: 'calendars__header',
            calendarsBody: 'calendars__body',
            calendarsBodyInner: 'calendars__body_inner',
            calendarsFooter: 'calendars__footer',
            calendarsSingle: 'calendars--single',
            calendarsMultiple: 'calendars--multiple',

            calendar: 'calendar',
            calendarHeader: 'calendar__header',
            calendarBody: 'calendar__body',
            calendarFooter: 'calendar__footer'
        },

        i18n: {
            de: {
                selectToday: 'Heute',
                selectCurrent: 'Aktuell',
                prevMonth: 'vorheriger Monat',
                nextMonth: 'nächster Monat',
                week: 'Kalenderwoche',
                gotoPrevMonthTitle: 'vorherigen Monat anzeigen',
                gotoNextMonthTitle: 'nächsten Monat anzeigen',
                gotoPrevYearTitle: 'ein Jahr zurück',
                gotoNextYearTitle: 'ein Jahr vorwärts',
                hintToday: 'heute',
                hintCurrent: 'aktuell',
                hintSelectable: 'wählen',
                hintHover: ''
            },
            en: {
                selectToday: 'today',
                selectCurrent: 'current',
                prevMonth: 'previous month',
                nextMonth: 'next month',
                week: 'calendar week',
                gotoPrevMonthTitle: 'show previous month',
                gotoNextMonthTitle: 'show next month',
                gotoPrevYearTitle: 'jump back one year',
                gotoNextYearTitle: 'jump forward one year',
                hintToday: 'today',
                hintCurrent: 'current',
                hintSelectable: 'select',
                hintHover: ''
            }
        }



        /*,

        // from here on: TBD

        defaultDate: null,
        defaultHours: 0,
        defaultMinutes: 0,
        defaultSeconds: 0,
        defaultMilliseconds: 0,
        yearRange: [0, 10],
        showYearSelect: true,
        showMonthSelect: true,
        showWeekNumbers: true,
        showExcessDays: true,
        showTime: true,
        showSeconds: false,
        showMilliseconds: false,
        highlight: [
            {
                date: '2015-01-24',
                css: 'holidays',
                title: 'some short title'
            },
            {
                date: '2015-01-24T12:45:00.000',
                css: 'event'
            }
        ],
        //highlightFunctions: [],

        disableInput: false,
        position: 'bottom left',
        autoReposition: true,
        shouldReflow: true,
        use24hour: true,
        hourFormat: '',
        meridiem: '',
        yearSuffix: '',
        showMonthAfterYear: false,
        htmlAttributes: {
        }
*/
    };

    function getRandomString() {
        return (Math.random().toString(36)+'00000000000000000').slice(2, 10);
    }

    function cloneSpecialValues(value) {
        if (moment.isMoment(value)) {
            return moment(value);
        }
        return undefined;
    }

    function isModifierKey(key) {
        return key === 'ctrl' || key === 'meta' || key === 'alt' || key === 'shift';
    }

    // constructor function for DatetimeLocalPicker instances
    return function(instance_settings) {
        // define some internal state
        var state = {
            currentDate: null,
            selectedDate: null,
            isVisible: false
        };

        var settings = {};
        prepareSettings(instance_settings || {});

        // TODO validate given HTML elements or selector strings

        var $elements = {
            content: null,
            input: null,
            output: null,
            picker: null,
            toggle: null
        };
        prepareElements();

        // read initial date from input element and highlight it invalid if necessary
        var initial_value = $elements.input.val();
        var initial_moment = parseDate(initial_value);
        if (isValidDate(initial_moment)) {
            setDate(initial_moment);
            markInputElementValid();
        } else {
            markInputElementInvalid();
            // without valid initial date use today as the initially selected date
            selectDate(parseDate());
        }

        if (settings.debug) { console.log('settings', settings); }

        // when the input element has the autofocus attribute set, show the picker instantly w/o further interaction
        if (settings.showOnAutofocus && $elements.input.attr('autofocus')) {
            $elements.input.removeAttr('autofocus');
            showPicker();
        }

        setDisplayMode(settings.defaultDisplayMode);

        // return public api
        return {
            getCurrentDate: function() {
                if (!getCurrentDate()) {
                    return null;
                }
                return getCurrentDate().clone();
            },
            getCurrentElement: function() {
                return getVisibleDayElement(getCurrentDate());
            },
            getDisplayMode: function() {
                return getDisplayMode();
            },
            getInputElement: function() {
                return $elements.input;
            },
            getMinDate: function() {
                return settings.constraints.minDate.clone();
            },
            getMaxDate: function() {
                return settings.constraints.maxDate.clone();
            },
            getNumberOfMonths: function(nom) {
                return settings.numberOfMonths;
            },
            getOutputElement: function() {
                return $elements.output;
            },
            getPickerContentElement: function() {
                return $elements.content;
            },
            getPickerElement: function() {
                return $elements.picker;
            },
            getToggleElement: function() {
                return $elements.toggle;
            },
            getSelectedDate: function() {
                return getSelectedDate().clone();
            },
            getSelectedElement: function() {
                return getVisibleDayElement(getSelectedDate());
            },
            getSettings: function() {
                return _.cloneDeep(settings, cloneSpecialValues);
            },
            isValidDate: function(mixed) {
                return isValidDate(parseDate(mixed));
            },
            isVisible: function() {
                return isVisible();
            },
            isWeekend: function(valid_moment) {
                return isWeekend(valid_moment);
            },
            parseDate: function(mixed, input_formats, locale) {
                return parseDate(mixed, input_formats, locale);
            },
            selectDate: function(mixed) {
                if (selectDate(mixed)) {
                    updateViewOrRedraw();
                }
                return this;
            },
            selectDay: function(mixed) {
                if (selectDay(mixed)) {
                    updateViewOrRedraw();
                }
                return this;
            },
            setDate: function(mixed) {
                if (setDate(mixed)) {
                    updateViewOrRedraw();
                }
                return this;
            },
            setDay: function(mixed) {
                if (setDay(mixed)) {
                    updateViewOrRedraw();
                }
                return this;
            },
            setDisplayMode: function(name) {
                setDisplayMode(name);
                return this;
            },
            setNumberOfMonths: function(nom) {
                updateNumberOfMonths(nom);
                return this;
            },
            bind: function() {
                bindEventHandlers();
                return this;
            },
            unbind: function() {
                unbindEventHandlers();
                return this;
            },
            draw: function() {
                draw();
                return this;
            },
            show: function() {
                showPicker();
                return this;
            },
            hide: function() {
                hidePicker();
                return this;
            },
            toggle: function() {
                togglePicker();
                return this;
            },
            clear: function() {
                clear();
                return this;
            },
            toLocaleString: function() {
                return getCurrentDate().toString();
            },
            toString: function() {
                return getCurrentDate().toISOString();
            }
        };

        /**
         * Creates a new moment instance from the given argument.
         *
         * All styles mentioned in the moment docs are supported – except
         * for ASP.net style strings as those won't work because of strict
         * "settings.inputFormats" based parsing in the settings' locale..
         *
         * @param {mixed} mixed string or anything moment accepts to create a valid moment instance
         * @param {string|array} input formats to use for strict parsing; defaults to settings.inputFormats
         * @param {string} locale locale to use instead of settings.locale
         *
         * @return {moment} instance
         */
        function parseDate(mixed, input_formats, locale) {
            var parsed_date;

            if (_.isString(mixed)) {
                parsed_date = moment(mixed, input_formats || settings.inputFormats, locale || settings.locale, settings.parseStrict);
            } else {
                parsed_date = moment(mixed);
            }

            parsed_date.locale(locale || settings.locale);

            return parsed_date;
        }

        function parseDay(mixed, input_formats, locale) {
            var parsed_date = parseDate(mixed, input_formats, locale);

            if (getCurrentDate()) {
                parsed_date.hours(getCurrentDate().hours());
                parsed_date.minutes(getCurrentDate().minutes());
                parsed_date.seconds(getCurrentDate().seconds());
                parsed_date.milliseconds(getCurrentDate().milliseconds());
            }

            return parsed_date;
        }

        function bindEventHandlers() {
            //var pointerevents = ['pointerdown', 'pointerup', 'pointermove', 'pointerover', 'pointerout', 'pointerenter', 'pointerleave', 'click'].join(' ');


            if (settings.showOnFocus) {
                $elements.input.on('focus.' + settings.logPrefix, showPicker);
            }

            $elements.input.on('change.' + settings.logPrefix, handleInputElementChange);
            $elements.input.on('keydown.' + settings.logPrefix, handleInputElementKeydown);
            $elements.input.on(
                'pointerup.' + settings.logPrefix + ' ' +
                'keyup.' + settings.logPrefix + ' ',
                handleInputElementPointerUp
            );

            $elements.output.on('change.' + settings.logPrefix, function(ev) {
                // TODO prevent invalid dates here? store last output date for restore?
                if (settings.debug) { console.log('output element value: ' + $elements.output_element.val()); }
            });

            $elements.toggle.on('click.' + settings.logPrefix, function(ev) {
                togglePicker();
                focusSelectedDate();
            });
        }

        function unbindEventHandlers() {
            if (!_.isNull($elements.toggle)) {
                $elements.toggle.off('.' + settings.logPrefix);
            }

            if (!_.isNull($elements.output)) {
                $elements.output.off('.' + settings.logPrefix);
            }

            if (!_.isNull($elements.input)) {
                $elements.input.off('.' + settings.logPrefix);
            }

            unbindPickerEventHandlers();
        }

        function bindPickerEventHandlers() {
            $elements.picker.on(
                'keydown.' + settings.logPrefix,
                '',
                handlePickerKeydown
            );
            $elements.picker.on(
                'click.' + settings.logPrefix,
                '',
                handlePickerClick
            );
        }

        function unbindPickerEventHandlers() {
            if (!_.isNull($elements.picker)) {
                $elements.picker.off('.'+settings.logPrefix);
            }
        }

        function rebindPickerEventHandlers() {
            unbindPickerEventHandlers();
            bindPickerEventHandlers();
        }

        function handlePickerClick(ev) {
            if (settings.debug) { console.log('handlePickerClick', ev); }
            var selected_day;
            var parsed_date;
            var value;
            var $btns;
            var btn_index;

            var $target =  $(ev.target);
            var $button = $target.closest('button');
            if ($button.hasClass(settings.cssClasses.selectToday)) {
                draw(parseDate());
                $elements.content.find('.'+settings.cssClasses.isToday+' .'+settings.cssClasses.setDay).focus();
            } else if ($button.hasClass(settings.cssClasses.selectCurrent)) {
                draw(getCurrentDate());
                $elements.content.find('.'+settings.cssClasses.isCurrent+' .'+settings.cssClasses.setDay).focus();
            } else if ($button.hasClass(settings.cssClasses.gotoPrevMonth)) {
                btn_index = getElementIndex($button, '.'+settings.cssClasses.gotoPrevMonth);
                value = $button.closest('.'+settings.cssClasses.calendar).attr('data-month');
                if (value) {
                    draw(parseDate(value).subtract(1, 'month'));
                    focusElementIndex('.'+settings.cssClasses.gotoPrevMonth, btn_index);
                }
            } else if ($button.hasClass(settings.cssClasses.gotoNextMonth)) {
                btn_index = getElementIndex($button, '.'+settings.cssClasses.gotoNextMonth);
                value = $button.closest('.'+settings.cssClasses.calendar).attr('data-month');
                if (value) {
                    draw(parseDate(value).add(1, 'month'));
                    $elements.content.find('.'+settings.cssClasses.gotoNextMonth)[btn_index].focus();
                    focusElementIndex('.'+settings.cssClasses.gotoNextMonth, btn_index);
                }
            } else if ($button.hasClass(settings.cssClasses.gotoPrevYear)) {
                btn_index = getElementIndex($button, '.'+settings.cssClasses.gotoPrevYear);
                value = $button.closest('.'+settings.cssClasses.calendar).attr('data-month');
                if (value) {
                    draw(parseDate(value).subtract(1, 'year'));
                    $elements.content.find('.'+settings.cssClasses.gotoPrevYear)[btn_index].focus();
                    focusElementIndex('.'+settings.cssClasses.gotoPrevYear, btn_index);
                }
            } else if ($button.hasClass(settings.cssClasses.gotoNextYear)) {
                btn_index = getElementIndex($button, '.'+settings.cssClasses.gotoNextYear);
                value = $button.closest('.'+settings.cssClasses.calendar).attr('data-month');
                if (value) {
                    draw(parseDate(value).add(1, 'year'));
                    focusElementIndex('.'+settings.cssClasses.gotoNextYear, btn_index);
                }
            } else if ($target.is($elements.picker)) {
                //this is a click on the outermost container element
                //it's used as a popup backdrop and a click on it closes the picker
                togglePicker();
            } else if ($button.hasClass(settings.cssClasses.setDay)) {
                var $day = $button.closest('.'+settings.cssClasses.day);
                if ($day.length > 0) {
                    parsed_date = parseDate($day.attr('data-iso-date'));
                    if (!$day.hasClass(settings.cssClasses.isDisabled) && setDay(parsed_date)) {
                        if (settings.hideOnSet) {
                            hidePicker();
                            $elements.input.focus();
                        } else {
                            updateViewOrRedraw();
                        }
                    }
                }
            } else {
                if (settings.debug) { console.log('Unhandled click on something.', $(ev.target)); }
            }
        }

        function handlePickerKeydown(ev) {
            if (settings.debug) { console.log('handlePickerKeydown', ev); }
            if (!isVisible()) {
                return;
            }

            var selected_date = getSelectedDate();
            if (!selected_date) {
                //check if document.activeElement is a button.set-day and set that as selected date
                var value = $elements.content.find('.'+settings.cssClasses.setDay+':focus').closest('.'+settings.cssClasses.day).attr('data-iso-date');
                var date = parseDate(value);
                if (date.isValid()) {
                    selected_date = date;
                }
            }

            var display_mode = selected_date ? getDisplayMode(selected_date) : 'table';
            if (settings.debug) { console.log('display_mode='+display_mode); }

            // -> change current_date instead of just moving around when shift key is pressed
            var set_day = ev.shiftKey ? true : false;

            switch (ev.keyCode) {
                case 37: // left
                    ev.preventDefault(); // prevent viewport scrolling
                    if (display_mode === 'table') {
                        if (gotoPreviousSelectableDate(selected_date, 'day', set_day)) {
                            updateViewOrRedraw();
                        }
                    } else if (display_mode === 'list') {
                        if (gotoPreviousSelectableDate(selected_date, 'day', set_day)) {
                            updateViewOrRedraw();
                        }
                    }
                    break;
                case 39: // right
                    ev.preventDefault(); // prevent viewport scrolling
                    if (display_mode === 'table' && gotoNextSelectableDate(selected_date, 'day', set_day)) {
                        updateViewOrRedraw();
                    } else if (display_mode === 'list' && gotoNextSelectableDate(selected_date, 'day', set_day)) {
                        updateViewOrRedraw();
                    }
                    break;
                case 38: // up
                    ev.preventDefault(); // prevent viewport scrolling
                    if (display_mode === 'table') {
                        if (gotoPreviousSelectableDate(selected_date, 'week', set_day)) {
                            updateViewOrRedraw();
                        }
                    } else if (display_mode === 'list') {
                        if (gotoPreviousSelectableDate(selected_date, 'day', set_day)) {
                            updateViewOrRedraw();
                        }
                    }
                    break;
                case 40: // down
                    ev.preventDefault(); // prevent viewport scrolling
                    if (display_mode === 'table' && gotoNextSelectableDate(selected_date, 'week', set_day)) {
                        updateViewOrRedraw();
                    } else if (display_mode === 'list' && gotoNextSelectableDate(selected_date, 'day', set_day)) {
                        updateViewOrRedraw();
                    }
                    break;
                case 27: // escape
                    ev.preventDefault();
                    hidePicker();
                    $elements.input.focus();
                    break;
                default:
                    break;
            }
        }

        function handleInputElementPointerUp(ev) {
            var parsed_date = parseDate($elements.input.val());
            if (isValidDate(parsed_date)) {
                markInputElementValid();
            } else {
                markInputElementValid();
                markInputElementInvalid();
            }
        }

        function handleInputElementKeydown(ev) {
            if (ev.keyCode === 13) { // enter
                ev.preventDefault(); // otherwise the focusSelectedDate() would close the dialog again ;-)
                togglePicker();
                focusSelectedDate();
            } else if (ev.keyCode === 27) {
                if (isVisible()) {
                    hidePicker();
                } else {
                    var parsed_date = parseDate($elements.input.val());
                    if (!isValidDate(parsed_date)) {
                        parsed_date = parseDate($elements.output.val());
                        if (isValidDate(parsed_date)) {
                            resetInputElementDate(parsed_date);
                        }
                    }
                }
            }
        }

        function handleInputElementChange(ev) {
            var val = $elements.input.val();
            if (val === '') {
                clear(); // forget currentDate; reset selectedDate as explicitely no value is wanted
            } else {
                var parsed_date = parseDate(val);
                if (!setDate(parsed_date)) {
                    resetInputElementDate(getCurrentDate());
                }
            }
            updateViewOrRedraw();
        }

        function updateViewOrRedraw() {
            highlightInputElement();

            if (!isVisible()) {
                return false;
            }

            if (!hasVisibleDayElement(getSelectedDate())) {
                draw();
            }

            updateView();

            return true;
        }

        function updateView() {
            highlightToday();
            highlightCurrentDate();
            focusSelectedDate();
            highlightInputElement();
            if (settings.autoFitViewport) {
                fitViewport();
            }
        }

        function fitViewport() {
            var $calendar = $elements.content.find("."+settings.cssClasses.calendar);
            $elements.content.css("font-size", "");

            var width = $calendar.outerWidth();
            var ratio = screen.availWidth / width;
            var font_size = parseInt($elements.content.css("font-size"), 10);

            if (settings.debug) { console.log('font_size=' + font_size, 'calendar_width=' + width); }

            if (ratio < 1) {
                font_size *= ratio;
                $elements.content.css("font-size", font_size+"px");
            }

            if (settings.debug) { console.log('screen.availWidth=' + screen.availWidth, 'ratio=' + ratio); }
        }

        function guessDisplayMode(date) {
            var value = 'unknown';

            var $selected_day = getDayElement(date);
            if ($selected_day.length > 0) {
                value = $selected_day.closest('.'+settings.cssClasses.calendarBody).css('display');
            } else {
                value = $elements.content.find('.'+settings.cssClasses.calendarBody).first().css('display');
            }

            if (value && _.has(settings.displayModeMap, value)) {
                return settings.displayModeMap[value];
            }

            return value;
        }

        function getDisplayMode() {
            var display_mode;

            // is there a known class set on the picker element?
            _.forIn(settings.cssClasses.displayMode, function(value, key, object) {
                if ($elements.picker.hasClass(settings.cssClasses.displayMode[key])) {
                    display_mode = key;
                }
            });

            // if no class is set, try to guess the displayMode via the CSS display property value
            if (!display_mode) {
                display_mode = guessDisplayMode(getCurrentDate());
            }

            if (!display_mode) {
                display_mode = settings.defaultDisplayMode;
            }

            return display_mode;
        }

        // force a specific displayMode via setting a class on the picker element
        function setDisplayMode(name) {
            if (_.has(settings.cssClasses.displayMode, name)) {
                _.forIn(settings.cssClasses.displayMode, function(value, key, object) {
                    $elements.picker.removeClass(value);
                });
                $elements.picker.addClass(settings.cssClasses.displayMode[name]);
                return true;
            }

            return false;
        }

        function createEvent(event_name, event_data) {
            event_name = event_name || 'unnamed';
            event_data = event_data || {};

            var default_event = {
                name: event_name,
                currentDate: parseDate(getCurrentDate()),
                selectedDate: parseDate(getSelectedDate()),
                isVisible: isVisible()
            };

            return _.merge(default_event, event_data);
        }

        function gotoPreviousSelectableDate(prev, period, set_day) {
            prev = parseDate(prev);
            period = period || 'day';
            set_day = set_day || false;

            do {
                // TODO decrease jump period unit if necessary?
                if (settings.isRTL && period === 'day') {
                    prev.add(1, period);
                } else {
                    prev.subtract(1, period);
                }
            } while (isDisabled(prev) && prev.isAfter(settings.constraints.minDate));

            if (isValidDate(prev)) {
                return set_day ? setDay(prev) : selectDay(prev);
            }

            return false;
        }

        function gotoNextSelectableDate(next, period, set_day) {
            next = parseDate(next);
            period = period || 'day';
            set_day = set_day || false;

            do {
                // TODO decrease jump period unit if necessary?
                if (settings.isRTL && period === 'day') {
                    next.subtract(1, period);
                } else {
                    next.add(1, period);
                }
            } while (isDisabled(next) && next.isBefore(settings.constraints.maxDate));

            if (isValidDate(next)) {
                return set_day ? setDay(next) : selectDay(next);
            }

            return false;
        }

        function togglePicker() {
            setVisible(!isVisible());
            if (isVisible()) {
                showPicker();
            } else {
                hidePicker();
            }
        }

        function showPicker() {
            if (_.isFunction(settings.onBeforeShow)) {
                settings.onBeforeShow(createEvent('beforeShow'));
            }

            unbindPickerEventHandlers();
            $elements.picker.addClass(settings.cssClasses.isVisible);
            setVisible(true);
            draw();
            bindPickerEventHandlers();

            if (_.isFunction(settings.onShow)) {
                _.defer(settings.onShow, createEvent('show'));
            }
        }

        function hidePicker() {
            if (_.isFunction(settings.onBeforeHide)) {
                settings.onBeforeHide(createEvent('beforeHide'));
            }

            unbindPickerEventHandlers();
            $elements.picker.removeClass(settings.cssClasses.isVisible);
            setVisible(false);

            if (_.isFunction(settings.onHide)) {
                _.defer(settings.onHide, createEvent('hide'));
            }
        }

        function highlightInputElement() {
            if (isValidDate(getCurrentDate())) {
                markInputElementValid();
            } else {
                markInputElementInvalid();
            }
        }

        function markInputElementValid() {
            $elements.input.removeClass(settings.cssClasses.inputInvalid);
        }

        function markInputElementInvalid() {
            // empty valued input elements are not invalid when they're not required
            if ($elements.input.val().length === 0 && !$elements.input.prop('required')) {
                return;
            }

            $elements.input.addClass(settings.cssClasses.inputInvalid);
        }

        function highlightToday() {
            var ymd = getYMD(parseDate());
            $elements.content.find('.'+settings.cssClasses.isToday).removeClass(settings.cssClasses.isToday);
            $elements.content.find("[data-ymd='"+ymd+"']").addClass(settings.cssClasses.isToday);
        }

        function highlightCurrentDate() {
            var date = getCurrentDate();
            if (date) {
                var ymd = getYMD(date);
                $elements.content.find('.'+settings.cssClasses.isCurrent).removeClass(settings.cssClasses.isCurrent);
                $elements.content.find("[data-ymd='"+ymd+"']").addClass(settings.cssClasses.isCurrent);
            }
        }

        function blurSelectedDate() {
            $elements.content.find('.'+settings.cssClasses.isSelected).removeClass(settings.cssClasses.isSelected);
        }

        function focusSelectedDate() {
            blurSelectedDate();

            var parsed_date;
            var date = getSelectedDate();

            if (!date) {
                if (settings.debug) { console.log('Invalid selected date to focus.'); }
                return false;
            }

            var $day = getVisibleDayElement(date);
            if ($day.length === 0) {
                var $selected = $elements.content.find('.'+settings.cssClasses.isSelected);
                var $current = $elements.content.find('.'+settings.cssClasses.isCurrent);
                var $today = $elements.content.find('.'+settings.cssClasses.isToday);
                var $days = $elements.content.find('.'+settings.cssClasses.day);
                if ($selected.length > 0) {
                    parsed_date = parseDate($selected.attr('data-iso-date'));
                    if (parsed_date.isValid()) {
                        if (selectDay(parsed_date)) {
                            $selected.addClass(settings.cssClasses.isSelected).focus();
                        }
                    }
                } else if ($current.length > 0 && (parsed_date = parseDate($current.attr('data-iso-date'))) && parsed_date.isValid() && selectDay(parsed_date)) {
                    $current.first().addClass(settings.cssClasses.isSelected).focus();
                } else if ($today.length > 0 && (parsed_date = parseDate($today.attr('data-iso-date'))) && parsed_date.isValid() && selectDay(parsed_date)) {
                    $today.first().addClass(settings.cssClasses.isSelected).focus();
                } else if ($days.length > 0 && (parsed_date = parseDate($days.attr('data-iso-date'))) && parsed_date.isValid() && selectDay(parsed_date)) {
                    $days.first().addClass(settings.cssClasses.isSelected).focus();
                } else {
                    //console.log('idkwtd');
                }
            } else {
                $day.first().addClass(settings.cssClasses.isSelected).find('.'+settings.cssClasses.setDay).first().focus();
            }
        }

        function selectToday() {
            return selectDay(parseDate());
        }

        function setDay(date) {
            var parsed_date = parseDay(date);
            if (isValidDate(parsed_date)) {
                setSelectedDate(parsed_date);
                setCurrentDate(parsed_date);
                return true;
            }
            return false;
        }

        function setDate(date) {
            var parsed_date = parseDate(date);
            if (isValidDate(parsed_date)) {
                setSelectedDate(parsed_date);
                setCurrentDate(parsed_date);
                return true;
            }
            return false;
        }

        function selectDay(date) {
            return selectDate(parseDay(date));
        }

        function selectDate(date) {
            var parsed_date = parseDate(date);
            if (isValidDate(parsed_date)) {
                setSelectedDate(parsed_date);
                return true;
            }
            return false;
        }

        function setSelectedDate(date) {
            if (isValidDate(date)) {
                if (settings.debug) { console.log('selectedDate is now: '+date.toISOString()); }
                state.selectedDate = date.clone();
                if (_.isFunction(settings.onSetSelectedDate)) {
                    _.defer(settings.onSetSelectedDate, createEvent('setSelectedDate'));
                }
                return true;
            }
            return false;
        }

        function setCurrentDate(date) {
            if (isValidDate(date)) {
                if (settings.debug) { console.log('currentDate is now: '+date.toISOString()); }
                state.currentDate = date.clone();
                setOutputElementDate(date);
                setInputElementDate(date);
                if (_.isFunction(settings.onSetCurrentDate)) {
                    _.defer(settings.onSetCurrentDate, createEvent('setCurrentDate'));
                }
                return true;
            } else {
                if (settings.debug) { console.log('resetting from setCurrentDate as date is invalid: '+date.toISOString()); }
                resetInputElementDate(getCurrentDate());
                return false;
            }
        }

        function getSelectedDate() {
            return state.selectedDate;
        }

        function getCurrentDate() {
            return state.currentDate;
        }

        function getYMD(date) {
            return date.format('YYYYMMDD');
        }

        function getDayElement(date) {
            // TODO add setting to allow selection of excess dates?
            var $days = $elements.content.find('[data-ymd="'+getYMD(date)+'"]'); // could be excess days in multiple calendars
            if ($days.length > 1) {
                // prefer the calendar where that day is part of the actual month
                var $day = $days.not('.'+settings.cssClasses.dayExcess);
                if ($day.length > 0) {
                    return $day.first();
                }
            }
            return $days.first();
        }

        function getVisibleDayElement(date) {
            var $days = $elements.content.find('[data-ymd="'+getYMD(date)+'"]').filter(':visible');
            if ($days.length > 1) {
                // prefer the calendar where that day is part of the actual month
                var $day = $days.not('.'+settings.cssClasses.dayExcess);
                if ($day.length > 0) {
                    return $day.first();
                }
            }
            return $days.first();
        }

        function hasVisibleDayElement(date) {
            return $elements.content.find('[data-ymd="'+getYMD(date)+'"]').filter(':visible').length > 0;
        }

        function setVisible(flag) {
            state.isVisible = flag;
            return flag;
        }

        function isVisible() {
            return state.isVisible;
        }

        function isWeekend(date) {
            var parsed_date = parseDate(date);
            return (settings.weekendDays.indexOf(parsed_date.day()) !== -1);
        }

        function setOutputElementDate(date) {
            if (date === '') {
                $elements.output.val('');
            } else {
                $elements.output.val(
                    //parseDate(date).utc().format(settings.outputFormat)
                    parseDate(date).toISOString()
                );
            }
        }

        function setInputElementDate(date) {
            if (date === '') {
                $elements.input.val('');
            } else {
                $elements.input.val(
                    parseDate(date).local().format(settings.displayFormat)
                );
            }
        }

        function clear() {
            setOutputElementDate('');
            setInputElementDate('');
            state.currentDate = null;
            markInputElementValid();
            markInputElementInvalid();
        }

        // set input element value to the last known valid date
        function resetInputElementDate(date) {
            var parsed_date = parseDate(date || $elements.output.val());
            if (isValidDate(parsed_date)) {
                $elements.input.val(parsed_date.format(settings.displayFormat));
                markInputElementValid();
            } else {
                markInputElementInvalid();
                //throw new Error('Output element contains an invalid moment: ' + parsed_date);
            }
        }

        function getElementIndex($element, selector) {
            var $elms = $elements.content.find(selector);
            var elm_index = 0;
            $elms.each(function(idx, item) {
                if (item === $element[0]) {
                    elm_index = idx;
                }
            });
            return elm_index;
        }

        function focusElementIndex(selector, index) {
            index = index || 0;
            var $elms = $elements.content.find(selector);
            if ($elms.length > index) {
                $elms[index].focus();
            } else {
                $elms.first().focus();
            }
        }

        function draw(date) {
            date = date || getSelectedDate() || getCurrentDate();
            var template_data = prepareCalendars(date);

            if (_.isFunction(settings.onBeforeDraw)) {
                settings.onBeforeDraw(
                    createEvent('beforeDraw', {
                        'date': date,
                        'template_data': template_data
                    })
                );
            }

            $elements.content.html(
                settings.templates.calendars(template_data)
            );
            updateView();

            if (_.isFunction(settings.onDraw)) {
                _.defer(settings.onDraw, createEvent('draw'));
            }
        }

        function prepareCalendars(date) {
            if (date && !moment.isMoment(date)) {
                throw new Error('No valid date given to prepare template data for.');
            }

            // when no date is given use today's date to prepare template data
            date = date || parseDate();

            var idx;
            var nom = settings.numberOfMonths.before + settings.numberOfMonths.after + 1;

            var css = settings.cssClasses.calendarsSingle;
            if (nom > 1) {
                css = settings.cssClasses.calendarsMultiple;
            }

            var calendar_data = {
                settings: settings,
                date: parseDate(date),
                localeData: date.localeData(),
                currentDate: parseDate(getCurrentDate()),
                selectedDate: parseDate(getSelectedDate()),
                i18n: settings.i18n[settings.locale] || 'en',
                css: css,
                numberOfMonths: nom,
                calendars: []
            };

            // render N previous months
            for (idx = settings.numberOfMonths.before; idx > 0; idx--) {
                calendar_data.calendars.push(prepareCalendarMonth(parseDate(date).subtract(idx, 'months')));
            }

            // render currently selected month
            calendar_data.calendars.push(prepareCalendarMonth(date));

            // render N next months
            for (idx = 1; idx <= settings.numberOfMonths.after; idx++) {
                calendar_data.calendars.push(prepareCalendarMonth(parseDate(date).add(idx, 'months')));
            }

            return calendar_data;
        }

        function prepareCalendarMonth(date) {
            date = date.clone().startOf('month'); // clone just in case someone modifies the date while rendering
            return {
                header: prepareHeader(date),
                footer: prepareFooter(date),
                weekdays: prepareWeekdays(date),
                weeks: prepareWeeks(date),
                currentMonth: date
            };
        }

        function prepareHeader(date) {
            var header_data = {
                date: date.clone(),
                year: date.format('YYYY'),
                month: date.format('MMMM'),
                content: date.format('MMMM YYYY')
            };

            // optionally use a compiled template for the content property
            if (settings.templates.calendarHeader && _.isFunction(settings.templates.calendarHeader)) {
                header_data.content = settings.templates.calendarHeader(header_data);
            }

            return header_data;
        }

        function prepareFooter(date) {
            var footer_data = {
                date: date.clone(),
                year: date.format('YYYY'),
                month: date.format('MMMM'),
                title: date.format('MMMM YYYY'),
                content: ''
            };

            // optionally use a compiled template for the content property
            if (settings.templates.calendarFooter && _.isFunction(settings.templates.calendarFooter)) {
                footer_data.content = settings.templates.calendarFooter(footer_data);
            }

            return footer_data;
        }

        function prepareWeekdays(date) {
            var weekdays_data = [];
            var locale_data = date.localeData();
            var weekday_data = {};
            var fdow = locale_data.firstDayOfWeek();
            for (var idx = fdow, len = fdow + 7; idx < len; idx++) {
                // determine actual index to lookup in local_data as that is 0-6 based
                var day = idx;
                if (day >= 7) {
                    day -= 7;
                }

                var css_classes = settings.cssClasses.dayName;
                if (settings.weekendDays.indexOf(day) !== -1) {
                    css_classes += ' ' + settings.cssClasses.weekend;
                }

                weekday_data = {
                    content: locale_data[settings.notationWeekdays][day],
                    fullName: locale_data._weekdays[day],
                    shortName: locale_data._weekdaysShort[day],
                    minName: locale_data._weekdaysMin[day],
                    css: css_classes
                };

                // optionally use a compiled template for the content property
                if (settings.templates.calendarWeekday && _.isFunction(settings.templates.calendarWeekday)) {
                    weekday_data.content = settings.templates.calendarWeekday(weekday_data);
                }

                weekdays_data.push(weekday_data);
            }

            /* unneccessary when html attribute dir="rtl" is set
            if (settings.isRTL) {
                weekdays_data.reverse();
            }*/

            return weekdays_data;
        }

        function prepareWeeks(date) {
            date = parseDate(date);
            var weeks_data = [];
            var days_per_week = 7;
            var today = parseDate();
            var days_in_month = getDaysInMonth(date);
            var first_date_of_month = date.clone().startOf('month');
            var first_week_of_month = first_date_of_month.week();
            var last_date_of_month = date.clone().endOf('month');
            var last_week_of_month = last_date_of_month.week();

            var num_weeks = Math.ceil(Math.abs(last_date_of_month.diff(first_date_of_month, 'weeks', true)));
            if (settings.minWeeksPerMonth > num_weeks) {
                num_weeks = settings.minWeeksPerMonth;
            }

            var days_before = first_date_of_month.weekday(); // idx of first day of week
            var days_after = (num_weeks * days_per_week) - (days_before + days_in_month);
            if (days_after < 0) {
                days_after += days_per_week;
            }

            var num_days = days_before + days_in_month + days_after;
            var num_cells = num_days + num_weeks; // week numbers before each week

            if (settings.debug) {
                console.log('num_weeks='+num_weeks);
                console.log('first_week='+first_week_of_month, 'last_week='+last_week_of_month);
                console.log('days_before='+days_before, 'dim='+days_in_month, 'days_after='+days_after);
                console.log('days_to_render='+num_days, 'cells_to_render='+num_cells);
            }

            var start_date = first_date_of_month.clone().subtract(+days_before, 'days');
            var end_date = last_date_of_month.clone().add(days_after, 'days');
            if (settings.debug) {
                console.log('start=', start_date.toString());
                console.log('  end=', end_date.toString());
            }

            var day_css;
            var day_content;
            var day_valid = true;
            var day_data = {};
            var week_data = {};
            var render_date = start_date.clone();
            var idx;
            var excess_counter = 0;

            for (idx = 0; idx < num_days; idx++) {

                // initialize a new week's data
                if (idx%(days_per_week) === 0) {
                    week_data = {
                        css: settings.cssClasses.week,
                        weekNumberCSS: settings.cssClasses.weekNumber,
                        date: render_date.clone(),
                        num: render_date.week(),
                        nameMin: render_date.format('WW'),
                        nameLong: render_date.format('['+settings.i18n[settings.locale].week+'] W'),
                        nameLongYear: render_date.format('['+settings.i18n[settings.locale].week+'] W YYYY'),
                        content: render_date.format('WW'),
                        days: [],
                        excess: false
                    };
                    excess_counter = 0;
                }

                day_valid = true;
                day_content = render_date.date();

                // css for one calendar day
                day_css = settings.cssClasses.day || '';
                if (isWeekend(render_date)) {
                    day_css += ' ' + settings.cssClasses.weekend;
                }
                if (idx < days_before) {
                    day_css += ' ' + settings.cssClasses.dayExcess + ' ' + settings.cssClasses.dayPrevMonth;
                    excess_counter++;
                } else if (idx >= (days_before + days_in_month)) {
                    day_css += ' ' + settings.cssClasses.dayExcess + ' ' + settings.cssClasses.dayNextMonth;
                    excess_counter++;
                }
                if (isDisabled(render_date)) {
                    day_css += ' ' + settings.cssClasses.isDisabled;
                    day_valid = false;
                }
                if (today.isSame(render_date, 'day')) {
                    day_css += ' ' + settings.cssClasses.isToday;
                }
                if (render_date.isSame(getSelectedDate(), 'day')) {
                    day_css += ' ' + settings.cssClasses.isSelected;
                }
                if (render_date.isSame(getCurrentDate(), 'day')) {
                    day_css += ' ' + settings.cssClasses.isCurrent;
                }

                // data for one calendar day
                day_data = {
                    css: day_css,
                    date: render_date.clone(),
                    isoDate: render_date.toISOString(),
                    ymd: getYMD(render_date),
                    dayValid: day_valid,
                    content: day_content
                };

                // optionally use a compiled template for the content property
                if (settings.templates.calendarDay && _.isFunction(settings.templates.calendarDay)) {
                    day_data.content = settings.templates.calendarDay(day_data);
                }

                // add day_data to current week's data
                week_data.days.push(day_data);

                // add whole week's data to weeks_data array
                if (week_data.days.length === days_per_week) {
                    if (settings.templates.calendarWeek && _.isFunction(settings.templates.calendarWeek)) {
                        week_data.content = settings.templates.calendarWeek(week_data);
                    }

                    week_data.excess = (excess_counter >= days_per_week); // whole week has excess days

                    if (settings.debug) { console.log('week_data', week_data); }

                    weeks_data.push(week_data);
                }

                // advance one day
                render_date.add(1, 'day');
            }

            /* unneccessary when html element has attribute dir="rtl" set
            if (settings.isRTL) {
                _.forEach(weeks_data, function(week_data, index, collection) {
                    week_data.days.reverse();
                });
            }*/

            return weeks_data;
        }

        function isValidDate(date) {
            if (!date || !moment.isMoment(date) || (moment.isMoment(date) && !date.isValid())) {
                return false;
            }
            return !isDisabled(date);
        }

        function isDisabled(date) {
            var min_date = parseDate(settings.constraints.minDate).subtract(1, 'millisecond');
            var max_date = parseDate(settings.constraints.maxDate).add(1, 'millisecond');

            if (!date.isBetween(min_date, max_date)) {
                return true;
            }

            if (settings.disableWeekends && isWeekend(date)) {
                return true;
            }

            // check every disabledDates entry and return false if date is invalid;
            var is_valid = _.every(settings.disabledDates, function(value, index, collection) {
                if (_.isFunction(value)) {
                    return !value(parseDate(date));
                } else {
                    return !(parseDate(value).isSame(date, 'day'));
                }
            });

            return !is_valid;
        }

        function prepareElements() {
            var content = '';

            $elements.toggle = settings.toggleElement ? $(settings.toggleElement) : null;
            $elements.input = $(settings.inputElement);

            $elements.picker = settings.pickerElement ? $(settings.pickerElement) : null;
            if (_.isNull($elements.picker)) {
                content = settings.templates.picker({
                   settings: settings
                });
                $elements.picker = $(content);
                $elements.picker.attr('id', 'dtlp' + settings.logPrefix);

                $elements.content = $elements.picker.find('.' + settings.cssClasses.pickerContent).first();
                $elements.content.attr('id', 'dtlpcontent' + settings.logPrefix);
                $elements.content.addClass(settings.cssClasses.pickerContent);

                // TODO make other insert positions configurable? OTOH pickerElement can be set already
                if ($elements.toggle) {
                    $elements.picker.insertAfter($elements.toggle);
                } else {
                    $elements.picker.insertAfter($elements.input);
                }
            }
            $elements.picker.addClass(settings.cssClasses.picker);

            $elements.output = $elements.input.clone();
            $elements.output.attr('id', $elements.output.attr('id') + settings.logPrefix);
            $elements.output.attr('type', 'hidden');
            $elements.output.insertBefore($elements.input);
            $elements.output.hide();
            $elements.input.removeAttr('name');

            bindEventHandlers();
        }

        function prepareSettings(s) {
            settings = $.extend(true, {}, default_settings, s, settings);

            // add (randomized) log prefix to instance
            settings.logPrefix = settings.logPrefix || 'DatetimeLocalPicker';
            if (!settings.randomizeLogPrefix || settings.randomizeLogPrefix === true || settings.logPrefix === '') {
                settings.logPrefix += '#' + getRandomString();
            }

            // use the input element as toggle element when no specific toggle was given
            settings.toggleElement = settings.toggleElement || settings.inputElement;

            // check for a valid given locale to use and store that in settings (as it might be invalid/unknown)
            settings.locale = settings.locale ? settings.locale : 'en-gb';
            if (settings.locale === 'de-de') {
                settings.locale = 'de';
            }
            if (settings.locale === 'en') {
                settings.locale = 'en-gb';
            }
            var m = parseDate();
            m.locale(settings.locale);
            settings.locale = m.locale(); // actual locale being used
            if (!_.has(settings.i18n, settings.locale)) {
                settings.i18n[settings.locale] = settings.i18n.en;
            }

            var fdow = settings.firstDayOfWeek;
            if (!_.isNull(fdow) && ((+fdow >= 0) && (+fdow <= 6))) {
                settings.firstDayOfWeek = fdow;
            } else {
                var l = m.localeData();
                settings.firstDayOfWeek = l.firstDayOfWeek();
            }

            if (!(_.isArray(settings.weekendDays) && _.min(settings.weekendDays) >= 0 && _.max(settings.weekendDays) <= 6)) {
                throw new Error('Setting weekendDays must be an array of positive integers that represent the index of weekdays to use as weekend (defaults to [0, 6] which is Sun/Sat).');
            }

            var valid_weekdays_notations = ['_weekdays', '_weekdaysShort', '_weekdaysMin'];
            if (!_.isString(settings.notationWeekdays) || (_.isString(settings.notationWeekdays) && valid_weekdays_notations.indexOf(settings.notationWeekdays) === -1)) {
                throw new Error('Setting notationWeekdays must be one of: ' + valid_weekdays_notations.join(', '));
            }
            settings.notationWeekdays = settings.notationWeekdays || '_weekdaysMin';

            settings.minWeeksPerMonth = Math.ceil(+settings.minWeeksPerMonth);
            if (_.isNaN(settings.minWeeksPerMonth) || (!_.isNaN(settings.minWeeksPerMonth) && (settings.minWeeksPerMonth < 0))) {
                throw new Error('Setting minWeeksPerMonth must be a positive integer value of minimum number of weeks to display.');
            }

            if (!_.isArray(settings.inputFormats)) {
                throw new Error('Setting inputFormats must be an array of acceptable date format strings for moment.');
            }
            settings.inputFormats.push(settings.displayFormat);

            settings.hideOnSet = !!settings.hideOnSet;
            settings.autoFitViewport = !!settings.autoFitViewport;
            settings.disableWeekends = !!settings.disableWeekends;
            settings.showOnAutofocus = !!settings.showOnAutofocus;
            settings.showOnFocus = !!settings.showOnFocus;
            settings.debug = !!settings.debug;

            if (!_.isString(settings.direction) || (_.isString(settings.direction) && ['rtl', 'ltr'].indexOf(settings.direction) === -1)) {
                throw new Error('Setting direction must be "ltr" or "rtl". To render correctly try to set the "dir" attribute on the HTML element and provide it as the direction setting value.');
            }
            settings.isRTL = settings.direction === 'rtl' ? true : false;

            settings.defaultDisplayMode = settings.defaultDisplayMode ? settings.defaultDisplayMode : 'table';
            if (!_.has(settings.cssClasses.displayMode, settings.defaultDisplayMode)) {
                throw new Error('Setting cssClasses.displayMode.'+settings.defaultDisplayMode+' does not exist, but value of defaultDisplayMode suggests it should.');
            }
            var exists = false;
            _.forIn(settings.displayModeMap, function(value, key, object) {
                if (value === settings.defaultDisplayMode) {
                    exists = true;
                }
            });
            if (!exists) {
                throw new Error('The displayModeMap settings does not have a mapping for a CSS display property value to the given defaultDisplayMode name.');
            }

            updateNumberOfMonths(settings.numberOfMonths);
            updateDateConstraints();
            compileTemplates();
        }

        function updateNumberOfMonths(nom) {
            nom = nom || 1;
            if (_.isNumber(nom) && !_.isNaN(nom)) {
                nom = Math.ceil(nom);
                if (nom > 0) {
                    settings.numberOfMonths = {
                        before: 0,
                        after: nom-1
                    };
                } else if (nom < 0) {
                    settings.numberOfMonths = {
                        before: Math.abs(nom),
                        after: 0
                    };
                } else {
                    settings.numberOfMonths = {
                        before: 0,
                        after: 0
                    };
                }
            } else if (_.isObject(nom) &&
                _.has(nom, 'before') && _.has(nom, 'after') &&
                _.isNumber(nom.before) && _.isNumber(nom.after) &&
                !_.isNaN(nom.before) && !_.isNaN(nom.after)
            ) {
                settings.numberOfMonths = {
                    before: Math.ceil(Math.abs(nom.before)),
                    after: Math.ceil(Math.abs(nom.after))
                };
            } else {
                throw new Error('Setting numberOfMonths must be a positive integer or an object with before/after properties with a positive number of months to display before or after the current calendar month.');
            }
        }

        function updateDateConstraints() {
            var min_date = parseDate(settings.constraints.minDate);
            var max_date = parseDate(settings.constraints.maxDate);

            var min_year = settings.constraints.minYear || default_settings.constraints.minYear;
            var min_month = settings.constraints.minMonth || default_settings.constraints.minMonth;

            var max_year = settings.constraints.maxYear || default_settings.constraints.maxYear;
            var max_month = settings.constraints.maxMonth || default_settings.constraints.maxMonth;

            if ((min_date.isValid() && max_date.isValid()) && max_date.isBefore(min_date)) {
                var temp = max_date.clone();
                max_date = min_date.clone();
                min_date = temp;
            }

            if (!_.isNumber(min_year) || !_.isNumber(max_year) || !_.isNumber(min_month) || !_.isNumber(max_month) ||
                _.isNaN(min_year) || _.isNaN(max_year) || _.isNaN(min_month) || _.isNaN(max_year)
            ) {
                throw new Error('Setting constraints.minYear/maxYear/minMonth/maxMonth must be positive integer values.');
            }

            if (!min_date.isValid()) {
                min_date = moment(min_year, 'YYYY', settings.locale, true);
                min_date.startOf('year').month(min_month);
            }

            if (!max_date.isValid()) {
                max_date = moment(max_year, 'YYYY', settings.locale, true);
                max_date.endOf('year').month(max_month);
            }

            settings.constraints = {
                minDate: min_date,
                maxDate: max_date
            };
        }

        function compileTemplates() {
            if (!_.isPlainObject(settings.templates)) {
                throw new Error('Settings must have a "templates" object with lodash templates (compiled or not).');
            }

            _.forIn(settings.templates, function(value, key, templates) {
                if (!_.isFunction(templates[key]) && _.isString(value)) {
                    templates[key] = _.template(value); // compile template string
                }
            });
        }

        function getDaysInMonth(moment_or_year, month) {
            if (moment.isMoment(moment_or_year)) {
                return moment_or_year.daysInMonth();
            }
            return moment(''+(+moment_or_year)+'-'+(+month), 'YYYY-MM').daysInMonth();
        }
    }; // end of constructor function
}));

