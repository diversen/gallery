<?php

/**
 * installer file for gallery module
 *
 * @package    gallery
 */
$_INSTALL['MAIN_MENU_ITEM'] = array (
    'title' => 'Gallery',
    'url' => '/gallery/index',
    'module_name' => 'gallery',
    'parent' => 0,
    'weight' => 2,
);

$admin_only = get_module_ini('gallery_admin_only');
if (isset($admin_only)) {
    $_INSTALL['MAIN_MENU_ITEM']['admin_only'] = 1;
}

$_INSTALL['VERSION'] = 1.31;
$_INSTALL['PUBLIC_CLONE_URL'] = 'git://github.com/diversen/gallery.git';
$_INSTALL['PRIVATE_CLONE_URL'] = 'git@github.com:diversen/gallery.git';
