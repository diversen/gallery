<?php

moduleLoader::includeModule('gallery');
$gallery = new gallery();

$id = uri::getInstance()->fragment(3);

$row = null;
if (!$id) {
    http::permMovedHeader('/gallery/index');
    return;
}
$row = $gallery->getSingleImage($id);

if (empty($row)) {
    http::permMovedHeader('/gallery/index');
    return;
}


$title = rawurldecode($row['file_name']);
if (!empty($row['title'])) {
    $title.= MENU_SUB_SEPARATOR;
    $title.= htmlspecialchars($row['title']);
}

$title.= MENU_SUB_SEPARATOR;
$title.= lang::translate('gallery_view_full_size');
template::setTitle($title);

if (!empty($row['description'])) {
    template::setMeta(array('description' => htmlspecialchars($row['description'])));
}

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
