<?php

namespace App;

enum UserStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Away = 'away';
}
