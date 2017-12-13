<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/mockup',function () {
        $content = view('dashboard_mockup');
        $test_data = ['content' => $content];
        return view('main_html',$test_data);
});


Route::middleware([\CareSet\CareSetJWTAuthClient\Middleware\JWTClientMiddleware::class])->group(function () {

	Route::get('/test',function() {

		return 'Hello World!';

	});

});