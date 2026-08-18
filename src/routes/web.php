<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;

Route::get('/', fn () => view('welcome'));

Route::get('/como-trabalhamos', fn () => view('como-trabalhamos'));

Route::post('/contato', [ContactController::class, 'store']);
