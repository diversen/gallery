
(function ($, F) {
    F.transitions.resizeIn = function() {
        var previous = F.previous,
            current  = F.current,
            startPos = previous.wrap.stop(true).position(),
            endPos   = $.extend({opacity : 1}, current.pos);

        startPos.width  = previous.wrap.width();
        startPos.height = previous.wrap.height();

        previous.wrap.stop(true).trigger('onReset').remove();

        delete endPos.position;

        current.inner.hide();

        current.wrap.css(startPos).animate(endPos, {
            duration : current.nextSpeed,
            easing   : current.nextEasing,
            step     : F.transitions.step,
            complete : function() {
                F._afterZoomIn();

                current.inner.fadeIn("fast");
            }
        });
    };

}(jQuery, jQuery.fancybox));

$(".fancybox")
    .attr('rel', 'example_group')
    .fancybox({
        nextMethod : 'resizeIn',
        nextSpeed  : 250,
        
        prevMethod : false,
        
        helpers : {
            title : {
                type : 'inside'
            }
        }
    });


/*
$(".fancybox")
    .attr('rel', 'example_group')
    .fancybox({
        padding    : 0,
        margin     : 5,
        nextEffect : 'fade',
        prevEffect : 'none',
        afterLoad  : function () {
            $.extend(this, {
                aspectRatio : false,
                type    : 'html',
                width   : '100%',
                height  : '100%',
                content : '<div class="fancybox-image" style="background-image:url(' + this.href + '); background-size: cover; background-position:50% 50%;background-repeat:no-repeat;height:100%;width:100%;" /></div>'
            });
        }
    });
*/