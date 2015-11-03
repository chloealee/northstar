<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return redirect()->to('https://github.com/DoSomething/api');
});

// https://api.dosomething.org/v1/
Route::group(['prefix' => 'v1', 'middleware' => 'auth.api'], function () {
    // Campaigns.
    Route::group(['middleware' => 'auth.token'], function () {
        Route::post('kudos', 'KudosController@store');
        Route::delete('kudos', 'KudosController@delete');

        Route::group(['middleware' => 'campaign'], function () {
            Route::get('user/campaigns/{campaign_id}', 'CampaignController@show');
            Route::post('user/campaigns/{campaign_id}/signup', 'CampaignController@signup');
            Route::post('user/campaigns/{campaign_id}/reportback', 'CampaignController@reportback');
            Route::put('user/campaigns/{campaign_id}/reportback', 'CampaignController@reportback');
            Route::post('forwardSignup', 'CampaignController@forwardSignup');
        });
    });

    // Sessions.
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');

    // Users.
    Route::group(['middleware' => 'user'], function () {
        Route::resource('users', 'UserController');
        Route::get('users/{term}/{id}', 'UserController@show');
        Route::post('users/{id}/avatar', 'AvatarController@store');
    });

    // User campaign activity.
    Route::group(['middleware' => 'campaign'], function () {
        Route::get('users/{term}/{id}/campaigns', 'CampaignController@index');
    });

    // Signup Groups.
    Route::resource('signup-group', 'SignupGroupController');
    Route::get('signup-group/{id}', 'SignupGroupController@show');

    // Api Keys.
    Route::resource('keys', 'KeyController');

});
