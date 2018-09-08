<?php
require_once('vendor/autoload.php');

use CedricZiel\FlysystemGcs\GoogleCloudStorageAdapter;
use League\Flysystem\Filesystem;

        //TODO, this system should be smart enough to detect a missing bucket and create it dynamically (as nearline class storage)
        //So that you did not need to create a new bucket on https://console.cloud.google.com/storage/browser/
        //which is required to use this now..


	// a couple of low level helpers for downloading stuff..
	//made into functions on an object that understands how to work with google files
class DownloadHelper {


	public $cloud_file_list = [];
	public $bucket = '';
	public $main_dir = '';
	public $projectId = '';
	public $keyFilePath = '';

	public $adapter;
	public $filesystem;
	

	public function __construct($projectId,$bucket,$main_dir,$keyFilePath){

		if(!file_exists($keyFilePath)){
			echo "Fatal Error: $keyFilePath does not exist\n";
			exit(-1);
		}

		$this->projectId = $projectId;
		$this->bucket = $bucket;
		$this->main_dir = $main_dir;
		$this->keyFilePath = $keyFilePath;


		$adapterOptions = [
    			'projectId' 	=> $projectId,
    			'bucket'    	=> $bucket,
    			'prefix'    	=> $main_dir,
    			'keyFilePath' 	=> $keyFilePath,
		];

		$this->adapter = new GoogleCloudStorageAdapter(null, $adapterOptions);
		$this->filesystem = new Filesystem($this->adapter);

		$this->updateCloudFileList();
	}

	// get the list of files 
	public function updateCloudFileList(){

		$is_recursive = true;

		$dir_contents = $this->filesystem->listContents('/',$is_recursive);
		foreach($dir_contents AS $object){
           		//echo $object['basename'].' is located at '.$object['path'].' and is a '.$object['type']."\n";
        		$this->cloud_file_list[] = $object['basename'];
		}

	}

/*
	Downloads a file from the web to a local subdirectory.. and automatically uploads the same file up the cloud
	$sub_dir = the location of the local working directory
	$url = the url to download and then subseqeuntly mirror
	$filename = the filename to use when uploading the file, if not set defaults to the basename from pathinfo()
	$is_use_cloud_name = should we add a md5 to the filename that we upload to the cloud.. defaults to true, set this to false to make the "current" version of files...

	return integer compatible with the overall EBB return strategy
		-1 for an error
		0 for file has not changed
		1 for new file with new data
	
*/
	public function mirror_that($sub_dir,$url,$filename = null,$is_use_cloud_name = true){
	
		if(is_null($url)){
			echo "Error: you tried to run mirror_that with a null instead of a url\n";
			exit(-1);
		}

		if(is_array($url)){
			echo "Error: you tried to run mirror_that with an array instead of a url...\n";
			var_export($url);
			echo "Error: you tried to run mirror_that with an array instead of a url...\n";
			exit(-1);
		}

		if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
    			echo "$url is Not a valid URL\n";
			exit(-1);
		}

		
	
		if(is_null($filename)){
			//lets try to calculate it from the url...
			$parseurl = parse_url($url);
			$path = $parseurl['path'];
			$pathinfo = pathinfo($path);
			$filename = $pathinfo['basename'];
		}


             	$local_tmp_file = __DIR__ . "/data/$filename"; //this is where we put the local copy...
             	$is_downloaded = self::downloadFile($url,$local_tmp_file); //call our curl download function, which will save the file into the local copy
              	if($is_downloaded){
                	$local_cloud_file = $this->rename_local_file_to_cloud_version($local_tmp_file); //get a version of the file that is dated with an md5 string
			if($is_use_cloud_name){
                     		$cloud_file_name = pathinfo($local_cloud_file,PATHINFO_BASENAME);
			}else{
                     		$cloud_file_name = $filename; //just use the name as written
			}
                     	if(!in_array($cloud_file_name,$this->cloud_file_list)){
         			//this means we have at least one new data file..
          			$is_new_data = true;
            			echo "Uploading $filename to $cloud_file_name from $local_cloud_file!!\n";
           			$cloud_file_contents = file_get_contents($local_cloud_file); //load the data into php memory...
            			$cloud_file_path = "$sub_dir/$cloud_file_name";
          			$this->filesystem->write($cloud_file_path,$cloud_file_contents); //savve the file to google cloud.
				return(1);
          		}else{
            			echo "The $filename is redundant to a file already in the cloud... doing nothing...\n";
				return(0);
              		}
           	}else{
			return(-1);
             		echo "Could not download $url\n";
          	}

	}

	//accepts a local file name like something.zip and copies it to a new file named with the 
	//calculate_cloud_file_name function and erases the old file... 
	public function rename_local_file_to_cloud_version($local_file){

		if(!file_exists($local_file)){
			echo "Error: trying to run rename_local_file_to_cloud_version function on $local_file and it does not exist\n";
			exit(-1);
		}	

		if(is_dir($local_file)){
			echo "Error: trying to run rename_local_file_to_cloud_version function on $local_file and it is a directory, this does not work on directories\n";
			exit(-1);	
		}	

		$cloud_file_name = $this->calculate_cloud_file_name($local_file);
	
		$pathinfo = pathinfo($local_file);
		$dirname = $pathinfo['dirname'];
		$new_full_path = "$dirname/$cloud_file_name";

		rename($local_file,$new_full_path);
		if(file_exists($new_full_path)){
			return($new_full_path);
		}else{
			echo "Error: I tried to move $local_file to $new_full_path in rename_local_file_to_cloud_version() and the new file does not exist\n";
			exit(-1);
		}

	}

//we need a reliable way to translate a file name into a dated and md5ed version of itself. 
//this function handles that file name transition...
//something.ASDFAERWADASDADSAD.2001-03-10.zip where 
//the form is original_file_name.MD5SumOfFile.todaysMySQLFormatDate.original_file_type
//then erases the old file. It is smart enough to do this in the same folder as the original file...
	public function calculate_cloud_file_name($local_file){

		$pathinfo = pathinfo($local_file);
		$file_name_first_part = $pathinfo['filename'];
		$file_extension = $pathinfo['extension'];
		$my_md5 = md5_file($local_file);
		$mysql_today_datestring = date("Y-m-d");


		$new_last_name = "$my_md5.$mysql_today_datestring.$file_extension"; 
		//we technically do not know how many characters this is, because $file_extension could be very long...

		$max_total_string_len = 500; //this will keep us well within the 1024 byte name limit for google files
					//https://cloud.google.com/storage/docs/naming

		$tail_strlen = strlen($new_last_name);	
		$left_over_space = $max_total_string_len - $tail_strlen;
		if(strlen($file_name_first_part) > $left_over_space){
			$new_first_name = substr($file_name_first_part,0,$left_over_space);
		}else{
			$new_first_name = $file_name_first_part;
		}

		$cloud_file_name = "$new_first_name.$new_last_name";

		return($cloud_file_name);
	
	}

//we need a way to look at the list of files that are already in a cloud directory, 
//and see if the file that we downloaded today ($current_file_name) is different than previously seen files...
//note if you have files stored in the cloud that are not using the cloud naming scheme... this will break...
//this version uses the actual md5 of a downloaded file to check and seee...
	public function is_downloaded_file_in_cloud_list($downloaded_file_name){
	


		$current_md5 = md5_file($downloaded_file_name);

		foreach($this->cloud_file_list as $this_cloud_file){
			$file_name_dot_array = explode('.',$this_cloud_file);
			$extension = array_pop($file_name_dot_array);
			$mysql_date = array_pop($file_name_dot_array);
			$file_md5 = array_pop($file_name_dot_array);
			if($current_md5 == $file_md5){
				return(true);
			}
		
		}

		return(false);

	}

//we need a way to look at the list of files that are already in a cloud directory, 
//this version just checks to see that there is a file with the same name in the file list...
	public function is_filename_in_cloud_list($filename){
	
		//we need to see what this filename would be translated to, given our 500 character limit...
		$max_length = 500;
		$file_parts = explode('.',$filename);
		$file_extension = array_pop($file_parts);
		$first_name = implode('.',$file_parts);
	
		$md5_length = 32; //always
		$mysqldate_length = 10; //always... well until the year 10000
		$tail_length = $md5_length + $mysqldate_length + strlen($file_extension);
		$max_len_for_first_name = $max_length - $tail_length;
		if(strlen($first_name) > $max_len_for_first_name){
			$new_first_name = substr($first_name,0,$max_len_for_first_name);
		}else{
			$new_first_name = $first_name;
		}

	

		foreach($this->cloud_file_list as $this_cloud_file){
			$file_name_dot_array = explode('.',$this_cloud_file);
			$extension = array_pop($file_name_dot_array);
			$mysql_date = array_pop($file_name_dot_array);
			$file_md5 = array_pop($file_name_dot_array);
			$cloud_first_name = implode('.',$file_name_dot_array);
			if($cloud_first_name == $new_first_name){
				//then there is a cloud file that has the same name as this file name would if it had been uploaded...
				return(true);
			}	
		}

		return(false);

}





// modified from https://stackoverflow.com/a/35271138/144364
//accepts a url to download, and a local file path to save the data to...
//returns true if the file exists and it is not a 404 result...
	public static function downloadFile($url, $filepath){

	    	$fp = fopen($filepath, 'w+');
     		$ch = curl_init($url);

     		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
     		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
     		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
     		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
     		curl_setopt($ch, CURLOPT_FILE, $fp);
     		curl_exec($ch);


        	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        	if($httpCode == 404) {
			echo "Error: got 404\n";
               	 	return(false);
        	}

     		curl_close($ch);
     		fclose($fp);

		if(filesize($filepath) > 0){
			return(true);
		}else{
			echo "Error: download file size was zero trying to save $url to $filepath \n";
			return(false);
		}
 	}




}
