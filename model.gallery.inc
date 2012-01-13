<?php

/**
 * File holding basic operations for gallery module
 *
 * @package gallery
 */

/**
 * @ignore
 */
include_once "upload.php";


/**
 * Class for creating galleries
 *
 * @package gallery
 */
class gallery {

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

        $uri = URI::getInstance();
        self::$galleryId = $uri->fragment($gallery_frag);
        self::$fileId = $uri->fragment($file_frag);
        
        $domain = config::getDomain ();
        if (!$domain) $domain = 'default';
        self::$uploadDir = _COS_PATH . "/htdocs/files/$domain/gallery/" . self::$galleryId;
        $options = array('upload_dir' => self::$uploadDir);
        $options['allow_mime'] = array (
            'image/gif', 
            'image/jpeg', 
            'image/pjpeg', 
            'image/png'
        );
        self::$options = $options;      
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
        
        $row = $db->selectOne('gallery_file', 'id', $id);
        $filename = self::$uploadDir . '/' . $row['file_name'];
        $res = $upload->unlinkFile($filename);

        $filename = self::$uploadDir . '/thumb-' . $row['file_name'];
        $res = $upload->unlinkFile($filename);
        
        $filename = self::$uploadDir . '/med-' . $row['file_name'];
        $res = $upload->unlinkFile($filename);
        
        $filename = self::$uploadDir . '/full-' . $row['file_name'];
        $res = $upload->unlinkFile($filename);
        
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
        
        //$upload = new upload(self::$options); 
        upload::setOptions(self::$options);
        $res = upload::moveFile('filename');
        if (!$res) {
            self::$errors = upload::$errors;            
            return false;
        }
        
        $savename = upload::$saveBasename;

        // $values for db. 
        $values = array();
        $values['gallery_id'] = self::$galleryId;
        $values['file_name'] = $savename;
              
        $filename = self::$uploadDir . '/' . $savename;

        // scale a thumb
        $thumb = self::$uploadDir . '/thumb-' . $savename;
        $res = self::scaleImage($filename, $thumb, config::getModuleIni('gallery_thumb_size'));
        
        if (!$res) return false;
        
        $med = self::$uploadDir . '/med-' . $savename;
        $res = self::scaleImage($filename, $med, config::getModuleIni('gallery_med_size'));
        
        if (!$res) return false;
        
        // scale large
        $normal = self::$uploadDir . '/small-' . $savename;
        $res = self::scaleImage($filename, $normal, config::getModuleIni('gallery_small_size'));
        
        if (!$res) return false;
        
        // scale large
        $normal = self::$uploadDir . '/full-' . $savename;
        $res = self::scaleImage($filename, $normal, config::getModuleIni('gallery_image_size'));
        
        
        if (!$res) return false;
        
        $db = new db();
        $res = $db->insert('gallery_file', $values);
        return $res;

    }
    // }}}
    // {{{ scaleImage ($image, $thumb, $length)
    /**
     * 
     * @param  string    the image file to scale
     * @param  string    the filename of the thumb
     * @param  int       the length of the image   
     */
    public static function scaleImage ($image, $thumb, $x){
        include_once "imagescale.php";
        
        $scale = new imagescale();
        $res = $scale->byX($image, $thumb, $x);
        
        if (!$res) {
            self::$errors = imagescale::$errors;
            return false;
        }

        return true;
    }
    // }}}
    // {{{ method getAllFiles() get all files as rows from db table file
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

    /**
     * method for getting all connected to a gallery
     *
     * @return      array   rows of a select query
     */
    public function getAllFileInfo($gallery_id) { 
        $db = new db();
        $rows = $db->select('gallery_file', 'gallery_id', $gallery_id);
        return $rows;
    }
    

    // {{{ method getFilesHTML return string with all files
    /**
     * method for getting files
     * 
     * @param   array   array from a db query $rows
     * @param   array   array ('no_admin' => 1, 'gallery_galleryId => 1);
     * 
     * @return  string  html displaying files connect to article.
     */
    public function getThumbTableHTML($rows, $options = array()){
        $str = '';
        //$num_rows = $this->getNumImagesGallery();
        $vars = array();
        $vars['rows'] = $rows; //$this->getAllFileInfo($options['gallery_id']);
        $vars['options'] = $options;
        // $vars['gallery_id'] = $options['gallery_id'];
        
        $display_module = config::getModuleIni('gallery_display_module');
        if ($display_module) {            
            moduleLoader::includeModule($display_module);
            $module = moduleLoader::modulePathToClassName($display_module);           
            return $module::displayGallery($vars);
            //return;
        }
        return;

    }
    // }}}
    public static function getBGImage () {
                // DISPLAY FOR RANDOM BG-image
        // XXXXXX
        $uri = uri::getInstance();
        echo $image_id = $uri->fragment(3);
        echo $gallery_id = $uri->fragment(2);
        $db = new db();
        //$gallery = new gallery();
        
        //$num_rows = self::ge
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
        $domain = config::getDomain();
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
    
    // {{{ getAdminCells() 
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
    // }}}
    // {{{ getRandomImageURL()
    /**
     * method for getting a random image url
     *
     */
    public static function getRandomImageURL($thumb = false){
        $uri= URI::getInstance();
        $module = $uri->fragment(0);
        $gallery_id = $uri->fragment(2);

        $db = new db();
        $search = array('gallery_id' => $gallery_id);
        $num_rows = $db->getNumRows('gallery_file', $search);

        if (!$num_rows){
            $query = "SELECT * FROM `gallery_file` ORDER BY RAND() LIMIT 1 ";
        } else {
            $query = "SELECT * FROM `gallery_file` WHERE gallery_id = ";

            $query.=  db::$dbh->quote($gallery_id) . ' ';
           $query.= " ORDER BY RAND() LIMIT 1";
        }
        
        $show = $db->selectQuery($query);

        if (!empty($show)){
            $show = $show[0];
            $domain = config::getDomain();
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
    // }}} 
    // {{{ getImageTag($imageSrc)
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

    // }}}
    // {{{ getNumImagesGallery()
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
    // }}}
    // {{{ method updateFile($values, $galleryId) updating q file table in db with new file info.

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
        
        $domain = config::getDomain();
        $link = "/files/$domain/gallery/$gallery_id/$type-$row[file_name]";
        return $link;
    }

    public static function viewGallery ($gallery_frag = 2, $file_frag = 3 ) {
        $gal = new gallery($gallery_frag, $file_frag);

        template::setTitle(lang::translate('View gallery'));
        if (isset($_POST['submit'])){

            if (!session::checkAccessControl('gallery_allow_edit')){
                return;
            }
            
            if (isset($_POST['file_id']) && $_POST['method'] == 'delete') {
                $gal->deleteFile($_POST['file_id']);
                session::setActionMessage(lang::translate('gallery_file_deleted'));
                header("Location: $_SERVER[REQUEST_URI]");
                exit;
            }
            
            if (isset($_POST['file_id']) && $_POST['method'] == 'default_image') {
                $gal->setDefaultImage($_POST['file_id']);
                session::setActionMessage(lang::translate('gallery_file_is_default'));
                header("Location: $_SERVER[REQUEST_URI]");
                exit;
            }
            
            if (!isset(self::$errors)){
                $res = $gal->insertFile('filename');
                if ($res){
                    session::setActionMessage(lang::translate('gallery_file_added'));
                    header("Location: $_SERVER[REQUEST_URI]");
                    exit;
                } else {
                    view_form_errors(self::$errors);
                }
            } else {
                view_form_errors(self::$errors);
            }
        }

        
        $row = $gal->getDefaultImage(self::$galleryId);
        $options = array ('gallery_id' => self::$galleryId, 'default' => $row);
        $files = $gal->getAllFiles();


        $rows = $gal->getAllFileInfo(self::$galleryId);
        echo $gal->getThumbTableHTML($rows, $options);
    }
    
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
        $query.= " ORDER BY RAND() LIMIT 1";       
        $row = $db->selectQuery($query);
        
        if (empty($row)) { 
            return array();
        } 
        
        return $row[0];               

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
// }}}
