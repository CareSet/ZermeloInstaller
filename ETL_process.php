<?php

chdir( dirname(__FILE__) );

require_once ('util/loader.php');


	$etl_title = "REPLACE_ME"; //your project name goes here
	$etl_file_stub = "replace_me";

//define a global variable mysql
$mysql = DB::get($config["mysql_host"],$config["mysql_website_database"],$config["mysql_user"],$config["mysql_password"]);


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
	exec("rm -rf {$working_dir}");
	Logger::error("Unable to fetch latest RxNorm"); //Returns -1
}


#############################################################################################
#############################################################################################


/********

INSERT CODE TO CREATE THE DATABASE HERE

*********/



#############################################################################################
#############################################################################################

exec("rm -rf {$working_dir}");

slack("`REPLACE_ME_WITH_TITLE` successfully Imported");
Logger::log("REPLACE_ME_WITH_TITLE Imported");


Logger::Log("Backing up REPLACE_ME_WITH_TITLE Database");

chdir(MYSQL_DIR);
$dest_file = MYSQL_DIR."/REPLACE_ME_WITH_DATABASE_NAME.tar.gz";
exec("tar -zcvf REPLACE_ME_WITH_DATABASE_NAME.tar.gz orangebook");
if(!file_exists(MYSQL_DIR."/REPLACE_ME_WITH_DATABASE_NAME.tar.gz"))
{
	slack("Unable to backup `REPLACE_ME_WITH_TITLE` Database");
	Logger::error("Unable to backup REPLACE_ME_WITH_TITLE database");
}

//Copies the file to the ETL DROPOFF, Both to .latest and with the timestamp
Logger::log("Moving to ETL Dropoff directory");
exec("sudo ".GSUTIL." cp {$dest_file} ".ETL_DROPOFF."REPLACE_ME_WITH_DATABASE_NAME.".date("Ymd").".tar.gz");
exec("sudo ".GSUTIL." cp {$dest_file} ".ETL_DROPOFF."REPLACE_ME_WITH_DATABASE_NAME.latest.tar.gz");

slack("`REPLACE_ME_WITH_TITLE` ETL Database Updated");


//Cleanup, remove working directory and mysql database file
exec("rm -rf {$working_dir}");
exec("rm -rf ".MYSQL_DIR."/REPLACE_ME_WITH_DATABASE_NAME.tar.gz");

Logger::log("Shutting down");
die(0);


?>





?>
