
(function ($) {

    $.fn.showhide = function (options) {

        // Create some defaults, extending them with any options that were provided
        var settings = $.extend({
            'initial': 'hide',
            'element': '.sliding_div',
            'hide_elements': ''

        }, options);

        //alert(settings.hide_elements);


        $(settings.element).hide();
        this.show();

        $(this).click(function () {
            $(settings.hide_elements).hide();
            $(settings.element).slideToggle();
        });

    };
})(jQuery);

$('.show_exif').showhide({
    'element': '.gallery_exif',
    'hide_elements': '.google_map, .edit_details'
});

$('.show_gps').showhide({
    'element': '.google_map',
    'hide_elements': '.gallery_exif, .edit_details'
});

$('.edit_image').showhide({
    'element': '.edit_details',
    'hide_elements': '.gallery_exif, .google_map'
});