<?php

use diversen\conf;
use diversen\db;
use diversen\event;
use diversen\file;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\moduleloader;
use diversen\session;
use diversen\template;
use diversen\uri;

/**
 * file containing administration class for gallery
 *
 * @package     gallery
 */
moduleloader::includeModule('gallery');

/**
 * class for working with content categories in gallery
 *
 * @package     gallery
 */
class gallery_admin extends gallery {

    /**
     *
     * @var object  holding URI instance
     */
    public $uri;

    /**
     * Sets galleryId
     */
    function __construct() {
        self::$galleryId = uri::getInstance()->fragment(3);
    }

    /**
     * Validate user input
     */
    function validate() {

        if (empty($_POST['submit'])) {
            return;
        }

        if (strlen($_POST['title']) < 3) {
            $this->errors['title'] = lang::translate('Gallery title should be at least 3 chars long');
        }
    }

    public function deleteAction() {

        if (!session::checkAccessFromModuleIni('gallery_allow_edit')) {
            return;
        }

        template::setTitle(lang::translate('Delete gallery'));
        $gallery = new gallery_admin();
        $row = $gallery->getGallery();

        if (!empty($_POST['submit'])) {
            $res = $gallery->deleteGallery($row['id']);
            if ($res) {
                session::setActionMessage(
                        lang::translate('Gallery has been deleted'));
                http::locationHeader('/gallery/index');
            }
        } else {
            view_gallery_form('delete', $row['id']);
        }
    }

    public function editAction() {

        if (!session::checkAccessFromModuleIni('gallery_allow_edit')) {
            return;
        }

        template::setTitle(lang::translate('Edit gallery'));
        $gallery = new gallery_admin();
        if (!empty($_POST['submit'])) {
            $gallery->validate();
            if (empty($gallery->errors)) {
                $res = $gallery->updateGallery();
                if ($res) {
                    session::setActionMessage(
                            lang::translate('Gallery updated'));
                    http::locationHeader('/gallery/index');
                }
                html::confirm(lang::translate());
            } else {
                html::errors(gallery::$errors);
                view_gallery_form('update', gallery::$galleryId);
            }
        } else {
            //$row = $category->getGallery();
            view_gallery_form('update', gallery::$galleryId);
        }
    }

    public function indexAction() {
        if (!session::checkAccessFromModuleIni('gallery_allow_edit')) {
            return;
        }

        template::setTitle(lang::translate('Add file'));
        $gallery = new gallery_admin(true);
        if (!empty($_POST['submit'])) {

            $gallery->validate();
            if (empty($gallery->errors)) {
                $res = $gallery->createGallery();
                session::setActionMessage(lang::translate('Gallery created'));
                http::locationHeader("/gallery/admin/index");
            } else {
                html::errors($gallery->errors);
                view_gallery_form('insert');
            }
        } else {
            view_gallery_form('insert');
        }
    }

    /**
     * Santize submission
     */
    public static function sanitize() {
        $_POST = html::specialEncode($_POST);
    }

    public static function adminOptions($id) {
        $str = '';
        if (session::isAdmin()) {
            $str = html::createLink("/gallery/view/$id", lang::translate('Edit files')) . " \n";
            $str.= MENU_SUB_SEPARATOR;
            $str.= html::createLink("/gallery/admin/edit/$id", lang::translate('Edit gallery')) . " \n";
            $str.= MENU_SUB_SEPARATOR;
            $str.= html::createLink("/gallery/admin/delete/$id", lang::translate('Delete gallery')) . " \n";
        }
        return $str;
    }

    public static function getRows($vars, $options) {
        
    }

    public static function uploadForm($vars) {
        $str = '';
        if (session::isAdmin() && !isset($vars['options']['no_admin'])) {
            $str.= '<form enctype="multipart/form-data" method="post" action="" />';
            $str.= '<input accept="image/*" type="file" size="5" name="filename" value="Upload" />';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::translate('Upload file') . '" />';
            $str.= '</form>';
        }
        return $str;
    }

    public static function getAdminOptions($val, $vars) {
        $str = '';
        if (session::isAdmin() && !isset($vars['options']['no_admin'])) {
            $str.= "<table>";
            $str.= "<tr><td>\n";
            $str.= '<form name="gallery_delete_image" action="" ';
            $str.= " method=\"post\" enctype=\"multipart/form-data\">";
            $str.= "<input type=\"hidden\" name=\"method\" value=\"delete\" />\n";
            $str.= '<input type="hidden" name="file_id" value="' . $val['id'] . '" />';
            $str.= '<input type="hidden" name="gallery_id" value="' . $vars['options']['gallery_id'] . '" />';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::translate('Delete') . '" />';
            $str.= '</form>';
            $str.= "</td></tr>";
            $str.= "<tr><td>\n";
            $str.= '<form name="gallery_delete_image" action="" ';
            $str.= ' method="post" enctype="multipart/form-data">';
            $str.= "<input type=\"hidden\" name=\"method\" value=\"default_image\" />\n";
            $str.= '<input type="hidden" name="file_id" value="' . $val['id'] . '" />';
            $str.= '<input type="hidden" name="gallery_id" value="' . $vars['options']['gallery_id'] . '" />';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::translate('Main image') . '" />';
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
    public static function deleteGallery($id) {

        $domain = conf::getDomain();
        if (!$domain)
            return false;

        $path = conf::pathHtdocs() . "/files/$domain/gallery/$id";

        file::rrmdir($path);

        $db = new db();
        $db->begin();
        $db->delete('gallery_file', 'gallery_id', $id);
        $db->delete('gallery', 'id', $id);
        $event_params = array(
            'action' => 'delete',
            'reference' => 'gallery',
            'parent_id' => $id);

        event::triggerEvent(
                conf::getModuleIni('gallery_events'), $event_params);
        $db->commit();
        return true;
    }

    /**
     * method for getting a category
     *
     * @return array  containing the selected category
     */
    public static function getGallery($id = null) {
        if (!$id)
            $id = self::$galleryId;
        if (!$id)
            return array();

        $db = new db();
        $row = $db->selectOne('gallery', 'id', $id);
        $row = html::specialEncode($row);
        return $row;
    }

    public static function displayTitle($val) {
        if (empty($val['title']))
            $val['title'] = lang::translate('No title');
        $link = html::createLink("/gallery/view/$val[id]", $val['title']);
        html::headline($link);
    }

    /**
     * method for creating a category
     *
     * @return int insert id
     */
    public static function createGallery($values = null) {
        if (!$values) {
            $values = array(
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'user_id' => session::getUserId());
        }
        $values = html::specialEncode($values);
        $db = new db();
        $res = $db->insert('gallery', $values);

        $last_insert_id = $db->lastInsertId();

        // create params for events that may be triggered
        $event_params = array(
            'action' => 'insert',
            'reference' => 'gallery',
            'parent_id' => $last_insert_id);

        // trigger events
        event::triggerEvent(
                conf::getModuleIni('gallery_events'), $event_params
        );

        return $last_insert_id;
        //return $res;
    }

    /**
     * method for updating a category
     *
     * @return int affected rows
     */
    public static function updateGallery($id = null) {

        if (!$id) {
            $id = self::$galleryId;
        }
        $db = new db();

        $values = db::prepareToPost();
        $values['user_id'] = session::getUserId();
        $values = html::specialDecode($values);

        $event_params = array(
            'action' => 'update',
            'reference' => 'gallery',
            'parent_id' => $id);

        event::triggerEvent(
                conf::getModuleIni('gallery_events'), $event_params
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
    public static function getAllGallery($from = null, $limit = null) {
        $db = new db();
        $rows = $db->selectAll('gallery', null, null, $from, $limit, 'updated');
        return $rows;
    }

    public function displayAllGallery($from = 0, $limit = 10) {
        $display_module = conf::getModuleIni('gallery_display_module');
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
function view_gallery_form($method, $id = null, $values = array()) {

    if ($method == 'delete') {
        html::$autoLoadTrigger = 'submit';
        //html::init($vars);
        html::formStart('gallery_from_delete');
        html::legend(lang::translate('Delete gallery'));
        html::submit('submit', lang::translate('Delete'));
        html::formEnd();
        echo html::getStr();
        return;
    }

    html::formStart('gallery_form');

    if (isset($_POST['submit'])) {
        $_POST['submit'] = html::specialEncode($values);
    }

    if (isset($id)) {
        $gallery = gallery_admin::getGallery($id);

        html::init($gallery, 'submit');
    }

    if (isset($id)) {
        $legend = lang::translate('Edit gallery');
    } else {
        $legend = lang::translate('Add file');
    }

    html::legend($legend);
    html::label('title', lang::translate('Title'));
    html::text('title');
    html::label('description', lang::translate('Abstract'));
    html::textareaSmall('description');

    // trigger form events
    if (isset($id)) {
        event::triggerEvent(
                conf::getModuleIni('gallery_events'), array(
            'action' => 'form',
            'reference' => 'gallery',
            'parent_id' => $id
                )
        );
    } else {

        event::triggerEvent(
                conf::getModuleIni('gallery_events'), array(
            'action' => 'form',
            'reference' => 'gallery'
                )
        );
    }

    html::submit('submit', lang::translate('Add'));
    html::formEnd();
    echo html::getStr();
    return;
}

/**
 * returns a form for editing a image inline. 
 * @param type $values
 * @return type
 */
function get_gallery_inline_form($values = null) {

    $values['file_name'] = file::getFilename($values['file_name'], array('utf8' => true));
    $values['file_name'] = rawurldecode($values['file_name']);

    $form = new html();
    html::$autoEncode = true;
    $form->formStart('gallery_form');
    $form->init($values, 'submit');
    $legend = lang::translate('Set image details');
    $form->legend($legend);
    $form->hidden('gallery_details', 1);
    $form->label('file_name', lang::translate('File name'));
    $form->text('file_name');
    $form->label('title', lang::translate('Title'));
    $form->text('title');
    $form->label('description', lang::translate('Abstract'));
    $form->textareaSmall('description');
    $form->submit('submit', lang::translate('Update'));
    $form->formEnd();
    $str = $form->getStr();
    $str = "<div class =\"edit_details\" >\n$str</div>\n";
    return $str;
}
