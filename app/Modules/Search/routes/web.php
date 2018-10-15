<?php

Route::group(['module' => 'Search', 'middleware' => ['web'], 'namespace' => 'App\Modules\Search\Controllers'], function() {

    Route::resource('Search', 'SearchController');

});
