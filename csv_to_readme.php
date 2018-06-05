<?php
/*
	This file accepts a csv file as an argument, and echo a markdown readme with the details of the file. 
	Very useful for initially creating documentation of the fields that are inside a given csv file. 
*/

	require_once('vendor/autoload.php');

	if(!isset($argv[1])){
		echo "USAGE: enter a csv that you would like to create markdown for as the first and only argument to this program\n";
		exit();
	}

	$file_name = $argv[1];
	if(!file_exists($file_name)){
		echo "Error: $file_name does not exist\n";
		exit();
	}

	$file_contents = file($file_name);

	$header_row = $file_contents[0];

	$rows_of_data = count($file_contents) - 1; 

	$header_array = str_getcsv($header_row);

	var_export($header_array);

	$output_file_name = readline("What should the name of the resulting markdown file be? ");

	if(file_exists($output_file_name)){
		echo "Error: $output_file_name already exists\n";
		exit();
	}

	$file_title = readline("What is the name of this type of file? ");

	$file_description = readline("What is the decription of the file contents? ");

	$markdown = "## $file_title\n $file_description \n typical file has $rows_of_data rows of data\n\n ### Field List\n ";

	foreach($header_array as $data_row){

		$variable_desc = readline("Description of $data_row (enter for none): ");
		$markdown .= "* `$data_row` - $variable_desc\n";

	}
	
	echo $markdown;

	echo "saving to $output_file_name...  ";

	file_put_contents($output_file_name,$markdown);

	echo "done\n";
	
