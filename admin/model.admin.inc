<?php

/**
 * file containing administration class for gallery
 *
 * @package     gallery
 */
moduleloader::includeModule ('gallery');

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
    
    public static function adminOptions ($id) {
        $str = '';
        if (session::isAdmin()){
            $str = html::createLink ("/gallery/view/$id", lang::translate('gallery_edit_files')) . " \n";
            $str.= MENU_SUB_SEPARATOR;
            $str.= html::createLink("/gallery/admin/edit/$id", lang::translate('gallery_edit_name')) . " \n";
            $str.= MENU_SUB_SEPARATOR;
            $str.= html::createLink("/gallery/admin/delete/$id", lang::translate('gallery_delete_gallery')) . " \n";
        }
        return $str;
    }
    
    public static function getRows ($vars, $options) {
        
    }
    
    public static function uploadForm ($vars){
        $str = '';
        if (session::isAdmin() && !isset($vars['options']['no_admin'])){
            $str.= '<form enctype="multipart/form-data" method="post" action="" />';   
            $str.= '<input type="file" size="5" name="filename" value="Upload" />';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::system('system_submit_upload') . '" />';
            $str.= '</form>';
        }
        return $str;
    } 
    
    public static function getAdminOptions ($val, $vars) {
        $str = '';
        if (session::isAdmin() && !isset($vars['options']['no_admin'])){
            $str.= "<table>"; 
            $str.= "<tr><td>\n"; 
            $str.= '<form name="gallery_delete_image" action="" ';
            $str.= " method=\"post\" enctype=\"multipart/form-data\">";
            $str.= "<input type=\"hidden\" name=\"method\" value=\"delete\" />\n";
            $str.= '<input type="hidden" name="file_id" value="' . $val['id'] . '" />';
            $str.= '<input type="hidden" name="gallery_id" value="' . $vars['options']['gallery_id'] . '" />';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::system('system_submit_delete') . '" />';
            $str.= '</form>';              
            $str.= "</td></tr>";
            $str.= "<tr><td>\n"; 
            $str.= '<form name="gallery_delete_image" action="" ';
            $str.= ' method="post" enctype="multipart/form-data">'; 
            $str.= "<input type=\"hidden\" name=\"method\" value=\"default_image\" />\n";
            $str.= '<input type="hidden" name="file_id" value="' . $val['id'] . '" />';
            $str.= '<input type="hidden" name="gallery_id" value="' . $vars['options']['gallery_id'] . '" />';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::translate('gallery_default_image') . '" />';
            $str.= '</form>';              
            $str.= "</td></tr>";
            $str.= "</table>\n";
        }    
        return $str;
    }

    /**
     * 
     * @return type 
     */
    public static function deleteGallery($id){
        
        $domain = config::getDomain();
        if (!$domain) return false;
        
        $path = _COS_HTDOCS . "/files/$domain/gallery/$id";
        
        file::rrmdir($path);
        
        $db = new db();
        $db->begin();
        $db->delete('gallery_file', 'gallery_id', $id);
        $db->delete('gallery', 'id',  $id);
        $event_params = array (
                'action' => 'delete',
                'reference' => 'gallery', 
                'parent_id' => $id);
            
        event::triggerEvent(
            config::getModuleIni('gallery_events'),
            $event_params);
        $db->commit();
        return true;
    }

    /**
     * method for getting a category
     *
     * @return array  containing the selected category
     */
    public static function getGallery($id = null){
        if (!$id) $id = self::$galleryId;
        if (!$id) return array ();
        
        $db = new db();
        $row = $db->selectOne('gallery', 'id', $id);
        $row = html::specialEncode($row);
        return $row;
    }
    
    public static function displayTitle ($val) {
        if (empty($val['title'])) $val['title'] = lang::translate('gallery: title: no title');
        $link = html::createLink("/gallery/view/$val[id]", $val['title']);
        html::headline($link);
    }
    
    

    /**
     * method for creating a category
     *
     * @return int insert id
     */
    public static function createGallery ($values = null){
        if (!$values) {
            $values = array(
                'title'=> $_POST['title'], 
                'description' => $_POST['description'],
                'user_id' => session::getUserId());
        }
        $values = html::specialEncode($values);
        $db = new db(); 
        $res = $db->insert('gallery', $values);
        
        $last_insert_id = $db->lastInsertId(); 
        
        // create params for events that may be triggered
        $event_params = array (
            'action' => 'insert',
            'reference' => 'gallery', 
            'parent_id' => $last_insert_id);
            
        // trigger events
        event::triggerEvent(
            config::getModuleIni('gallery_events'),
            $event_params
        );
        
        return $last_insert_id;
        //return $res;
    }

    /**
     * method for updating a category
     *
     * @return int affected rows
     */
    public static function updateGallery ($id = null){
        
        if (!$id) $id = self::$galleryId;
        $db = new db();
        
        $values = db::prepareToPost();
        $values['user_id'] = session::getUserId();
        $values = html::specialDecode($values);
        
        $event_params = array (
            'action' => 'update',
            'reference' => 'gallery', 
            'parent_id' => $id);
            
        event::triggerEvent(
            config::getModuleIni('gallery_events'),
            $event_params
        );
        
        unset($values['tags']);
        
        $res = $db->update('gallery', $values, $id);
        return $res;
    }


    /**
     * method for getting all categories
     *
     * @return array array categories
     */
    public static function getAllGallery ($from = null, $limit = null){
        $db = new db();
        $rows = $db->selectAll('gallery', null, null, $from, $limit, 'updated');
        return $rows;

    }
    


    public function displayAllGallery($from = 0, $limit = 10){
        $display_module = config::getModuleIni('gallery_display_module');         
        moduleloader::includeModule($display_module);
        $module = moduleloader::modulePathToClassName($display_module);           
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
    
 if ($method == 'delete') {
        html::$autoLoadTrigger = 'submit';
        //html::init($vars);
        html::formStart('gallery_from_delete');
        html::legend(lang::translate('gallery_delete_legend'));
        html::submit('submit', lang::system('system_submit_delete'));
        html::formEnd();
        echo html::getStr();
        return;
        
    }
    
    html::formStart('gallery_form');
    
    if (isset($_POST['submit'])) {
        $_POST['submit'] = html::specialEncode($values);
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
    
    // trigger form events
    if (isset($id)) {
        event::triggerEvent(
            config::getModuleIni('gallery_events'), 
            array(
                'action' => 'form',
                'reference' => 'gallery',
                'parent_id' => $id
            )
        );
    } else {
    
        event::triggerEvent(
            config::getModuleIni('gallery_events'), 
            array(
                'action' => 'form',
                'reference' => 'gallery'
            )
        );
    }
    
    html::submit('submit', lang::system('system_submit_add'));
    html::formEnd();
    echo html::getStr();
    return;
    
}


