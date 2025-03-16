<?php

use App\Models\Chat;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{chatId}', function (User $user, int $chatId) {
    $chat = Chat::query()->findOrFail($chatId);
    return $chat->user_one === $user->id || $chat->user_two === $user->id;
});

Broadcast::channel('chat.start.user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});
