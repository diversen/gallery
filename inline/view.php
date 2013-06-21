<?php

moduleloader::includeModule('gallery');

$gallery = new gallery_inline();
$gallery->setMeta();
$gallery->displayImage();
$gallery->displaySubModules();
