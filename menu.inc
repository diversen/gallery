<?php

/**
 * file for creating gallery module menu
 *
 * @package    gallery
 */
$_MODULE_MENU = array (
    array(
        'title' => lang::translate('List Galleries'),
        'url' => '/gallery/index'),
    array(
        'title' => lang::translate('Add Gallery'),
        'url' => '/gallery/admin/index',
        'auth' => 'admin'),

        
);

if (config::getModuleIni('gallery_allow_zip')) {
    $_MODULE_MENU[] =
        array(
            'title' => lang::translate('gallery_menu_upload_zip'),
            'url' => '/gallery/upload/index',
            'auth' => 'admin');
}
