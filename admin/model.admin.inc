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
     * @var array   holding category errors
     */
    public $errors = array();

    /**
     *
     * @var object  holding URI instance
     */
    public $uri;

    /**
     *
     * @var int holding category being worked on
     */
    public static $id;

    /**
     * checks input data
     * sets current category id if any
     *
     * @param   boolean should we sanitize or not
     */
    function __construct($sanitize = true){
        $this->uri = uri::getInstance();
        $this->id = (int)$this->uri->fragment(3);
    }

    /**
     * Validate user input
     *
     * @return void
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
    public function sanitize(){        
        $_POST = html::specialEncode($_POST);
    }

    /**
     * method for delting a category
     *
     * @todo    delete files folder! (cron todo)
     * @return  int always return 1
     */
    public function deleteGallery(){
        //$gallery = $this->getGallery();
        $gllery = self::getGallery();
        $gallery_id = $gallery['gallery_id'];
        $db = new db();
        $res = $db('gallery', 'id',  $this->id);
        return $res;
    }

    /**
     * method for getting a category
     *
     * @return array  containing the selected category
     */
    public static function getGallery($id){
        
        /*
        if (!isset($id)) {
            $id = (int)self::$id;
        }*/
        $db = new db();
        $row = $db->selectOne('gallery', 'id', $id);
        return $row;
    }
    
    

    /**
     * method for creating a category
     *
     * @return int insert id
     */
    public function createGallery (){
        try {
            $values = 
                array('title'=> $_POST['title']
                );
             $res = $this->insert('gallery', $values);
             return $res;
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
    }

    /**
     * method for updating a category
     *
     * @return int affected rows
     */
    public function updateGallery (){
        try {
            $values = 
                array('title'=> $_POST['title'],
                );
            $res = $this->update('gallery', $values, $this->id);
            return $res;
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
    }


    /**
     * method for getting all categories
     *
     * @return array array categories
     */
    public function getAllGallery (){
        try {
            $rows = $this->selectAll('gallery');
            return $rows;
        } catch (PDOException $e) {
            $this->fatalError($e->getMessage());
        }
    }

    public function displayAllGallery(){
        $galleries = $this->getAllGallery();
        foreach ($galleries as $key => $val){
            $val['title'] = $val['title'];
            $link = create_link ("/gallery/view/$val[id]", $val['title']);
            echo "<hr id=\"thick_hr\"/>\n";
            echo "<div id=\"headfont\">\n";
            echo $link;
            echo "</div>";

            echo "<div id=\"smallfont\">";

            if (session::isAdmin()){
                print create_link ("/gallery/view/$val[id]", lang::translate('Edit this gallery')) . " \n";
                print create_link ("/gallery/admin/edit/$val[id]", lang::translate('Edit gallery name')) . " \n";
            } 
            echo "</div>\n";
        }
    }


    public function displayAllGalleryJquery(){
        
        $galleries = $this->getAllGallery();
        foreach ($galleries as $key => $val){
            $link = html::createLink("/gallery/view/$val[id]", $val['title']);
            $info = $this->getAllFileInfo($val['id']);
                    
            $i = 0;
            foreach ($info as $k => $v){
                
                $image_url = "/files/gallery/$val[id]/$v[file_name]";
                $thumb_url = "/files/gallery/$val[id]/thumb-$v[file_name]";
                
                if ($i == 0){
                    echo "<a rel=\"example_group\" href=\"$image_url\" title=\"Bla bla\"><img alt=\"\" src=\"$thumb_url\" /></a>\n";
                } else {
                    echo "<a rel=\"example_group\" href=\"$image_url\" title=\"\"><img alt=\"\" /></a>\n";
                }
                $i++;
                    //echo "<a rel=\"example_group\" href =\"$random_url\" title=\"bla\"><img alt=\"\" src=\"$random_url\" /></a>";
                //}

            }
            echo "<hr id=\"thick_hr\"/>\n";
            echo "<div id=\"headfont\">\n";
            print $link;
            echo "</div>";
            //print lang::translate('Name') . ": $val[title]" . "<br />";
            //print "<hr id=\"thin_hr\"/>\n";
            echo "<div id=\"smallfont\">";
            if (session::isAdmin()){
                print create_link ("/gallery/view/$val[id]", lang::translate('Edit this gallery')) . " \n";
                print create_link ("/gallery/admin/edit/$val[id]", lang::translate('Edit gallery name')) . " \n";
            } else {
                //print create_link ("/gallery/view/$val[id]", lang::translate('View this gallery')) . " \n";
            }
            print "</div>\n";
        }

    }
}

include_once "upload.php";
include_once "mySqlForm.php";

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
    html::label('title', lang::translate('gallery_form_title'));
    html::text('title');
    html::submit('submit', lang::translate('gallery_submit_title'));
    echo html::getStr();
    return;
    
}
