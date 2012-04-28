<?php

include_module('gallery');
config::loadPHPModuleConfig(config::getModulePath('gallery/inline') . "/config.php");
template::setInlineCss(config::getModulePath('gallery/inline') . "/assets/inline.css");

class galleryinline extends gallery {
    
    public $id = null;
    public $row = null;
    
    function __construct($gallery_frag = 2, $file_frag = 3) {
        parent::__construct($gallery_frag, $file_frag);
        $this->getRow();
    }
    
    public function getRow () {
        $this->id = uri::getInstance()->fragment(3);

        //$row = null;
        if (!$this->id) {
            http::permMovedHeader('/gallery/index');
            return;
        }
        
        $this->row = $this->getRowAndSrc($this->id);
        $this->row = html::specialEncode($this->row);

        if (empty($this->row)) {
            http::permMovedHeader('/gallery/index');
            return;
        }
    }

    public function setMeta () {
        
        //$row = $this->getRow();
        $title = rawurldecode($this->row['file_name']);
        if (!empty($this->row['title'])) {
            $title.= MENU_SUB_SEPARATOR;
            $title.= htmlspecialchars($this->row['title']);
        }

        $title.= MENU_SUB_SEPARATOR;
        $title.= lang::translate('gallery_view_full_size');
        template::setTitle($title);

        if (!empty($this->row['description'])) {
            template::setMeta(array('description' => htmlspecialchars($this->row['description'])));
        }
    }
    
    public function displayImage () {
        
        $gal_info = galleryAdmin::getGallery($this->row['gallery_id']);
        $title = "<a name=\"image\"></a>\n";
        $title.= html::createLink("/gallery/view/$gal_info[id]", $gal_info['title']);
        $title.= MENU_SUB_SEPARATOR_SEC . $this->row['title'];
        headline_message($title);
        $this->displayInlineForm();
        echo "<br />";
        
        echo $this->getImageSrc($this->row['id'], 'full');
        $row = $this->getRowAndSrc($this->row['id']);
        echo $link = html::createLink($this->row['src'], lang::translate('gallery_view_original_size'));
    
        $this->postActions();
        $rows = $this->getAllFileInfo($this->row['gallery_id']);
        //$row = $this->getDefaultImage($id);
        $options = array ('gallery_id' => $this->row['gallery_id'], 'no_admin' => true);
        //$rows = $gal->getAllFileInfo($id);
        $vars['rows'] = $rows; 
        $vars['options'] = $options;
        echo $str = self::getRows($vars, $options);
        //return $str;
    }
    
    public function displayInlineForm () {
        if (session::isAdmin()) {
            if (isset($_POST['gallery_details'])) {
                $res = $this->updateImageDetails($this->id);
                if ($res) {
                    $location = "/gallery/inline/view/" . $this->id;
                    $message = lang::translate('gallery_image_details_updated');
                    http::locationHeader($location, $message);
                } else {
                    view_form_errors(gallery::$errors);
                }
            }
            view_gallery_inline_form($this->row);
        }
    }
    
    public function displaySubModules () {
        $return_url = gallery::getReturnUrlFromId($this->row['id']);
        $options = array (
            'parent_id' => $this->row['id'],
            'reference' => 'gallery',
            'return_url' => $return_url
        );

        $subs = array ('comment');
        moduleLoader::includeModules($subs);
        echo moduleLoader::subModuleGetPostContent($subs, $options);
    }
    
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
        
        //print_r($vars);
        if ($num_rows == 0) {
            $str.= galleryAdmin::uploadForm ($vars);
            return $str;
        } else {
            // more than one image
            if (config::getModuleIni('gallery_use_default_image')) {
                $str.= self::getDefaultImage($vars['options']['default']);
            }

            $str.= self::getRows($vars);
            $str.=galleryAdmin::uploadForm ($vars);
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
        $use_anchors = config::getModuleIni('gallery_image_anchors');
        foreach ($vars['rows'] as $key => $val) {
            $domain = config::getDomain();
            $base_path = "/files/$domain/gallery";

            $image_url = "$base_path/$val[gallery_id]/full-$val[file_name]";
            $image_url = "/gallery/inline/view/$val[id]";
            if ($use_anchors) {
                $image_url.= "#image"; 
            }
            
            $thumb_url = "$base_path/$val[gallery_id]/thumb-$val[file_name]";
            $img_tag = html::createImage(
                    $thumb_url, 
                    array (
                        'title' => $val['description'],
                        'alt' => $val['file_name'])
            );
            $str.="<td><a href=\"$image_url\">$img_tag</a>";
            $str.=galleryAdmin::getAdminOptions ($val, $vars);
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
        $str.= "<table width =\"600px\"><tr>\n";
        $i = 0;
        
        $use_anchors = config::getModuleIni('gallery_image_anchors');
        $per_row = config::getModuleIni('gallery_image_row_size');
        
        foreach ($vars as $key => $val) {
            
            $domain = config::getDomain();
            $base_path = "/files/$domain/gallery";

            $image_url = "$base_path/$val[gallery_id]/full-$val[file_name]";
            $image_url = "/gallery/inline/view/$val[id]";
            
            if ($use_anchors) {
                $image_url.= "#image"; 
            }
            
            $thumb_url = "$base_path/$val[gallery_id]/thumb-$val[file_name]";
            
            $img_tag = html::createImage($thumb_url, 
                    
                    array (
                        'title' => $val['description'],
                        'alt' => $val['file_name'])
                    
                    );
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
    
    public static function getDefaultImage ($row) {
        $str = "<div id=\"imageview\">\n"; 
        $str.= "<img src=\"/files/default/gallery/$row[gallery_id]/med-$row[file_name]\" alt=\"example\" />"; 
        $str.= "</div>";
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
            $ary = array ();
            
            galleryAdmin::displayTitle($val);
            $date_formatted = time::getDateString($val['updated']);
            echo user::getProfileSimple($val['user_id'], $date_formatted);
            
            echo self::displaySingleRow($val['id']);
            echo $val['description'] . "<br />\n";

            $event_params = array(
                'action' => 'get',
                'reference' => 'gallery',
                'parent_id' => $val['id'],
                'return' => 'array');
            
            $ary = event::triggerEvent(
                config::getModuleIni('gallery_events'), 
                $event_params
            );    
            
            
            if (session::isAdmin()) {
                $ary[] = galleryAdmin::adminOptions($val['id']);
            }
            if (!empty($ary)) {
                echo "<hr />\n";
            }
            echo implode("<hr />\n", $ary);
            echo "<hr />\n";
            
         
        }
        echo "<br />\n";
        $pager->pearPage();
    }
}
