/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2015, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

window.onload = function () {
    var forums = document.getElementsByClassName('sticky-table')[0].children[1].children;
    document.getElementById('edit-display-all-forums').addEventListener('click', checkAllForums);
    document.getElementsByClassName('sticky-table')[0].children[0].children[0].children[0].children[0].addEventListener('click', uncheckAllForums);
    for (var i = 0; i < forums.length; i++) {
        forums[i].children[0].children[0].children[0].addEventListener('click', uncheckAllForums);
    }
    var twitterAPI = document.getElementById('edit-twitter-api');
    twitterAPI.addEventListener('change', appendAtSign);
    appendAtSign();

    /**
     * Appends an '@' in front of textfield if user selects tweets from twitter handle
     */
    function appendAtSign() {
        var parentE = document.getElementById('form-item-twitter-param');
        var prefix = document.createElement('span');
        prefix.appendChild(document.createTextNode('@'));
        if (twitterAPI.selectedIndex == 0) {
            parentE.insertBefore(prefix, parentE.childNodes[0]);
        } else if (twitterAPI.selectedIndex == 1) {
            parentE.removeChild(parentE.firstChild);
        }
    }

    /**
     * Automatically checks all forums when 'Display topics from all forums' is checked
     */
    function checkAllForums() {
        if (document.getElementById('edit-display-all-forums').checked) {
            document.getElementsByClassName('sticky-table')[0].children[0].children[0].children[0].children[0].checked = true;
            for (var i = 0; i < forums.length; i++) {
                forums[i].children[0].children[0].children[0].checked = true;
            }
        }
    }

    /**
     * Unchecks 'Display topics from all forums' when at least one of the forums is unchecked
     */
    function uncheckAllForums() {
        if (document.getElementById('edit-display-all-forums').checked) {
            document.getElementById('edit-display-all-forums').checked = false;
        }
    }
};