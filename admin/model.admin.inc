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
        $values = array('title'=> $_POST['title']);
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

    public function displayAllGallery(){
        $galleries = $this->getAllGallery();
        foreach ($galleries as $key => $val){
            $val['title'] = $val['title'];
            $link = html::createLink("/gallery/view/$val[id]", $val['title']);
            echo "<hr id=\"thick_hr\"/>\n";
            echo "<div id=\"headfont\">\n";
            echo $link;
            echo "</div>";
            
            echo "<div id=\"smallfont\">";
            if (session::isAdmin()){
                echo html::createLink ("/gallery/view/$val[id]", lang::translate('gallery_edit_files')) . " \n";
                echo MENU_SUB_SEPARATOR_SEC;
                echo create_link ("/gallery/admin/edit/$val[id]", lang::translate('gallery_edit_name')) . " \n";
            } 
            echo "</div>\n";
            
        }
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
    html::submit('submit', lang::system('system_submit_add'));
    echo html::getStr();
    return;
    
}
