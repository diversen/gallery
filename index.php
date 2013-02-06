<?php

/**
 * view file for admin gallery 
 *
 * @package    gallery
 */
moduleloader::includemodule("gallery/admin");
template::setTitle(lang::translate('List Galleries'));

$gal = new galleryAdmin();
$gal->displayAllGallery();
