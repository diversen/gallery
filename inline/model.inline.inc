<?php


class galleryinline {

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
        $vars = html::specialEncode($vars);
        $str = ''; 
        $str.= "<div id=\"gallery_thumbs\">\n";
        $str.= "<table><tr>\n";
        $i = 0;
        $per_row = config::getModuleIni('gallery_image_row_size');
        foreach ($vars['rows'] as $key => $val) {
            $domain = config::getDomain();
            $base_path = "/files/$domain/gallery";

            $image_url = "$base_path/$val[gallery_id]/full-$val[file_name]";
            $image_url = "/gallery/inline/view/$val[id]";
            
            $thumb_url = "$base_path/$val[gallery_id]/thumb-$val[file_name]";
            
            $img_tag = html::createImage($thumb_url, array ('alt' => $val['description']));
            $str.="<td><a href=\"$image_url\">$img_tag</a>";
            
            //$str.=self::getAdminOptions($val, $vars);
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
    
    public static function getSingleRow($vars, $options = null){
        
        $vars = html::specialEncode($vars);
        $str = ''; 
        $str.= "<div id=\"gallery_thumbs\">\n";
        $str.= "<table><tr>\n";
        $i = 0;
        $per_row = config::getModuleIni('gallery_image_row_size');
        
        foreach ($vars as $key => $val) {
            
            $domain = config::getDomain();
            $base_path = "/files/$domain/gallery";

            $image_url = "$base_path/$val[gallery_id]/full-$val[file_name]";
            $image_url = "/gallery/inline/view/$val[id]";
            
            $thumb_url = "$base_path/$val[gallery_id]/thumb-$val[file_name]";
            
            $img_tag = html::createImage($thumb_url, array ('alt' => $val['description']));
            $str.="<td><a href=\"$image_url\">$img_tag</a>";
            
            $str.="</td>\n";
            $i++;
            if ($i == $per_row) { 
                break;
            }

        }
        
        $str.="</tr></table>\n";
        $str.="</div>\n";
        return $str;        
    }
    
    public static function getAdminOptions ($val, $vars) {
        return galleryAdmin::getAdminOptions ($val, $vars);
    }
    
    public static function getDefaultImage ($row) {
        $str = "<div id=\"imageview\">\n"; 
        $str.= "<img src=\"/files/default/gallery/$row[gallery_id]/med-$row[file_name]\" alt=\"example\" />"; 
        $str.= "</div>";
        return $str;
    }
    
    public static function getUploadForm ($vars) {
        return galleryAdmin::uploadForm ($vars);
        
    }
    
    public static function displaySingleRow ($id) {
        $gal = new gallery;
        $rows = $gal->getAllFileInfo($id);
        $str = self::getSingleRow($rows);
        return $str;
    }
    
    
    public static function displayAll () {
        include_once "pearPager.php";
  
        $gallery = new galleryAdmin();
        $db = new db();
        $num_rows = $db->getNumRows('gallery');

        $per_page = 10;
        $pager = new pearPager($num_rows, $per_page);
        $rows = $db->selectAll('gallery', null, null, $pager->from, $per_page, 'updated');              
        $rows = html::specialEncode($rows);
        
        foreach ($rows as $key => $val){

            galleryAdmin::displayTitle($val);
            echo self::displaySingleRow($val['id']);
            echo $val['description'] . "<br />\n";

            $event_params = array(
                'action' => 'view',
                'reference' => 'gallery',
                'parent_id' => $val['id']);
            
            event::triggerEvent(
                config::getModuleIni('gallery_events'), 
                $event_params
            );    
            
            echo galleryAdmin::adminOptions($val['id']);
            echo "<hr />\n";           
        }
        echo "<br />\n";
        $pager->pearPage();
    }
}
