<?php

use Illuminate\Support\Facades\Route;



/*Route::get('/api/documentation', function () {
    if (App::environment('local')) {
        return view('swagger.index');
    }
    abort(403, 'Access denied');
});*/

Route::get('/api/documentation', function () {
    return view('swagger.index');
});


