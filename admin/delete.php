<?php

if (!session::checkAccessControl('gallery_allow_edit')){
    return;
}

template::setTitle(lang::translate('Delete Gallery'));
$gallery = new galleryAdmin();
$row = $gallery->getGallery();

if (!empty($_POST['submit'])){
    $res = $gallery->deleteGallery($row['id']);
    if ($res){
        session::setActionMessage(
            lang::translate('gallery_gallery_has_been_deleted'));
        http::locationHeader('/gallery/index');
    }
} else { 
    view_gallery_form('delete', $row['id']);
}
