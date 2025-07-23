<?php

use Illuminate\Support\Facades\Route;

// SPA用のルート - すべてのリクエストをapp.blade.phpに送信
Route::get('/{path?}', function () {
    return view('app');
})->where('path', '.*');
