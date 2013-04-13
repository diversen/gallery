<?php


moduleloader::includeModel('tags');
moduleloader::includeModel('gallery/inline');

$id = uri::getInstance()->fragment(2);
$tag = tags::getTagSingle($id);
        
if (empty($tag)) {
    moduleloader::$status['404'] = true;
    return;
}
        
$url = strings::utf8Slug("/gallery/tags/$tag[id]", $tag['title']);
http::permMovedHeader($url);

$title = lang::translate('gallery_tags_list_entries_title');
$title.= MENU_SUB_SEPARATOR;
$title.= $tag['title'];

template::setTitle($title);
        
$headline = lang::translate('gallery_tags_list_entries_title');
$headline.= MENU_SUB_SEPARATOR_SEC;
$headline.= $tag['title'];
        
html::headline($headline);

$num_rows = tags::getAllReferenceTagNumRows('gallery', $id);

include_once "pearPager.php";
$per_page = 10;
$pager = new pearPager($num_rows, $per_page);
        
$references = tags::getAllReferenceTag ('gallery', $id, $pager->from, $per_page);
$rows = db_q::setSelect('gallery')->
        filterIn("id IN ", $references)->
        order('updated', 'DESC')->
        fetch();

$gallery = new gallery();
foreach ($rows as $key => $val){
    $val['title'] = $val['title'];
    $link = html::createLink("/gallery/view/$val[id]", $val['title']);
    html::headline($link);
    $date_formatted = time::getDateString($val['updated']);
    echo user::getProfileSimple($val['user_id'], $date_formatted);
    if (config::getModuleIni('gallery_preview_display_all')) {
        $rows = $gallery->getAllFileInfo($val['id']);
        $options = array ('gallery_id' => $val['id'], 'no_admin' => true);
        $vars['rows'] = $rows; 
        $vars['options'] = $options;
        echo $str = galleryInline::getRows($vars, $options);
    } else {
        echo self::displaySingleRow($val['id']);
    }
    echo $val['description'] . "<br />\n";
    echo galleryAdmin::adminOptions($val['id']);
    echo "<hr />\n";    
}

echo "<br />\n";
$pager->pearPage();
