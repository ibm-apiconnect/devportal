/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

window.onload = function () {
    const TOPIC_MAX_LINES = 5;
    const CONTAINER_CLASS = '.socialblock.container';
    const TITLE_CLASS = '.socialblock.title';
    const TITLES = document.querySelectorAll(TITLE_CLASS);
    const REM = getRem();
    const SINGLE_COL = 20 * REM;
    const MULTI_COL = (20 * 2 + 1.25) * REM;
    for (var i = 0; i < TITLES.length; i++) {
        var borderWidth = parseFloat(getComputedStyle(TITLES[i]).getPropertyValue('border-width'));
        var titleHeight = parseFloat(getComputedStyle(TITLES[i]).getPropertyValue('height')) - borderWidth * 2;
        var lineHeight = getLineHeight(TITLE_CLASS, CONTAINER_CLASS);
        var numberOfLines = titleHeight / lineHeight;
        if (numberOfLines > TOPIC_MAX_LINES) {
            TITLES[i].style.height = lineHeight * TOPIC_MAX_LINES + borderWidth * 2 + 'px';
            TITLES[i].style.overflow = 'hidden';
        }
    }
    var tweet_cards = document.querySelectorAll('.socialblock.card.tweet');
    for (var j = 0; j < tweet_cards.length; j++) {
        if (tweet_cards[j].children.length > 2) {
            tweet_cards[j].children[0].style.border = 'none';
        }
    }
    var containers = document.querySelectorAll(CONTAINER_CLASS);
    for (var k = 0; k < containers.length; k++) {
        eqjs.definePts(containers[k], {
            single_col: SINGLE_COL,
            multi_col: MULTI_COL
        });
        jQuery(containers[k]).masonry({
            gutter: 1.25 * REM,
            fitWidth: true
        });
        containers[k].addEventListener('eqResize', function (e) {
            var width = jQuery(e.target).width();
            var all_cards = document.querySelectorAll('.socialblock.card');
            if (width <= SINGLE_COL) {
                if (Masonry.data(e.target)) { //Masonry initialised
                    jQuery(e.target).masonry('destroy');
                }
                for (var l = 0; l < all_cards.length; l++) {
                    all_cards[l].style.width = 'auto';
                    all_cards[l].style.maxWidth = '20rem';
                }
            } else {
                if (!Masonry.data(e.target)) {
                    jQuery(e.target).masonry({
                        gutter: 1.25 * REM,
                        fitWidth: true
                    });
                }
                for (var l = 0; l < all_cards.length; l++) {
                    all_cards[l].style.width = '20rem';
                    all_cards[l].style.maxWidth = '';
                }
            }
        });
    }
};

/**
 * Returns the size of 1 rem in px
 */
function getRem() {
    var dummyDiv = document.createElement('div');
    dummyDiv.style.cssText = 'font-size:1rem;visibility:hidden';
    document.body.appendChild(dummyDiv);
    var out = parseFloat(getComputedStyle(dummyDiv).getPropertyValue('font-size'));
    document.body.removeChild(dummyDiv);
    return out;
}

/**
 * Returns an element's line height in px
 * @param {string} c - the element class
 * @param {string} p - the parent class
 */
function getLineHeight(c, p) {
    var fontFamily = getComputedStyle(document.querySelector(c)).getPropertyValue('font-family');
    var actualFontSize = parseFloat(getComputedStyle(document.querySelector(c)).getPropertyValue('font-size')); //font-size in px
    var cssFontSize = actualFontSize / getRem();                                                                //css-defined font-size in rem
    var cssLineHeight = getComputedStyle(document.querySelector(c)).getPropertyValue('line-height');            //css-defined line-height in rem
    var dummyDiv = document.createElement('div');
    dummyDiv.appendChild(document.createTextNode('blah'));
    dummyDiv.style.cssText = 'font-family:' + fontFamily + ';font-size:' + cssFontSize + 'rem;line-height:' + cssLineHeight + ';visibility:hidden';
    document.querySelector(p).appendChild(dummyDiv);
    var actualLineHeight = parseFloat(getComputedStyle(dummyDiv).getPropertyValue('height'));
    document.querySelector(p).removeChild(dummyDiv);
    return actualLineHeight;
}