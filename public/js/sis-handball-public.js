(function ($) {
    'use strict';

    $(window).load(function () {
        $('.sis-limit-hidden').hide();
    });

    $(document).ready(function () {

        $('.sis-limit-show-more').click(function () {
            $(this).parents().find('.sis-limit-hidden').show();
            $(this).remove();
        });

    });

})(jQuery);
