<?php

/**
 * Simple die/var_dump to to see variables/elements
 * Similar to laravel's dd command
 * @param  all  $text 	  element to display, can be any type.
 * @param  boolean $pre   will append <pre> before the display
 * @return none 		  will halt script
 */
function debug($text, $pre=false)
{
	if($pre) echo "<pre>";
	die(var_dump($text)."\r\n");
}

/**
 * Create a temporary directory inside the system temporary directory with the defined prefix
 * @param  string $prefix prefix to directory
 * @return string         directory path
 */
function tempdir($prefix='') 
{
    $tempfile=tempnam(sys_get_temp_dir(),$prefix);
    if (file_exists($tempfile)) 
	{ 
		unlink($tempfile); 
	}
    mkdir($tempfile);
    if (is_dir($tempfile)) 
	{ 
		return $tempfile; 
	}
    return null;
}


/**
 * Creates a temporary file in the system temporary directory with the defined prefix
 * @param  string $prefix prefix to file
 * @return string         file path
 */
function tempfile($prefix='') 
{
	return tempnam(sys_get_temp_dir(),$prefix);
}


/**
 * Recursive glob.
 * @param  string  $pattern pattern to match
 * @param  integer $flags   glob flag. see php glob function
 * @return array 			array of strings (file paths)
 */
function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags); 
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}


/**
 * Zip the content of a folder into a temporary zip file
 * @param  string $directory directory to zip
 * @return string            path to zip file
 */
function zip_folder($directory)
{

	$tmp_file_file = tempfile("zip_folder_");

	// Get real path for our folder
	$rootPath = realpath($directory);


	// Initialize archive object
	$zip = new ZipArchive();
	$zip->open($tmp_file_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

	// Create recursive directory iterator
	/** @var SplFileInfo[] $files */
	$files = new RecursiveIteratorIterator(
	    new RecursiveDirectoryIterator($rootPath),
	    RecursiveIteratorIterator::LEAVES_ONLY
	);

	foreach ($files as $name => $file)
	{
	    // Skip directories (they would be added automatically)
	    if (!$file->isDir())
	    {
	        // Get real and relative path for current file
	        $filePath = $file->getRealPath();
	        $relativePath = substr($filePath, strlen($rootPath) + 1);

	        // Add current file to archive
	        $zip->addFile($filePath, $relativePath);
	    }
	}

	// Zip archive will be created only after closing object
	$zip->close();


	return $tmp_file_file;
}


/**
 * Dummy Slack function.
 * @param  string $message  Message to be displayed
 * @param  string $channel  Channel to send message to
 * @param  string $username UserName that will be sending message
 * @param  string $url      slack post-url
 * @return true             
 */
function slack($message,$channel="",$username="",$url='')
{
    return true;
}



?>