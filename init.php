<?php
	//this is the script that sets up Eden...

	$userinfo = posix_getpwuid(posix_geteuid());

	if(!$userinfo['name'] == 'root'){
		echo "This file needs to be run as root..\n";
		exit();
	}

	$real_user = trim(shell_exec('whoami'));

	echo "running as root, but from |$real_user| unix account\n";

	$cmds = [
		"sudo -u $real_user cp .env.example .env",
		"sudo -u $real_user composer update",
		"sudo -u $real_user php artisan key:generate",
		"sudo -u $real_user cp ./templates/ReadMe.template.md README.md",
		"sudo -u $real_user php artisan vendor:publish --provider='CareSet\DURC\DURCServiceProvider'",
		"sudo -u $real_user php artisan vendor:publish --tag=laravel-handlebars",
		"chmod g+w storage/* -R", //this will actually be run as root!!
		];


	if(!file_exists('.env')){
		array_unshift($cmds,"sudo -u $real_user cp .env.example .env");	
	}else{
		echo "The .env file already exists, so we are not deleting it\n";
	}

	foreach($cmds as $this_command){
		echo "Running $this_command\n";
		system($this_command);
	}

/*
// for now, we are ignoring the installation of zermelo, because it requires the database to be configured
#these commands need to run as the regular user...
#create our local .env file, it is ignored by .gitignore and is where lots of good configirations live
sudo -u $real_user php artisan install:zermelo
sudo -u $real_user php artisan install:zermelobladetabular
sudo -u $real_user php artisan install:zermelobladecard
*/

