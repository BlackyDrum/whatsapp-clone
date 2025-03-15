<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserHasContact extends Model
{
    protected $fillable = [
        'user_id',
        'contact_id'
    ];
}
