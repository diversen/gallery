<?php

template::setTitle(lang::translate('Delete Gallery'));
$gallery = new gallery();
if (!empty($_POST['submit'])){
    $res = $gallery->deleteGallery();
    if ($res){
        session::setActionMessage(
            lang::translate("Gallery") . " " .
            lang::translate('has been deleted'));
        header("Location: /gallery/admin/index");
    }
} else {
    $row = $gallery->getGallery();
    view_gallery_form('delete');
}
