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

echo $gallery->getImageSrc($id, 'full');
$row = $gallery->getImageUrl($id);
echo $link = html::createLink($row['src'], lang::translate('gallery_view_full_size'));

?>
<style type="text/css">
#form_hidden, .form_hide { display: none }
</style>
<script type="text/javascript">
$(document).ready(function()
{
    
    $(".form_show").click(function()
    {
        $("#form_hidden").show();
        $(".form_show").hide();
        $(".form_hide").show();
        return false;
    });
    $(".form_hide").click(function()
    {
        $("#form_hidden").hide();
        $(".form_show").show();
        $(".form_hide").hide();
        
        return false;
    });
});
</script>
<?php

if (session::isAdmin()) {
    //print_r($row);
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
