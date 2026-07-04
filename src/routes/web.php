<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;

Route::get('/', fn () => view('welcome'));

Route::post('/contato', [ContactController::class, 'store']);
