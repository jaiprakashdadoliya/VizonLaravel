<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Profile photo
Route::get('media/{source}/{image_name}', '\App\Modules\Admin\Controllers\AdminController@get_media');

// Live live streaming
Route::get('live-streaming/{id}', 'LiveStreamingController@doLiveStreaming');

// Auth module
Route::group(['module' => 'Auth', 'middleware' => ['api'], 'namespace' => 'App\Modules\Auth\Controllers'], function() {
	// Device registration
	Route::post('device_registration', '\App\Modules\Auth\Controllers\AuthController@device_registration');
	//Login
	Route::post('login', '\App\Modules\Auth\Controllers\AuthController@post_login');
	// Register 
	Route::post('register', '\App\Modules\Auth\Controllers\AuthController@post_register');	
	//Logout
	Route::post('logout', '\App\Modules\Auth\Controllers\AuthController@post_logout');
	//Admin Login
	Route::post('admin_login', '\App\Modules\Auth\Controllers\AuthController@admin_login');
	//Logout
	Route::post('admin_logout', '\App\Modules\Auth\Controllers\AuthController@admin_logout');
	//Reset Password
	Route::post('forgot_password', '\App\Modules\Auth\Controllers\AuthController@get_reset_password');
	// importMongoData
	Route::get('importData', '\App\Modules\Auth\Controllers\AuthController@importMongoData');

});

// User module
Route::group(['module' => 'User', 'middleware' => ['auth:api'], 'namespace' => 'App\Modules\User\Controllers'], function() {
	// Update user 
	Route::post('update_user', '\App\Modules\User\Controllers\UserController@update_user');
	// get user
	Route::post('get_user', '\App\Modules\User\Controllers\UserController@get_user');
	// change password
	Route::post('change_password', '\App\Modules\User\Controllers\UserController@change_password');
	// get_trip
	Route::post('get_trip', '\App\Modules\User\Controllers\UserController@get_trip');
	// save_trip
	Route::post('save_trip', '\App\Modules\User\Controllers\UserController@save_trip');
	// get_dashboard_count
	Route::post('get_dashboard_count', '\App\Modules\User\Controllers\UserController@get_dashboard_count');
	// block_user
	Route::post('block_user', '\App\Modules\User\Controllers\UserController@block_user');
	// update miles
	Route::post('update_miles', '\App\Modules\User\Controllers\UserController@update_miles');
	// insert_user_video_api
	Route::post('insert_user_video', '\App\Modules\User\Controllers\UserVideoController@insert_user_video');
	// get_user_video_api
	Route::post('get_user_video', '\App\Modules\User\Controllers\UserVideoController@get_user_video');
	// delete_user_video_api
	Route::post('delete_user_video', '\App\Modules\User\Controllers\UserVideoController@delete_user_video');
	// get_all_user_videos_according to company id
	Route::post('get_all_user_video', '\App\Modules\User\Controllers\UserVideoController@get_all_user_videos');
	// get_user_trip_location
	Route::post('get_user_trip_locations', '\App\Modules\User\Controllers\UserController@get_user_trip_locations');
	// test_s3
	Route::post('test_s3_video', '\App\Modules\User\Controllers\UserVideoController@test_s3_video');

});

Route::post('save_location', '\App\Modules\User\Controllers\UserController@save_location');

// Driver module
Route::group(['module' => 'Admin', 'middleware' => ['auth:api'], 'namespace' => 'App\Modules\Admin\Controllers'], function() {
	// Get Driver list
	Route::post('drivers', '\App\Modules\Admin\Controllers\DriverController@index');
	Route::post('add_driver', '\App\Modules\Admin\Controllers\DriverController@store');
	Route::post('delete_driver', '\App\Modules\Admin\Controllers\DriverController@destroy');
});

// Vehicles module
Route::group(['module' => 'Admin', 'middleware' => ['auth:api'], 'namespace' => 'App\Modules\Admin\Controllers'], function() {
	//Parents
	Route::post('parents', '\App\Modules\Admin\Controllers\VehicleController@parents');
	//Parent password send by email
	Route::post('create_password', '\App\Modules\Admin\Controllers\VehicleController@create_password');
	//Stoppage
	Route::post('stoppage', '\App\Modules\Admin\Controllers\VehicleController@stoppage');
	//Students
	Route::post('students', '\App\Modules\Admin\Controllers\VehicleController@students');
	//Vehicles
	Route::post('vehicles', '\App\Modules\Admin\Controllers\VehicleController@vehicles');
	// Save vehicle list
	Route::post('add_vehicles', '\App\Modules\Admin\Controllers\VehicleController@add_vehicles');
	// Delete vehicle
	Route::post('delete_vehicle', '\App\Modules\Admin\Controllers\VehicleController@delete_vehicle');
	
	// vehicle assignments
	Route::post('get_vehicle_assignments', '\App\Modules\Admin\Controllers\VehicleController@get_vehicle_assignments');
	// add vehcle assignment
	Route::post('add_vehicles_assignments', '\App\Modules\Admin\Controllers\VehicleController@add_vehicles_assignments');
	// delete vehicle assignmnet
	Route::post('delete_vehicles_assignments', '\App\Modules\Admin\Controllers\VehicleController@delete_vehicles_assignments');

});

Route::group(['module' => 'Search', 'middleware' => ['auth:api'], 'namespace' => 'App\Modules\Search\Controllers'], function() {
    // Get User Videos
    Route::post('get_user_videos', '\App\Modules\Search\Controllers\SearchController@get_user_videos');
    // Get User Videos Url
    Route::post('get_user_video_url', '\App\Modules\Search\Controllers\SearchController@get_user_video_url');
});

Route::group(['middleware' => ['auth:api', 'App\Http\Middleware\AuthTokenMiddleware']], function() {
    Route::get('vehicle-image/{imagePath}', '\App\Modules\Admin\Controllers\VehicleController@getVehicleImage');
    Route::get('driver-image/{imagePath}', '\App\Modules\Admin\Controllers\DriverController@getDriverImage');
    
});
Route::get('s3_image_url/{imagePath}', '\App\Modules\User\Controllers\UserVideoController@s3_image_url');