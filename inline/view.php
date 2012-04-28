<?php

moduleLoader::includeModule('gallery');

$gallery = new galleryInline();
$gallery->setMeta();
$gallery->displayImage();
$gallery->displaySubModules();
