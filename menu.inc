<?php

/**
 * file for creating gallery module menu
 *
 * @package    gallery
 */

// ini file loaded first so we can use the module settings.

$con = uri::$info['controller'];
if ($con != 'view'){
    $_MODULE_MENU = array (
            array(
            'title' => lang::translate('List Galleries'),
            'url' => '/gallery/index',
            'auth' => 'admin'),
        array(
            'title' => lang::translate('Add Gallery'),
            'url' => '/gallery/admin/index',
            'auth' => 'admin'),
        
    );
}

/*
if (get_module_ini('gallery_use_jquery')){
    $_MODULE_MENU = array (
            array(
            'title' => lang::translate('List Galleries'),
            'url' => '/gallery/index',
            ),
        array(
            'title' => lang::translate('Add Gallery'),
            'url' => '/gallery/admin/index',
            'auth' => 'admin'),

    );
}*/
