<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class genPHPImportFromCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ebb:gen_php_import_from_csv {csv_file_full_path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate import php code with SQL import from a sample CSV file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $file_path = $this->argument('csv_file_full_path');

//	echo "Trying to create SQL from \n\t$file_path\n into $target_database.$target_table\n";

	$target_database = '$db';
	$target_table = '$table';
	$target_file = '$file_path';

	$sql_array = \App\CSVTools::generateImportSQLArray($file_path,$target_database,$target_table,$target_file);

	$php_code = '<?php
        require_once(__DIR__ . \'/vendor/autoload.php\');
        require_once(__DIR__ . \'/util/mysqli.php\');
        require_once(__DIR__ . \'/util/global_functions.php\');
        require_once(__DIR__ . \'/util/run_sql_loop.function.php\');


        if(!isset($argv[1])){
                $this_file = __FILE__;
                echo "Usage: $this_file {file_to_import} {target_db} {target_table} \n";
                exit();
        }

        $file_path = $argv[1];
        $db = $argv[2];
        $table = $argv[3];

        $start_time = microtime(true);

        if(!file_exists($file_path)){
                echo "Error: could not open $file_path\n";
                exit(-1);
        }
'."
//auto gen import from $file_path
".'
	$sql = [];

';

        foreach($sql_array as $this_sql){
		//the SQL we are defining needs to have double quotes (") in the string..
		//becasue we are making php code that lives inside double quotes, we use addslashes to escape those inner double quotes..
		//https://stackoverflow.com/a/5611562/144364
		$php_code .= '
	$sql[] = "
'.addcslashes($this_sql,'"').'
";
';
        }

	$php_code .= '

        $is_echo_sql = true;
        run_sql_loop($sql,$is_echo_sql,$start_time);

';


	echo $php_code;
   }
}
