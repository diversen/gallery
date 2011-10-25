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
include_once "mySqlForm.php";

include_template_inc('fancybox');
fancybox_include();

/**
 * class content files is used for keeping track of file changes
 * in db. Uses object fileUpload
 *
 * @package gallery
 */
class gallery extends db{
    // {{{ vars uploadFile (object) errors (array) status (array) id (int) fileId (int) uploadDir (string) path (string)
    /** @var    object   holding an uploadFile object */
    public $uploadFile;
    /** @var    array   array holding errors */
    public $errors = null;
    /** @var    array   array holding   status messages */
    public $status = null;
    /** @var    int     gallery id */
    public static $id;
    /** @var    int     holding file id */
    public $fileId;
    /** @var    string  holding sever path to upload dir e.g: /files/content */
    public $uploadDir;
    /** @var    string  holding path to path where script will be run */
    public $path;
    // }}}
    // {{{ function __construct()
    /**
     *
     * constructor sets init vars
     */
    function __construct($gallery_frag = 2, $file_frag = 3){
        
        $createDir = true;
        
        $uri = URI::getInstance();
        $this->id = $uri->fragment($gallery_frag);
        $this->fileId = $uri->fragment($file_frag);
        
        $domain = get_domain ();
        if ($domain == 'default') {
            $this->uploadDir = "/files/default/gallery";
        } else {
            $this->uploadDir = "/files/$domain/gallery";
        }
        
        $this->uploadDir = $this->uploadDir . '/' . $this->id;
        $this->path = '/gallery';
        $options = array('upload_dir' => $this->uploadDir);
        $this->uploadFile = new upload($options, $createDir);
        if (isset($this->uploadFile->errors)){
            $this->errors = $this->uploadFile->errors;
        }
        
    }
    // }}}
    // {{{ function validate insert
    /**
     * method for validating a post before insert
     */
    public function validateInsert(){
        if (empty($_FILES['filename']['name'])){
            $this->errors[] = lang::translate('No file specified');
            return;
        }
        $type = substr($_FILES['filename']['type'], 0,5);
        if ($type != 'image'){
            $this->errors[] = lang::translate('Does not seem to be a image type of file');
            return;
        }

        // Example
        $error_message = file_upload_error_message($_FILES['filename']['error']);
        if ($_FILES['filename']['error'] != 0) {
            $this->errors[] = file_upload_error_message($error_message);
        }

        if ($_FILES['filename']['size'] > return_bytes(ini_get('post_max_size'))){
            $this->errors[] = lang::translate('File specified is to big') . ' ' .
                              lang::transate('Maximum size is: ') . ini_get('post_max_size');
        }
        $res = $this->uploadFile->moveFile('filename');
        if (!$res){
            $this->errors[] = lang::translate('File seems to already exist. Only unique filenames allowed');
        }

    }
    // }}}
    // {{{ function deleteFile($id) $id = fileId
    /**
     * method for delting a file
     *
     * @param   int   id of file
     */
    public function deleteFile($id){
        // TODO: transaction.
        $row = $this->selectOne('gallery_file', 'id', $id);
        $res = $this->delete('gallery_file', 'id', $id);
        if (!$res) {
            $this->errors[] = lang::translate ("could not delete file from id");
        }
        $filename = _COS_PATH . '/htdocs' . $this->uploadDir . '/' . $row['file_name'];
        $res = $this->uploadFile->unlinkFile($filename);
        if (!$res) {
            $this->errors[] = lang::translate("Could not delete file from file system");
        }
        $filename = _COS_PATH . '/htdocs' . $this->uploadDir . '/thumb-' . $row['file_name'];
        $res = $this->uploadFile->unlinkFile($filename);
        if (!$res) {
            $this->errors[] = lang::translate("Could not delete thumb from file system");
        }
        return true;
    }
    // }}}
    // {{{ method insertFile() inserts a file in db and put file on file system
    /**
     * method for inserting a file
     *
     * @return  boolean true on success and false on failure
     */
    public function insertFile(){
        
        $values = db::prepareToPost();
        $values['gallery_id'] = $this->id;
        $values['file_name'] = $_FILES['filename']['name'];
        
        $filename = _COS_PATH . '/htdocs' . $this->uploadDir . '/' . $values['file_name'];

        // scale thumb
        $thumb = _COS_PATH . '/htdocs' . $this->uploadDir . '/thumb-' . $values['file_name'];
        $this->scaleImage($filename, $thumb, get_module_ini('gallery_thumb_size'));
        // scales uploaded image down
        $normal = _COS_PATH . '/htdocs' . $this->uploadDir . '/' . $values['file_name'];
        $this->scaleImage($filename, $normal, get_module_ini('gallery_image_size'));

        $res = $this->insert('gallery_file', $values);
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
    private function scaleImage ($image, $thumb, $length){
        require_once 'Image/Transform.php';

        //create transform driver object
        $it = Image_Transform::factory('GD');
        if (PEAR::isError($it)) {
            die($it->getMessage());
        }

        //load the original file
        $ret = $it->load($image);
        if (PEAR::isError($ret)) {
            die($ret->getMessage());
        }

        //scale it to 150px
        $ret = $it->scaleByLength($length);
        if (PEAR::isError($ret)) {
            die($ret->getMessage());
        }

        //save it into a different file
        $ret = $it->save($thumb);
        if (PEAR::isError($ret)) {
            die($ret->getMessage());
        }
    }
    // }}}
    // {{{ method getAllFiles() get all files as rows from db table file
    /**
     * method for getting all files connected to a parent id
     *
     * @return      array   rows of a select query
     */
    public function getAllFiles($id = null){
        if (!$id) { 
            $id = $this->id;
        }
        $rows = $this->select('gallery_file', 'gallery_id', $id);
        return $rows;
    }

    /**
     * method for getting all connected to a gallery
     *
     * @return      array   rows of a select query
     */
    public function getAllFileInfo($gallery_id ){
        $rows = $this->select('gallery_file', 'gallery_id', $gallery_id);
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
                $str.="&nbsp;<a href=\"$this->path/delete/$this->id/$val[id]\">" . lang::translate('Delete') . "</a>";
                //$str.="&nbsp;<a href=\"$this->path/update/$this->id/$val[id]\">Update</a>";
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
     * @param   array   array ('no_admin' => 1, 'gallery_id => 1);
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
            $path = "/gallery/view/$this->id/$val[id]";
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
            $str.= '<form action="/gallery/delete/' . $this->id . '/' . $this->fileId;
            $str.= '" method="post" name="test" enctype="1" >';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::translate('Delete') . '" />';
            $str.= '</form>';
            $str.= '</td>';
        }
        if (session::isAdmin()){

            $str.= '<td>';
            $str.= '<form action="/gallery/view/' . $this->id;
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
        $gallery_id = $uri->fragment(2);

        $search = array('gallery_id' => $gallery_id);
        $num_rows = $this->getNumRows('gallery_file', $search);

        if (!$num_rows){
            $query = "SELECT * FROM `gallery_file` ORDER BY RAND() LIMIT 1 ";
        } else {
            $query = "SELECT * FROM `gallery_file` WHERE gallery_id = ";
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
        $where = array('gallery_id' => $this->id);
        $num_rows = $this->getNumRows('gallery_file', $where);
        return $num_rows;
    }
    // }}}
    // {{{ method updateFile($values, $id) updating q file table in db with new file info.

    /**
     * method for updating a file in db table
     * 
     * @return  boolean  true on success and false on failure
     */
    public function updateFile ($values, $id){
        try {
            db::prepareToPost();
            $res = $this->update('gallery_file', $values, $id);
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
        return $res;
    }
    // }}}
    /**
     * view file for gallery
     *
     * @package    gallery
     */
    public static function viewGallery ($gallery_frag = 2, $file_frag = 3 ) {
        $galImg = new gallery($gallery_frag, $file_frag);

        template::setTitle(lang::translate('View gallery'));
        if (isset($_POST['submit'])){

            if (!session::checkAccessControl('gallery_allow_edit')){
                return;
            }
            
            if (isset($_POST['file_id'])) {
                $galImg->deleteFile($_POST['file_id']);
                session::setActionMessage(lang::translate('gallery_file_deleted'));
                header("Location: $_SERVER[REQUEST_URI]");
                exit;
            }
            
            $galImg->validateInsert();
            if (!isset($galImg->errors)){
                $res = $galImg->insertFile('filename');
                if ($res){
                    session::setActionMessage(lang::translate('gallery_file_added'));
                    header("Location: $_SERVER[REQUEST_URI]");
                }
            } else {
                view_form_errors($galImg->errors);
            }
        }

        $options = array ('gallery_id' => $galImg->id);
        $files = $galImg->getAllFiles();
        $info = $galImg->getAllFileInfo($galImg->id);
        echo $galImg->getThumbTableHTML($info, $options);
    }
}
// }}}

// {{{ function view_image_form($method, $id = null, $values = array())
/**
 * function for creating crud form for files
 *
 * @param string create, delete or update
 * @param int if delete or update set this to id
 */
function view_image_form($method, $id = null, $values = array()){

    $my = new mySqlForm('gallery_file', array('title', 'file'), $values);
    $my->setLabels(array(
        'title' => lang::translate('Title'),
        'file' => lang::translate('File to upload'))
    );
    if (isset($id)){
        $my->setMethod($method, $id);
        $caption = lang::translate('Edit File');
    } else {
        $my->setMethod($method);
        $caption = lang::translate('Add File');
    }
    $my->createForm('', 'post',  'test', '',
                       true,  $caption);
}
// }}}
