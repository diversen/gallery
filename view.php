<?php

/**
 * view file for gallery
 *
 * @package    gallery
 */
$galImg = new gallery(true);

template::setTitle(lang::translate('View gallery'));
if (isset($_POST['submit'])){

    if (!session::checkAccessControl('gallery_allow_edit')){
        return;
    }

    $galImg->validateInsert();
    if (!isset($galImg->errors)){
        $res = $galImg->insertFile('filename');
        if ($res){
            session::setActionMessage(lang::translate('File added'));
            header("Location: $_SERVER[REQUEST_URI]");
        }
    } else {
        view_form_errors($galImg->errors);
    }
}

$options = array ('gallery_id' => $galImg->id);
$files = $galImg->getAllFiles();
$info = $galImg->getAllFileInfo($galImg->id);
echo $galImg->getThumbTableHTML($info, $options);
