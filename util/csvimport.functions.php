<?php

class CSVImportFunctions {


	public static function get_static_field_data_cache($cache_file_name,$cache_dir=null){

		if(!file_exists($cache_file_name)){
			if($cache_dir==null) $cache_dir = __DIR__."/field_guess_cache/";
			$cache_file_name = $cache_dir . $cache_file_name;
			if(!file_exists($cache_file_name)){
				echo "CSVImportFunctions Error: get_static_field_data_cache: I could not find $cache_file_name\n";
				exit();
			}
		}

 		return(json_decode(file_get_contents($cache_file_name),true));
	}


	public static function manually_create_field_data_from_first_line($this_csv_file,$default_type = "VARCHAR(255)"){

		if (($handle = fopen($this_csv_file, "r")) !== FALSE) {
    			$first_line = fgetcsv($handle, 100000, ",");
			if($first_line){
				foreach($first_line as $this_field){
					$field_array[] = CSVImportFunctions::build_clean_db_name($this_field);
				}
			}else{
				echo "I could not read the first line from $this_csv\n";
			}

		}
		
		$return_me = [];
		foreach($field_array as $this_field_name){
			$tmp = [];
			$tmp['field'] = $this_field_name;
			$tmp['should_index'] = false;
			$tmp['type'] = $default_type;
			$return_me[] = $tmp;
		}

		return($return_me);

	}

	public static function guess_field_data_from_csv_file_first_100000_lines($this_csv_file,$default_type = "VARCHAR(255)",$cache_dir=null){

		$debug = true;

	 	if($cache_dir==null) $cache_dir = __DIR__."/field_guess_cache/";
	
		$field_array =	[]; 

		$field_data = [];
			

		if(($handle = fopen($this_csv_file, "r")) !== FALSE) {
    		
    		$first_line = fgetcsv($handle/*, 100000, ","*/);
			if($first_line){
				foreach($first_line as $this_field){
					$field_array[] = CSVImportFunctions::build_clean_db_name($this_field);
				}
			}else{
				echo "I could not read the first line from $this_csv\n";
			}

			$use_cache = true;

			//this is where we implement MD5 caching
			$md5_sum = md5(serialize($field_array));
			$csv_alone = basename($this_csv_file);
			$cache_file = "$cache_dir$md5_sum.$csv_alone.json";
			echo "Checking to see if $cache_file exists\n";
			if(file_exists($cache_file) && $use_cache){
				echo "Returning cache contents\n";
				return(json_decode(file_get_contents($cache_file),true));
			}else{
				echo "No cache, processing file\n";
			}


			$long_guess_array = [];

			$read_enough= false;
			$all_rows = [];
			$row_count = 0; /*dont count header*/
    			while (($data = fgetcsv($handle/*, 100000, ","*/)) !== FALSE) {

    				if(++$row_count>100000) break;

				$mapped_data = [];
				foreach($data as $index => $value){
					$mapped_data[$field_array[$index]] = $value;
				}
				$all_rows[] = $mapped_data;
			}

			return CSVImportFunctions::guess_field_types($all_rows,$field_array,$default_type,$csv_alone,$cache_dir);
		}else{
			echo "guess_field_data_from_csv_file ERROR: I could not read the file $this_csv_file\n";
			exit();
		}

		}//end function...

	public static function guess_field_data_from_csv_file($this_csv_file,$default_type = "VARCHAR(255)",$cache_dir=null){

		$debug = true;

	 	if($cache_dir==null) $cache_dir = __DIR__."/field_guess_cache/";
	
		$field_array =	[]; 

		$field_data = [];
			
		if(($handle = fopen($this_csv_file, "r")) !== FALSE) {
    			$first_line = fgetcsv($handle, 100000, ",");
			if($first_line){
				foreach($first_line as $this_field){
					$field_array[] = CSVImportFunctions::build_clean_db_name($this_field);
				}
			}else{
				echo "I could not read the first line from $this_csv\n";
			}

			$use_cache = true;

			//this is where we implement MD5 caching
			$md5_sum = md5(serialize($field_array));
			$csv_alone = basename($this_csv_file);
			$cache_file = "$cache_dir$md5_sum.$csv_alone.json";
			echo "Checking to see if $cache_file exists\n";
			if(file_exists($cache_file) && $use_cache){
				echo "Returning cache contents\n";
				return(json_decode(file_get_contents($cache_file),true));
			}else{
				echo "No cache, processing file\n";
			}


			$long_guess_array = [];

			$read_enough= false;
			$all_rows = [];
    			while (($data = fgetcsv($handle, 100000, ",")) !== FALSE) {

				$mapped_data = [];
				foreach($data as $index => $value){
					$mapped_data[$field_array[$index]] = $value;
				}
				$all_rows[] = $mapped_data;
			}

			return CSVImportFunctions::guess_field_types($all_rows,$field_array,$default_type,$csv_alone,$cache_dir);
		}else{
			echo "guess_field_data_from_csv_file ERROR: I could not read the file $this_csv_file\n";
			exit();
		}

		}//end function...


	function guess_field_types($all_rows, $field_array, $default_type, $cache_file_stub = false,$cache_dir=null){

			$debug = false;

		$code_strings = [
			'_icd',
			'_cpt',
			'code',
			'coding_system',
			'_drg',
			];

		$long_strings = [
			'_name',
			'suffix',
			'credential',
			'specialt', //matches specialty and specialties
			'address',
		];

		$indexed_strings = [
			'city',
			'state',
			'zipcode',
			'zip_code',
			'postal',
			];



			if($cache_dir==null) $cache_dir = __DIR__."/field_guess_cache/";
			//$cache_dir = __DIR__."/field_guess_cache/";

			$md5_sum = md5(serialize($field_array));
			$cache_file = "$cache_dir$md5_sum.$cache_file_stub.json";

			$field_data = [];
			$found = false;
			foreach($all_rows as $mapped_data){
				if($found) break;
				foreach($mapped_data as $this_field => $this_value){	
					if($found) break;
					if($debug){echo "working on $this_field -> $this_value\n";}	
					if(isset($field_data[$this_field])){
						//then this field has been sorted... we should move on..
					}else{
						//this is where we sort the field...
						if(strlen($this_value) > 0){

							$type = false;
							$should_index = false;
							//ok now lets look at the contents and make some smart DB decisions...
                        				if(strpos($this_field,'_npi') !== false || $this_field == 'npi' || $this_field == 'national_provider_identifier'){
                                				$type = "BIGINT(11)";
								$should_index = true;
                       	 				}
						
							foreach($code_strings as $this_code){
                        					if(strpos($this_field,$this_code) !== false){
									$type = "VARCHAR(10)";
									$should_index = true;
                        					}
							}

							foreach($long_strings as $this_string){
                        					if(strpos($this_field,$this_string) !== false){
									$type = "VARCHAR(255)";
									$should_index = false;
                        					}
							}

							foreach($indexed_strings as $this_string){
                        					if(strpos($this_field,$this_string) !== false){
									$type = "VARCHAR(255)";
									$should_index = true;
                        					}
							}



			
							if(!$type){	//then the type cannot be guessed with trivial guesses...
								$this_length = strlen($this_value);
								if(isset($long_guess_array[$this_field])){
									$guess = $long_guess_array[$this_field];
									if($this_length > $guess['max_length']){
										$guess['max_length'] = $this_length;
									}
								}else{
									$guess = [	//set everything to false.. lets us use extract later
										'is_string' => false,
										'is_float' => false,
										'is_bigint' => false,
										'is_int' => false,
										'max_length' => $this_length,
										];
									echo "Seeing $this_field for the first time\n";
								}	
										
								if(!is_numeric($this_value)){	//if its a string even once.. its a string 
									if(!$guess['is_string']){
										echo "$this_field set to string";
									}
									$guess['is_string'] = true;
								}else{	
									if(is_string_float($this_value)){
										$guess['is_float'] = true;
									}else{
										if($this_value > 2147483647 || $this_value < -2147483648){ //these are max and min for INT in MYSQL
											$guess['is_bigint'] = true;
										}else{
											$guess['is_int'] = true;
										}
									}

								}

								$long_guess_array[$this_field] = $guess;

							}else{
								//then we know this fields type
								$field_data[$this_field] = [
									'field' => $this_field,
									'should_index' => $should_index,
									'type' => $type,
										];
								if($debug){
									echo "$this_field guessed as $type and should_index $should_index\n";	
								}
							}

							//if(count($field_data) == count($field_array)){
								//then we are done... every field has a type guess..
								//return($field_data);
							//	$found=true;
							//	break;
							//}
							

						}else{
							//this field is blank.. moving on...
						}
					
					}//end if we have not processed this field
				}//end foreach field
			}
			echo "Here are my type guess results\n";
			var_export($long_guess_array);


			foreach($long_guess_array as $field => $this_guess){
			
				extract($this_guess);
				$tmp = ['field' => $field, 'should_index' => false];	
		
				if($is_string){
					$type	= "VARCHAR($max_length)";
				}else{
					if($is_float){
						$type = "FLOAT";
					}else{
						if($is_bigint){
							$type="BIGINT(20)";
						}else{
							$type="INT(11)";
						}
						
					}

				}
		
				$tmp['type'] = $type;
				
				$field_data[$field] = $tmp;			
			}

			if($debug){
			//	echo "Guesses\n";
			//	var_export($long_guess_array);
			//	echo "Field Data\n";
			//	var_export($field_data);
			}


			//we have to order the fields exactly like they appear in the field_array...
			//position matters
			$new_field_data = [];
			foreach($field_array as $index => $this_field){
				$new_field_data[$index] = $field_data[$this_field];
			}
			$field_data = $new_field_data;

			$json_string = json_encode($field_data, JSON_PRETTY_PRINT);
			if($cache_file){
				file_put_contents($cache_file,$json_string);
			}
			return($field_data);


	}	




//from http://stackoverflow.com/a/14114419/14436
	public static function build_clean_db_name($string,$length = 64){ //64 from http://stackoverflow.com/a/3486844/144364
        	$string = str_replace('.txt', '', $string); // Replaces all spaces with hyphens.
        	$string = str_replace('.TXT', '', $string); // Replaces all spaces with hyphens.
        	$string = str_replace('.csv', '', $string); // Replaces all spaces with hyphens.
        	$string = str_replace('.CSV', '', $string); // Replaces all spaces with hyphens.
		$string = trim($string); //remove spaces at front and back...	
        	$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        	$string = str_replace('_', '-', $string); // Replaces all spaces with hyphens.
        	$string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
		$string = trim($string,'-'); //remove hypens at the begining or end...
        	$string = preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
        	$string =  str_replace('-','_',$string);
        	$string = str_replace('_NPI_Number', '_npi', $string); // Replaces all spaces with hyphens.
        	$string = strtolower($string);
        	$string = str_replace('national_provider_identifier', 'npi', $string); // Replaces all spaces with hyphens.
        	$string = substr($string,0,$length);
        	return($string);
	}

} // end class


function is_string_float($f){
	 return ($f == (string)(float)$f);
}

?>
