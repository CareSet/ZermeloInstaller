<?php	
	require_once('vendor/autoload.php');
        require_once('util/mysqli.php');
	require_once('util/global_functions.php');
	require_once('run_sql_loop.function.php');


        if(!isset($argv[1])){
		$this_file = __FILE__;		
                echo "Usage: $this_file {target_db} \n";
                exit();
        }

        $db = $argv[1];

	$start_time = microtime(true);


        $sql = [];

	$sql[] = "ENTER your SQL commands here... you do not need to use an index...";
	$sql['comments are good, use them!!'] = "ENTER your SQL commands here...";


	run_sql_loop($sql,$start_time);



