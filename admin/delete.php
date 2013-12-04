<?php

if (!session::checkAccessFromModuleIni('gallery_allow_edit')){
    return;
}

template::setTitle(lang::translate('Delete gallery'));
$gallery = new gallery_admin();
$row = $gallery->getGallery();

if (!empty($_POST['submit'])){
    $res = $gallery->deleteGallery($row['id']);
    if ($res){
        session::setActionMessage(
            lang::translate('Gallery has been deleted'));
        http::locationHeader('/gallery/index');
    }
} else { 
    view_gallery_form('delete', $row['id']);
}
