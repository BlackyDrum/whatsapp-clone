<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::get('/', [HomeController::class, 'show'])->name('home');

    Route::post('/contact/store', [HomeController::class, 'storeContact'])->name('contact.store');

    Route::post('/chat/start', [HomeController::class, 'startChat'])->name('chat.start');
    Route::get('/chat/{id}/messages', [HomeController::class, 'getMessages'])->name('chat.messages.get');
    Route::post('/chat/message', [HomeController::class, 'sendMessage'])->name('chat.message.send');
});

require __DIR__ . '/auth.php';
