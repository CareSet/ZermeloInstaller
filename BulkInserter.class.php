<?php
	//a class that accepts single insert commands (which are easier to write)
	//and caches them up into many at a time insert queries which are faster...
require_once('vendor/autoload.php');
require_once('util/mysqli.php');

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Utils\Query;

class BulkInserter{

	public $insert_sql_array = [];
	public $per_target_batch_size = []; //someday we will use this instead of a global batch size....
	public $batch_size = 100;
	public $is_debug_echo = true;
	public $table_list = [];

	private $supported_query_types = [
		'INSERT',
		'REPLACE',
		];

	private $db_link; //we need this to shutdown correctly.. 

	function __construct($db_link){
		$this->db_link = $db_link;
	}

	function __destruct(){
		$GLOBALS['DB_LINK'] = $this->db_link;
		if(count($this->insert_sql_array) > 0){
			if($this->is_debug_echo){
				echo "BulkInserter: Saving last group of inserts before shutdown\n";
			}
			$this->run_all_batch_insert();
			if($this->is_debug_echo){
				echo "\nBulkInserter: all done.\n";
			}
			
		}
	}


	public function run_all_batch_insert(){
		foreach($this->insert_sql_array as $target => $sql_array){
			$this->run_batch_insert_on_target($target);
		}
	}

	public function run_batch_insert_on_target($target = null){
		if(is_null($target)){
			echo "Error: target parameter cannot be null\n";
			exit(-1);
		}

		if(!isset($this->insert_sql_array[$target])){
			echo "Error: $target is not a db.table or table that I have insert sqls for..\n";
			exit(-1);
		}

		if(count($this->insert_sql_array[$target]) > 0){
			//the magic...
			$first_insert = trim(array_pop($this->insert_sql_array[$target]));
			$query = rtrim($first_insert,';'); //lets get ride of that first semicolon...

//			echo "\nStarting query = $query\n";
		
			//this technically starts on the second query
			foreach($this->insert_sql_array[$target] as $this_query){
				list($trash,$values_string) = explode('VALUES',$this_query);
				$values_string = rtrim(trim($values_string),';');
				$query .= ", $values_string";
			}
	
			if($this->is_debug_echo){
				echo '.';
			}
//			echo "Running $query\n";
			f_mysql_query($query);	


			//ok, data saved... empty the insert sql array... 
			$this->insert_sql_array[$target] = [];	
		}else{
			if($this->is_debug_echo){
				echo "Nothing more to save for $target\n";
			}
		}

	}


	public function add_insert_query($insert_sql){

	//	echo "Adding \n$insert_sql\n";

		$is_ok_type = false;
		foreach($this->supported_query_types as $ok_query_type){
			if(strpos($insert_sql,$ok_query_type) !== false){
				$is_ok_type = true;
			}
		}
	
		if(!$is_ok_type){
			echo "Error: I do not know how to work with \n$insert_sql\n it does not look like a valid insert sql command\n";
			exit(-1);
		}

		$db_target = '';	
		preg_match("/\s+into\s+`?([a-z\d_\.]+)`?/i", $insert_sql, $match);
		if(isset($match[1])){
			$db_target = $match[1];
			$this->insert_sql_array[$db_target][] = $insert_sql;
			if(count($this->insert_sql_array[$db_target]) > $this->batch_size){
				//then it is high time that we saved a batch of data!!!
				$this->run_batch_insert_on_target($db_target);
			}

		}else{
			echo "Error: the regex to figure out what db.table did not work\n";
			exit(-1);
		}

	}


}
