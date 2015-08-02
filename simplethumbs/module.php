<?php

use diversen\conf;
use diversen\event;
use diversen\html;
use diversen\session;
use diversen\template;

template::setInlineJs(conf::getModulePath('gallery') . '/simplethumbs/simplethumbs.js');
template::setInlineJs(conf::getModulePath('gallery') . '/simplethumbs/command.js');
template::setInlineCss(conf::getModulePath('gallery/inline') . "/assets/inline.css");

class gallery_simplethumbs {

    /**
     * method for getting files
     *
     * @param   array   array from a db query $rows
     * @param   array    opt unused so far
     * @return  string  html displaying files connect to article.
     */
    public static function displayGallery($vars){
      
        //$i = 0;
   
        $str = '';
        //print_r($vars);
        $num_rows = count($vars['rows']);
        if ($num_rows == 0) {
            // no images
            $str.= self::getUploadForm($vars);
            return $str;
        } else if ($num_rows == 1) {
            // one image
            $str.= self::getDefaultImage($vars['options']['default']);
            if (session::isAdmin() && !isset($vars['options']['no_admin'])) {
                $str.= self::getRows($vars);
            }
            
            $str.= self::getUploadForm($vars);
            return $str;
            
        } else {
            // more than one image

            $str.= self::getDefaultImage($vars['options']['default']);
            $str.= self::getRows($vars);
            $str.= self::getUploadForm($vars);
            return $str;
        }       
        return $str;
    }
    
    public static function getUploadForm ($vars) {
        return gallery_admin::uploadForm($vars);
    }
    
    public static function getRows ($vars) {
        $i = 0;
        $str = '';
        $str.="<div id=\"thumbnails\">";    
        $str.="<table><tr>\n";
        
        $per_row = conf::getModuleIni('gallery_image_row_size');
        foreach ($vars['rows'] as $key => $val) {           
            $src = "/files/default/gallery/$val[gallery_id]/med-$val[file_name]";
            $thumb_src = "/files/default/gallery/$val[gallery_id]/thumb-$val[file_name]";
            $thumb_img = html::createImage($thumb_src);
            $link = html::createLink($src, $thumb_img);

            $str.="<td>$link";
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
    
    public static function displayAll () {
        $gallery = new gallery_admin();
        $galleries = $gallery->getAllGallery();
        foreach ($galleries as $key => $val){
            gallery_admin::displayTitle($val);
            //echo self::displaySingleRow($val['id']);
            echo $val['description'] . "<br />\n";

            $event_params = array(
                'action' => 'view',
                'reference' => 'gallery',
                'parent_id' => $val['id']);
            
            event::triggerEvent(
                conf::getModuleIni('gallery_events'), 
                $event_params
            );    
            
            echo gallery_admin::adminOptions($val['id']);
            echo "<hr />\n";             
        }
    }
    
}