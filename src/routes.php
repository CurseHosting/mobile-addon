<?php

use Illuminate\Http\Request;
use Fruitcake\Cors\HandleCors;
use App\MobileAddon\Middleware\InfoDecorator;

Route::group([
    'prefix' => 'api/app/v1',
    'middleware' => [
        HandleCors::class,
        InfoDecorator::class,
    ],
], function() {
    Route::get('info', 'App\MobileAddon\Controllers\InfoController@index');
    Route::get('user', 'App\MobileAddon\Controllers\UserController@info');
    Route::post('token', 'App\MobileAddon\Controllers\AuthController@login');
    Route::delete('token', 'App\MobileAddon\Controllers\AuthController@logout');
    Route::post('token/exchange', 'App\MobileAddon\Controllers\AuthController@exchange');
    Route::get('servers', 'App\MobileAddon\Controllers\ServersController@index');
    Route::get('servers/{serverId}', 'App\MobileAddon\Controllers\ServerController@get');
});
