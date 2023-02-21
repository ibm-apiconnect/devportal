/*! jQuery UI - v1.13.2 - 2022-09-11
* http://jqueryui.com
* Copyright jQuery Foundation and other contributors; Licensed  */
!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","./version","./focusable"],e):e(jQuery)}((function(e){"use strict";return e.extend(e.expr.pseudos,{tabbable:function(n){var t=e.attr(n,"tabindex"),u=null!=t;return(!u||t>=0)&&e.ui.focusable(n,u)}})}));