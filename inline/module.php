<?php

use diversen\gps;
moduleloader::includeModule('gallery/admin');
moduleloader::includeModule('gallery');
//config::loadPHPModuleConfig(config::getModulePath('gallery/inline') . "/config.php");
template::setInlineCss(config::getModulePath('gallery/inline') . "/assets/inline.css");
$js_url = '/js/jquery.showhide.js';
template::setJs($js_url, null, array('head' => true));

class gallery_inline extends gallery {

    public $id = null;
    public $row = null;

    function __construct($gallery_frag = 2, $file_frag = 3) {
        parent::__construct($gallery_frag, $file_frag);
        $this->getRow();
    }

    public function viewAction() {
        
        moduleloader::includeModule('gallery');

        
        $gallery = new gallery_inline();

        $gallery->setMeta();
        $gallery->displayImage();

        $gallery->displaySubModules();
    }

    public function getRow() {
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

    public function setMeta() {

        //$row = $this->getRow();
        $title = rawurldecode($this->row['file_name']);
        if (!empty($this->row['title'])) {
            $title.= MENU_SUB_SEPARATOR;
            $title.= htmlspecialchars($this->row['title']);
        }

        $title.= MENU_SUB_SEPARATOR;
        $title.= lang::translate('Large image');
        template::setTitle($title);

        if (!empty($this->row['description'])) {
            template::setMeta(array('description' => htmlspecialchars($this->row['description'])));
        }
    }

    public function getGmap($ll, $spn, $z) {
        $width = config::getModuleIni('gallery_image_size');
        $gmap = <<<EOF
<div class ="google_map">
<iframe 
width="$width" 
height="350" 
frameborder="0" 
scrolling="no" 
marginheight="0" 
marginwidth="0" 
src="http://maps.google.com/?ie=UTF8&amp;hq=&amp;hnear=Termestrup,+Denmark&amp;t=h&amp;ll=$ll,$spn&amp;spn=0.016643,0.036478&amp;z=14&amp;output=embed"></iframe>
</div>
EOF;

        return $gmap;
    }

    public function displayImage() {
        ?>
        <style type="text/css">
            .edit_details, .google_map, .gallery_exif { display: none }
        </style><?php

        $rows = $this->getAllFileInfo($this->row['gallery_id']);

        // create a simple set for pagination
        $ary = array();
        foreach ($rows as $val) {
            $url = "/gallery/inline/view/$val[id]";
            $ary[] = $url;
        }

        $page_opt = array();
        if (config::getModuleIni('gallery_image_anchors')) {
            $page_opt['attach'] = '#image';
        }

        $pager = new pageSets($page_opt);
        $pager_str = $pager->getPrevNext($ary, $page_opt);

        echo "<a name=\"image\"></a>\n";

        html::headline($this->row['title']);
        html::headline($pager_str);

        echo "<br />";
        echo $src = $this->getImageSrc($this->row['id'], 'full', array('width' => true));

        $elements = array();
        $elements_content = array();
        $exif = $this->getExifData($this->row['src']);

        if ($exif) {
            $elements_content [] = $table = $this->getExifHTML($exif);
            $view_exif = lang::translate('Image details (exif)');

            $elements[] = $exif_str = <<<EOF
<a name = "exif"></a>
<a href="#exif" class="show_exif">$view_exif</a>
    <script>
        $('.show_exif').showhide({
  'element' : '.gallery_exif',
  'hide_elements' : '.google_map, .edit_details'
});
    </script> 
EOF;
        }

        $gps_map = null;
        if ($exif && isset($exif['GPS'])) {
            $gps = gps::get($exif['GPS'], true);
            $elements_content[] = $gps_map = $this->getGmap($gps['latitude'], $gps['longitude'], 12);
            $lang_gps = lang::translate('GPS');
            $elements[] = $gps_str = <<<EOF
<a name = "google_map"></a>
<a href="#google_map" class="show_gps">$lang_gps</a>
    <script>
        $('.show_gps').showhide({
  'element' : '.google_map',
  'hide_elements' : '.gallery_exif, .edit_details'
});
        
    </script> 
EOF;
        }

        if (session::isAdmin()) {
            $edit_details = lang::translate('Edit image details');

            $elements[] = <<<EOF
<a name = "edit_details"></a>
<a href="#edit_details" class="edit_image">$edit_details</a>
    <script>
        $('.edit_image').showhide({
  'element' : '.edit_details',
  'hide_elements' : '.gallery_exif, .google_map'
});
    </script> 

EOF;
            $elements_content[] = $this->displayInlineForm();
        }

        $elements[] = $link = html::createLink($this->row['src'], lang::translate('Original image'));

        $this->postActions();
        echo implode(MENU_SUB_SEPARATOR, $elements);
        echo implode("\n", $elements_content);

        $options = array('gallery_id' => $this->row['gallery_id'], 'no_admin' => true);

        $vars['rows'] = $rows;
        $vars['options'] = $options;
        echo $str = self::getRows($vars, $options);
    }

    /**
     * displays inline form. 
     * Errors displayed in postActions if any
     * @return type 
     */
    public function displayInlineForm() {
        if (session::isAdmin()) {
            if (isset($_POST['gallery_details'])) {
                $res = $this->updateImageDetails($this->id);
                if ($res) {
                    $location = "/gallery/inline/view/" . $this->id;
                    $message = lang::translate('Image details has been updated');
                    http::locationHeader($location, $message);
                }
            }
            return get_gallery_inline_form($this->row);
        }
    }

    /**
     * displays inline module's submodules
     */
    public function displaySubModules() {
        $return_url = gallery::getReturnUrlFromId($this->row['id']);
        $options = array(
            'parent_id' => $this->row['id'],
            'reference' => 'gallery',
            'return_url' => $return_url
        );

        $subs = config::getModuleIni('gallery_sub_modules');
        moduleloader::includeModules($subs);
        $ary = moduleloader::subModuleGetPostContent($subs, $options);
        echo implode("<hr />\n", $ary);
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
    public static function displayGallery($vars) {

        $i = 0;
        $str = '';
        $num_rows = count($vars['rows']);

        if ($num_rows == 0) {
            $str.= gallery_admin::uploadForm($vars);
            return $str;
        } else {
            // more than one image
            if (config::getModuleIni('gallery_use_default_image')) {
                $str.= self::getDefaultImage($vars['options']['default']);
            }

            $str.= self::getRows($vars);
            $str.=gallery_admin::uploadForm($vars);
            return $str;
        }
        return $str;
    }

    /**
     * returns gallery rows
     * @param array $vars
     * @param array|null $options
     * @return string
     */
    public static function getRows($vars, $options = null) {

        $vars = html::specialEncode($vars);
        $str = '';
        $str.= "<div class=\"gallery_thumbs\">\n";
        $str.= "<table><tr>\n";
        $i = 0;
        $per_row = config::getModuleIni('gallery_image_row_size');
        $use_anchors = config::getModuleIni('gallery_image_anchors');
        foreach ($vars['rows'] as $key => $val) {
            $domain = config::getDomain();
            $base_path = "/files/$domain/gallery";

            $image_url = "/gallery/inline/view/$val[id]";
            if ($use_anchors) {
                $image_url.= "#image";
            }

            $thumb_url = "$base_path/$val[gallery_id]/thumb-$val[file_name]";
            $img_tag = html::createImage(
                            $thumb_url, array(
                        'title' => $val['description'],
                        'alt' => $val['file_name'])
            );
            $str.="<td><a href=\"$image_url\">$img_tag</a>";
            $str.=gallery_admin::getAdminOptions($val, $vars);
            $str.="</td>\n";
            $i++;
            $t = $i % $per_row;
            if (!$t) {
                $str.="</tr><tr>\n";
                $i = 0;
            }
        }

        $extra = $per_row - ($i % $per_row);
        $str.= self::getExtraTd($extra);
        $str.="</tr></table>\n";
        $str.="</div>\n";
        return $str;
    }

    /**
     * get extra td
     * @param type $num
     * @return string
     */
    public static function getExtraTd($num) {
        $str = '';
        while ($num) {
            $str.= "<td>&nbsp;</td>\n";
            $num--;
        }
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

        $use_anchors = config::getModuleIni('gallery_image_anchors');
        $per_row = config::getModuleIni('gallery_image_row_size');

        foreach ($vars as $val) {

            $domain = config::getDomain();
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

    public static function displaySingleRow($id) {
        $gal = new gallery;
        $rows = $gal->getAllFileInfo($id);
        $str = self::getSingleRow($rows);
        return $str;
    }

    public static function displayAll() {


        $gallery = new gallery_admin();
        $db = new db();
        $num_rows = $db->getNumRows('gallery');

        $per_page = 10;
        $pager = new pearPager($num_rows, $per_page);
        $rows = $db->selectAll('gallery', null, null, $pager->from, $per_page, 'updated');
        $rows = html::specialEncode($rows);

        foreach ($rows as $key => $val) {
            $ary = array();

            self::displayTitle($val);
            $date_formatted = time::getDateString($val['updated']);
            echo user::getProfileSimple($val['user_id'], $date_formatted);
            if (config::getModuleIni('gallery_preview_display_all')) {
                $rows = self::getAllFileInfo($val['id']);
                $options = array('gallery_id' => $val['id'], 'no_admin' => true);

                $vars['rows'] = $rows;
                $vars['options'] = $options;
                echo $str = self::getRows($vars, $options);
            } else {
                echo self::displaySingleRow($val['id']);
            }

            if (session::isAdmin()) {
                array_unshift($ary, gallery_admin::adminOptions($val['id']));
            }

            $event_params = array(
                'action' => 'get',
                'reference' => 'gallery',
                'parent_id' => $val['id'],
                'return' => 'array');

            $events = event::triggerEvent(
                            config::getModuleIni('gallery_events'), $event_params
            );

            $ary = array_merge($ary, $events);
            if (empty($val['description'])) {
                $val['description'] = '...';
            }

            array_unshift($ary, $val['description']);
            echo implode("<hr />\n", $ary);
            echo "<hr />\n";
        }

        echo "<br />\n";
        $pager->pearPage();
    }

    public static function displayTitle($val) {
        //$link = html::createLink("/gallery/view/$val[id]", $val['title']);
        html::headline($val['title']);
    }

}
