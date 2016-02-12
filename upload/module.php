<?php

namespace modules\gallery\upload;

use diversen\conf;
use diversen\db;
use diversen\file;
use diversen\html;
use diversen\http;
use diversen\lang;
use diversen\log;
use diversen\session;
use diversen\strings;
use diversen\upload;
use modules\gallery\admin\module as admin;
use modules\gallery\module as gallery;
use ZipArchive;

class module {
    
    public $errors = array ();
    public function form () {
        
        $values = html::specialEncode($_POST);

        html::formStart('gallery_upload');
        html::init($values, 'submit');
        $legend = lang::translate('Upload zip file');
        html::legend($legend);
        html::label('title', lang::translate('Title'));
        html::text('title');
        html::label('image_add', lang::translate('Rename files after following title'));
        html::text('image_add');
        html::label('description', lang::translate('Abstract'));
        html::textareaSmall('description');        
        
        html::fileWithLabel('file', conf::getModuleIni('gallery_zip_max'));
        html::submit('submit', lang::translate('Update'));
        html::formEnd();
        $str= html::getStr();
        return $str;
    }
    
    public function indexAction() {

        set_time_limit(0);


        if (!empty($_POST)) {
            $res = $this->uploadFile();
            if (!empty($this->errors)) {
                echo html::getErrors($this->errors);
            } else {
                session::setActionMessage(lang::translate('Zip archive uploaded'));
                http::locationHeader('/gallery/index');
            }
        }

        echo $this->form();
    }

    public function extractZip ($zip, $unzipped) {
        $arch = new ZipArchive;
        $res = $arch->open($zip);
        if ($res === TRUE) {
            $res = $arch->extractTo($unzipped);
            if (!$res) {
                log::error("could not unzip $zip");
                return false;
            }
            $arch->close();
        } 
        return true;
    }
    
    
    public function uploadFile () {

        if (isset($_POST['submit']) && isset($_FILES['file'])) {

            $res = upload::checkUploadNative($_FILES['file']);
            if (!$res) {
                $this->errors = upload::$errors;
                return false;
            }
            $res = upload::checkMaxSize($_FILES['file'], conf::getModuleIni('gallery_zip_max'));
            if (!$res){
                $this->errors = upload::$errors;
                return false;
            }
            
            $db = new db();            
            $gal = new admin();
            $id = $gal->createGallery();
            
            $tmp_dir = sys_get_temp_dir();
            $zip = $tmp_dir . '/' . escapeshellcmd($_FILES['file']['name']);

            $res = copy (escapeshellcmd($_FILES['file']['tmp_name']) , $zip);
            if (!$res) {
                $this->errors[] = lang::translate('Could not move files');
                return false;
            }

            $md5 = md5(uniqid());
            $unzipped = $tmp_dir . '/' . "$md5";
            
            $res = $this->extractZip ($zip, $unzipped);
            if (!$res ){
                $this->errors[] = lang::translate('Could not set correct permissions on files');
                return false;
            }
           
            $info = pathinfo($unzipped);   
            $dir = $info['dirname'] . '/'  . $info['filename'];
            $files = file::scandirRecursive($dir);

            // rename all files from given pattern
            if (!empty($_POST['image_add'])) {
                
                $i = 0;
                foreach ($files as $key => $file) {
                    
                    $info = pathinfo($file);
                    $oldname = $file;
                    $image_add = strings::sanitizeUrlRigid($_POST['image_add']); 
                    $image_add = str_replace(' ', '-', $image_add);
                    $files[$key] = $name = $image_add . "-" . "$i.$info[extension]";
                    $newname = "$dir/" . $name;
                    $res = rename($oldname, $newname);
                    if (!$res) {
                        $this->errors[] = lang::translate('Could not rename files');
                        return false;
                    }
                    $i++;
                }
            } else {
                $i = 0;
                foreach ($files as $key => $file) {
                    $info = pathinfo($file);

                    $oldname = $file;
                    $files[$key] =  $name = "$info[filename].$info[extension]";
                    $newname = "$dir/" . $name;
                    $res = rename($oldname, $newname);
                    if (!$res) {
                        $this->errors[] = lang::translate('Could not rename files');
                        return false;
                    }
                
                    $i++;
                }
            }
           
            $db->begin();
            $sizes = array (
                    'thumb' => 'gallery_thumb_size', 
                    'full' =>  'gallery_image_size', 
                    'med' =>   'gallery_med_size', 
                    'small' => 'gallery_small_size'
            );
                    
            // scale images into different sizes
            foreach ($files as $file) {
                
                $did_scale = null;
                foreach ($sizes as $key => $size) { 
                    $scaled = $dir . "/$key-$file";
                    
                    $full_name = "$dir/$file";
                    $did_scale = gallery::scaleImage(
                            $full_name, 
                            $scaled, 
                            conf::getModuleIni($size)
                        );
                    if (!$did_scale) { 
                        continue;
                    }
                    
                }
                if ($did_scale) {
                    $res = $db->insert('gallery_file', 
                            array (
                                'file_name' => $file, 
                                'title' => $file ,
                                'gallery_id' => $id)
                        );
                }
            }
            
            // create web dir
            if ($id) {
                $domain = conf::getDomain();
                $path = conf::pathHtdocs() . "/files/$domain/gallery/$id";
                @mkdir ($path, 0777, true);
            } else {
                return false;
            }

            // move dir and 
            $res = rename($dir, $path);            
            if (!$res) {
                $this->errors[] = lang::translate('Could not move files');
                return false;
            }
            
            $res = $db->commit();
            if (!$res) {
                $this->errors[] = lang::translate('Could not commit to database');
                return false;
            } 
            return true;
        }
    }
}
