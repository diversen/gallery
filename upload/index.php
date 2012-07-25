<?php

include_once "coslib/upload.php";
include_module ('gallery/admin');

class galleryUpload {
    
    public $errors = array ();
    public function form () {
        $values = html::specialEncode($values);

        html::formStart('gallery_upload');
        html::init($values, 'submit');
        $legend = lang::translate('gallery_upload_zip_legend');
        html::legend($legend);
        html::label('title', lang::system('system_form_label_title'));
        html::text('title');
        html::label('image_add', lang::translate('gallery_label_zip_add_chars'));
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
        
        
        html::fileWithLabel('file', get_module_ini('gallery_zip_max'));
        html::submit('submit', lang::system('system_submit_update'));
        html::formEnd();
        $str= html::getStr();
        return $str;
    }
    
    
    
    
    
    public function uploadFile () {
       
        if (isset($_POST['submit']) && isset($_FILES['file'])) {
            
            $res = upload::checkUploadNative('file');
            if (!$res) {
                $this->errors = upload::$errors;
                return false;
            }
            $res = upload::checkMaxSize('file', get_module_ini('gallery_zip_max'));
            if (!$res){
                $this->errors = upload::$errors;
                return false;
            }
            
            $db = new db();
            $db->begin();
            $gal = new galleryAdmin();
            
            $id = $gal->createGallery();
            
            if ($id) {
                $domain = config::getDomain();
                $path = _COS_PATH . "/htdocs/files/$domain/gallery/$id";
                @mkdir ($path, 0777, true);
            } else {
                return false;
            }
            
            $zip = "/tmp/" . $_FILES['file']['name'];
            $command = "mv " . $_FILES['file']['tmp_name'] . " $zip";
            //die;
            exec ($command, $output = array (), $res);
            if ($res) {
                $this->errors[] = lang::translate('gallery_error_zip_mv');
                return false;
            }
            
            $command = "chmod 777 " . $zip;
            exec ($command, $output = array (), $res);
            if ($res) {
                $this->errors[] = lang::translate('gallery_error_zip_chmod');
                return false;
            }
            
            chdir("/tmp");
            $command = "unzip -o -UU " . $zip;
            exec ($command, $output = array (), $res);
            if ($res) {
                $this->errors[] = lang::translate('galler_error_unzip');
                return false;
            }
           

            $info = pathinfo($zip);            
            $dir = $info['dirname'] . "/"  . $info['filename'];
            $files = scandir($dir);
            unset($files[0], $files[1]);
            //print_r($files); die;
            if (!empty($_POST['image_add'])) {
                // rename files
                
                $i = 0;
                foreach ($files as $key => $file) {
                    $info = pathinfo($file);
                    $oldname = "$dir/$file";
                    $image_add = strings::sanitizeUrlRigid($_POST['image_add']); 
                    $image_add = str_replace(' ', '-', $image_add);
                    $files[$key] = $name = $image_add . "-" . "$i.$info[extension]";
                    $newname = "$dir/" . $name;
                    $res = rename($oldname, $newname);
                    if (!$res) {
                        $this->errors[] = lang::translate('gallery_zip_could_not_rename_file');
                        return false;
                    }
                    $i++;
                }
            }
            
            //$files = scandir($dir);
            //unset($files[0], $files[1]);

            foreach ($files as $file) {
                $sizes = array (
                    'thumb' => 'gallery_thumb_size', 
                    'full' => 'gallery_image_size', 
                    'med' => 'gallery_med_size', 
                    'small' => 'gallery_small_size');
                foreach ($sizes as $key => $size) { 
                    
                    $scaled = $dir . "/$key-$file";
                    $res = gallery::scaleImage(
                            "$dir/$file", 
                            $scaled, 
                            config::getModuleIni($size)
                        );

                    if (!$res) {
                        $this->errors[] = lang::translate('gallery_zip_error_scale');
                        $db->rollback();
                        return false;
                    }
                    
                }
                $res = $db->insert('gallery_file', 
                            array (
                                'file_name' => $file, 
                                'title' => $file ,
                                'gallery_id' => $id)
                        );
            }
            
            $dest = _COS_PATH . "/htdocs/files/$domain/gallery/$id";
            exec("mv $dir/* $dest", $output, $res);
            exec("rm -Rf $dir*");
            if ($res) {
                $this->errors[] = lang::translate('gallery_zip_mv_error');
                //die('could not mv file');
                return false;
            }
            
            $res = $db->commit();
            if (!$res) {
                $this->errors[] = lang::translate('gallery_zip_commit_error');
                //$db->rollback();
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
        view_form_errors($gal->errors);
    } else {
        session::setActionMessage(lang::translate('gallery_zip_archive_uploaded'));
        http::locationHeader('/gallery/index');
    }
    
}

echo $gal->form();
