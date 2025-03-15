<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserHasContact;
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

        return Inertia::render('Home', [
            'contacts' => $contacts
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
}
