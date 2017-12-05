<?php

/**
 * This is a dummy Logger function, this will not log to a file.
 * This will only output to the console
 */
class Logger 
{

	private static $_log_directory = 'log';
	private static $_prepend = "";

	/**
	 * Prepend the output text
	 * @param  string $t Text to prepend
	 * @return none
	 */
	public static function prepend($t)
	{
		self::$_prepend = $t;
	}

	/**
	 * Set the log location. This is NOT USED
	 * @param string $directory Directory to set the log
	 */
	public static function set_log_directory($directory)
	{
		self::$_log_directory = $directory;
	}

	/**
	 * Get the log location
	 * @return string Path to log location
	 */
	public static function get_log_directory()
	{
		return self::$_log_directory."/".date("Y-m-d");
	}

	/**
	 * Get the current process PID
	 * @return int process pid
	 */
	public static function get_pid()
	{
		return getmypid();
	}

	/**
	 * Check if the DEBUG flag is set to true
	 * @return boolean 
	 */
	public static function is_debug()
	{
		return defined('DEBUG') && DEBUG;
	}

	/**
	 * What is the main file that is being called. this will also be logged
	 * @return string basename of main php file that was called
	 */
	public static function get_caller_file()
	{
		$bt =  debug_backtrace();
		$last = end($bt);
		$first_file = basename($last['file']);
		return $first_file;
	}


	/**
	 * Standard Log output text
	 * @param  string $text text to output
	 * @return none
	 */
	public static function log($text)
	{
		$std_out = fopen('php://stdout', 'w');
		$message =  ( (self::$_prepend!="")?"[".self::$_prepend."]":""  ).  "[".self::get_pid()."][log]"."[".date("Y-m-d H:i:s"). "] - ".$text;
		self::_write_to_stream($std_out,$message);
		fclose($std_out);
	}

	/**
	 * Error log to output
	 * @param  string  $text text to output
	 * @param  boolean $quit will terminate the process
	 * @return none
	 */
	public static function error($text,$quit=true)
	{
		$std_error = fopen('php://stderr', 'w');	
		$message = ( (self::$_prepend!="")?"[".self::$_prepend."]":""  )."[".self::get_pid()."][error]"."[".date("Y-m-d H:i:s"). "] - ".$text;
		self::_write_to_stream($std_error,$message);
		fclose($std_error);
		if($quit) die(-1);
	}

	/**
	 * Write to stream
	 * @param  stream $stream actual stream to output to, either file or stdout/stderr
	 * @param  string $text   message to write
	 * @return none
	 */
	private static function _write_to_stream($stream, $text)
	{
		fputs($stream,$text."\r\n");
	}

}



?>