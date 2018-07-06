<?php	
	require_once('vendor/autoload.php');
        require_once('util/mysqli.php');
	require_once('util/global_functions.php');



        if(!isset($argv[1])){
		$this_file = __FILE__;		
                echo "Usage: $this_file {target_db} \n";
                exit();
        }

        $db = $argv[1];

	$start_time = microtime(true);


        $sql = [];

	$sql[''] = "";

	$total_steps = count($sql);

	$current_step = 1;

	$is_echo_full_sql = false;
	foreach($sql as $comment => $this_sql){
		$this_sql = trim($this_sql);
		if(strlen($this_sql) > 0){
			echo "Status: ($current_step of $total_steps) \t\t $comment\n";
			if($is_echo_full_sql){
				echo "Running $this_sql\n";
			}
			//actually run the sql here...
			f_mysql_query($this_sql);
		
		}else{
			echo "Skipping step $current_step of $total_steps because it is blank\n";
		}
		$current_step++;
	}

	$time_elapsed_secs = microtime(true) - $start_time;
	$time_elapsed_min = round($time_elapsed_secs / 60,2);
	echo "Processing run took $time_elapsed_min minutes.\n"; 


