<?php
/*
	Once EBB has determined that a data source has been updated(happens in ETL_watch.php)
	Then there should be a new copy of the raw source data that is available in the google filestorage system
	That data lives under docgraph.com account under the savvy-summit-133807 project
	
	Then this script needs to take that new data, correctly handle all data processing required in order
	To import the updated data set into the databaes. 

*/
chdir( dirname(__FILE__) );

require_once('vendor/autoload.php'); //PHU added this. 
require_once('util/loader.php');

$target_db = 'REPLACE_ME'; //the database that you will put the data into in the end. 

$etl_title = "REPLACE_ME"; //your project name goes here
$etl_file_stub = "REPLACE_ME";

$process_name = 'REPLACE_ME_WITH_PROCESS_NAME'; // something like NPPES_ETL 
		//this will be used as the prefix in our log file. 
	
$data_title = 'REPLACE_ME_WITH_DATA_TITLE'; //something like NPPES or that_awesome_data 
		//this will be used as the directory name in our cloud file system. 

//define a global variable mysql
$mysql = DB::get($config["mysql_host"],$config["mysql_website_database"],$config["mysql_user"],$config["mysql_password"]);

//PHU link to Logger documentation here..
Logger::prepend("$etl_title Process");
Logger::log("Started");


/*
	ARCHIVE_LATEST	- Where the latest file is stored
	ETL_DROPOFF		- Where the database dropoff with be (the latest.tar.gz)
	SQLIMPORT 		- Location of the SQLImport Script
	MYSQL_DIR 		- Location of MySQL (it will go here to tar the database)
	GSUTIL 			- Where gsutil process is located at. can be found using whereis
*/
define('ARCHIVE_LATEST',"gs://website-archive/$etl_file_stub/00-Latest/");
define('ETL_DROPOFF',"gs://careset-etl/$etl_file_stub/");
define('SQLIMPORT', './import_all_csv_in_dir_into_same_db.php');
define('MYSQL_DIR', '/var/lib/mysql' );

//TODO this should be defined by a "which" command to ensure future proof. 
define('GSUTIL','/opt/google-cloud-sdk/bin/gsutil');

// Creates a temporary working directory
$working_dir = tempdir("/tmp/$etl_file_stub");
Logger::log("Working Directory: {$working_dir}");

// Define where the extract will live
$extract_dir = "{$working_dir}/extract";

chdir($working_dir);


// Path of the latest file inside ARCHIVE_LATEST
$latest_file = ARCHIVE_LATEST."$etl_file_stub.latest.zip";


// Path where the file will copy down to
$working_zip  = "{$working_dir}/$etl_file_stub.zip";
exec(GSUTIL." cp {$latest_file} {$working_zip}",$output,$return);

//Unable to copy the file down
if($return != 0)
{
	exec("rm -rf {$working_dir}"); //PHU Why would we erase the evidence? doesnt this make debugging harder?
	Logger::error("Unable to fetch latest $process_name"); //Returns -1
	//PHU shouldnt we die() here with an error code?
	//Does the Logger do that? If so, shoulnt the command be Logger::die()?
}


#############################################################################################
#############################################################################################


/********

INSERT CODE TO CREATE THE DATABASE HERE

THis code needs to start with the data on the file system, import it into the database
And perform any transformations needed to get the data into its final form. 
For instance, if there are addresses in the dataset, this is where they should be sent to 
Smarty Streets..

*********/



#############################################################################################
#############################################################################################

//Clean up our work area. 
exec("rm -rf {$working_dir}");

slack("`$process_name` successfully Imported");
Logger::log("$process_name Imported");

//Now that the database has been properly imported, we download the MyISAM database 
//and put that back into the cloud storage. That allows us to run our ETL
//on distinct servers and have the results available to all production and development servers. 
Logger::Log("Backing up $process_name Database");

chdir(MYSQL_DIR);
$dest_file = MYSQL_DIR."/$target_db.tar.gz";
exec("tar -zcvf $target_db.tar.gz orangebook");
if(!file_exists(MYSQL_DIR."/$target_db.tar.gz"))
{
	slack("Unable to backup `$process_name` Database");
	Logger::error("Unable to backup $process_name database");
	//PHU shouldnt we exit here?
	//perhaps a die() with the appropriate code...
}

//Copies the file to the ETL DROPOFF, Both to .latest and with the timestamp
Logger::log("Moving to ETL Dropoff directory");
exec("sudo ".GSUTIL." cp {$dest_file} ".ETL_DROPOFF."$target_db.".date("Ymd").".tar.gz");
exec("sudo ".GSUTIL." cp {$dest_file} ".ETL_DROPOFF."$target_db.latest.tar.gz");

slack("`$process_name` ETL Database Updated");


//Cleanup, remove working directory and mysql database file
exec("rm -rf {$working_dir}");
exec("rm -rf ".MYSQL_DIR."/$target_db.tar.gz");

Logger::log("Shutting down");

die(0); //PHU what do the exit codes mean here? Is there a mechanism to communicate failures?


?>
