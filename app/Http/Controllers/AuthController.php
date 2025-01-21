<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function tempNewFirstUser()
    {
        $user = new User();
        $user->name = 'test';
        $user->password = Hash::make('Hunter2');
        $user->email = 'test@test.test';
        $user->save();
    }
}
