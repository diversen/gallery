<?php


include_model('tags');
include_model('gallery/inline');

$id = uri::getInstance()->fragment(2);
$tag = tags::getTagSingle($id);
        
if (empty($tag)) {
    moduleLoader::$status['404'] = true;
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
        
headline_message($headline);

$num_rows = tags::getAllReferenceTagNumRows('gallery', $id);

include_once "pearPager.php";
$per_page = 10;
$pager = new pearPager($num_rows, $per_page);
        
$references = tags::getAllReferenceTag ('gallery', $id, $pager->from, $per_page);
        
$q = new QBuilder();
$q->setSelect('gallery');
$q->filterIn("id IN ", $references);
$q->order('updated', 'DESC');

$rows = $q->fetch();
//print_r($rows); die;

foreach ($rows as $key => $val){
    $val['title'] = $val['title'];
    $link = html::createLink("/gallery/view/$val[id]", $val['title']);
    headline_message($link);

    echo galleryInline::displaySingleRow($val['id']);
    echo $val['description'] . "<br />\n";
            
    echo galleryAdmin::adminOptions($val['id']);
            
    echo "<hr />\n";    
}

echo "<br />\n";
$pager->pearPage();
