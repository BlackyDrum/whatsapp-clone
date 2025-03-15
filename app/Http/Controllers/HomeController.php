<?php

namespace App\Http\Controllers;

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
}
