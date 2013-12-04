<?php

/**
 * view file delete.php
 *
 * @package    gallery
 */
if (!session::checkAccessFromModuleIni('gallery_allow_edit')){
    return;
}

template::setTitle(lang::translate('Delete image'));
$fileObj = new gallery();
if (isset($_POST['submit'])){
    if (!isset($fileObj->errors)){
        $res = $fileObj->deleteFile($fileObj->fileId);
        if ($res){
            //session::setActionMessage(lang::translate('File deleted'));
            http::locationHeader('/gallery/view/' . $fileObj->id);
        }
    } else {
        html::errors($fileObj->errors);
    }
}
if (!empty($fileObj->fileId)){
    view_image_form('delete');
}

//$files = $fileObj->getAllFiles();
//print $fileObj->getFilesHTML($files);