<?php

if (!session::checkAccessControl('gallery_allow_edit')){
    return;
}

template::setTitle(lang::translate('Edit gallery'));
$gallery = new galleryAdmin();
if (!empty($_POST['submit'])){
    $gallery->validate();
    if (empty($gallery->errors)){
        $res = $gallery->updateGallery();
        if ($res){
            session::setActionMessage(
            lang::translate('Gallery updated'));
            header("Location: /gallery/index");
        }
        view_confirm(lang::translate());
    } else {
        view_form_errors(gallery::$errors);
        view_gallery_form('update', gallery::$galleryId);
    }
} else {
    //$row = $category->getGallery();
    view_gallery_form('update', gallery::$galleryId);
}