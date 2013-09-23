<?php

/**
 * file for creating gallery module menu
 *
 * @package    gallery
 */
$_MODULE_MENU = array (
    array(
        'title' => lang::translate('List galleries'),
        'url' => '/gallery/index'),
    array(
        'title' => lang::translate('Add file'),
        'url' => '/gallery/admin/index',
        'auth' => 'admin'),

        
);

if (config::getModuleIni('gallery_allow_zip')) {
    $_MODULE_MENU[] =
        array(
            'title' => lang::translate('Upload zip'),
            'url' => '/gallery/upload/index',
            'auth' => 'admin');
}
