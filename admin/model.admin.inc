<?php

/**
 * file containing administration class for gallery
 *
 * @package     gallery
 */
include_model ('gallery');

/**
 * class for working with content categories in gallery
 *
 * @package     gallery
 */
class galleryAdmin extends gallery {


    /**
     *
     * @var object  holding URI instance
     */
    public $uri;

    /**
     * Sets galleryId
     */
    function __construct(){
        self::$galleryId = uri::getInstance()->fragment(3);

    }

    /**
     * Validate user input
     */
    function validate(){

        if (empty($_POST['submit'])){
            return;
        }

        if (strlen($_POST['title']) < 3){
            $this->errors['title'] = lang::translate('Gallery title should be at least 3 chars long');
        }
    }

    /**
     * Santize submission
     */
    public static function sanitize(){        
        $_POST = html::specialEncode($_POST);
    }

    /**
     * 
     * @return type 
     */
    public static function deleteGallery(){
        $gllery = self::getGallery();
        $gallery_id = $gallery['gallery_id'];
        $db = new db();
        $res = $db->delete('gallery', 'id',  self::$galleryId);
        return $res;
    }

    /**
     * method for getting a category
     *
     * @return array  containing the selected category
     */
    public static function getGallery($id){
        $db = new db();
        $row = $db->selectOne('gallery', 'id', self::$galleryId);
        $row = html::entitiesEncode($row);
        return $row;
    }
    
    

    /**
     * method for creating a category
     *
     * @return int insert id
     */
    public static function createGallery (){
        $values = array('title'=> $_POST['title']);
        $db = new db(); 
        $res = $db->insert('gallery', $values);
        return $res;
    }

    /**
     * method for updating a category
     *
     * @return int affected rows
     */
    public static function updateGallery (){
        $db = new db();
        //$values = array('title'=> $_POST['title']);
        
        $values = db::prepareToPost();
        $values = html::entitiesDecode($values);
        
        $res = $db->update('gallery', $values, self::$galleryId);
        return $res;

    }


    /**
     * method for getting all categories
     *
     * @return array array categories
     */
    public static function getAllGallery (){
        $db = new db();
        $rows = $db->selectAll('gallery');
        return $rows;

    }

    public function displayAllGallery($from = 0, $limit = 10){
        $display_module = config::getModuleIni('gallery_display_module');         
        include_module($display_module);
        $module = moduleLoader::modulePathToClassName($display_module);           
        $module::displayAll($from, $limit);
    }
}

/**
 * function for creating crud form for files
 *
 * @param string create, delete or update
 * @param int if delete or update set this to id
 */
function view_gallery_form($method, $id = null, $values = array()){
    
    html::formStart('gallery_form');
    
    if (isset($_POST['submit'])) {
        $_POST['submit'] = html::entitiesEncode($values);
    }
     
    if (isset($id)) {
        $gallery = galleryAdmin::getGallery($id);
        
        html::init($gallery, 'submit');
    }

    if (isset($id)) {
        $legend = lang::translate('Edit Gallery');    
    } else {
        $legend = lang::translate('Add Gallery');
        
    }
    
    html::legend($legend);
    html::label('title', lang::system('system_form_label_title'));
    html::text('title');
    html::label('description', lang::system('system_form_label_abstract'));
    html::textareaSmall('description');
    html::label('file_path', lang::translate('gallery_form_label_path'));
    html::text('file_path');
    html::submit('submit', lang::system('system_submit_add'));
    echo html::getStr();
    return;
    
}
