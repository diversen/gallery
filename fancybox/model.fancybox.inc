<?php

include_template_inc('fancybox');
fancybox_include();

class galleryFancybox {

    /**
     * method for getting files
     *
     * @param   array   array from a db query $rows
     * @param   array    opt unused so far
     * @return  string  html displaying files connect to article.
     */
    public function displayThumbs($vars){
        return templateView::includeModuleView('gallery', 'thumb_table_jquery', $vars, 1);
        
    }  
}