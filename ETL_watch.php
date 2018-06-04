<?php
/*
	The purpose of this file is to watch a given website, to understand when a particular dataset has been refreshed.
	Then download that dataset.  
	This process should return one of the following three return codes to the calling ETL process: 	

 	< 0 - FAILED. An error occured and this script failed to properly run. 
 	= 0 - NO CHANGE. The current cloud version of this file is correct. 
 	> 0 - CHANGE. There is a new file in teh cloud and the next step needs to be processed

	this process expects a database to exist with the following structure in the same database 
	that you are importing everything into... 

*/

/*

CREATE TABLE `website_history` (
  `url` varchar(255) NOT NULL,
  `md5` varchar(32) NOT NULL,
  `downloaded_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `website_history`
  ADD PRIMARY KEY (`url`,`md5`);

*/

//always working directory to where this php lives
//all paths are according to this file
chdir( __DIR__ );
require_once('vendor/autoload.php'); //PHU I added this... so that it is clear how to use other packages to solve various problem...
require_once ('util/loader.php'); //this expects that .env files will be properly populated to access the database etc.


//define a global variable mysql
$mysql = DB::get($config["mysql_host"],$config["mysql_website_database"],$config["mysql_user"],$config["mysql_password"]);
//PHU this is your custom mysql object... and the only one for whome this is obvious is you... 
//There needs to a link here to markdown documentation file that lives right beside your util/mysql.pdo.php object...
//Also, if that class is "pdo style" singleton, then it should not be named mysql.pdo.php which makes it seem like you are just setting up a pdo...
//but instead you are setting up a singleton object that works in a specific way... that is better/different thanjust pdo 
//for reasons that I would hope eventually get listed in your documentation file... 

$db = $config["mysql_website_database"];

$process_name = 'REPLACE_ME_WITH_PROCESS_NAME'; // something like NPPES_ETL 
		//this will be used as the prefix in our log file. 
	
$data_title = 'REPLACE_ME_WITH_DATA_TITLE'; //something like NPPES or that_awesome_data 
		//this will be used as the directory name in our cloud file system. 


//PHU, ths is a great place to document where you logs live, and how to work with them...  
Logger::prepend("REPLACE_ME_WITH_PROCESS_NAME"); //this will be prepended to every later call to logger
Logger::log("Started");


//	FETCH_URL       - where the main website to fetch. This URL is important since the database will use this as the KEY to determining what the process is
define('FETCH_URL','REPLACE_ME_WITH_URL_TO_FETCH'); 

//Note that all of the Google Cloud Storage references are for the docgraph.com account
//under the savvy-summit-133807 project
//	ARCHIVE_LATEST  - Where to store the latest copy of the website change
define('ARCHIVE_LATEST',"gs://website-archive/$data_title/00-Latest/");
//	ARCHIVE_ROOT    - where the archive of every changes live
define('ARCHIVE_ROOT',"gs://website-archive/$data_title/");


//there are two ways to work with google cloud. One is the Google Cloud PHP library 
// https://github.com/googlecloudplatform/google-cloud-php#google-cloud-datastore-ga
// and the other is the GSutil command line library... this variable details where to find the gsutil command line library.
// the other just requires composer package to be installed and credentials to live in the *.auth.json file for google API credentials.
//	GSUTIL 		- Where gsutil process is located at. can be found using whereis
define('GSUTIL','/opt/google-cloud-sdk/bin/gsutil');

Logger::Log("Archive Destination: ".ARCHIVE_ROOT);


/**
 *
 * Checking the last ran information. This uses the FETCH_URL ABOVE
 */
$last_md5 = null;
$last_ran_date = null;

$last_ran = $mysql->query("SELECT * FROM $db.website_history where url=? ORDER BY downloaded_date DESC, id DESC LIMIT 1",[FETCH_URL]);






if(is_array($last_ran) && count($last_ran)==1) //then we have an entry for the last run...
{
	$last_ran = $last_ran[0];
	$last_md5 = $last_ran["md5"];
	$last_ran_date = $last_ran["downloaded_date"];

	Logger::log("Last Ran Date: ".date("Y-m-d H:i:s",strtotime($last_ran_date)));
	Logger::log("Last Ran MD5: ".$last_md5);
}	

if($last_ran == null || !is_array($last_ran) || (is_array($last_ran) && empty($last_ran))) //finding nothing here means that there is no record in the website_history database...
{
	Logger::log("Last Ran: Never");
	//note last_md5 and last_ran_date remain null
}






###################################################################################################################################################
###################################################################################################################################################
###################################################################################################################################################

$site_content = file_get_contents(FETCH_URL); //download the current version of the website...




/********************************

WEBSITE PARSING / CHECKING GOES HERE

PHU What is being done here? What need to be accomplished?
There are at least two cases that should be distinctly covered... 

One is the DEA case, where we know, pretty excatly what the next version of the downloadable zip file with the data is going to be...
and it is either already downloadable, or it is not downloadable... 

The other is the more typical case that we need to download some HTML and look inside to see there is new data.
In that case, the HTML can (and frequently does) change (changing the MD5) without actually adding a new download link of any kind...
So we need to account for the possibility that the "is changed md5" needs to really point to the "latest downloadable file link".. 

There are likely other things that need to be done... 

And then there is the matter of just backing up different variations of the website... when that is worthwhile, and when it is not. 
I think we can at least be sure that a websites content will need to have changed either the <title> or <body> tags for us to care...

What about multiple zip files... you are obviously handling that case with the "compare" datasets... but why, and how?

This section should have a whole lot of prose about what to do in which situation in order to be compatible with your ETL basics...
Otherwise other developers will not be able to make a new ETL that shoehorns a given downloadable problem set into your 
ETL process. So what are the rules? Why does it work the way it works... 

Here is where you need to briefly write that out...

********************************/


$file_stub = "REPLACE_OR_CALCULATE_FILE_STUB_HERE"; //assuming that sometimes the filename will be calculated from the website, and sometimes we will able to just define it..


//create a temporary working directory in /tmp/ (or where tempdir is defined)
$working_dir = tempdir($file_stub); //function defined in util/global_functions.php
Logger::log("Working Dir: $working_dir");

//PHU why do we need to consider the previousl zipped version of the file here?
//Don't we already have the md5 from the SQL above?
//get the latest md5 of the files and website to put into file name
$latest_md5 = md5_file($latest_backup);
$website_backup_md5 = md5_file($website_backup);


###################################################################################################################################################
###################################################################################################################################################
###################################################################################################################################################


//if the file md5 changes
if($latest_md5 != $last_md5)
{
	slack("`$data_title` Site Updated"); //note backticks format message as 'codish'

	if($latest_md5 != $last_md5) //PHU isnt this completely reduntant?
	{
		Logger::log("MD5 differs: [current] $latest_md5 - [previous] ".($last_md5==null?"{none}":$last_md5));
	}

	//this will attempt to copy the file to the GCS, will try 10 times. pausing 30 seconds between each try
	$give_up_timer = 10;
	$failed = true;
	while($failed && $give_up_timer > 0) //we assume that this process has failed... until we clarify that it has not...
	{
		Logger::log("Copying to Archive folder. Try #$give_up_timer");			

		//put into or create a new folder by format "Y-m"
		$archive_date = ARCHIVE_ROOT.date("Y-m");
		$failed = false;


		//PHU the entire strategy of how you want the ETL system to fuction is here...
		//But I cannot guess what, it is trying to do. 
		//Why do you hve a 'latest'? Why do you have a "WebsiteBackup" as distinct from the data file?
		//
		//copies the file to the GCS using GSUTIL
		exec(GSUTIL." cp $latest_backup ".$archive_date."/$file_stub.".date("Ymd").".{$latest_md5}.zip",$output,$return);
		if($return == 0) exec(GSUTIL." cp $latest_backup ".ARCHIVE_LATEST."$file_stub.latest.zip",$output,$return);
		if($return == 0) exec(GSUTIL." cp $website_backup ".$archive_date."/$file_stub.Website_Backup.".date("Ymd").".{$website_backup_md5}.zip",$output,$return);

		$failed = $return != 0;

		
		if($failed) //we need to repeatedidly call this code until it either works, of it becomes clear that it will not work..
		{
			--$give_up_timer; //decriment the 'timer' referenced in the while..

			if($give_up_timer>0)
			{
				Logger::log("Copying Failed. Waiting to retry");
				sleep(30); //lets wait for the tubes to unclog or whatever....
			}
		} else {
			slack("`$data_title` Successfully Backed up");
		}
	}

	//remove working directory
	exec("rm -rf {$working_dir}");


	//if failed, then slack and call the error function
	if($failed)
	{
		slack("`$data_title` Failed to backup");

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
