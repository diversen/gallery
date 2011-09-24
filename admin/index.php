<?php

/**
 * controller file for content/category/add
 * add a categories
 *
 * @package gallery
 */
if (!session::checkAccessControl('gallery_allow_edit')){
    return;
}

template::setTitle(lang::translate('Add Gallery'));
$gallery = new galleryAdmin(true);
if (!empty($_POST['submit'])){

    $gallery->validate();
    if (empty($gallery->errors)){
        $res = $gallery->createGallery();
        session::setActionMessage(lang::translate('Gallery created'));
        header("Location: /gallery/admin/index");
    } else {
        view_form_errors($gallery->errors);
        view_gallery_form('insert');
    }
} else {
    view_gallery_form('insert');
}
