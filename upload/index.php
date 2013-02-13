<?php


if (!session::checkAccessControl('gallery_allow_edit')){
    return;
}

include_once "coslib/upload.php";
moduleloader::includeModule ('gallery/admin');

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
        
        
        html::fileWithLabel('file', config::getModuleIni('gallery_zip_max'));
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
            $res = upload::checkMaxSize('file', config::getModuleIni('gallery_zip_max'));
            if (!$res){
                $this->errors = upload::$errors;
                return false;
            }
            
            $db = new db();            
            $gal = new galleryAdmin();
            $id = $gal->createGallery();
            
            if ($id) {
                $domain = config::getDomain();
                $path = _COS_HTDOCS . "/files/$domain/gallery/$id";
                @mkdir ($path, 0777, true);
            } else {
                return false;
            }
            
            $zip = "/tmp/" . escapeshellcmd($_FILES['file']['name']);
            //$zip = "/tmp/" . escapeshellcmd($_FILES['file']['name']);
            $command = "mv " . escapeshellcmd($_FILES['file']['tmp_name']) . " $zip";
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
            $md5 = md5(uniqid());
            $unzipped = "/tmp/$md5";
            $command = "unzip -o -UU -d $unzipped " . $zip;
            exec ($command, $output = array (), $res);
            if ($res) {
                $this->errors[] = lang::translate('galler_error_unzip');
                return false;
            }
           

            $info = pathinfo($unzipped);   
            $dir = $info['dirname'] . "/"  . $info['filename'];
            $files = file::scandirRecursive($dir);

            if (!empty($_POST['image_add'])) {
                // rename files
                
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
                        $this->errors[] = lang::translate('gallery_zip_could_not_rename_file');
                        return false;
                    }
                    $i++;
                }
            }
           
            $db->begin();


            foreach ($files as $file) {
                $sizes = array (
                    'thumb' => 'gallery_thumb_size', 
                    'full' => 'gallery_image_size', 
                    'med' => 'gallery_med_size', 
                    'small' => 'gallery_small_size');
                $did_scale = null;
                foreach ($sizes as $key => $size) { 
                    
                    $scaled = $dir . "/$key-$file";
                    $did_scale = gallery::scaleImage(
                            "$dir/$file", 
                            $scaled, 
                            config::getModuleIni($size)
                        );
                    if (!$did_scale) continue;
                    
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
            
            $dest = _COS_HTDOCS . "/files/$domain/gallery/$id";
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
        html::errors($gal->errors);
    } else {
        session::setActionMessage(lang::translate('gallery_zip_archive_uploaded'));
        http::locationHeader('/gallery/index');
    }
    
}

echo $gal->form();

