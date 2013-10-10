<?php

/**
 * view file for admin gallery 
 *
 * @package    gallery
 */
moduleloader::includemodule("gallery/admin");
template::setTitle(lang::translate('List galleries'));

$gal = new gallery_admin();
$gal->displayAllGallery();
