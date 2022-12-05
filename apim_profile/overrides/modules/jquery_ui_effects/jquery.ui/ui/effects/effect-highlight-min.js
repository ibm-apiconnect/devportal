/*! jQuery UI - v1.13.2 - 2022-09-11
* http://jqueryui.com
* Copyright jQuery Foundation and other contributors; Licensed  */
!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","../version","../effect"],e):e(jQuery)}((function(e){"use strict";return e.effects.define("highlight","show",(function(n,o){var i=e(this),t={backgroundColor:i.css("backgroundColor")};"hide"===n.mode&&(t.opacity=0),e.effects.saveStyle(i),i.css({backgroundImage:"none",backgroundColor:n.color||"#ffff99"}).animate(t,{queue:!1,duration:n.duration,easing:n.easing,complete:o})}))}));