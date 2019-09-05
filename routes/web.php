<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// См. App\Http\Controllers\UserController
$router->put('signUp', 'UserController@signUp');
$router->post('signIn', 'UserController@signIn');
$router->patch('refreshPosition', 'UserController@refreshPosition');
$router->patch('refreshGoogleToken', 'UserController@refreshGoogleToken');
