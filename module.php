<?php

namespace modules\gallery;

use diversen\conf;
use diversen\db;
use diversen\file;
use diversen\html;
use diversen\http;
use diversen\imagescale;
use diversen\lang;
use diversen\log;
use diversen\moduleloader;
use diversen\session;
use diversen\strings;
use diversen\template;
use diversen\upload;
use diversen\uri;

/**
 * File holding basic operations for gallery module
 *
 * @package gallery
 */

/**
 * @ignore
 */

//moduleloader::includeModule('gallery/admin');
use modules\gallery\admin\module as adminModule;


/**
 * Class for creating galleries
 *
 * @package gallery
 */
class module {

    /**
     * var holding errors
     * @var array $errors
     */
    public static $errors = null;
    
    /**
     * var for holding gallery id 
     * @var int $galleryId
     */
    public static $galleryId;
    
    /**
     * when the is af file id we use $filId to hold it
     * @var int $fileId 
     */
    public static $fileId;
    
    /**
     *
     * @var string $uploadDir holding the dir where files are uploaded to
     */
    public static $uploadDir;
    
    /**
     * 
     * @var array   $options holding speciel options 
     */
    public static $options = null;

    /**
     * constructor. Sets gallery id and file id from url fragements. 
     * also sets uploaddir based om gallery fragement
     * @param type $gallery_frag
     * @param type $file_frag 
     */
    function __construct($gallery_frag = 2, $file_frag = 3){
        
        $uri = uri::getInstance();
        self::$galleryId = $uri->fragment($gallery_frag);
        self::$fileId = $uri->fragment($file_frag);
        
        $domain = conf::getDomain ();
        if (!$domain) { 
            $domain = 'default';
        }
        self::$uploadDir = 
                conf::pathHtdocs() . 
                "/files/$domain/gallery/" . 
                self::$galleryId;
        
        $options = array('upload_dir' => self::$uploadDir);
        $options['allow_mime'] = array (
            'image/gif', 
            'image/jpeg', 
            'image/pjpeg', 
            'image/png'
        );
        self::$options = $options;
    }
    
    public function deleteAction() {
        if (!session::checkAccessFromModuleIni('gallery_allow_edit')) {
            return;
        }

        template::setTitle(lang::translate('Delete image'));
        $fileObj = new self();
        if (isset($_POST['submit'])) {
            if (!isset($fileObj->errors)) {
                $res = $fileObj->deleteFile($fileObj->fileId);
                if ($res) {
                    //session::setActionMessage(lang::translate('File deleted'));
                    http::locationHeader('/gallery/view/' . $fileObj->id);
                }
            } else {
                html::errors($fileObj->errors);
            }
        }
        if (!empty($fileObj->fileId)) {
            view_image_form('delete');
        }
    }

    public function viewAction () {

        self::viewGallery();
        
    }
    
    public function indexAction () {
        /**
 * view file for admin gallery 
 *
 * @package    gallery
 */
moduleloader::includemodule("gallery/admin");
template::setTitle(lang::translate('List galleries'));

$gal = new adminModule();
$gal->displayAllGallery();
    }
   
    /**
     * method for delting a file
     *
     * @param   int   galleryId of file
     */
    public function deleteFile($id){
        
        $upload = new upload(self::$options, true); 
        
        db::$dbh->beginTransaction();
        $db = new db();
        
        $domain = conf::getDomain();
        $row = $db->selectOne('gallery_file', 'id', $id);
        $path = $this->getGalleryPath($row);
        $filename = $path . "/" . $row['file_name'];
        
        $res = unlink($filename);
        //if (!$res) die ('could not unlink');

        $filename = $path . '/thumb-' . $row['file_name'];
        $res = unlink($filename);
        
        $filename = $path . '/small-' . $row['file_name'];
        $res = unlink($filename);
        
        $filename = $path . '/med-' . $row['file_name'];
        $res = unlink($filename);
        
        $filename = $path . '/full-' . $row['file_name'];
        $res = unlink($filename);
        
        $res = $db->delete('gallery_file', 'id', $id);
        return db::$dbh->commit();
    }
    
    public static function setDefaultImage ($file_id) {
        $db = new db();
        $db->update(
                'gallery_file', 
                array('default' => 0), 
                array('gallery_id' => self::$galleryId));
    
        $db->update('gallery_file', array('default' => 1), array('id' => $file_id));
    }
        

    /**
     * method for inserting a file
     *
     * @return  boolean true on success and false on failure
     */
    public static function insertFile(){

        upload::setOptions(self::$options);
        $res = upload::moveFile('filename');
        if (!$res) {
            self::$errors = upload::$errors;            
            return false;
        }
        
        $savename = upload::$saveBasename;

        $values = array();
        $values['gallery_id'] = self::$galleryId;
        $values['file_name'] = $savename;
              
        $filename = self::$uploadDir . '/' . $savename;

        // scale a thumb
        $thumb = self::$uploadDir . '/thumb-' . $savename;
        $res = self::scaleImage($filename, $thumb, conf::getModuleIni('gallery_thumb_size'));
        
        if (!$res) return false;
        
        $med = self::$uploadDir . '/med-' . $savename;
        $res = self::scaleImage($filename, $med, conf::getModuleIni('gallery_med_size'));
        
        if (!$res) return false;
        
        // scale large
        $normal = self::$uploadDir . '/small-' . $savename;
        $res = self::scaleImage($filename, $normal, conf::getModuleIni('gallery_small_size'));
        
        if (!$res) return false;
        
        // scale large
        $normal = self::$uploadDir . '/full-' . $savename;
        $res = self::scaleImage($filename, $normal, conf::getModuleIni('gallery_image_size'));
        
        if (!$res) return false;        
        
        $db = new db();
        $res = $db->insert('gallery_file', $values);
        return $res;
    }

    /**
     * 
     * @param  string    the image file to scale
     * @param  string    the filename of the thumb
     * @param  int       the length of the image   
     */
    public static function scaleImage ($image, $thumb, $x){
        
        $scale = new imagescale();
        $res = $scale->byX($image, $thumb, $x);
        
        if (!$res) {
            
            self::$errors = imagescale::$errors;
            return false;
        }

        return true;
    }

    /**
     * method for getting all files connected to a parent galleryId
     *
     * @return      array   rows of a select query
     */
    public function getAllFiles($gallery_id = null){
        if (!$gallery_id) { 
            $gallery_id = self::$galleryId;
        }
        $db = new db();
        $rows = $db->select('gallery_file', 'gallery_id', $gallery_id);
        return $rows;
    }
    
    public static function getReturnUrlFromId ($id){
        $link_url = "/gallery/inline/view/$id";
        return $link_url;
    }
    
    public static function getRedirect ($id) {
        return self::getReturnUrlFromId($id);
    }
    
    public static function getLinkFromId ($id){
        $url = self::getReturnUrlFromId($id);
        $row = self::getSingleImage($id);
        if (empty($row)) { 
            return lang::translate('Reference has been removed');
        }
        $link = html::createLink($url, rawurldecode($row['file_name']));
        return $link;
    }
    
    public function getSingleImage($id){
        $db = new db();
        $row = $db->selectOne('gallery_file', 'id', $id);
        return $row;
    }
    
    public function getRowAndSrc ($id, $size = '') {
        static $row;
        if (!empty($size)) {
            $size = "$size-";
        }
        
        $row = $this->getSingleImage($id);
        if (!$row) return false;
        
        $domain = conf::getDomain();
        $row['src'] = "/files/$domain/gallery/$row[gallery_id]/" . $size . "$row[file_name]";
        $row['width'] = conf::getModuleIni('gallery_image_size');
        return $row;
    }
    
    public function getImageSrc ($id, $size = '') {
        $org = $this->getRowAndSrc($id);
        
        $row = $this->getRowAndSrc($id, 'full');
        
        $str = "<div id=\"imageview\">\n"; 
        $str.= "<a href=\"$org[src]\">";
        $str.= "<img src=\"$row[src]\" alt=\"$row[description]\"";
        if (isset($row['width'])) {
            $str.= " width=\"$row[width]\">";
        }
        $str.= "</a>\n";
        $str.= "</div>";

        return $str;
    }


    /**
     * method for getting all connected to a gallery
     *
     * @return      array   rows of a select query
     */
    public static function getAllFileInfo($gallery_id) { 
        $db = new db();
        $rows = $db->select('gallery_file', 'gallery_id', $gallery_id);
        return $rows;
    }
    

    /**
     * method for displaying a gallery
     * 
     * @param   array   array from a db query $rows
     * @param   array   array ('no_admin' => 1, 'gallery_galleryId => 1);
     * 
     * @return  string  html displaying files connect to article.
     */
    public static function viewGallery ($gallery_frag = 2, $file_frag = 3, $options = array () ) {
        
        // set a default redirct if there is no gallery for a URL
        if (!isset($options['no_gallery_redirect'])) {
            $options['no_gallery_redirect'] = '/gallery/index';
        }
        
        $vars = array();
        $gal = new self($gallery_frag, $file_frag);  
        $id = self::$galleryId;
        $gal_info = adminModule::getGallery($id);
        
        if (empty($gal_info)) {
            if (!isset($options['no_redirect'])) {
                http::permMovedHeader($options['no_gallery_redirect']);
            }
        }
                
        $gal->postActions($gallery_frag, $file_frag);
        if (!empty($gal_info['title'])) {
            $title = lang::translate('View gallery');
            $title.= MENU_SUB_SEPARATOR . $gal_info['title'];        
            template::setTitle($title);
            template::setMeta(array ('description' => $gal_info['description']));
            html::headline($gal_info['title']);
            //echo "<br />\n";
        }
        
        $row = $gal->getDefaultImage($id);
        $options['gallery_id'] = $id;
        $options['default'] = $row;

        $rows = $gal->getAllFileInfo($id);
        $vars['rows'] = $rows; 
        $vars['options'] = $options;
        
        $display_module = conf::getModuleIni('gallery_display_module');
        if ($display_module) {            
            moduleloader::includeModule($display_module);
            $module = "modules\\" . moduleloader::modulePathToClassName($display_module) . "\\module";
            echo $module::displayGallery($vars);
        }
        return;
    }
    
    /**
     * returns array with exif info. 
     * empty if no exif data exists in the image
     * @param string $web_src path to image
     * @return array
     */
    public function getExifData($web_src) {
        $file = conf::pathHtdocs() . $web_src;
        $exif = @exif_read_data($file, 'FILE,ANY_TAG, IFD0, COMMENT, EXIF', true);

        if (!$exif) {
            return array();
        }
        
        // flatten exif. Preserve keys
        function prefixKey($prefix, $array) {
            $result = array();
            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $result = array_merge($result, prefixKey($prefix . $key . '.', $value));
                } else {
                    $result[$prefix . $key] = $value;
                }
            }
            return $result;
        }
        $res = prefixKey('', $exif);
        return $res;
    }

    public function getExifHTML ($exif) {
        
        $exif = html::specialEncode($exif);
        $str = "<div class =\"gallery_exif\">\n";
        $str.= "<table>\n";

        foreach ($exif as $key => $val) {

                $str.= "<tr>\n";
                $str.= "<td>$key</td>\n";
                $str.= "<td>$val</td>\n";
                $str.= "</tr>\n";

        }
        $str.= "</table>\n";
        $str.= "</div>\n";
        return $str;
        
    }
    
    public static function getBGImage () {

        $uri = uri::getInstance();
        echo $image_id = $uri->fragment(3);
        echo $gallery_id = $uri->fragment(2);
        $db = new db();

        if (!$image_id){
            $query = "SELECT * FROM `gallery_file` ORDER BY RAND() LIMIT 1";
            $show = $db->selectQuery($query);
            if (!empty($show)){
                $show = $show[0];
            }
        } else {
            $show = $db->selectOne('gallery_file', 'id', $image_id);
        }

        $rows = self::getAllFiles(self::$galleryId);
        $str = '';
        $str.="<div id=\"gallery_pager\">";
        $str.="<table><tr>\n";
        $domain = conf::getDomain();
        foreach ($rows as $key => $val) {
            $thumb_src = "/files/$domain/gallery/thumb-" . $val['file_name'];          
            $path = "/gallery/view/" . self::$galleryId . "/$val[id]";
            $str.= "<td><a href=\"$path\">";
            $str.= self::getImageTag($thumb_src) . "</a></td>\n";
        }
        $str.= self::getAdminCells();
        $str.="</tr></table>\n";
        $str.="</div>\n";
        return $str;
    }
     
    /**
     * method for returning string containing admin forms for deleting images 
     * and uploading images.
     * 
     * @return  string  string containing admin links
     */
    public function getAdminCells(){
        $str = '';
        if (session::isAdmin() && !empty($this->fileId)){
            $str.= '<td>';
            $str.= '<form action="/gallery/delete/' . $this->galleryId . '/' . $this->fileId;
            $str.= '" method="post" name="test" enctype="1" >';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::translate('Delete') . '" />';
            $str.= '</form>';
            $str.= '</td>';
        }
        
        if (session::isAdmin()){
            $str.= '<td>';
            $str.= '<form action="/gallery/view/' . $this->galleryId;
            $str.= '" method="post" name="test" enctype="multipart/form-data" >';
            $str.= '<input type="file" size="5" name="filename" value="Upload" />';
            $str.= "</td><td>\n";
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::translate('Upload image') . '" />';
            $str.= '</form>';
            $str.= '</td>';
        }
        return $str;
    }

    /**
     * method for getting a random image url
     */
    public static function getRandomImageURL($thumb = false){
        $uri= uri::getInstance();
        $module = $uri->fragment(0);
        $gallery_id = $uri->fragment(2);

        $db = new db();
        $search = array('gallery_id' => $gallery_id);
        $num_rows = $db->getNumRows('gallery_file', $search);

        if (!$num_rows){
            $query = "SELECT * FROM `gallery_file` LIMIT 1 ";
        } else {
            $query = "SELECT * FROM `gallery_file` WHERE gallery_id = ";

            $query.=  db::$dbh->quote($gallery_id) . ' ';
           $query.= " LIMIT 1";
        }
        
        $show = $db->selectQuery($query);

        if (!empty($show)){
            $show = $show[0];
            $domain = conf::getDomain();
            //config::getFullFilesPath();
            $image_url = "/files/$domain/gallery/" .
                         $show['gallery_id'] . '/' .
                         $show['file_name'];
            if ($thumb){
                $image_url = "/files/$domain/gallery/" .
                         $show['gallery_id'] . '/thumb-' .
                         $show['file_name'];
            }
           return $image_url;
        }
    }

    /**
     * method for getting html image tag
     * 
     * @param    string    source of the image
     * @return   string    tag of image
     */
    private function getImageTag($imageSrc){
        $str = "<img src=\"$imageSrc\" />";
        return $str;
    }


    /**
     * method for getting number of images in a gallery
     * @return   int    number of images in a gallery
     */
    private function getNumImagesGallery(){
        $db = new db();
        $where = array('gallery_id' => $this->galleryId);
        $num_rows = $db->getNumRows('gallery_file', $where);
        return $num_rows;
    }

    /**
     * method for updating a file in db table
     * 
     * @return  boolean  true on success and false on failure
     */
    public function updateFile ($values, $id){
        db::prepareToPost();
        $res = $this->update('gallery_file', $values, $id);
        return $res;
    }
    
    public static function getDefaultImageUrl($gallery_id, $type = 'full') {
        $row = self::getDefaultImage($gallery_id);
        if (empty($row)) {
            return '';
        }
        
        $domain = conf::getDomain();
        $link = "/files/$domain/gallery/$gallery_id/$type-$row[file_name]";
        return $link;
    }
    
    /**
     * performs actions, add and delete based on url fragements
     * @param  int $gallery_frag
     * @param int $file_frag
     * @return boolean $res true on success and false on failure
     */
    public function postActions ($gallery_frag = 2, $file_frag = 3 ) {
        
        if (isset($_POST['submit'])){
            if (!session::checkAccessFromModuleIni('gallery_allow_edit')){
                return;
            }
            
            if (isset($_POST['file_id']) && $_POST['method'] == 'delete') {
                $this->deleteFile($_POST['file_id']);
                session::setActionMessage(lang::translate('File deleted'));
                http::locationHeader($_SERVER['REQUEST_URI']);
                
            }
            
            if (isset($_POST['file_id']) && $_POST['method'] == 'default_image') {
                $this->setDefaultImage($_POST['file_id']);
                session::setActionMessage(lang::translate('File is gallery default'));
                http::locationHeader($_SERVER['REQUEST_URI']);
            }
            
            if (!isset(self::$errors)){
                $res = $this->insertFile('filename');
                if ($res){
                    session::setActionMessage(lang::translate('File added'));
                    http::locationHeader($_SERVER['REQUEST_URI']);
                } else {
                    html::errors(self::$errors);
                }
            } else {
                html::errors(self::$errors);
            }
        }
    }
    
    /**
     * gets a default image. If no image we return a random
     * @param int $gallery_id
     * @return array $row image row
     */
    public static function getDefaultImage ($gallery_id) {
        $db = new db();
        $row = $db->selectOne(
                'gallery_file', 
                null,
                array('gallery_id' => $gallery_id, 'default' => 1));
        
        if (!empty($row)) {
            return $row;
        }
        
        // select random
        $query = "SELECT * FROM `gallery_file` WHERE gallery_id = ";
        $query.= db::$dbh->quote($gallery_id) . ' ';
        $query.= " LIMIT 1";       
        $row = $db->selectQuery($query);
        
        if (empty($row)) { 
            return array();
        } 
        return $row[0];               
    }
    
    /**
     * method for updating a image's details, filename  title, description. 
     * @param int $id image id
     * @return boolean $res true on success else false 
     */
    public function updateImageDetails ($id) {
        $values = db::prepareToPostArray(array('file_name', 'title', 'description'));
        $values = html::specialDecode($values);
        $row = $this->getSingleImage($id);
        
        $db = new db();
        $db->begin();
        if ( file::getFilename($row['file_name']) != $values['file_name']) {
            // rename file names
            $values['file_name'] = strings::sanitizeUrlRigid($values['file_name']);
            $values['file_name'] = str_replace(' ', '-', $values['file_name']);
            if (empty($values['file_name'])){
                $values['file_name'] = $row['file_name'];
            } else {
                $ext = file::getExtension($row['file_name']);
                $values['file_name'].= "." . $ext;
                $res = $this->renameImages($row, $values);
                if (!$res) {
                    self::$errors[] = lang::translate('Could not rename file. File name already exists');
                    $db->rollback();
                    return false;
                } 
            }
        } else {
            $values['file_name'] = $row['file_name'];
        }
        
        $db->update('gallery_file', $values, $id);
        return $db->commit();
    }
    
    /**
     * renmae images
     * @param type $row
     * @param type $values
     * @return boolean
     */
    public static function renameImages ($row, $values) {
        $path = self::getGalleryPath($row);
        $newname = $oldname = null;
        $sizes = array ('', 'thumb', 'med', 'full', 'small') ;
        foreach ($sizes as $size) {
            if (empty($size)) {
                $oldname = $path . "/" . rawurldecode($row['file_name']);
                $newname = $path . "/" . rawurldecode($values['file_name']);
                if (file_exists($newname)) return false;
                log::error($oldname);
                log::error($newname);
                
            } else {
                $oldname = $path . "/$size-" . rawurldecode($row[file_name]);
                $newname = $path . "/$size-" . rawurldecode($values[file_name]);
                log::error($newname);
            }
            $res = rename($oldname, $newname);
            if (!$res) log::error ('Error: Could not rename image in gallery::renameImages');
        }
        return true;
    }
    
    /**
     * gets a url path to gallery
     * @param array $row gallery row
     * @return string $url url to gallery
     */
    public static function getGalleryPath ($row) {
        $domain = conf::getDomain();
        return conf::pathHtdocs() . "/files/$domain/gallery/$row[gallery_id]";
    }
    
    /**
     * method for getting a random image url based on $_SESSION
     * if user is not in session a new random image will be
     * given every time. Else the a new image will be given every
     * $shift_num time
     * 
     * can be used for getting e.g. a background image. 
     * @param  int $shift_num when to shift the image after shift_num views. 
     * @return string $image_url a random image url 
     */
    public static function getShiftingImageURL ($shift_num = 7) {

        if (session::isInSession()){
            if (isset($_SESSION['gallery_count'])){
                (int)$_SESSION['gallery_count']++;
            } else {
                $_SESSION['gallery_count'] = 0;
                $_SESSION['gallery_image'] = gallery::getRandomImageURL();
            }
            
            if ($_SESSION['gallery_count'] >= $shift_num){
                $_SESSION['gallery_count'] = 0;
                $_SESSION['gallery_image'] = gallery::getRandomImageURL();
            } else {
               // $_SESSION['gallery_image'] = gallery::getRandomImageURL();
            }

        } else {
            $_SESSION['gallery_count'] = 0;
            $_SESSION['gallery_image'] = gallery::getRandomImageURL();
        }
        return $_SESSION['gallery_image'];
    }
}


