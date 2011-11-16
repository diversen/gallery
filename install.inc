<?php

/**
 * installer file for gallery module
 *
 * @package    gallery
 */

//$gallery_menu_item = get_module_ini('gallery_menu_item');

if (get_module_ini('gallery_menu_item')) {
    $_INSTALL['MAIN_MENU_ITEM'] = array (
        'title' => 'Gallery',
        'url' => '/gallery/index',
        'module_name' => 'gallery',
        'parent' => 0,
        'weight' => 2,
        'section' => get_module_ini('gallery_section'),
        'auth' => get_module_ini('gallery_menu_item_auth'),
        'admin_only' => get_module_ini('gallery_admin_only'),
    );
}
$_INSTALL['VERSION'] = 1.321;
$_INSTALL['PUBLIC_CLONE_URL'] = 'git://github.com/diversen/gallery.git';
$_INSTALL['PRIVATE_CLONE_URL'] = 'git@github.com:diversen/gallery.git';
