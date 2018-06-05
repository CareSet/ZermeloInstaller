<?php

	/*
		For ETL projects. run.php is the starting point for the ETL process.
		Erase this file if you are not creating an ETL step project.
		This template assumes that you just need to run a bunch of sql commands...
		
		
		Note, that all required tables for running an ETL on a clean server (with a mysql server with nothing installed)  
		Should be listed here... 
		
		So there should be a bunch of "CREATE TABLE" stuff here..

	*/

require_once('util/loader.php');

$mysql = DB::get($config["mysql_host"],$config["mysql_website_database"],$config["mysql_user"],$config["mysql_password"]);



$sql = []; 

$sql['something'] = "SELECT * FROM npi.npi";

foreach($sql as $comment => $this_sql){

        if(is_numeric($comment)){
                //then there was no comment put in as an index...
        }else{
                echo "$comment\n";
        }
        echo "Running $this_sql\n";
        $mysql->query($this_sql);

}


