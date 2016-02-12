<?php

namespace modules\gallery\inline;

use diversen\conf;
use diversen\db;
use diversen\db\q;
use diversen\gps;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\pagination;
use diversen\pagination\sets;
use diversen\session;
use diversen\template;
use diversen\time;
use diversen\uri;
use diversen\user;
use diversen\template\assets;
use diversen\template\meta;

use modules\gallery\module as gallery;
use modules\gallery\admin\module as admin;

assets::setInlineCss(conf::getModulePath('gallery/inline') . "/assets/inline.css");
assets::setInlineJs(conf::getModulePath('gallery/inline') . "/assets/showhide.jquery.js");

class module extends gallery {

    public $id = null;
    public $row = null;
    
    public function __construct() {
        $this->id = uri::fragment(3);
        $this->row = $this->getImageDbAndSrc($this->id);
    }

    public function setMeta() {

        
        
        //print_r($this->row); die;
        
        
        
        $title = rawurldecode($this->row['file_name']);
        if (!empty($this->row['title'])) {
            $title.= MENU_SUB_SEPARATOR;
            $title.= $this->row['title'];
        }

        $title.= MENU_SUB_SEPARATOR;
        $title.= lang::translate('Large image');

        if (!empty($this->row['description'])) {
            template::setMeta(array('description' => htmlspecialchars($this->row['description'])));
        }
        
        $abs = $this->row['src'];
        meta::setMetaAll($title, $this->row['description'], '', $abs);
        
    }

    public function getGmap($lat, $long) {
        $width = conf::getModuleIni('gallery_image_size');
        $gmap = <<<EOF
<div class ="google_map">
<iframe 
width="$width" 
height="350" 
frameborder="0" 
scrolling="no" 
marginheight="0" 
marginwidth="0" 
src="https://maps.google.com/?ie=UTF8&amp;hq=&amp;hnear=Termestrup,+Denmark&amp;t=h&amp;ll=$lat,$long&amp;spn=0.016643,0.036478&amp;z=14&amp;output=embed"></iframe>
</div>
EOF;

        return $gmap;
    }
    
    
    /**
     * gallery/inline/view action
     */
    public function viewAction () {
        
        $this->setMeta();
        $image_id = uri::fragment(3);  
        echo $this->displayImage($image_id);        
    } 
    
    /**
     * Display image 
     * @param int $id image id
     */
    public function displayImage($id) {

        $row = $this->getImageDbAndSrc($id);
        $gallery_id = $this->getGalleryIdFromImageId($id);

        ?>
        <style type="text/css">
            .edit_details, .google_map, .gallery_exif { display: none }
        </style><?php

        $rows = $this->getImagesFromGalleryId($gallery_id);

        // create a simple set for pagination
        $ary = array();
        foreach ($rows as $val) {
            $url = "/gallery/inline/view/$val[id]";
            $ary[] = $url;
        }

        $page_opt = array();
        $page_opt['attach'] = '#image';

        $pager = new sets($page_opt);
        $pager_str = $pager->getPrevNext($ary, $page_opt);

        echo "<a name=\"image\"></a>\n";

        echo html::getHeadline($row['title']);
        echo html::getHeadline($pager_str);

        echo "<br />";
        echo $src = $this->getImageSrcDiv($row['id'], 'full', array('width' => true));

        $elements = array();
        $elements_content = array();
        $exif = $this->getExifData($row['src']);

        if (!empty($exif)) {
            $elements_content [] = $table = $this->getExifHTML($exif);
            $view_exif = lang::translate('Image details (exif)');

            $elements[] = $exif_str = <<<EOF
<a name = "exif"></a>
<a href="#exif" class="show_exif">$view_exif</a>
EOF;
        }

        $gps_map = null;
        
        $gps = new gps();
        $ary = $gps->getGpsPosition(conf::pathHtdocs() . $row['src']);        
        if (!empty($ary)) {
            
            //$gps = gps::get($gps_data, true);
            $elements_content[] = $this->getGmap($ary['latitude'], $ary['longitude']);
            $lang_gps = lang::translate('GPS');
            $elements[] = $gps_str = <<<EOF
<a name = "google_map"></a>
<a href="#google_map" class="show_gps">$lang_gps</a>

EOF;
        }

        if (session::isAdmin()) {
            $edit_details = lang::translate('Edit image details');

            $elements[] = <<<EOF
<a name = "edit_details"></a>
<a href="#edit_details" class="edit_image">$edit_details</a>
EOF;
            $elements_content[] = $this->displayInlineForm();
        }

        $elements[] = $link = html::createLink($row['src'], lang::translate('Original image'));

        $this->postActions();
        echo implode(MENU_SUB_SEPARATOR, $elements);
        echo implode("\n", $elements_content);

        $options = array('gallery_id' => $row['gallery_id']);

        $vars['rows'] = $rows;
        $vars['options'] = $options;
        echo $str = $this->getRows($vars, $options);
    }

    /**
     * displays inline form. 
     * Errors displayed in postActions if any
     * @return type 
     */
    public function displayInlineForm() {
        if (session::isAdmin()) {
            if (isset($_POST['gallery_details'])) {
                $res = $this->updateImage($this->id);
                if ($res) {
                    $location = "/gallery/inline/view/" . $this->id;
                    $message = lang::translate('Image details has been updated');
                    http::locationHeader($location, $message);
                }
            }
            $row = $this->getImageDbAndSrc($this->id);
            return admin::formInline($row);
        }
    }


    /**
     * returns gallery rows
     * @param array $vars
     * @param array|null $options
     * @return string
     */
    public function getRows($vars, $options = array()) {

        $vars = html::specialEncode($vars);
        
        $str = '';
        $str.= "<div class=\"gallery_thumbs\">\n";
        $str.= "<table><tr>\n";
        $i = 0;
        $per_row = conf::getModuleIni('gallery_image_row_size');
        foreach ($vars['rows'] as $val) {
            $domain = conf::getDomain();
            $base_path = "/files/$domain/gallery";

            $image_url = "/gallery/inline/view/$val[id]";
            $image_url.= "#image";

            $thumb_url = "$base_path/$val[gallery_id]/thumb-$val[file_name]";
            $img_tag = html::createImage(
                            $thumb_url, array(
                        'title' => $val['description'],
                        'alt' => $val['file_name'])
            );
            $str.="<td><a href=\"$image_url\">$img_tag</a>";
            
            if (isset($options['admin']))  {
                $str.=admin::getAdminOptions($val, $vars);
            }
            $str.="</td>\n";
            $i++;
            $t = $i % $per_row;
            if (!$t) {
                $str.="</tr><tr>\n";
                $i = 0;
            }
        }


        $str.="</tr></table>\n";
        $str.="</div>\n";
        return $str;
    }


    /**
     * return a single row
     * @param type $vars
     * @param type $options
     * @return string
     */
    public static function getSingleRow($vars, $options = null) {

        $vars = html::specialEncode($vars);
        $str = '';
        $str.= "<div class=\"gallery_thumbs\">\n";
        $str.= "<table width =\"600px\"><tr>\n";
        $i = 0;

        $use_anchors = conf::getModuleIni('gallery_image_anchors');
        $per_row = conf::getModuleIni('gallery_image_row_size');

        foreach ($vars as $val) {

            $domain = conf::getDomain();
            $base_path = "/files/$domain/gallery";

            $image_url = "$base_path/$val[gallery_id]/full-$val[file_name]";
            $image_url = "/gallery/inline/view/$val[id]";

            if ($use_anchors) {
                $image_url.= "#image";
            }

            $thumb_url = "$base_path/$val[gallery_id]/thumb-$val[file_name]";

            $img_tag = html::createImage($thumb_url, array(
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

    public static function getDefaultImage($row) {
        $str = "<div id=\"imageview\">\n";
        $str.= "<img src=\"/files/default/gallery/$row[gallery_id]/med-$row[file_name]\" alt=\"example\" />";
        $str.= "</div>";
        return $str;
    }

    public function displaySingleRow($id) {
        $gal = new gallery;
        $rows = $gal->getImagesFromGalleryId($id);
        $str = $this->getSingleRow($rows);
        return $str;
    }

    
    public function displayAllGalleries($options = array()) {

        $db = new db();
        $num_rows = $db->getNumRows('gallery');
        $per_page = 10;
        $pager = new pagination($num_rows, $per_page);

        $rows = q::select('gallery')->order('updated', 'DESC')->limit($pager->from, $per_page)->fetch();
        foreach ($rows as $row) {
            $this->displayGallery($row, $options);
        }

        $pager->echoPagerHTML();
    }
    
    public function displayGallery($val, $options) {
        $ary = array();

        echo html::getHeadline($val['title']);
        $date_formatted = time::getDateString($val['updated']);
        echo user::getProfile($val['user_id'], $date_formatted);

        $rows = $this->getImagesFromGalleryId($val['id']);
        $options['gallery_id'] = $val['id'];

        $vars['rows'] = $rows;
        $vars['options'] = $options;
        echo $this->getRows($vars, $options);


        if (empty($val['description'])) {
            $val['description'] = '...';
        }

        echo $val['description'];
        echo "<hr />";
        if (!isset($options['admin'])) {
            echo admin::adminOptions($val['id']);
        }
    }

}
