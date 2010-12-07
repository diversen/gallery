<?php

/**
 * view file delete.php
 *
 * @package    gallery
 */
if (!session::checkAccessControl('allow_edit_gallery')){
    return;
}

template::setTitle(lang::translate('Delete image'));
$fileObj = new galleryImage();
if (isset($_POST['submit'])){
    if (!isset($fileObj->errors)){
        $res = $fileObj->deleteFile($fileObj->fileId);
        if ($res){
            //session::setActionMessage(lang::translate('File deleted'));
            header("Location: /gallery/view/$fileObj->id");
        }
    } else {
        view_form_errors($fileObj->errors);
    }
}
if (!empty($fileObj->fileId)){
    view_image_form('delete');
}

$files = $fileObj->getAllFiles();
print $fileObj->getFilesHTML($files);