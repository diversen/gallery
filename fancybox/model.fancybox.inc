<?php

moduleLoader::includeTemplateCommon('fancybox');
fancybox_include();

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
        //print_r($vars);
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
    public function getRows($vars){

        $str = ''; 
        $str.= "<div id=\"gallery_thumbs\">\n";
        $str.= "<table><tr>\n";
        $i = 0;
        foreach ($vars['rows'] as $key => $val) {

            $domain = config::getDomain();
            $base_path = "/files/$domain/gallery";

            $image_url = "$base_path/$val[gallery_id]/full-$val[file_name]";
            $thumb_url = "$base_path/$val[gallery_id]/thumb-$val[file_name]";

            $str.="<td><a rel=\"example_group\" href=\"$image_url\"><img src=\"$thumb_url\" /></a>";
            $str.=self::getAdminOptions($val, $vars);
            /*
            if (session::isAdmin() && !isset($vars['options']['no_admin'])){
                $str.= "<table>"; 
                $str.= "<tr><td>\n"; 
                $str.= '<form name="gallery_delete_image" action="" ';
                $str.= '" method="post" enctype="multipart/form-data">';
                $str.= "<input type=\"hidden\" name=\"method\" value=\"delete\" />\n";
                $str.= '<input type="hidden" name="file_id" value="' . $val['id'] . '" />';
                $str.= '<input type="hidden" name="gallery_id" value="' . $vars['options']['gallery_id'] . '" />';
                $str.= '<input type="submit" name="submit" value="';
                $str.= lang::system('system_submit_delete') . '" />';
                $str.= '</form>';              
                $str.= "</td></tr>";
                $str.= "<tr><td>\n"; 
                $str.= '<form name="gallery_delete_image" action="" ';
                $str.= '" method="post" enctype="multipart/form-data">'; 
                $str.= "<input type=\"hidden\" name=\"method\" value=\"default_image\" />\n";
                $str.= '<input type="hidden" name="file_id" value="' . $val['id'] . '" />';
                $str.= '<input type="hidden" name="gallery_id" value="' . $vars['options']['gallery_id'] . '" />';
                $str.= '<input type="submit" name="submit" value="';
                $str.= lang::translate('gallery_default_image') . '" />';
                $str.= '</form>';              
                $str.= "</td></tr>";
                $str.= "</table>\n";
            }*/
            $str.="</td>\n";
            $i++;
            $t = $i % 4;
            if (!$t){
                $str.="</tr><tr>\n";
                $i = 0;
            }
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
            $str.= '" method="post" enctype="multipart/form-data">';
            $str.= "<input type=\"hidden\" name=\"method\" value=\"delete\" />\n";
            $str.= '<input type="hidden" name="file_id" value="' . $val['id'] . '" />';
            $str.= '<input type="hidden" name="gallery_id" value="' . $vars['options']['gallery_id'] . '" />';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::system('system_submit_delete') . '" />';
            $str.= '</form>';              
            $str.= "</td></tr>";
            $str.= "<tr><td>\n"; 
            $str.= '<form name="gallery_delete_image" action="" ';
            $str.= '" method="post" enctype="multipart/form-data">'; 
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
    
    public static function displayAll () {
        $gallery = new galleryAdmin();
        $galleries = $gallery->getAllGallery();
        foreach ($galleries as $key => $val){
            $val['title'] = $val['title'];
            $link = html::createLink("/gallery/view/$val[id]", $val['title']);
            headline_message($link);

            echo "<div id=\"smallfont\">";
            if (session::isAdmin()){
                echo html::createLink ("/gallery/view/$val[id]", lang::translate('gallery_edit_files')) . " \n";
                echo MENU_SUB_SEPARATOR;
                echo html::createLink("/gallery/admin/edit/$val[id]", lang::translate('gallery_edit_name')) . " \n";
            } 
            echo "</div>\n";           
        }
    }
}
