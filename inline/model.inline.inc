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
            
            $str.=self::getAdminOptions($val, $vars);
            
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
            
            //$str.=self::getAdminOptions($val, $vars);
            
            $str.="</td>\n";
            $i++;
            if ($i == $per_row) break;
        //    $t = $i % 4;
        //    if (!$t){
        //        $str.="</tr><tr>\n";
        //        $i = 0;
          //  }
        }
        


        $str.="</tr></table>\n";
        $str.="</div>\n";
        return $str;        
    }
    
    public static function getAdminOptions ($val, $vars) {
        $str = '';
        if (session::isAdmin() && !isset($vars['options']['no_admin'])){
            $str.= "<table>"; 
            $str.= "<tr><td>\n"; 
            $str.= '<form name="gallery_delete_image" action="" ';
            $str.= " method=\"post\" enctype=\"multipart/form-data\">";
            $str.= "<input type=\"hidden\" name=\"method\" value=\"delete\" />\n";
            $str.= '<input type="hidden" name="file_id" value="' . $val['id'] . '" />';
            $str.= '<input type="hidden" name="gallery_id" value="' . $vars['options']['gallery_id'] . '" />';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::system('system_submit_delete') . '" />';
            $str.= '</form>';              
            $str.= "</td></tr>";
            $str.= "<tr><td>\n"; 
            $str.= '<form name="gallery_delete_image" action="" ';
            $str.= ' method="post" enctype="multipart/form-data">'; 
            $str.= "<input type=\"hidden\" name=\"method\" value=\"default_image\" />\n";
            $str.= '<input type="hidden" name="file_id" value="' . $val['id'] . '" />';
            $str.= '<input type="hidden" name="gallery_id" value="' . $vars['options']['gallery_id'] . '" />';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::translate('gallery_default_image') . '" />';
            $str.= '</form>';              
            $str.= "</td></tr>";
            $str.= "</table>\n";
            }    
            return $str;
    }
    
    public static function getDefaultImage ($row) {
        $str = "<div id=\"imageview\">\n"; 
        $str.= "<img src=\"/files/default/gallery/$row[gallery_id]/med-$row[file_name]\" alt=\"example\" />"; 
        $str.= "</div>";
        return $str;
    }
    
    public static function getUploadForm ($vars) {
        $str = '';
        if (session::isAdmin() && !isset($vars['options']['no_admin'])){
            $str.= '<form enctype="multipart/form-data" method="post" action="" />';   
            $str.= '<input type="file" size="5" name="filename" value="Upload" />';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::system('system_submit_upload') . '" />';
            $str.= '</form>';
        }
        return $str;
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
            $val['title'] = $val['title'];
            $link = html::createLink("/gallery/view/$val[id]", $val['title']);
            headline_message($link);

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
            
            
            
            if (session::isAdmin()){
                echo html::createLink ("/gallery/view/$val[id]", lang::translate('gallery_edit_files')) . " \n";
                echo MENU_SUB_SEPARATOR;
                echo html::createLink("/gallery/admin/edit/$val[id]", lang::translate('gallery_edit_name')) . " \n";
                echo MENU_SUB_SEPARATOR;
                echo html::createLink("/gallery/admin/delete/$val[id]", lang::translate('gallery_delete_gallery')) . " \n";
            }
            
            echo "<hr />\n";

 
            
        }
        echo "<br />\n";
        $pager->pearPage();
    }
}
