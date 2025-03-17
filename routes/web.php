<?php

use App\Http\Controllers\HomeController;
use App\Http\Middleware\UpdateUserLastSeen;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', UpdateUserLastSeen::class])->group(function () {
    Route::get('/', [HomeController::class, 'show'])->name('home');

    Route::post('/contact/store', [HomeController::class, 'storeContact'])->name('contact.store');

    Route::post('/chat/start', [HomeController::class, 'startChat'])->name('chat.start');
    Route::get('/chat/{id}/messages', [HomeController::class, 'getMessages'])->name('chat.messages.get');
    Route::post('/chat/message', [HomeController::class, 'sendMessage'])->name('chat.message.send');

    Route::patch('/user-status', [HomeController::class, 'updateUserStatus'])->name('user.status.update');
});

require __DIR__ . '/auth.php';
