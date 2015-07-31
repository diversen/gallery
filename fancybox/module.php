<?php


function fancybox_include () {

    // no cache css - as we just keep the orginal image paths.
    template::setNoCacheCss('/templates/fancyBox/source/jquery.fancybox.css');
    template::setJs("/templates/fancyBox/source/jquery.fancybox.js", null, array ('head' => true));
    
    template::setNoCacheCss("/templates/fancyBox/source/helpers/jquery.fancybox-buttons.css");
	//<script type="text/javascript" src="/fancybox/source/helpers/jquery.fancybox-buttons.js?v=2.1.5"></script>
        template::setJs("/templates/fancyBox/source/helpers/jquery.fancybox-buttons.js", null, array ('head' => true));

}

fancybox_include();
template::setInlineJs(_COS_MOD_PATH . '/gallery/fancybox.js');
template::setInlineCss(conf::getModulePath('gallery/inline') . "/assets/inline.css");


class gallery_fancybox {

    /**
     * method for getting files
     *
     * @param   array   array from a db query $rows
     * @param   array    opt unused so far
     * @return  string  html displaying files connect to article.
     * 
     * 
     */
    public static function displayGallery($vars){
      
        $i = 0;
   
        $str = '';
        $num_rows = count($vars['rows']);
        if ($num_rows == 0) {
            // no images
            $str.= self::getUploadForm($vars);
            //return $str;
        } else {
            // more than one image
            if (conf::getModuleIni('gallery_use_default_image')) {
                $str.= self::getDefaultImage($vars['options']['default']);
            }

            $str.= self::getRows($vars);
            $str.= self::getUploadForm($vars);
            //return $str;
        }       
        return $str;
    }
    public static function getRows($vars, $options = null){

        $str = ''; 
        $str.= "<div id=\"gallery_thumbs\">\n";
        $str.= "<table><tr>\n";
        $i = 0;
        
        $per_row = conf::getModuleIni('gallery_image_row_size');
        foreach ($vars['rows'] as $key => $val) {

            $domain = conf::getDomain();
            $base_path = "/files/$domain/gallery";

            $image_url = "$base_path/$val[gallery_id]/full-$val[file_name]";
            $thumb_url = "$base_path/$val[gallery_id]/thumb-$val[file_name]";

            $str.="<td><a rel=\"gallery_group\" href=\"$image_url\" class=\"fancybox\"><img src=\"$thumb_url\" /></a>";    
            $str.=gallery_admin::getAdminOptions($val, $vars);
            
            $str.="</td>\n";
            $i++;
            $t = $i % $per_row;
            if (!$t){
                $str.="</tr><tr>\n";
                $i = 0;
            }
        }
        
        $str.="</tr></table>\n";
        $str.="</div>\n";
        return $str;      
    }
    

    
    public static function getDefaultImage ($row) {
        $str = "<div id=\"imageview\">\n"; 
        $str.= "<img src=\"/files/default/gallery/$row[gallery_id]/med-$row[file_name]\" alt=\"example\" />"; 
        $str.= "</div>";
        return $str;
    }
    
    public static function getUploadForm ($vars) {
        return gallery_admin::uploadForm($vars);
    }
    
    public static function displayAll () {
        $gallery = new gallery_admin();
        $galleries = $gallery->getAllGallery();
        foreach ($galleries as $key => $val){
            gallery_admin::displayTitle($val);
            //echo self::displaySingleRow($val['id']);
            echo $val['description']; // . "<br />\n";
            /*
            $event_params = array(
                'action' => 'view',
                'reference' => 'gallery',
                'parent_id' => $val['id']);
            
            event::triggerEvent(
                config::getModuleIni('gallery_events'), 
                $event_params
            );*/    
            
            echo gallery_admin::adminOptions($val['id']);
            echo "<hr />\n";             
        }
    }
}
