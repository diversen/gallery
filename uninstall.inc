<?php

/**
 * remove any gallery files when uninstalling gallery
 */
function gallery_uninstall () {
    $domain = config::getDomain();
    $dir = _COS_HTDOCS . "/files/$domain/gallery";
    if (file_exists($dir)) {
        file::rrmdir($dir);
    }
}
