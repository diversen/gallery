<?php

moduleLoader::includeTemplateCommon('fancybox');
fancybox_include();
template::setInlineCss(config::getModulePath('gallery/inline') . "/assets/inline.css");

class galleryFancybox {

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
            return $str;
        } else {
            // more than one image
            if (config::getModuleIni('gallery_use_default_image')) {
                $str.= self::getDefaultImage($vars['options']['default']);
            }

            $str.= self::getRows($vars);
            $str.= self::getUploadForm($vars);
            return $str;
        }       
        return $str;
    }
    public function getRows($vars, $options = null){

        $str = ''; 
        $str.= "<div id=\"gallery_thumbs\">\n";
        $str.= "<table><tr>\n";
        $i = 0;
        
        $per_row = config::getModuleIni('gallery_image_row_size');
        foreach ($vars['rows'] as $key => $val) {

            $domain = config::getDomain();
            $base_path = "/files/$domain/gallery";

            $image_url = "$base_path/$val[gallery_id]/full-$val[file_name]";
            $thumb_url = "$base_path/$val[gallery_id]/thumb-$val[file_name]";

            $str.="<td><a rel=\"example_group\" href=\"$image_url\"><img src=\"$thumb_url\" /></a>";    
            $str.=galleryAdmin::getAdminOptions($val, $vars);
            
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
        return galleryAdmin::uploadForm($vars);
    }
    
    public static function displayAll () {
        $gallery = new galleryAdmin();
        $galleries = $gallery->getAllGallery();
        foreach ($galleries as $key => $val){
            galleryAdmin::displayTitle($val);
            //echo self::displaySingleRow($val['id']);
            echo $val['description'] . "<br />\n";
            /*
            $event_params = array(
                'action' => 'view',
                'reference' => 'gallery',
                'parent_id' => $val['id']);
            
            event::triggerEvent(
                config::getModuleIni('gallery_events'), 
                $event_params
            );*/    
            
            echo galleryAdmin::adminOptions($val['id']);
            echo "<hr />\n";             
        }
    }
}
