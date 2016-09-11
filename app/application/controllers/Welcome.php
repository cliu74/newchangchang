<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$this->load->view('welcome_message');
	}
    
    // Return number of uploaded pics
    public function numberOfUploads()
    {
        echo $this->getNumberOfUploads();
    }
    
    // Given index in POST, return the number
    public function getUpload()
    {
        $index = $this->input->post("imageIndex");
        if ($index == null) {
            echo "Not a POST";
            return;
        }
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/app/wed-upload/";
        $target_file = $target_dir . $index;
        if (file_exists($target_file)) {
            error_log("Returning file: " . $target_file);
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($target_file));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($target_file));
            error_log(readfile($target_file));
            exit;
        } else {
            echo "File not exist";
        }
    }
    
    public function upload()
    {
        error_log('Uploading photos');
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/app/wed-upload/";
        
        // Stop processing if files is not in the array
        if (!array_key_exists("files", $_FILES)) {
            echo "Not an upload.";
            return;
        }
        
        $name = $this->input->cookie("name");
        error_log("Got upload request from " . $name);
        
        $uploadOk = 1;
        $imageFileType = pathinfo($_FILES["files"]["name"][0],PATHINFO_EXTENSION);
        $filename = strval($this->getNumberOfUploads()+1);
        $target_file = $target_dir . $filename;
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["files"]["tmp_name"][0]);
        if($check !== false) {
            error_log('File is an image: ' . $check["mime"] . ", filePath: " . $_FILES["files"]["tmp_name"][0]);
            $uploadOk = 1;
        } else {
            error_log('File is not an image' . $_FILES["files"]["tmp_name"][0]);
            $uploadOk = 0;
        }
        // Check if file already exists
        if (file_exists($target_file)) {
            error_log("Sorry, file already exists");
            $uploadOk = 0;
        }
        // Check file size
        if ($_FILES["files"]["size"][0] > 5000000) {
            error_log("Sorry, your file is too large" . (string)$_FILES["files"]["size"][0]);
            $uploadOk = 0;
        }
        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
            error_log("Sorry, only JPG, JPEG, PNG & GIF files are allowed: " . $imageFileType);
            $uploadOk = 0;
        }
        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            error_log("Sorry, your file was not uploaded");
        // if everything is ok, try to upload file
        } else {
            if (move_uploaded_file($_FILES["files"]["tmp_name"][0], $target_file)) {
                error_log("The file ". basename($_FILES["files"]["name"][0]). " has been uploaded.");
                echo '{"status":"success","index":"'.$filename.'"}';
                $this->setNameIndex($name, $filename);
            } else {
                error_log("Sorry, there was an error uploading your file to: " . $target_file);
                error_log(print_r($_FILES["files"], true)); // Dump files variable for debugging
            }
        }
    }
    
    public function nameForIndex() {
        $index = $this->input->post("imageIndex");
        if ($index == null) {
            echo "Not a pull";
            return;
        }
        echo $this->getNameForIndex($index);
    }
    
    private function getNumberOfUploads()
    {
        $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/app/wed-upload/";
        $contents = scandir($target_dir);
        if (!$contents) {
            error_log("Failed to read target directory: " . $target_dir);
            return 0;
        } else {
            return count($contents)-2;
        }
    }
    
    private function setNameIndex($name, $index) {
        $map = $this->loadNameIndexMap();
        $map[strval($index)] = $name;
        $t = json_encode($map);
        $myfile = fopen($_SERVER['DOCUMENT_ROOT'] . "/app/name-index.txt", "w") or die("Unable to open file!");
        fwrite($myfile, $t);
        fclose($myfile);
    }
    
    private function getNameForIndex($index) {
        $map = $this->loadNameIndexMap();
        if (!array_key_exists(strval($index), $map)) {
            return "";
        }
        return $map[strval($index)];
    }
    
    private function loadNameIndexMap() {
        $file = $_SERVER['DOCUMENT_ROOT'] . "/app/name-index.txt";
        error_log("file path:" . $file);
        if (!file_exists($file)) {
            return array();
        }
        $myfile = fopen($file, "r") or die("Unable to open file!");
        $t = fread($myfile,filesize($file));
        fclose($myfile);
        
        return json_decode($t, true);
    }
}
