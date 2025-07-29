<?php

use Illuminate\Support\Facades\Route;

/* // routes/web.php
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard'); */

// routes/web.php
Route::get('/{any}', function () {
    return view('app'); // render Vue SPA
})->where('any', '.*');
