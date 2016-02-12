<?php

namespace modules\gallery\admin;

use diversen\conf;
use diversen\db;
use diversen\file;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\session;
use diversen\template;
use diversen\uri;
use diversen\user;

/**
 * file containing administration class for gallery
 *
 * @package     gallery
 */
use modules\gallery\module as gallery;

/**
 * class for working with content categories in gallery
 *
 * @package     gallery
 */
class module extends gallery {


    /**
     * Sets galleryId
     */
    public function __construct() {
        $this->galleryId = uri::fragment(3);
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

    /**
     * /gallery/admin/delete action
     * @return void
     */
    public function deleteAction() {

        if (!session::checkAccessFromModuleIni('gallery_allow_edit')) {
            return;
        }

        template::setTitle(lang::translate('Delete gallery'));

        $row = $this->getGallery($this->galleryId);

        if (!empty($_POST['submit'])) {
            $res = $this->deleteGallery($row['id']);
            if ($res) {
                session::setActionMessage(
                        lang::translate('Gallery has been deleted'));
                http::locationHeader('/gallery/index');
            }
        } else {
            $this->formGallery('delete', $row['id']);
        }
    }

    /**
     * gallery/edit action
     * @return void
     */
    public function editAction() {

        if (!session::checkAccessFromModuleIni('gallery_allow_edit')) {
            return;
        }

        template::setTitle(lang::translate('Edit gallery'));
        $gallery = new self();
        if (!empty($_POST['submit'])) {
            $gallery->validate();
            if (empty($gallery->errors)) {
                $res = $gallery->updateGallery();
                if ($res) {
                    session::setActionMessage(
                            lang::translate('Gallery updated'));
                    http::locationHeader('/gallery/index');
                }
                echo html::getConfirm(lang::translate());
            } else {
                echo html::getErrors($this->errors);

            }
        } else {
            $this->formGallery('update', $this->galleryId);
        }
    }

    /**
     * /gallery/index action
     * @return type
     */
    public function indexAction() {
        if (!session::checkAccessFromModuleIni('gallery_allow_edit')) {
            return;
        }

        template::setTitle(lang::translate('Add file'));
        $gallery = new self(true);
        if (!empty($_POST['submit'])) {

            $gallery->validate();
            if (empty($gallery->errors)) {
                $gallery->createGallery();
                session::setActionMessage(lang::translate('Gallery created'));
                http::locationHeader("/gallery/admin/index");
            } else {
                html::errors($gallery->errors);
                $this->formGallery('insert');
            }
        } else {
            $this->formGallery('insert');
        }
    }

    /**
     * Santize submission
     */
    public static function sanitize() {
        $_POST = html::specialEncode($_POST);
    }

    /**
     * Admin options
     * @param int $id gallery id
     * @return string $html
     */
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

    /**
     * Image files upload form
     * @return void
     */
    public function formUpload() {
        $str = '';
        
        if (session::isAdmin()) {
            $f = new html();
            $f->formStart();
            $f->label('files[]', lang::translate('Upload file') );
            $f->fileWithLabel('files[]', conf::getModuleIni('gallery_file_max'), array('multiple' => 'multiple'));
            $f->submit('submit', lang::translate('Upload file'));
            $f->formEnd();
            return $f->getStr();
        }
    }

    /**
     * 
     * @param type $val
     * @param type $vars
     * @return string
     */
    public static function getAdminOptions($val, $vars) {
        
        $str = '';
        if (session::isAdmin() ) {
            $str.= "<table>";
            $str.= "<tr><td>\n";

            $f = new html();
            $f->formStart();
            $f->hidden('file_id', $val['id']);
            $f->hidden('method', 'delete');
            $f->hidden('gallery_id', $vars['options']['gallery_id']);
            $f->submit('submit', lang::translate('Delete'));
            $f->formEnd();
            
            $str.= $f->getStr();
            $str.= "</td></tr>";
            $str.= "</table>\n";
        }

        return $str;
    }

    /**
     * 
     * @return type 
     */
    public function deleteGallery($id) {

        $domain = conf::getDomain();
        if (!$domain)
            return false;

        $path = conf::pathHtdocs() . "/files/$domain/gallery/$id";

        file::rrmdir($path);

        $db = new db();
        $db->begin();
        $db->delete('gallery_file', 'gallery_id', $id);
        $db->delete('gallery', 'id', $id);

        return $db->commit();

    }

    /**
     * method for getting a category
     *
     * @return array  containing the selected category
     */
    public function getGallery($id) {

        $db = new db();
        $row = $db->selectOne('gallery', 'id', $id);
        $row = html::specialEncode($row);
        return $row;
    }

    /**
     * Method that display a title
     * @param type $val
     */
    public function displayTitle($val) {
        if (empty($val['title'])) {
            $val['title'] = lang::translate('No title');
        }
        $link = html::createLink("/gallery/view/$val[id]", $val['title']);
        echo html::getHeadline($link);
        echo user::getProfile($val['user_id'], $val['updated']);
    }

    /**
     * method for creating a callery
     * @return int insert id
     */
    public function createGallery($values = null) {
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
        return $last_insert_id;

    }

    /**
     * method for updating a category
     *
     * @return int affected rows
     */
    public function updateGallery($id = null) {

        if (!$id) {
            $id = $this->galleryId;
        }
        $db = new db();

        $values = db::prepareToPost();
        $values['user_id'] = session::getUserId();
        $values = html::specialDecode($values);

        $res = $db->update('gallery', $values, $id);
        return $res;
    }

    /**
     * method for getting all categories
     *
     * @return array array categories
     */
    public function getAllGallery($from = null, $limit = null) {
        $db = new db();
        $rows = $db->selectAll('gallery', null, null, $from, $limit, 'updated');
        return $rows;
    }


    /**
     * returns a form for editing a image inline. 
     * @param type $values
     * @return type
     */
    public static function formInline($values = null) {

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

    /**
     * function for creating crud form for files
     *
     * @param string create, delete or update
     * @param int if delete or update set this to id
     */
    public function formGallery($method, $id = null, $values = array()) {

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
            $gallery = self::getGallery($id);
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

        html::submit('submit', lang::translate('Add'));
        html::formEnd();
        echo html::getStr();
        return;
    }
}
