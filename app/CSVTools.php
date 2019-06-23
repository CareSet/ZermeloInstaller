<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


if(isset($argv[0]) && $argv[0] == basename(__FILE__)){
        //then we are testing via direct call...
        //lets test this thing

	if(isset($argv[1])){
		if(file_exists($argv[1])){
			$file = $argv[1];
		}else{
			echo "Error: you gave an argument, but it is not a file that exists...\n";
			exit(-1);
		}
	}else{
		//by default test on this file...
		$gdrive_path = "/home/storage2/gdrive_rclone/DocGraph/DG_Data/";
		$file_path = "VRDC/FTR_37240 (Insitutional Affiliation with correct RNDRNG NPI)/D_INST_OTON_SETTING_RIFQ2016.csv";
		$file = $gdrive_path.$file_path;
	}

	if(isset($argv[3])){
		$target_db  = $argv[2];
		$target_table = $argv[3];
	}else{
		$target_db = 'testdb';
		$target_table = 'testtable';
	}


//        $schema_data =  CSVTools::getSchemaFromCSV($file);
//	$table_sql = CSVTools::generateCreateTableSQL($target_db,$target_table,$schema_data);
	$sql_array = CSVTools::generateImportSQLArray($file,$target_db,$target_table);
	
	foreach($sql_array as $this_sql){
		echo "$this_sql;\n\n";
	}
	
}



/*

	This takes an input of a file ($file)
	and determines the column name and format based on the value of each column based on the value of each rows 
	(by default, it will only look at $limit = 100000 rows).
	
	Optionally, it can take a previous json format and use the json format as a starting point for the column format.
	This is effective when having multiple files of the same hash (same bolt), it will make sure all the files are parsed to determine the correct format.
	
	It uses a cascading logic to determine the column data type -
		the order of the cascade is listed below: 
                                                                             	0 => "INT",
                                                                                1 => "BIGINT",
                                                                                2 => "DECIMAL",
                                                                                3 => "VARCHAR"


		It will find the first 'cascading rule' that matches starting from the previous found rule for that column.
		If the new rule is higher (index is higher), it will use the new rule.
		a rule index can never go below the rule index.
		
		Example -
			HCPCS_CD
			'0',
			'AABB',
			'123',
			'AABBCC'
	
		First row - rule index set to 0 for 'integer'
		Second row - rule updated, set to 3 for VARCHAR, length of 4.
		Third row - rule still at 3 for VARCHAR, since it cannot go from 3 to 0.
		Fourth row - rule still at 3 for VARCHAR, length updated to 6.

	validate and length callback are functions to determine if value matches that rule index.
	keyword - forcing a column to START at the rule index, example - a column with 'npi' will always jump to bigint and skip int.

	//Note this file was imported from the babybump DataSetImporterFile file
	//originall written by phu, tweaked and made static by Fred
*/
class CSVTools
{

	//max length should be 64, but doing 60 for fields that are the same in the first 60's character to add _1,_2,_3
    const MAX_COLUMN_LENGTH = 60;



	public static $FIELD_RENAME = 	[
										'_npi_number'					=>'_npi',
										'national_provider_identifier'	=>'npi',
										'prscrbr_id' => 'prscrbr_id_npi', //this is the part D field..
									];


	//can only go up, cannot go back down
	//key = type
	//value = has length
	private static $CASCADE =   [
									[			"format"=>'int',
												"keyword"  		 => [],
												"index"			 => [],
												"validate" 		 => array('self','CheckIsInteger'),
												"length" 		 => array('self','CalculateDecimalLength') //calculate the length regardless just in case it needs to be used 
									],
									[			"format"=>'bigint',
												"keyword"  		 => ["npi","identifier"],
												"index"			 => ["npi","identifier"],
												"validate" 		 => array('self','CheckIsBigInt'),
												"length" 		 => array('self','CalculateDecimalLength') //calculate the length regardless just in case it needs to be used 
									],
									[			"format"=>'float',
												"keyword"  		 => [],
												"index"			 => [],
												"validate" 		 => array('self','CheckIsDecimal'),
												"length" 		 => array('self','CalculateDecimalLength')
									],
									[			"format"=>'varchar',
												"keyword"  		 => ["procedure","icd","cpt","code","coding","drg","name","suffix","credential","specialt","specialty","address","city","state","zip","postal","zipcode"],
												"index"			 => ["procedure","hcpcs","ndc","icd","cpt","code","coding","drg","name"],
												"validate" 		 => array('self','CheckIsString'),
												"length" 		 => array('self','CalculateStringLength')
									],
								];


	//faster index to type conversion, should match $CASCADE
	public static $FORMAT_TYPE = 	[
										0 => "INT",
										1 => "BIGINT",
										2 => "DECIMAL",
										3 => "VARCHAR"
									];
//this is pulled from console command ParentHoodImport.php 
/*
		This function accepts a fully qualified path


*/ 
	public static function generateImportSQLArray($real_file,$target_database,$target_table,$target_file = null, $delimiter  = ','){

		if(!file_exists($real_file)){
			echo "CSVTools::generateImportSQLArray() Error: file $real_file does not exist. \n";
			exit(-1);
		}

		//sometimes we want an abstract version of the file import...
		//so we want to study an example file to import, but then put a template variable into the actual sql..
		//which will we can find and replace later...
		if(is_null($target_file)){
			$target_file = $real_file;
		}

		$return_sql = [];	

        	$schema_data =  CSVTools::getSchemaFromCSV($real_file); //lets use the default file scanning length...
		$eol = \App\CSVTools::detectEOF($real_file);//and to find the EOF

		if(!$eol) $eol = "\\n";

		$return_sql[] = "CREATE DATABASE IF NOT EXISTS {$target_database}";
		$return_sql[] = "DROP TABLE IF EXISTS {$target_database}.{$target_table}";
		$return_sql[] = CSVTools::generateCreateTableSQL($target_database,$target_table,$schema_data);
		$return_sql[] = "
LOAD DATA LOCAL INFILE '{$target_file}'
INTO TABLE {$target_database}.{$target_table}
FIELDS ENCLOSED BY '\"'
TERMINATED BY '{$delimiter}'
ESCAPED BY '\"'
LINES TERMINATED BY '{$eol}'
IGNORE 1 LINES
";

		return($return_sql);

	}


	//pass in the file (full path), the number of lines to consider for column type and a delimiter
	//the last field is how many decimals to tolerate in DECIMAL data types. 
	public static function getSchemaFromCSV($file,$limit=1000000,$delimiter=","){

		$CASCADE_LENGTH = count(self::$CASCADE);

		$f = fopen($file,"r");

		//this is the final result
		//$column_format = [];


		//get the first row (header)
		$header_row = fgetcsv($f,0,$delimiter);


		//the process here runs as follow:
		//go through the header, check the keyword, if match any, set that index to that header by default

		//go through each row and check each column, if not blank, check starting from the header start position and going down and check the values
		//by default it should be varchar(255) as last resort
		

		//this will take a stab at guessing the field type based on the column
		//this can configured at the top using the CASCADE variable
		//this also determines the column name
			//this variable is to keep track of any column name that are the same
			//this allows us to properly count and append the counter at the end
			$column_name_counter = [];

			//$i should correlate to the column index
			foreach($header_row as $i=>$col)
			{
				$column_name = preg_replace('/[^a-z0-9.\']+/i', '_', strtolower($col));

				//replaces the column with the renamer
				foreach(self::$FIELD_RENAME as $search=>$replace)
				{
					$search=strtolower($search);
					$repalce=strtolower($replace);
					$column_name = str_replace($search,$replace,$column_name);
				}

				//get the length after the rename
				$column_name = substr($column_name,0,self::MAX_COLUMN_LENGTH);
				$column_name = trim($column_name," -_\t\n\r\0\x0B");

				//keeps track of each of the column name and counter
				$column_name_counter[$column_name][] = $i;

				//split the header to different columns to catch keyword
				$col_array = preg_split('/[^a-z0-9.\']+/i', strtolower($col));

				$start_index = 0;
				$to_index = false;
				foreach($col_array as $col_word)
				{
					foreach(self::$CASCADE as $index=>$format)
					{
						if(in_array($col_word, $format['keyword']))
						{
							$start_index = $index;
						}
						if(!$to_index && in_array($col_word, $format['index']))
						{
							$to_index = true;
						}
					}
				}

				$column_format[] = [
										"name"=>$column_name,
										"format"=>"INT",
										"format_index"=>$start_index,
										"to_index"=>$to_index,
										"length"=>null,
										"found"=>false
									];


			} /*end header loop*/


			//this stage will rename the column to add the counter,
			//do we this after the loop so we get a clean _1,_2 instead of starting with _2 and so on

			foreach($column_name_counter as $column_name=>$indexes)
			{
				//this column name has more than 1 field that has it
				if(count($indexes) > 1)
				{
					//rename the column name with the proper counter
					foreach($indexes as $counter=>$i)
					{
						$n_counter = $counter+1;
						$new_name = $column_name."_{$n_counter}";

						//$i is the column index
						$column_format[$i]['name'] = $new_name;
					}
				}
			}



		$row_counter = 0;
		while( ($line = fgetcsv($f)) !== FALSE )
		{
			++$row_counter;

			//only process the first x-row if limit is non-0
			if($limit > 0 && $row_counter > $limit) break;


			//need the column to get the header target
			foreach($line as $column=>$value)
			{
				$value = trim($value);
				if($value=='') continue;
				$column_header = &$column_format[$column];

				//we want to start with the $format_index and go up till the length of the CASCADE

				for($i = $column_header['format_index'];$i<$CASCADE_LENGTH;++$i)
				{
					$validate = call_user_func(self::$CASCADE[$i]['validate'],$value);
					if($validate)
					{
						$column_header['found'] = true; //that way it will not try to default to varchar(255)
						$column_header['format_index'] = $i;

						//this format type has a length, calculate length
						//returns an array.example: [22,20,2] or [20,null,null] (for string)
						//position 
						//         0 - before decimal (or full length)
						//         2 - after decimal
						if (self::$CASCADE[$i]['length'] !== NULL)
						{
							$len = call_user_func(self::$CASCADE[$i]['length'],$value);

							if($column_header["length"] === null)
							{
								$column_header["length"] = $len;
							} else
							{

								//lets make sure if the previous where floats,
								//we combine the numbers together (+1 for dot) if the current length is null
								if(!isset($len[1]) && isset($column_header['length'][1]))
								{
									$column_header["length"] = 	[
																	$column_header['length'][0]+
																	$column_header['length'][1]
																	,null
																];
								}

								if($len[0] !== null && $len[0] > $column_header["length"][0])
									$column_header["length"][0] = $len[0];
								if($len[1] !== null && $len[1] > $column_header["length"][1])
									$column_header["length"][1] = $len[1];
							}
						}

						break;
					}

				}
			}

		} /* end file check */

		fclose($f);


		//convert between numeric format index to readable format
		foreach($column_format as $index=>$column)
		{
			$column_format[$index]['format'] = self::$FORMAT_TYPE[$column['format_index']];
			unset($column_format[$index]['format_index']);

			//reformat the column length if float type. this is for user-friendly JSON editing.
			if(isset($column['length'][1]))
			{
				$full_length = $column['length'][0]+$column['length'][1];
				$column_format[$index]['length'] = [ $full_length,$column['length'][1]];
			}

		}
		return $column_format;
	}


	//integer check
	public static function CheckIsInteger($value)
	{
		return (is_numeric($value) && preg_match('/^\d+$/',$value) && ($value == $value*1) && $value<2147483647 && $value>-2147483648);
	}

	//big int check
	//since this is cascaded, do not need to check the value, just that its numeric
	public static function CheckIsBigInt($value)
	{
		return (is_numeric($value) && preg_match('/^\d+$/',$value) && ($value == $value*1));
	}

	//float check, this one checks to make sure theres no scientific notation
	public static function CheckIsDecimal($value)
	{
		return is_numeric($value) && (is_float($value*1)||is_int($value*1)) && preg_match('/^[\d\.]+$/',$value);
	}

	//check for string. always true since its the default
	public static function CheckIsString($value)
	{
		return true;
	}


	public static function CalculateDecimalLength($value)
	{
		$buffer = 4;		

		$value = preg_replace('/[^\d\.]$/','',$value)*1; //converts it into actual numeric
		$value .= ""; //converts it back into string just in case.

		$period = strpos($value,"."); //locate the decimal
		if($period === FALSE)
		{
			return [strlen($value) + $buffer,0];
		}
		return [$period + $buffer , (strlen($value) - $period - 1) + $buffer];
	}

	public static function CalculateStringLength($value)
	{
		return [strlen($value),null];
	}


	private static function findIndexByName($name)
	{
		$name = strtoupper($name);
		return array_search($name,self::$FORMAT_TYPE);
	}

	//given a database, table, and  database schema data structure (presumably from getSchemaFromCSV )
	//this function will return a working CREATE TABLE SQL statement
	public static function generateCreateTableSQL($database,$table_name,$schema_json){

        	$fields = [];
        	$indexes = [];
        	foreach($schema_json as $column)
        	{
                	$name = $column['name'];
                	$format = strtoupper($column['format']);

                	if($format=="VARCHAR"){
                        	if(empty($column['length'][0]))
                                	$column['length'][0] = 255;
                        	$format.="({$column['length'][0]})";
                	}
                	else if($format=="DECIMAL")
                        	$format.="({$column['length'][0]},{$column['length'][1]})";
                	$fields[] = "`{$name}` {$format}";

                	if($column['to_index'])
                 	       $indexes[] = "INDEX({$name})";
        	}
        	$all_fields = implode(",\n",array_merge($fields,$indexes));

        	return "CREATE TABLE {$database}.{$table_name} (\n{$all_fields} \n) ENGINE=MyISAM";

	}//generateCreateTableSQL

	public static function detectEOF($file_path){
           static $eols = array(
             '\\n\\r'   => "\n\r",  // 0x0A - 0x0D - acorn BBC
             '\\r\\n' => "\r\n",  // 0x0D - 0x0A - Windows, DOS OS/2
             '\\n'    => "\n",    // 0x0A -      - Unix, OSX
             '\\r'   => "\r",    // 0x0D -      - Apple ][, TRS80
          );

        if (!file_exists($file_path))
                return false;

                $handle = @fopen($file_path, "r");
                if ($handle === false)
                return false;
                $line = fgets($handle);
                fclose($handle);

        $key = "";
        $curCount = 0;
        $curEol = '';


        foreach($eols as $k => $eol) {
                if( ($count = substr_count($line, $eol)) > $curCount) {
                        $curCount = $count;
                        $curEol = $eol;
                        $key = $k;
                }
        }

        return $key;

	}//end DetectEOF



}

?>
