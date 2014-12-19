<?php

use diversen\upload;
use diversen\file;
use diversen\strings;
use diversen\html;

if (!session::checkAccessFromModuleIni('gallery_allow_edit')){
    return;
}

moduleloader::includeModule ('gallery/admin');

class galleryUpload {
    
    public $errors = array ();
    public function form () {
        
        $values = html::specialEncode($_POST);

        html::formStart('gallery_upload');
        html::init($values, 'submit');
        $legend = lang::translate('Upload zip file');
        html::legend($legend);
        html::label('title', lang::system('system_form_label_title'));
        html::text('title');
        html::label('image_add', lang::translate('Rename files after following title'));
        html::text('image_add');
        html::label('description', lang::system('system_form_label_abstract'));
        html::textareaSmall('description');
        
        event::triggerEvent(
            config::getModuleIni('content_article_events'), 
                array(
                    'action' => 'form',
                    'reference' => 'gallery',
                    /*'parent_id' => $vars['id']*/)
        );
        
        
        html::fileWithLabel('file', config::getModuleIni('gallery_zip_max'));
        html::submit('submit', lang::system('system_submit_update'));
        html::formEnd();
        $str= html::getStr();
        return $str;
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
            
            $res = upload::checkUploadNative('file');
            if (!$res) {
                $this->errors = upload::$errors;
                return false;
            }
            $res = upload::checkMaxSize('file', config::getModuleIni('gallery_zip_max'));
            if (!$res){
                $this->errors = upload::$errors;
                return false;
            }
            
            $db = new db();            
            $gal = new gallery_admin();
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
                            config::getModuleIni($size)
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
                $domain = config::getDomain();
                $path = _COS_HTDOCS . "/files/$domain/gallery/$id";
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

set_time_limit(0);
$gal = new galleryUpload();

if (!empty($_POST)) {
    $res = $gal->uploadFile();
    if (!empty($gal->errors)) {
        html::errors($gal->errors);
    } else {
        session::setActionMessage(lang::translate('Zip archive uploaded'));
        http::locationHeader('/gallery/index');
    }
    
}

echo $gal->form();
