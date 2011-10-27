<?php

/**
 * file for creating gallery module menu
 *
 * @package    gallery
 */
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

