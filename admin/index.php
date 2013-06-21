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
$gallery = new gallery_admin(true);
if (!empty($_POST['submit'])){

    $gallery->validate();
    if (empty($gallery->errors)){
        $res = $gallery->createGallery();
        session::setActionMessage(lang::translate('Gallery created'));
        http::locationHeader("/gallery/admin/index");
    } else {
        html::errors($gallery->errors);
        view_gallery_form('insert');
    }
} else {
    view_gallery_form('insert');
}
