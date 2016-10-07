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
//        if (!array_key_exists("files", $_FILES)) {
//            echo "Not an upload.";
//            return;
//        }
        
        $name = $this->input->cookie("name");
        error_log("Got upload request from " . $name);
        
        $index = $this->getNumberOfUploads()+1;
        $filename = strval($index);
        $target_file = $target_dir . $filename;
        $this->setNameIndex($name, $index);
        
        if ($this->input->post("image") == null) {
            echo "Not a post";
            return;
        }
        list(, $b64Data) = explode(",", $this->input->post("image"));
        $data = base64_decode($b64Data);
        
        $ifp = fopen($target_file, "wb"); 
        fwrite($ifp, $data); 
        fclose($ifp); 

        error_log(file_put_contents($target_file, $data));
        echo '{"status":"success","index":"'.$filename.'"}';
    }
    
    public function nameForIndex() {
        $index = $this->input->post("imageIndex");
        if ($index == null) {
            echo "Not a pull";
            return;
        }
        echo $this->getNameForIndex($index);
    }
    
    public function getMaxUploader() {
        $map = $this->loadNameIndexMap();
        $c = array();
        $max = 0;
        $maxName = "";
        foreach ($map as $key=>$val) {
            $c[$val] = array_key_exists($val, $c) ? $c[$val]+1 : 1; 
            if ($c[$val] > $max) {
                $max = $c[$val];
                $maxName = $val;
            }
        }
        echo '{"name":"'.$maxName.'", "count":"'.$max.'"}';
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
