<?php
require_once('vendor/autoload.php');

use Google\Cloud\Storage\StorageClient;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

        //TODO, this system should be smart enough to detect a missing bucket and create it dynamically (as nearline class storage)
        //So that you did not need to create a new bucket on https://console.cloud.google.com/storage/browser/
        //which is required to use this now..


	// a couple of low level helpers for downloading stuff..
	//made into functions on an object that understands how to work with google files
class EbbHelper {


	public $cloud_file_list = [];
	public $bucket = '';
	public $main_dir = '';
	public $projectId = '';
	public $keyFilePath = '';

	public $adapter;
	public $filesystem;

	private $is_debug = false;	

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

		putenv("GOOGLE_APPLICATION_CREDENTIALS=$keyFilePath");

		$storageClient = new StorageClient([
    			'projectId' => $projectId,
		]);

		$_ENV['SUPPRESS_GCLOUD_CREDS_WARNING'] = true;

		$bucket = $storageClient->bucket($bucket);
		$this->adapter = new GoogleStorageAdapter($storageClient, $bucket);
		$this->filesystem = new Filesystem($this->adapter);

		$this->updateCloudFileList();
	}

	// get the list of files for this bucket and path..
	// and store it locally... 
	public function updateCloudFileList(){

		$is_recursive = true;

		$dir_contents = $this->filesystem->listContents('/',$is_recursive);
		foreach($dir_contents AS $object){
           		//echo $object['basename'].' is located at '.$object['path'].' and is a '.$object['type']."\n";
        		$this->cloud_file_list[] = $object['basename'];
		}

	}

	/*
		Lets you download the latest version of a file in any sub-path (in the bucket and underneath the prefix)
		to a local directory of your preference..

		if they download file is a zip/tar/gzip/etc file... it will extract the contents to the directory you specified. 

		This understands the cloud files, if you have multiple types of files in the sub-dir you specify..
		It will download the latest version of all of the files..

		arguments:
		$sub_path - the sub-directory (underneath the bucket and prefix) that you want to download from
		$local_dir - the local subdirectory.. 

	*/
	public function downloadLatestMirror($sub_path,$local_dir){

		$sub_path = rtrim($this->main_dir.'/'.$sub_path,'/').'/'; 

		echo "Mirroring cloud file in $sub_path to $local_dir\n";

		$local_FS = new Filesystem(new Local($local_dir));

		$is_recursive = true;

		$dir_contents = $this->filesystem->listContents($sub_path,$is_recursive);
//		$dir_contents = $this->filesystem->listContents('/',$is_recursive);

		foreach($dir_contents as $object){
		
//			var_export($object);
	
			$full_path = $object['path'];
			$basename = $object['basename'];
			echo "Cloning $full_path into $local_dir\n";
			if($this->filesystem->has($full_path)){
				echo "We have $full_path\n";
			}

			//$contents = $this->filesystem->read($full_path);
			//$local_FS->write($basename,$contents);
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
             		echo "Could not download $url\n";
			return(-1);
          	}

	}



/*
	Works exactly like mirror_that, except it understands specifically how to work with the socrata API to 
	check the file integrity etc etc.. 	
	it knows how to check its arguments for validity and then call the downloadSocrataFile instead of downloadFile 
	which will run additional checks to make sure things work correctly...
	$sub_dir - the subdirectory of the cloud bucket to use
	$base_url - the socrata base url... 
	$socrata_four_by_four - the socrata four by four
	$filename_stub - the filename to use as the prefix for all files gooddataset will result in gooddataset.four-four.MD5.date.zip etc etc.. 
	$is_use_cloud_name - should we put an md5 and/or date in the new name of the file.. 
*/
	public function mirror_that_socrata_id($sub_dir,$base_url,$socrata_four_by_four,$filename_stub,$is_use_cloud_name = true){
	
		if(is_null($base_url)){
			echo "Error: you tried to run mirror_that with a null instead of a base_url\n";
			exit(-1);
		}

		if(is_null($socrata_four_by_four)){
			echo "Error: you tried to run mirror_that_socrata_url with a null instead of a socrata four by four\n";
			exit(-1);
		}

		if(is_array($base_url)){
			echo "Error: you tried to run mirror_that_socrata_url with an array instead of a url...\n";
			var_export($base_url);
			echo "Error: you tried to run mirror_that_socrata_url with an array instead of a url...\n";
			exit(-1);
		}



		if (filter_var($base_url, FILTER_VALIDATE_URL) === FALSE) {
    			echo "base_url $base_url is Not a valid URL\n";
			exit(-1);
		}

			
		$local_tmp_file = __DIR__ . "/data/$filename_stub.$socrata_four_by_four.tgz";

             	$local_cloud_file = $this->downloadSocrataFile($base_url,$socrata_four_by_four,$filename_stub,$local_tmp_file); //call our curl download function, which will save the file into the local copy
              	if($local_cloud_file){
                     	$cloud_file_name = pathinfo($local_cloud_file,PATHINFO_BASENAME);

                     	if(!in_array($cloud_file_name,$this->cloud_file_list)){
         			//this means we have at least one new data file..
          			$is_new_data = true;
            			echo "Uploading  $cloud_file_name from $local_cloud_file!!\n";
           			$cloud_file_contents = file_get_contents($local_cloud_file); //load the data into php memory...
            			$cloud_file_path = "$sub_dir/$cloud_file_name";
          			$this->filesystem->write($cloud_file_path,$cloud_file_contents); //savve the file to google cloud.
				return(1);
          		}else{
            			echo "The $cloud_file_name is redundant to a file already in the cloud... doing nothing...\n";
				return(0);
              		}
           	}else{
             		echo "Could not download $url\n";
			return(-1);
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
	public function calculate_cloud_file_name($local_file, $md5_arg = null, $date_string_arg = null){

		$pathinfo = pathinfo($local_file);
		$file_name_first_part = $pathinfo['filename'];
		$file_extension = $pathinfo['extension'];
		if(is_null($md5_arg)){
			$my_md5 = md5_file($local_file);
		}else{
			$my_md5 = $md5_arg; //have an argument allows us to have a different method for calculating the md5 of zip files etc.
		}
		if(is_null($date_string_arg)){
			$mysql_today_datestring = date("Y-m-d");
		}else{
			$mysql_today_datestring = $date_string_arg; //not actually sure why you would need this.. but.
		}

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

/*
	Is essentially identical to downloadFile.. except that it understands how to download Socrata meta data
	as well as csv, and store both the metadata and the csv file into a zip file
	and make sure that a basic sense check on the data has occured..	
	implemented as multiple calls back the original downloadFile()
	arguments
	$base_url - the socrata base url (something like https://data.cms.gov/)
	$socrata_four_by_four - the dataset identifier like je57-c47h in https://data.cms.gov/Medicare-Enrollment/Address-Sub-File/je57-c47h
	$target_tar_file - where to put the zip file that is built from downloaded files... 

	returns
	$zip_file_path - the real zip file path (includes an md5 and date);
*/
	public function downloadSocrataFile($base_url, $socrata_four_by_four, $filename_stub, $target_tar_file){

		//we are going to be building a zip file... 
		//and we want to put all of the component files into the same working directory...
		//we use pathinfo to figure out where that is.
		$pathinfo = pathinfo($target_tar_file);
		$out_dir = $pathinfo['dirname']. "/"; //this is where we will put all of the file we download before we zip them up...
		$just_tar_file = $pathinfo['basename'];

		$base_url = rtrim($base_url,'/'); //its easier to remove it and then add it back...

		$metadata_url = "$base_url/api/views/$socrata_four_by_four.json";
		$csv_download_url = "$base_url/api/views/$socrata_four_by_four/rows.csv?accessType=DOWNLOAD";
		$row_count_url = "$base_url/api/id/$socrata_four_by_four?\$query=select%20count(*)%20as%20COLUMN_ALIAS_GUARD__count";
		$metadata_filename = 	"$out_dir$filename_stub.$socrata_four_by_four.metadata.json";		
		$rowcount_filename = 	"$out_dir$filename_stub.$socrata_four_by_four.rowcount.json";		
		$data_filename = 	"$out_dir$filename_stub.$socrata_four_by_four.data.csv";		

		//sometimes we want to do exactly the same thing to all of these files... so lets make a list to loop over..
		$to_download_list = [
			$metadata_filename => 	$metadata_url,
			$rowcount_filename => 	$row_count_url,			
			$data_filename => 	$csv_download_url,
		];

		foreach($to_download_list as $filename => $url){
			self::downloadFile($url,$filename);
		}

		//lets get the row count from the api. This is the entire reason we get this file..
		$row_count_data = json_decode(file_get_contents($rowcount_filename),true);

		if(isset($row_count_data[0]['column_alias_guard__count'])){
			$api_row_count = $row_count_data[0]['column_alias_guard__count'];
			//ok now we know how many rows the API thinks there are!!
		}else{
			echo "Error: could not parse $rowcount_filename to get the row count information\n";
			exit(-1);
		}
		
		//how many lines are there actually. Nothing is faster for this than the wc command...
		$wc_cmd = "wc -l $data_filename";
		$wc_result = exec($wc_cmd);
		list($real_row_count,$trash) = explode(' ',$wc_result);

		//the csv file has column headers, and should be exactly one row large than the row count from the socrata API.
		if($real_row_count == ($api_row_count + 1)){
			//then we are good!!
		}else{
			echo "Error: We got $api_row_count from the API, and $real_row_count from the downloaded csv file... something went wrong...\n";
			exit(-1);
		}
		//ok we now have all 3 files downloaded... we need to zip them...

/* 
		//it is not possible, as far as I can tell, to use the -X flag with ZipArchive.. and as a result the md5 of zip files 
		//created in subsequent runs of this code willhave different md5... even though they have identical contents..
		//so we cannot use this method to zip the files...
		$zip = new ZipArchive;
		if($zip->open($zip_file_path, ZipArchive::CREATE) === TRUE){
			foreach($to_download_list as $filename => $url){
				$short_filename = pathinfo($filename,PATHINFO_BASENAME);
				$zip->addFile($filename,"$socrata_four_by_four/$short_filename"); //lets add the files, but not make a tarbomb
			}
			$zip->close();
		}else{	
			echo "Error: I was not able to create zip file $zip_file_path in downloadSocrataFile\n";
			exit(-1);
		}	
*/
		//gzip has the same problem... according to this:
		//https://serverfault.com/a/775740/72025
		//the GZIP temporary environment variable should sort it... 
		$year = date('Y');
		//you have to do all of the things here:
		//https://reproducible-builds.org/docs/archives/
		//this seems to work for a few moments and then no longer match. 
		//including it here to prevent some future developer (i.e. myself) from rabbit holing on this.. 
	
		//you have to do all of the things here:
		//https://reproducible-builds.org/docs/archives/
		//this seems to work for a few moments and then no longer match. 
		//including it here to prevent some future developer (i.e. myself) from rabbit holing on this.. 
		//$tar_cmd = "tar --sort=name   --mtime='$year-01-01 00:00Z'  --owner=0 --group=0 --numeric-owner -cf $zip_file_path_template.tar ";

		//that does not fucking work.
		//this command should not need me to change directory to the outdir..
		$tar_cmd = "tar -C $out_dir -czvf $just_tar_file";
		
		//but 'should' is a terrible terrible word
		chdir($out_dir);

		$merged_md5_string = '';

		foreach($to_download_list as $full_filename => $url){
			$filename = pathinfo($full_filename,PATHINFO_BASENAME);
			$tar_cmd .= " $filename";
			$this_md5_string = md5_file($full_filename);
		//	echo "Got $this_md5_string md5 for $filename\n";
			$merged_md5_string .= $this_md5_string;
		}

		
		echo "Tarring with $tar_cmd\n";
		system($tar_cmd);

		chdir(__DIR__); //go back
		
		$meta_md5 = md5($merged_md5_string);
		$cloud_file_name = $this->calculate_cloud_file_name($target_tar_file, $meta_md5);
		//echo "Merging $merged_md5_string into $meta_md5 for $cloud_file_name\n";

		$local_cloud_file = "$out_dir/$cloud_file_name";

		rename($target_tar_file,$local_cloud_file);
		
		//if we get all the way here then we have built the zip file correctly...
		return($local_cloud_file);

 	}


/*
// modified from https://stackoverflow.com/a/35271138/144364
//accepts a url to download, and a local file path to save the data to...
//returns true if the file exists and it is not a 404 result...
	arguments:
	$url - the url to download
	$filepath - the local file to store it in.. 	
*/
	public static function downloadFile($url, $filepath){

		$is_debug = false;
	
		if($is_debug){
			if(file_exists($filepath)){
				//in debug mode, we just assume that if the file already exists... then it is the current download...
				echo "Warning!! in debug mode, not downloading  $url because $filepath already exists..\n";
				return(true);
			}
		}

		echo "Downloading $url...";

	    	$fp = fopen($filepath, 'w+');
		if($fp){
     			$ch = curl_init($url);

     			curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
     			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
     			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_TIMEOUT, 0); //0 is infinite.
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
				echo "done.\n";
				return(true);
			}else{
				echo "Error: download file size was zero trying to save $url to $filepath \n";
				return(false);
			}
		}else{
			//got a false file pointer here...
			echo "Error: Failed to open $filepath for writing...\n";
			exit(-1);
		}
 	}




}
