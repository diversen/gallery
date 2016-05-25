<?php

namespace modules\gallery;

use diversen\conf;
use diversen\db;
use diversen\db\q;
use diversen\file;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\log;
use diversen\moduleloader;
use diversen\session;
use diversen\strings;
use diversen\template;
use diversen\upload;
use diversen\uri;
use diversen\imageRotate;
use Exception;
use Gregwar\Image\Image;
use modules\gallery\admin\module as admin;
use modules\gallery\inline\module as inline;


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
    public $errors = null;
    
   /**
     * var holding gallery id
     * @var array $errors
     */
    public $galleryId = null;
    
    /**
     *
     * @var string $uploadDir holding the dir where files are uploaded to
     */
    public $uploadDir;
    
    /**
     * 
     * @var array   $options holding speciel options 
     */
    public  $options = null;

    /**
     * constructor. Sets gallery id and file id from url fragements. 
     * also sets uploaddir based om gallery fragement
     * @param type $gallery_frag
     * @param type $file_frag 
     */
    public function __construct(){
        
        $domain = conf::getDomain ();
        if (!$domain) { 
            $domain = 'default';
        }

        
        $this->galleryId = uri::fragment(2);

        $this->uploadDir = 
                conf::pathHtdocs() . 
                "/files/$domain/gallery/" . $this->galleryId;
        
        $options = array('upload_dir' => $this->uploadDir);
        $options['allow_mime'] = array (
            'image/gif', 
            'image/jpeg', 
            'image/pjpeg', 
            'image/png'
        );
        $this->options = $options;
    }
    
    public function deleteAction() {
        if (!session::checkAccessFromModuleIni('gallery_allow_edit')) {
            return;
        }

        template::setTitle(lang::translate('Delete image'));

        if (isset($_POST['submit'])) {
            if (!isset($this->errors)) {
                $res = $this->deleteFile($fileObj->fileId);
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
        
        $this->postActions();
        
        $id = uri::fragment(2);        
        $view = new inline();

        $row = q::select('gallery')->filter('id =', $id)->fetchSingle();
        $view->displayGallery($row, array('admin' => 1));

        $a = new admin();
        echo $a->formUpload();
        return;
        
    }
    
    /**
     * /gallery/index action
     */
    public function indexAction() {

        moduleloader::includemodule("gallery/admin");
        template::setTitle(lang::translate('List galleries'));
        $view = new \modules\gallery\inline\module();
        $view->displayAllGalleries(array());
    }

    /**
     * method for delting a file
     *
     * @param   int   galleryId of file
     */
    public function deleteFile($id){
        
        $upload = new upload($this->options, true); 
        
        db::$dbh->beginTransaction();
        $db = new db();
        
        $domain = conf::getDomain();
        $row = $db->selectOne('gallery_file', 'id', $id);
        $path = $this->getGalleryPath($row);
        $filename = $path . "/" . $row['file_name'];
        
        $res = unlink($filename);

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
    
    public function setDefaultImage ($file_id) {
        $db = new db();
        $db->update(
                'gallery_file', 
                array('default' => 0), 
                array('gallery_id' => $this->galleryId));
    
        $db->update('gallery_file', array('default' => 1), array('id' => $file_id));
    }
   
    /**
     * Get uploaded files as a organized array
     * @return array $ary
     */
    private function getUploadedFilesArray () {
                
        $ary = array ();
        foreach ($_FILES['files']['name'] as $key => $name) {
            $ary[$key]['name'] = $name;
        }
        foreach ($_FILES['files']['type'] as $key => $type) {
            $ary[$key]['type'] = $type;
        }
        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            $ary[$key]['tmp_name'] = $tmp_name;
        }
        foreach ($_FILES['files']['error'] as $key => $error) {
            $ary[$key]['error'] = $error;
        }
        foreach ($_FILES['files']['size'] as $key => $size) {
            $ary[$key]['size'] = $size;
        }
        return $ary;
    }

    /**
     * 
     * @param type $filesInsert uploaded files
     */
    public function insertFiles () {
        
        $id = uri::fragment(2);  
        $files = $this->getUploadedFilesArray();

        foreach($files as $file) {
            $res = $this->insertFile($file, $id);
            if (!$res) {
                return false;
            }
        }
        return true;
         
    }
    
    /**
     * method for inserting a file
     *
     * @return  boolean true on success and false on failure
     */
    public function insertFile($file, $id){


        upload::setOptions($this->options);        
        $res = upload::moveFile($file);
        if (!$res) {
            $this->errors = upload::$errors;            
            return false;
        }
        
        $savename = upload::$saveBasename;

        $values = array();
        $values['gallery_id'] = $id;
        $values['file_name'] = $savename;
              
        $filename = $this->uploadDir . '/' . $savename;
        
        $rotate = new imageRotate();
        $rotate->fixOrientation($filename);

        // scale a thumb
        $thumb = $this->uploadDir . '/thumb-' . $savename;
        $res = $this->scaleImage($filename, $thumb, conf::getModuleIni('gallery_thumb_size'));
        
        if (!$res) { 
            return false;
        }
        
        $med = $this->uploadDir . '/med-' . $savename;
        $res = $this->scaleImage($filename, $med, conf::getModuleIni('gallery_med_size'));
        
        if (!$res) { 
            return false;
        }
        
        // scale large
        $normal = $this->uploadDir . '/small-' . $savename;
        $res = $this->scaleImage($filename, $normal, conf::getModuleIni('gallery_small_size'));
        
        if (!$res) { 
            return false;
        }
        
        // scale large
        $normal = $this->uploadDir . '/full-' . $savename;
        $res = $this->scaleImage($filename, $normal, conf::getModuleIni('gallery_image_size'));
        
        if (!$res) { 
            return false;
        }
        
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
    public function scaleImage ($image, $thumb, $x){

        try {
            Image::open($image)->cropResize($x)->save($thumb);
        } catch (Exception $e) {
            self::$errors[] = $e->getMessage();
            return false;
        }
        return true;

    }

    /**
     * method for getting all files connected to a parent galleryId
     *
     * @return      array   rows of a select query
     */
    public function getAllFiles(){
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
    
    /**
     * Get a single image from database
     * @param type $id
     * @return type
     */
    public function getImageFromId($id){
        return q::select('gallery_file')->filter('id =', $id)->fetchSingle();
    }
    
    
    /**
     * Get gallery id from image id
     * @param type $id
     * @return type
     */
    public function getGalleryIdFromImageId($id) {
        $image = $this->getImageFromId($id);
        if (!empty($image)) {
            return $image['gallery_id'];
        }
    }

    /**
     * Get image rows with 'src' and 'width' set in in row
     * @param int $id image id
     * @param type $size size of the image
     * @return array $row
     */
    public function getImageDbAndSrc ($id, $size = '') {
        if (!empty($size)) {
            $size = "$size-";
        }
        
        $row = $this->getImageFromId($id);
        if (!$row) { 
            return false;
        }
        
        $domain = conf::getDomain();
        $row['src'] = "/files/$domain/gallery/$row[gallery_id]/" . $size . "$row[file_name]";
        $row['width'] = conf::getModuleIni('gallery_image_size');
        return $row;
    }
    
    public function getImageSrcDiv ($id, $size = '') {
        $org = $this->getImageDbAndSrc($id);
        
        $row = $this->getImageDbAndSrc($id, 'full');
        
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
     * Get all images in a gallery
     * @return array $rows
     */
    public function getImagesFromGalleryId($gallery_id) { 
        return q::select('gallery_file')->filter('gallery_id =', $gallery_id)->fetch();
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
        $res = $this->prefixKey('', $exif);
        return $res;
    }
    
    /**
     * Flatten array and add prefix
     * @param string $prefix
     * @param array $array
     * @return array $array
     */
    private function prefixKey($prefix, $array) {
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->prefixKey($prefix . $key . '.', $value));
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    /**
     * Get exif HTML as string
     * @param array $exif
     * @return string $html
     */
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

    /**
     * Performs action when posting
     * @param  int $gallery_frag
     * @param int $file_frag
     * @return boolean $res true on success and false on failure
     */
    public function postActions () {
        
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
            
            if (!isset($this->errors)){
                $res = $this->insertFiles();
                if ($res){
                    session::setActionMessage(lang::translate('File(s) added'));
                    http::locationHeader($_SERVER['REQUEST_URI']);
                } else {
                    echo html::getErrors($this->errors);
                }
            } 
        }
    }
    
    /**
     * Method for updating a image's details, filename  title, description. 
     * @param int $id image id
     * @return boolean $res true on success else false 
     */
    public function updateImage ($id) {
        $values = db::prepareToPostArray(array('file_name', 'title', 'description'));
        $values = html::specialDecode($values);
        $row = $this->getImageFromId($id);
        
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
                $this->renameImages($row, $values);
            }
        } else {
            $values['file_name'] = $row['file_name'];
        }
        
        $db->update('gallery_file', $values, $id);
        return $db->commit();
    }
    
    /**
     * Rename image file names
     * @param array $row
     * @param array $values
     * @return boolean
     */
    public function renameImages ($row, $values) {
        $path = $this->getGalleryPath($row);
        $newname = $oldname = null;
        $sizes = array ('', 'thumb', 'med', 'full', 'small') ;
        foreach ($sizes as $size) {
            if (empty($size)) {
                $oldname = $path . "/" . rawurldecode($row['file_name']);
                $newname = $path . "/" . rawurldecode($values['file_name']);
                if (file_exists($newname)) {
                    return false;
                } 
            } else {
                $oldname = $path . "/$size-" . rawurldecode($row['file_name']);
                $newname = $path . "/$size-" . rawurldecode($values['file_name']);
                log::error($newname);
            }
            $res = rename($oldname, $newname);
            if (!$res) { 
                log::error ('Error: Could not rename image in gallery::renameImages');
            }
        }
        return true;
    }
    
    /**
     * gets a url path to gallery
     * @param array $row gallery row
     * @return string $url url to gallery
     */
    public function getGalleryPath ($row) {
        $domain = conf::getDomain();
        return conf::pathHtdocs() . "/files/$domain/gallery/$row[gallery_id]";
    }
}


