<?php

Route::post('/chart-export', 'ChartController@export')->name('chart-export');

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

//
//Route::group([
//    'namespace' => "App\\Http\\Controllers",
//], function () {
//    $prefix = config('cupparis-site.route_prefix');
//    Route::get("$prefix", 'CupSiteController@page')->name('cup_site');
//    Route::get("$prefix/{menu?}", 'CupSiteController@page')->name('cup_site');
//    Route::get("$prefix/news/{menu?}", 'CupSiteController@news')->name('cup_site_news');
//    Route::get("$prefix/eventi/{menu?}", 'CupSiteController@news')->name('cup_site_eventi');
//    //Route::get("cup-site-admin", 'CupSiteController@admin')->name('cup_site_admin');
//});
//
//
//Route::group([
//    'namespace' => "App\\Http\\Controllers",
//    'middleware' => 'web'
//], function () {
//    //$prefix = config('cupparis-site.route_prefix');
//    //Route::get("$prefix/{menu?}", 'CupSiteController@page')->name('cup_site');
//    Route::get("cup-site-admin", 'CupSiteController@admin')->name('cup_site_admin');
//});

Route::prefix('cupsite')->group(function() {
    Route::get('/', 'CupSiteController@index');
});
