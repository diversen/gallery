<?php

$str = '';       
$str.="<div>";
$str.="<table><tr>\n";
$i = 0;
foreach ($vars['rows'] as $key => $val) {

    $domain = get_domain();
    $base_path = "/files/$domain/gallery";
   
    $image_url = "$base_path/$val[gallery_id]/$val[file_name]";
    $thumb_url = "$base_path/$val[gallery_id]/thumb-$val[file_name]";
    
    $str.="<td><a rel=\"example_group\" href=\"$image_url\"><img src=\"$thumb_url\" /></a>";
    if (session::isAdmin() && !isset($vars['no_admin'])){
        $str.= "<table><tr><td>\n"; 
        $str.= '<form name="gallery_delete_image" action="" ';
        $str.= '" method="post" enctype="multipart/form-data">';  
        $str.= '<input type="hidden" name="file_id" value="' . $val['id'] . '" />';
        $str.= '<input type="hidden" name="gallery_id" value="' . $vars['gallery_id'] . '" />';
        $str.= '<input type="submit" name="submit" value="';
        $str.= lang::system('system_submit_delete') . '" />';
        $str.= '</form>';
        $str.= "</td></tr></table>\n";
    }
    $str.="</td>\n";
    $i++;
    $t = $i % 4;
    if (!$t){
        $str.="</tr><tr>\n";
        $i = 0;
    }
}

$str.="</tr></table>\n";
if (session::isAdmin() && !isset($vars['no_admin'])){
    $str.= '<form enctype="multipart/form-data" method="post" action="" />';   
    $str.= '<input type="file" size="5" name="filename" value="Upload" />';
    $str.= '<input type="submit" name="submit" value="';
    $str.= lang::system('system_submit_upload') . '" />';
    $str.= '</form>';
}

$str.="</div>\n";
echo $str;