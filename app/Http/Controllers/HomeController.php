<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Models\UserHasContact;
use App\Models\UserInChat;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function show()
    {
        $contacts = UserHasContact::query()
            ->where('user_id', Auth::id())
            ->join('users', 'users.id', 'user_has_contacts.contact_id')
            ->select(['users.name', 'users.email', 'users.status', 'users.about', 'users.last_seen'])
            ->orderBy('users.name')
            ->get()
            ->each(function ($item, $index) {
                $item->id = ++$index;
            });

        $chats = Chat::query()
            ->where('user_one', Auth::id())
            ->orWhere('user_two', Auth::id())
            ->get()
            ->map(function ($chat) {
                $partnerId = $chat->user_one === Auth::id() ? $chat->user_two : $chat->user_one;
                $chat->partner = User::select(['name', 'email', 'id'])->find($partnerId);
                return $chat;
            });

        return Inertia::render('Home', [
            'contacts' => $contacts,
            'chats' => $chats
        ]);
    }

    public function storeContact(Request $request)
    {
        $validated = $request->validate([
            'contact_email' => 'required|string|exists:users,email'
        ]);

        if ($validated['contact_email'] === Auth::user()->email)
            return response()->json(['message' => 'The selected contact email is invalid.'], 422);

        $contact = User::query()
            ->where('email', $validated['contact_email'])
            ->first();

        $alreadyHasContact = UserHasContact::query()
            ->where('user_id', Auth::id())
            ->where('contact_id', $contact['id'])
            ->exists();

        if ($alreadyHasContact)
            return response()->json(['message' => "You already have {$contact->name} in your contact list."], 409);

        UserHasContact::query()
            ->create([
                'user_id' => Auth::id(),
                'contact_id' => $contact['id']
            ]);

        return response()->json(['message' => 'Contact added successfully.'], 201);
    }

    public function startChat(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|exists:users,email'
        ]);

        $partner = User::query()
            ->where('email', $validated['email'])
            ->first();

        $hasContact = UserHasContact::query()
            ->where('user_id', Auth::id())
            ->where('contact_id', $partner['id'])
            ->exists();

        if (!$hasContact)
            return response()->json(['message' => 'The selected contact email is invalid.'], 400);

        // TODO: Check if a conversation between two users already exist

        $chat = Chat::query()
            ->create([
                'user_one' => Auth::id(),
                'user_two' => $partner['id']
            ]);
    }

    public function getMessages(Request $request, string $id)
    {
        try {
            $chat = Chat::query()
                ->findOrFail($id);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'The selected chat is invalid.'], 400);
        }

        $canAccessChat = $chat->user_one === Auth::id() || $chat->user_two === Auth::id();

        if (!$canAccessChat)
            return response()->json(['message' => 'The selected chat is invalid.'], 400);

        $messages = Message::query()
            ->where('chat_id', $chat->id)
            ->join('users', 'users.id', 'messages.user_id')
            ->select(['messages.user_id', 'messages.chat_id', 'messages.created_at', 'messages.message', 'messages.status AS message_status', 'messages.id', 'users.name', 'users.email', 'users.status AS user_status', 'users.last_seen'])
            ->orderBy('messages.created_at')
            ->get();

        return response()->json(['messages' => $messages]);
    }
}
