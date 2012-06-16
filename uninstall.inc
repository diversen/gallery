<?php

function gallery_uninstall () {
    $domain = config::getDomain();
    $dir = _COS_PATH . "/htdocs/files/$domain/gallery";    
    rrmdir($dir);
}