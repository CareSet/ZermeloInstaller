<?php

//always working directory to where this php lives
//all paths are according to this file
chdir( __DIR__ );
require_once ('util/loader.php');


/**
 * NOTE, ETL process is dictated by the return code.
 * < 0 - FAILED.
 * = 0 - NO CHANGE.
 * > 0 - CHANGE. PROCESS NEXT STEP
 */


//define a global variable mysql
$mysql = DB::get($config["mysql_host"],$config["mysql_website_database"],$config["mysql_user"],$config["mysql_password"]);

Logger::prepend("REPLACE_ME_WITH_PROCESS_NAME");
Logger::log("Started");

/*
	FETCH_URL       - where the main website to fetch. This URL is important since the database will use this as the KEY to determining what the process is
	ARCHIVE_LATEST  - Where to store the latest copy of the website change
	ARCHIVE_ROOT    - where the archive of every changes live
	GSUTIL 			- Where gsutil process is located at. can be found using whereis
*/
define('FETCH_URL','REPLACE_ME_WITH_URL_TO_FETCH');
define('ARCHIVE_LATEST','gs://website-archive/REPLACE_ME_WITH_TITLE/00-Latest/');
define('ARCHIVE_ROOT','gs://website-archive/REPLACE_ME_WITH_TITLE/');
define('GSUTIL','/opt/google-cloud-sdk/bin/gsutil');


Logger::Log("Archive Destination: ".ARCHIVE_ROOT);



/**
 *
 * Checking the last ran information. This uses the FETCH_URL ABOVE
 */
$last_md5 = null;
$last_ran_date = null;

$last_ran = $mysql->query("SELECT * FROM website_history where url=? ORDER BY downloaded_date DESC, id DESC LIMIT 1",[FETCH_URL]);

if(is_array($last_ran) && count($last_ran)==1)
{
	$last_ran = $last_ran[0];
	$last_md5 = $last_ran["md5"];
	$last_ran_date = $last_ran["downloaded_date"];

	Logger::log("Last Ran Date: ".date("Y-m-d H:i:s",strtotime($last_ran_date)));
	Logger::log("Last Ran MD5: ".$last_md5);
}

if($last_ran == null || !is_array($last_ran) || (is_array($last_ran) && empty($last_ran)))
{
	Logger::log("Last Ran: Never");
}





//create a temporary working directory in /tmp/ (or where tempdir is defined)
$working_dir = tempdir('REPLACE_ME_FILE_STUB_');
Logger::log("Working Dir: $working_dir");

###################################################################################################################################################
###################################################################################################################################################
###################################################################################################################################################

$site_content = file_get_contents(FETCH_URL);




/********************************

WEBSITE PARSING / CHECKING GOES HERE

********************************/





$lastest_backup = "PATH_TO_THE_LATEST_ZIP_BACKUP_FILE"; //this is the target file(s) from the website
$website_backup = "PATH_TO_THE_LATEST_ZIP_OF_WEBSITE"; //this usually means doing a wget to all content of the website including the html/css/javascript


//get the latest md5 of the files and website to put into file name
$latest_md5 = md5_file($latest_backup);
$website_backup_md5 = md5_file($website_backup);


###################################################################################################################################################
###################################################################################################################################################
###################################################################################################################################################


//if the file md5 changes
if($latest_md5 != $last_md5)
{
	slack("`REPLACE_ME_WITH_TITLE` Site Updated");

	if($latest_md5 != $last_md5)
	{
		Logger::log("MD5 differs: [current] $latest_md5 - [previous] ".($last_md5==null?"{none}":$last_md5));
	}



	//this will attempt to copy the file to the GCS, will try 10 times. pausing 30 seconds between each try
	$give_up_timer = 10;
	$failed = true;
	while($failed && $give_up_timer > 0)
	{
		Logger::log("Copying to Archive folder. Try #$give_up_timer");			

		//put into or create a new folder by format "Y-m"
		$archive_date = ARCHIVE_ROOT.date("Y-m");
		$failed = false;

		//copies the file to the GCS using GSUTIL
		exec(GSUTIL." cp $latest_backup ".$archive_date."/REPLACE_ME_WITH_FILE_STUB.".date("Ymd").".{$latest_md5}.zip",$output,$return);
		if($return == 0) exec(GSUTIL." cp $latest_backup ".ARCHIVE_LATEST."REPLACE_ME_WITH_FILE_STUB.latest.zip",$output,$return);
		if($return == 0) exec(GSUTIL." cp $website_backup ".$archive_date."/REPLACE_ME_WITH_FILE_STUB.Website_Backup.".date("Ymd").".{$website_backup_md5}.zip",$output,$return);

		$failed = $return != 0;

		if($failed)
		{
			--$give_up_timer;

			if($give_up_timer>0)
			{
				Logger::log("Copying Failed. Waiting to retry");
				sleep(30);
			}
		} else {
			slack("`REPLACE_ME_WITH_TITLE` Successfully Backed up");
		}
	}

	//remove working directory
	exec("rm -rf {$working_dir}");


	//if failed, then slack and call the error function
	if($failed)
	{
		slack("`REPLACE_ME_WITH_TITLE` Failed to backup");

		//note Logger::error will exit with a -1
		Logger::error("Unable to copy to archive folder. Tries exceeded limit.");
	}

	//Update database last just in case copying fails		
	Logger::log("Updating database...");


	//Update the website_history with the newest md5
	$result = $mysql->query("INSERT INTO website_history(url,md5,downloaded_date) values (?,?,?)",
			[FETCH_URL,$website_md5,date("Y-m-d H:i:s")]
		);


	//removes working directory if exists
	exec("rm -rf {$working_dir}");
	Logger::log("Shutting down");

	//note that there was change.
	//returning of a value > 0 means success
	die(1);
}

//NO change was detected
else
{	
	exec("rm -rf {$working_dir}");
	Logger::log("MD5 matched: [current] $website_md5 - [previous] ".($last_md5==null?"{none}":$last_md5));
	Logger::log("Nothing to download");
}


Logger::log("Shutting down");
//return of a value of = 0 means no change
die(0);

?>