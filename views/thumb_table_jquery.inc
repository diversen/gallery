<?php

$str = '';
//$num_rows = $this->getNumImagesGallery();
       
$str.="<div>";
$str.="<table><tr>\n";
$i = 0;
foreach ($vars['rows'] as $key => $val) {

    $image_url = "/files/gallery/$val[gallery_id]/$val[file_name]";
    $thumb_url = "/files/gallery/$val[gallery_id]/thumb-$val[file_name]";
    
    $str.="<td><a rel=\"example_group\" href=\"$image_url\" ><img src=\"$thumb_url\" /></a>";
    if (session::isAdmin() && !isset($vars['no_admin'])){
            //$str.= '<td>';
            $str.= '<form action="/gallery/delete/' . $vars['gallery_id'] . '/' . $val['id'];
            $str.= '" method="post" name="test" enctype="1" >';
            $str.= '<input type="submit" name="submit" value="';
            $str.= lang::translate('Delete') . '" />';
            $str.= '</form>';
            //$str.= '</td>';
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
    $str.= '<table>';
    $str.= '<tr><td>';
    $str.= '<form action="/gallery/view/' . $vars['gallery_id'];
    $str.= '" method="post" name="test" enctype="multipart/form-data" >';
    $str.= '<input type="file" size="5" name="filename" value="Upload" />';
    $str.= "</td><td>\n";
    $str.= '<input type="submit" name="submit" value="';
    $str.= lang::translate('Upload image') . '" />';
    $str.= '</form>';
    $str.= '</td>';
    $str.="</tr></table>\n";
}

$str.="</div>\n";
echo $str;