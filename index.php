<?php

/**
 * view file for admin gallery 
 *
 * @package    gallery
 */
include_model("gallery/admin");
template::setTitle(lang::translate('List Galleries'));

$gal = new galleryAdmin();
$gal->displayAllGallery();
    
