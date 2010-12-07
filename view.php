<?php

/**
 * view file for gallery
 *
 * @package    gallery
 */
$galImg = new galleryImage(true);

template::setTitle(lang::translate('View gallery'));
if (isset($_POST['submit'])){

    if (!session::checkAccessControl('allow_edit_gallery')){
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

$files = $galImg->getAllFiles();
$info = $galImg->getAllFileInfo($galImg->id);

if (get_module_ini('use_jquery_gallery')){
    print $galImg->getThumbTableHTMLJquery($info);
} else {
    print $galImg->getThumbTableHTML($info);

}
