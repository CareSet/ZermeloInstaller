<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    realpath(__DIR__.'/../')
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

$app->configureMonologUsing(function ($monolog) {

        $pdo = \DB::connection()->getPdo();

        //is there a log config set?
        $db = env('LOG_DB',false);
        if(!$db){
                //guess not. lets us the default database for the app.
                $db = env('DB_DATABASE');
        }

        $extra_fields = []; //this is not really nessecary anymore should redesign the logger not to use it..
        //Create MysqlHandler
        $mySQLHandler = new \TwoMySQLHandler\TwoMySQLHandler($pdo,$db, "log_message", "log_context", $extra_fields, \Monolog\Logger::WARNING);

        $monolog->pushHandler($mySQLHandler);
});



return $app;
