<?php

moduleLoader::includeModule('gallery');
$gallery = new gallery();

$id = uri::getInstance()->fragment(3);

$row = null;
if (!$id) {
    return;
}
$row = $gallery->getSingleImage($id);

if (empty($row)) {
    return;
}

// we got a row


echo $gallery->getImageSrc($id, 'full');
$row = $gallery->getImageUrl($id);
echo $link = html::createLink($row['src'], lang::translate('gallery_view_full_size'));


if (session::isAdmin()) {
    // edit
    if (isset($_POST['gallery_details'])) {
        $res = $gallery->updateImageDetails($id);
        if ($res) {
            $location = "/gallery/inline/view/$id";
            $message = lang::translate('gallery_image_details_updated');
            http::locationHeader($location, $message);
        } else {
            
        }
    }
    
    
    view_gallery_inline_form($row);
}

$return_url = gallery::getReturnUrlFromId($row['id']);
$options = array (
    'parent_id' => $row['id'],
    'reference' => 'gallery',
    'return_url' => $return_url
);

$subs = array ('comment');
moduleLoader::includeModules($subs);
echo moduleLoader::subModuleGetPostContent($subs, $options);
