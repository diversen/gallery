<?php

namespace modules\gallery\fancybox;

use diversen\conf;
use diversen\template;
use diversen\session;
use diversen\template\assets;

use modules\gallery\admin\module as adminModule;

function fancybox_include() {

    // no cache css - as we just keep the orginal image paths.
    assets::setRelAsset('css', '/templates/fancyBox/source/jquery.fancybox.css', null, array(
        'head' => true,
        'no_cache' => true));
    assets::setRelAsset('js', '/templates/fancyBox/source/jquery.fancybox.js', null, array('head' => true));
}

fancybox_include();

assets::setInlineJs(conf::pathModules() . '/gallery/fancybox.js');


class module {

    /**
     * method for getting files
     *
     * @param   array   array from a db query $rows
     * @param   array    opt unused so far
     * @return  string  html displaying files connect to article.
     * 
     * 
     */
    public static function displayGallery($vars) {

        $str = '';
        $num_rows = count($vars['rows']);
        if ($num_rows == 0) {
            // no images
            $str.= self::getUploadForm($vars);
        } else {
            // more than one image
            if (conf::getModuleIni('gallery_use_default_image')) {
                $str.= self::getDefaultImage($vars['options']['default']);
            }
            $str.= self::getRows($vars);
            $str.= self::getUploadForm($vars);
        }
        return $str;
    }

    public static function getRows($vars, $options = null) {

        $str = '';
        $str.= "<div id=\"gallery_thumbs\">\n";
        $str.= "<table><tr>\n";
        $i = 0;

        $per_row = conf::getModuleIni('gallery_image_row_size');
        foreach ($vars['rows'] as $val) {

            $domain = conf::getDomain();
            $base_path = "/files/$domain/gallery";

            $image_url = "$base_path/$val[gallery_id]/full-$val[file_name]";
            $thumb_url = "$base_path/$val[gallery_id]/thumb-$val[file_name]";

            $str.="<td><a rel=\"gallery_group\" href=\"$image_url\" class=\"fancybox\"><img src=\"$thumb_url\" /></a>";
            $str.=adminModule::getAdminOptions($val, $vars);

            $str.="</td>\n";
            $i++;
            $t = $i % $per_row;
            if (!$t) {
                $str.="</tr><tr>\n";
                $i = 0;
            }
        }

        $str.="</tr></table>\n";
        $str.="</div>\n";
        return $str;
    }

    public static function getDefaultImage($row) {
        $str = "<div id=\"imageview\">\n";
        $str.= "<img src=\"/files/default/gallery/$row[gallery_id]/med-$row[file_name]\" alt=\"example\" />";
        $str.= "</div>";
        return $str;
    }

    public static function getUploadForm($vars) {
        return adminModule::uploadForm($vars);
    }

    public static function displayAll() {
        $gallery = new adminModule();
        $galleries = $gallery->getAllGallery();
        foreach ($galleries as $val) {
            adminModule::displayTitle($val);
            echo "<br />";
            echo $val['description']; 
            echo "<hr />\n";
            if (session::authIni('gallery_allow_edit', false)) {
                echo adminModule::adminOptions($val['id']);
                echo "<hr />\n";
            }
            
        }
    }

}
