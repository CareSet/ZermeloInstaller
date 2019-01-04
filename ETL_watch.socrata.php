<?php
/*
	This file understands how to download new copies of the data... 
	and to initiate futher processing
*/

require_once('vendor/autoload.php');
require_once('EbbHelper.class.php');

use Sunra\PhpSimple\HtmlDomParser;
use CedricZiel\FlysystemGcs\GoogleCloudStorageAdapter;
use League\Flysystem\Filesystem;

	//always change..
    	$bucket    = 'ebb_pecos'; // needs to be changed with every script, should be something like ebb_yourthing. NOTE for now you need to create this bucket using the google console


	//These should not change.
    	$projectId = 'savvy-summit-133807'; //this should likely not change
    	$keyFilePath = __DIR__ . '/google_cloud.auth.json'; //should likely not change

	//Create the download helper.. this lets you translate between things on the Internet, and our file backup system easily.
	$EH = new EbbHelper(
			$projectId,
			$bucket,
			$keyFilePath
		);

	//this lets you specify what the arguments to your script should be.. 
	//at this stage you need a place to store data the ./data file in the local repo is an OK place
	//for small files... 
        if(!isset($argv[1])){
                echo "Usage: ETL_watch.php {directory_to_put_downloads}\n";
                exit();
        }


	$data_dir = $argv[1];//this is actually hardcoded in the data helper to ./data/


$urls_to_get = [
	'base_provider_enrollment' => [
	       	'socrata_id' => 'ykfi-ffzq',
		],
        'reassignment_subfile' => [
		'socrata_id' => 'rta9-bts3',
		],
        'address_subfile' => [
		'socrata_id' => 'je57-c47h',
		],
        'secondary_specialty' => [
		'socrata_id' => 'n48j-8qtj',
		],
	'ordering_and_referring' => [
		'socrata_id' => 'qcn7-gc3g',
	],
];

	//TODO you need to figure out what urls you want to download here...
	//like urls_to_get[] = ['url' = "http://dommain.com/something/something/something/thefile.zip",
	//			'is_current' => false]; //this variable will control if a url is recorded as the current version...

	$base_url = "https://data.cms.gov/";

	$is_new_data = false;


	foreach($urls_to_get as $dataset_name => $url_data){
		$this_id = $url_data['socrata_id'];

		$sub_dir = "$dataset_name/";

		$result = $EH->mirror_that_socrata_id($sub_dir,$base_url,$this_id,$dataset_name);
		if($result > 0){
			$is_new_data = true;
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












