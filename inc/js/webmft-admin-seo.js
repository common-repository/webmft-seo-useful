(function( $ ) {
    'use strict';

    $(function() {

        /**
         * Tabs
         */
        if (readCookie("webmft-tab") != null) {
            var tab_id = readCookie("webmft-tab");
            var $tab = $('#' + tab_id);

            if ($tab.length) {
                $('.js-tab-wrapper a').removeClass('nav-tab-active');
                $('#' + tab_id + '-tab').addClass('nav-tab-active');

                $('.js-tab-item').removeClass('active');
                $tab.addClass('active');
            }
        }

        jQuery('.js-tab-wrapper a').click(function () {
            jQuery('.js-tab-wrapper').find('a').removeClass('nav-tab-active');
            jQuery(this).addClass('nav-tab-active');

            createCookie("webmft-tab", jQuery(this).attr("id").replace("-tab", ""));

            jQuery('.js-tab-item').removeClass('active');
            jQuery("#" + jQuery(this).attr("id").replace("-tab", "")).addClass('active');
        });

        /**
         * Top buttons
         */
        jQuery('.js-webmft-recommend').click(function(){
            jQuery('.js-webmft-form').find(':checkbox').prop('checked', false);
            jQuery('.webmft-recommend').parents('label').find('input').prop('checked', true);
        });

        jQuery('.js-webmft-enable').click(function(){
            jQuery('.js-webmft-form').find(':checkbox').prop('checked', true);
        });

        jQuery('.js-webmft-disable').click(function(){
            jQuery('.js-webmft-form').find(':checkbox').prop('checked', false);
        });

    });

})( jQuery );

function createCookie(name, value, days) {
    var expires;

    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toGMTString();
    } else {
        expires = "";
    }
    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
}

function readCookie(name) {
    var nameEQ = encodeURIComponent(name) + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
    }
    return null;
}

function eraseCookie(name) {
    createCookie(name, "", -1);
}
