<?php

namespace App\Http\Controllers;

use App\Events\ChatStarted;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\UserStatusChange;
use App\MessageStatus;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Models\UserHasContact;
use App\UserStatus;
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
            ->select(['users.name', 'users.email', 'users.is_active', 'users.about', 'users.last_seen'])
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

                $lastMessage = Message::query()
                    ->where('chat_id', $chat->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $chat->last_message = $lastMessage?->message;
                $chat->last_message_created_at = $lastMessage?->created_at;
                $chat->unread_messages = Message::query()
                    ->where('chat_id', $chat->id)
                    ->where('user_id', '!=', Auth::id())
                    ->where('status', MessageStatus::Delivered)
                    ->count();

                return $chat;
            })
            ->sortByDesc('last_message_created_at')
            ->values();

        Auth::user()->update([
            'is_active' => true
        ]);

        broadcast(new UserStatusChange(Auth::id(), true))->toOthers();

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
            return response()->json(['message' => 'You do not have permission to interact with this contact.'], 403);

        // Check if there is already a chat between two users
        $chat = Chat::query()
            ->where(function ($query) use ($partner) {
                $query->where('user_one', Auth::id())
                    ->where('user_two', $partner['id']);
            })
            ->orWhere(function ($query) use ($partner) {
                $query->where('user_one', $partner['id'])
                    ->where('user_two', Auth::id());
            })
            ->first();

        if (!$chat) {
            $chat = Chat::create([
                'user_one' => Auth::id(),
                'user_two' => $partner['id']
            ]);

            broadcast(new ChatStarted($chat))->toOthers();
        }

        return response()->json(['chat_id' => $chat->id, 'created' => $chat->wasRecentlyCreated]);
    }

    public function getMessages(Request $request, int $id)
    {
        try {
            $chat = Chat::query()
                ->findOrFail($id);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Chat not found.'], 404);
        }

        $canAccessChat = $chat->user_one === Auth::id() || $chat->user_two === Auth::id();

        if (!$canAccessChat)
            return response()->json(['message' => 'You are not authorized to access this chat.'], 403);

        $parnterId = $chat->user_one === Auth::id() ? $chat->user_two : $chat->user_one;

        $messages = Message::query()
            ->where('chat_id', $chat->id)
            ->select(['messages.user_id', 'messages.created_at', 'messages.message', 'messages.status', 'messages.id'])
            ->orderBy('messages.created_at')
            ->get();

        $partnerData = User::query()
            ->where('id', $parnterId)
            ->select(['id', 'name', 'email', 'is_active', 'last_seen'])
            ->first();

        return response()->json(['messages' => $messages, 'partner' => $partnerData, 'chat_id' => $chat->id]);
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'chat_id' => 'required|numeric|exists:chats,id',
            'message' => 'required|string|max:4096'
        ]);

        $chat = Chat::query()->find($validated['chat_id']);

        $canAccessChat = $chat->user_one === Auth::id() || $chat->user_two === Auth::id();

        if (!$canAccessChat)
            return response()->json(['message' => 'You are not authorized to access this chat.'], 403);

        $message = Message::query()
            ->create([
                'user_id' => Auth::id(),
                'message' => $validated['message'],
                'chat_id' => $chat->id,
                'status' => MessageStatus::Delivered
            ])
            ->only(['id', 'message', 'created_at', 'status', 'user_id', 'chat_id']);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['message' => $message]);
    }

    public function updateUserStatus(Request $request)
    {
        $validated = $request->validate([
            'active' => 'required|boolean'
        ]);

        User::query()
            ->find(Auth::id())
            ->update([
                'is_active' => $validated['active']
            ]);

        broadcast(new UserStatusChange(Auth::id(), $validated['active']))->toOthers();
    }

    public function updateMessageStatus(Request $request)
    {
        $validated = $request->validate([
            'message_ids' => 'array|required|min:1',
            'message_ids.*' => 'required|numeric|exists:messages,id'
        ]);

        foreach ($validated['message_ids'] as $messageId) {
            $message = Message::find($messageId);
            $user = User::find($message->user_id);
            $chat = Chat::find($message->chat_id);

            $canAccessChat = ($chat->user_one === Auth::id() || $chat->user_two === Auth::id()) && $user->id !== Auth::id();

            if (!$canAccessChat)
                return response()->json(['message' => 'You are not authorized to do this action.'], 403);

            $message->update([
                'status' => MessageStatus::Read
            ]);

            broadcast(new MessageRead($message->fresh()))->toOthers();
        }
    }
}
