<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->get('/', [HomeController::class, 'show'])->name('home');

require __DIR__ . '/auth.php';
