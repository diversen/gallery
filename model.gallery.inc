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
        
        $domain = get_domain ();
        if (!$domain) $domain = 'default';
        self::$uploadDir = _COS_PATH . "/htdocs/files/$domain/gallery/" . self::$galleryId;
        $options = array('upload_dir' => self::$uploadDir);
        self::$options = $options;      
    }

    public function validateImageUpload(){
        /*
        if (empty($_FILES['filename']['name'])){
            $this->errors[] = lang::translate('No file specified');
            return;
        }*/
        
        // se settings/logo

        // Example
        /*
        $error_message = file_upload_error_message($_FILES['filename']['error']);
        if ($_FILES['filename']['error'] != 0) {
            $this->errors[] = file_upload_error_message($error_message);
        }

        if ($_FILES['filename']['size'] > return_bytes(ini_get('post_max_size'))){
            $this->errors[] = lang::translate('File specified is to big') . ' ' .
                              lang::transate('Maximum size is: ') . ini_get('post_max_size');
        }*/
        
        
        
        
        
        //if (!$res){
        //    $this->errors[] = lang::translate('File seems to already exist. Only unique filenames allowed');
        // }

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
        
        $res = $db->delete('gallery_file', 'id', $id);
        return db::$dbh->commit();
    }

    /**
     * method for inserting a file
     *
     * @return  boolean true on success and false on failure
     */
    public function insertFile(){
        
        $upload = new upload(self::$options); 
        $res = $upload->moveFile('filename');
        if (!$res) {
            gallery::$errors = upload::$errors;
            return false;
        }
        
        
        $values = db::prepareToPost();
        $values['gallery_id'] = self::$galleryId;
        $values['file_name'] = $_FILES['filename']['name'];
              
        $filename = self::$uploadDir . '/' . $values['file_name'];

        $thumb = self::$uploadDir . '/thumb-' . $values['file_name'];
        $res = $this->scaleImage($filename, $thumb, get_module_ini('gallery_thumb_size'));
        
        if (!$res) return false;
        
        $normal = self::$uploadDir . '/' . $values['file_name'];
        $res = $this->scaleImage($filename, $normal, get_module_ini('gallery_image_size'));
        
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
     * @param   array    array from a db query $rows
     * @param   array    opt unused so far
     * @return  string   html displaying files connect to article.
     */
    /*
    public function getFilesHTML($rows, $opt = null){
        $str = '';
        foreach ($rows as $key => $val) {
            $file = $this->uploadDir;
            $file.= '/' . $val['file_name'];
            $str.= "<a href=\"$file\">$val[file_name]<a>";
            if (session::isAdmin()){
                $str.="&nbsp;<a href=\"$this->path/delete/$this->galleryId/$val[galleryId]\">" . lang::translate('Delete') . "</a>";
                //$str.="&nbsp;<a href=\"$this->path/update/$this->galleryId/$val[galleryId]\">Update</a>";
            }
            $str.= "<br />";
        }
        return $str;
    }*/
    // }}}
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
        $vars['rows'] = $this->getAllFileInfo($options['gallery_id']);
        
        if (isset($options['no_admin'])) {
            $vars['no_admin'] = 1;
        }
        
       // $vars['rows'] = $rows;
        $vars['gallery_id'] = $options['gallery_id'];
        
        $display_module = get_module_ini('gallery_display_module');
        if ($display_module) {            
            include_module($display_module);
            $module = moduleLoader::modulePathToClassName($display_module);           
            return $module::displayThumbs($vars);
            //return;
        }
        return;
        // DISPLAY FOR RANDOM BG-image
        // XXXXXX
        $num_rows = $this->getNumImagesGallery();
        if (empty($this->fileId)){
            $query = "SELECT * FROM `gallery_file` ORDER BY RAND() LIMIT 1";
            $show = $this->selectQuery($query);
            if (!empty($show)){
                $show = $show[0];
            }
        } else {
            $show = $this->selectOne('gallery_file', 'id', $this->fileId);
        }

        $str = '';
        $str.="<div id=\"gallery_pager\">";
        $str.="<table><tr>\n";
        foreach ($rows as $key => $val) {
            $thumb_src = $this->uploadDir . "/thumb-" . $val['file_name'];          
            $path = "/gallery/view/$this->galleryId/$val[id]";
            $str.= "<td><a href=\"$path\">";
            $str.= $this->getImageTag($thumb_src) . "</a></td>\n";
        }
        $str.= $this->getAdminCells();
        $str.="</tr></table>\n";
        $str.="</div>\n";
        return $str;
    }
    // }}}
    
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
    public function getRandomImageURL($thumb = false){
        $uri= URI::getInstance();
        $module = $uri->fragment(0);
        $gallery_galleryId = $uri->fragment(2);

        $search = array('gallery_id' => $gallery_id);
        $num_rows = $this->getNumRows('gallery_file', $search);

        if (!$num_rows){
            $query = "SELECT * FROM `gallery_file` ORDER BY RAND() LIMIT 1 ";
        } else {
            $query = "SELECT * FROM `gallery_file` WHERE gallery_galleryId = ";
            $query.= self::$dbh->quote($gallery_id) . ' ';
            $query.= " ORDER BY RAND() LIMIT 1";
        }
        
        $show = $this->selectQuery($query);
        if (!empty($show)){
            $show = $show[0];
            $image_url = "/files/gallery/" .
                         $show['gallery_id'] . '/' .
                         $show['file_name'];
            if ($thumb){
                $image_url = "/files/gallery/" .
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
        $where = array('gallery_id' => $this->galleryId);
        $num_rows = $this->getNumRows('gallery_file', $where);
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

    public static function viewGallery ($gallery_frag = 2, $file_frag = 3 ) {
        $gal = new gallery($gallery_frag, $file_frag);

        template::setTitle(lang::translate('View gallery'));
        if (isset($_POST['submit'])){

            if (!session::checkAccessControl('gallery_allow_edit')){
                return;
            }
            
            if (isset($_POST['file_id'])) {
                $gal->deleteFile($_POST['file_id']);
                session::setActionMessage(lang::translate('gallery_file_deleted'));
                header("Location: $_SERVER[REQUEST_URI]");
                exit;
            }
            
            $gal->validateImageUpload();
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

        $options = array ('gallery_id' => self::$galleryId);
        $files = $gal->getAllFiles();
        $info = $gal->getAllFileInfo(self::$galleryId);
        echo $gal->getThumbTableHTML($info, $options);
    }
}
// }}}
