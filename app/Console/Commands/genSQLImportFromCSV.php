<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class genSQLImportFromCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ebb:gen_raw_sql_from_csv {csv_file_full_path} {target_database} {target_table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate import SQL from a sample CSV file';

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
	$target_database = $this->argument('target_database');
	$target_table = $this->argument('target_table');

//	echo "Trying to create SQL from \n\t$file_path\n into $target_database.$target_table\n";

	$sql_array = \App\CSVTools::generateImportSQLArray($file_path,$target_database,$target_table);

        foreach($sql_array as $this_sql){
                echo "$this_sql;\n\n";
        }

    }
}
