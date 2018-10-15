<?php

Route::group(['module' => 'Search', 'middleware' => ['api'], 'namespace' => 'App\Modules\Search\Controllers'], function() {

    Route::resource('Search', 'SearchController');

});
