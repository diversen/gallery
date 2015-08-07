<?php

use diversen\conf;
use diversen\file;
use diversen\lang;

/**
 * installer file for gallery module
 *
 * @package    gallery
 */

if (conf::getModuleIni('gallery_menu_item')) {
    $_INSTALL['MAIN_MENU_ITEM'] = array (
        'title' => lang::translate('Gallery', null, array ('no_translate' => true)),
        'url' => '/gallery/index',
        'module_name' => 'gallery',
        'parent' => 0,
        'weight' => 2,
        'section' => 'content'
    );
    
    $section = conf::getModuleIni('gallery_section');
    if ($section) {
        $_INSTALL['MAIN_MENU_ITEM']['section'] = $section;
    }
    
    $auth = conf::getModuleIni('gallery_menu_item_auth');
    if ($auth) {
        $_INSTALL['MAIN_MENU_ITEM']['auth'] = $auth;
    }
    
    $admin_only = conf::getModuleIni('gallery_menu_item_admin_only');
    if ($admin_only) {
        $_INSTALL['MAIN_MENU_ITEM']['admin_only'] = $admin_only;
    }    
}

$_INSTALL['VERSION'] = 4.01;
$_INSTALL['PUBLIC_CLONE_URL'] = 'git://github.com/diversen/gallery.git';
$_INSTALL['PRIVATE_CLONE_URL'] = 'git@github.com:diversen/gallery.git';

// try to remove files on uninstall
// sql is in mysql
$_INSTALL['UNINSTALL'] = function ($version = null) {

    $domain = conf::getDomain();
    $dir = conf::pathHtdocs() . "/files/$domain/gallery";
    
    // only direct web user can remove gallery dir. 
    if (conf::isCli()) {
        echo "You have to remove $dir manual\n";
        return;
    }
    
    if (file_exists($dir)) {
        file::rrmdir($dir);
    }
};

$_INSTALL['INSTALL'] = function ($version = null) {};
