<?php
	require_once('util/mysqli.php');


if($argv[0] == basename(__FILE__)){

	$test_numbers = [
		'(713)965-4327',
		'(713-965-4327 ',
		'1-713)-965-4327',
		'713-965-4327',
		'(713))965- 4327',
		'2102327756',
		'9991112222',
		'0',
		'',
		'n/a',
		'NOne',
		];

	foreach($test_numbers as $this_number){
		$number_id = linknumber($this_number);
		$display_phone = getdisplayphone($number_id);

		echo "input $this_number links to $number_id which is '$display_phone'\n";

	}


	reconcile_rawnumber();

}




	function reconcile_rawnumber(){



		echo "Beginning rawnumber reconcilation...\n";
		//in order to work, we must rethlessly enforcce lowercaseness
		//means that we can use = instead of LIKE on joins
		$update_sql =  "UPDATE address.rawnumber SET rawnumber = LOWER(rawnumber)";

		$result = f_mysql_query($update_sql);

        	$sql = "
SELECT * FROM address.rawnumber 
WHERE number_id IS NULL
ORDER BY rawnumber DESC
";

        	$result = f_mysql_query($sql);


        	while($row = mysqli_fetch_assoc($result)){
                	$messy_number = $row['rawnumber'];
                	$number_id = linknumber($messy_number);
                	$new_number = getdisplayphone($number_id);
                	echo "$messy_number -> $new_number with id ($number_id)\n";

        	}


		echo "rawnumber reconcilation finished.\n";

	}


/*
	The rule for adding numbers in simple... 
	If it can be converted to a 10-digit phone number
	Then save it as that. 
	If it cannot be converted into a 10 digit phone number
	Then save it in its original form. 
	This function returns the id from the address.number table
	After it is done..
*/
	function linknumber($input_number){


		//first lets try to quickly search and then return the id...
		$e_input_number = strtolower(f_mysql_real_escape_string($input_number));
		$check_sql = "SELECT id FROM address.number WHERE number = '$e_input_number'";
	//	echo "searching with $check_sql\n";
		$result = f_mysql_query($check_sql);
		$row = mysqli_fetch_assoc($result);
		if(isset($row['id'])){
			$number_id = $row['id'];
			//make sure both tables have the map.
			$insert_raw_sql = "REPLACE INTO address.rawnumber (`rawnumber`, `number_id`) VALUES ('$e_input_number','$number_id');";
			f_mysql_query($insert_raw_sql);
			return($number_id);
		}

		//ok, this is a messy number, lets see if it is already mapped
		$check_sql = "SELECT number_id FROM address.rawnumber WHERE LOWER(rawnumber) = '$e_input_number'";
	//	echo "searching with $check_sql\n";
		$result = f_mysql_query($check_sql);
		$row = mysqli_fetch_assoc($result);
		if(isset($row['number_id'])){
			return($row['number_id']);
		}
		


		//if we get here, then we have to clean it..
		$clean_number = trim(preg_replace("/[^0-9]/", "", $input_number ));

		if(strlen($clean_number) == 11){
			if($clean_number[0] == '1'){
				//then it is an 11 digit number that starts with 1
				//lets make it a 10 digit number that has the 1 removed
				$clean_number = substr($clean_number,1,10);
			}
		}
		
		if(strlen($clean_number) == 10){
			//then this is a clean, good old fashioned american phone number
			//and we should save it in its clean version...

		//	echo "Clean number = '$clean_number'\n";

			$check_sql = "SELECT id FROM address.number WHERE number = '$clean_number'";
			$result = f_mysql_query($check_sql);
			$row = mysqli_fetch_assoc($result);
			if(isset($row['id'])){
				return($row['id']);
			}

		}


		$insert_sql = "INSERT INTO address.number (`id`, `number`) VALUES (NULL, '$clean_number');";
		f_mysql_query($insert_sql);
		$number_id = f_mysql_insert_id();

		$insert_raw_sql = "REPLACE INTO address.rawnumber (`rawnumber`, `number_id`) VALUES ('$e_input_number','$number_id');";
		f_mysql_query($insert_raw_sql);

		return($number_id);
		
	}


	function getdisplayphone($number_id){

		$e_number_id = f_mysql_real_escape_string($number_id);
		$select_sql = "SELECT number FROM address.number WHERE id = '$e_number_id'";
		$result = f_mysql_query($select_sql);
		$row = mysqli_fetch_assoc($result);
		if(isset($row['number'])){
			return($row['number']);
		}else{
			return(false);
		}
		
	}

