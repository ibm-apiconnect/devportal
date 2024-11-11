/*
 * Menu overflow handler
 * This plugin handle extra menu items for you
 *
 * Copyright 2015, Mehdi Dehghani
 * (http://www.github.com/dehghani-mehdi)
 *
 * For changelogs, check github repository (https://github.com/dehghani-mehdi/menu-overflow-handler):
 *
 * @author   Mehdi Dehghani
 * @license  MIT
 */

(function ($, window, document, undefined) {
    function overflowHandler($ul, config) {
        var self = this,
            version = '3.0.0';

        self.init = function () {
            // #debug
            if (config.debug) {
                console.info('overflowHandler init method fired! \O/');
                console.log('You called this plugin on:');
                console.log($ul);
            }

            _fixMarkup();
        }

        var _fixMarkup = function () {
            var $firstLevelItems = $ul.children('li'),
                $addedLi,
                $addedUl,
                deleteFlag = true,
                bootstrapAttributes = '',
                text = (typeof config.overflowItem.text == 'undefined') ? 'More' : config.overflowItem.text,
                availableCount = _calc(text);

            $addedLi = $('<li />').attr('id', 'navoverflow').addClass(config.overflowItem.className).insertAfter($firstLevelItems.eq(availableCount - 1));

            // #debug
            if (config.debug) {
                console.log('overflowHandler added following li:');
                console.log($addedLi);
            }

            if (config.bootstrapMode)
                bootstrapAttributes = 'aria-expanded="false" aria-haspopup="true" role="button" data-toggle="dropdown" class="dropdown-toggle"';

            $('<a ' + bootstrapAttributes + ' href="#" title="' + text + '">' + text + '<span><svg aria-hidden="true" class="bx--overflow-menu__icon" width="3" height="15" viewBox="0 0 3 15">\n' +
                '      <g fill-rule="evenodd">\n' +
                '        <circle cx="1.5" cy="1.5" r="1.5" />\n' +
                '        <circle cx="1.5" cy="7.5" r="1.5" />\n' +
                '        <circle cx="1.5" cy="13.5" r="1.5" />\n' +
                '      </g>\n' +
                '    </svg></span></a>').appendTo($addedLi);

            $('<ul />').addClass(config.bootstrapMode ? 'dropdown-menu' : '').appendTo($addedLi);

            $addedUl = $addedLi.children('ul');

            // #debug
            if (config.debug) {
                console.log('overflowHandler added following ul:');
                console.log($addedUl);
            }

            $firstLevelItems.each(function () {
                var $this = $(this);

                if (availableCount > 0 && $this.index() > availableCount) {
                    $this.appendTo($addedUl);
                    deleteFlag = false;
                }
            });

            // no overflow detected
            if (deleteFlag) {
                $addedLi.remove();

                // #debug
                if (config.debug) {
                    console.info('overflowHandler has no detected any overflow, Great !!');
                }
            }
        };

        var _calc = function (text) {
            $ul.prepend('<li><a>' + text + '</a></li>');

            /* var availableWidth = $ul.parent().width(), */
            var availableWidth = $('div.navbar-header').width() - $('a.logo.navbar-btn').width(),
                liWidth = 0,
                count = 0;

            // #debug
            if (config.debug) console.info('available width: ' + availableWidth);

            $ul.children('li').each(function (i, el) {
                if ( $(this).is(":visible")) {
                    liWidth += $(this).width()+1;
                }
                // #debug
                if (config.debug) console.info('li width: ' + liWidth);

                if (liWidth >= availableWidth) {
                    count = i;
                    return false;
                }
            });

            $ul.children('li:first-child').remove();

            if (liWidth < availableWidth) return -1;

            // #debug
            if (config.debug) console.info('count: ' + count);

            return count - 1;
        };
    };

    $.fn.overflowHandler = function (config) {
        config = $.extend({}, $.fn.overflowHandler.config, config);
        this.each(function () {
            var o = new overflowHandler($(this), config);
            o.init();
        });
        return this;
    };

    $.fn.overflowHandler.config = {
        overflowItem: {
            text: 'More',
            href: '#',
            className: 'has-child' // this must be 'dropdown-menu' if using bootstrap default menu
        },
        bootstrapMode: false,
        debug: false
    };
})(jQuery, window, document);
