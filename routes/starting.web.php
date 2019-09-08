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

Route::get('changeCard/{channel_id}/{multiverse_id}', 'cardShowController@sendCardPush');
Route::get('showCard/{channel_id}', 'cardShowController@showCard');

Route::get('genericLinkerForm/{durc_type_left}/{durc_type_right}/{durc_type_link}','GenericLinker@linkForm');
Route::post('genericLinkerSave/{durc_type_left}/{durc_type_right}/{durc_type_link}','GenericLinker@linkSaver');

Route::get('pusher', function () {


        $app_key = config('broadcasting.connections.pusher.key');
        $app_secret = config('broadcasting.connections.pusher.secret');
        $app_id = config('broadcasting.connections.pusher.app_id');
        $cluster = config('broadcasting.connections.pusher.options.cluster');

        echo "Creating pusher with \n\tapp_key:$app_key\n\tapp_secret:$app_secret\n\tapp_id:$app_id\n\tapp_cluster:$cluster\n";

        $pusher = new \Pusher\Pusher($app_key, $app_secret, $app_id,['cluster' => $cluster]);

        $pusher->trigger( 'in_closure', 'my_event', 'hello world' );

        //return view('pusher_test');

});

