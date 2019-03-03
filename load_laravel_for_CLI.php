<?php
/*
	We need to be able to use laravel eloquent from command line scripts occasionally
	and sometimes setting up artisan is a bother...
	The code below does the job.
*/
        require_once(__DIR__ . "/vendor/autoload.php");

	use Illuminate\Database\Capsule\Manager as Capsule;

	$dotenv = new \Dotenv\Dotenv(__DIR__);
	$dotenv->load();

	$capsule = new Capsule;


	$login_with = [
		"driver" => "mysql",
		"host" => env('DB_HOST'),
		"database" => env('DB_DATABASE'),
		"username" => env('DB_USERNAME'),
		"password" => env('DB_PASSWORD'),
	];

	$capsule->addConnection($login_with);

	//Make this Capsule instance available globally.
	$capsule->setAsGlobal();

	// Setup the Eloquent ORM.
	$capsule->bootEloquent();
