<?php

moduleloader::includeModule('gallery');

$gallery = new galleryInline();
$gallery->setMeta();
$gallery->displayImage();
$gallery->displaySubModules();
