<?php
/*
	This file understands how to download new copies of the data... 
	and to initiate futher processing
*/

require_once('vendor/autoload.php');
require_once('DownloadHelper.class.php');

use Sunra\PhpSimple\HtmlDomParser;
use CedricZiel\FlysystemGcs\GoogleCloudStorageAdapter;
use League\Flysystem\Filesystem;

	//always change..
    	$bucket    = 'REPLACE_ME'; // needs to be changed with every script, should be something like ebb_yourthing. NOTE for now you need to create this bucket using the google console

	//sometimes change..
	$path = 'raw_file_mirror'; //this is an ok starting point NOTE for now you need to create this directory using the Google console!!

	//These should not change.
    	$projectId = 'savvy-summit-133807'; //this should likely not change
    	$keyFilePath = './google_cloud.auth.json'; //should likely not change

	//Create the download helper.. this lets you translate between things on the Internet, and our file backup system easily.
	$DH = new DownloadHelper(
			$projectId,
			$bucket,
			$path,
			$keyFilePath
		);

	//this lets you specify what the arguments to your script should be.. 
	//at this stage you need a place to store data the ./data file in the local repo is an OK place
	//for small files... 
        if(!isset($argv[1])){
                echo "Usage: ETL_watch.php {directory_to_put_downloads}\n";
                exit();
        }


	$data_dir = $argv[1];


	$urls_to_get = [];

	//TODO you need to figure out what urls you want to download here...
	//like urls_to_get[] = ['url' = "http://dommain.com/something/something/something/thefile.zip",
	//			'is_current' => false]; //this variable will control if a url is recorded as the current version...

	$is_new_data = false;

	foreach($urls_to_get as $url_data){
		$this_url = $url_data['url'];
		$is_current = $url_data['is_current'];

		$mirror_to_dir = "REPLACE_ME/"; //you can organize your data however you want, note the trailing slash!!

		$result = $DH->mirror_that($mirror_to_dir,$this_url);
		if($result > 0){
			$is_new_data = true;
		}

		if($is_current){ //this should only be true once for each type of file..
			$is_use_cloud_name = false; //we do not want to have the md5 and date for the current version..
			$file_name = 'current.zip'; //may need to adjust this!!!
			$DH->mirror_that($mirror_to_dir,$this_url,$file_name,$is_use_cloud_name);
		}
	}



/*
	returns the standard...

 	< 0 - FAILED. An error occured and this script failed to properly run. 
 	= 0 - NO CHANGE. The current cloud version of this file is correct. 
 	> 0 - CHANGE. There is a new file in teh cloud and the next step needs to be processed

*/

//we would have exited with -1 before now if we had an error
if($is_new_data){
	exit(1); //there has neen a change..
}else{
	exit(0);
}		












