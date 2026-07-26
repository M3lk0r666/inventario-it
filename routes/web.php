<?php

use Illuminate\Support\Facades\Route;

/* Route::get('/', function () {
    return view('welcome');
});
 */

// Página pública de bienvenida (entrada principal al portal).
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Compatibilidad con enlaces de Jetstream (route('dashboard')) → panel admin.
Route::redirect('/dashboard', '/admin')->name('dashboard');

