<?php

namespace App\Controllers;

use App\Models\User;
use Docile\Http\Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class HomeController
{
    public function index()
    {

        User::create([
            'first_name' => 'Ali Naqi',
            'last_name' => 'Al-Musawi',
            'email' => 'alinaqai2000@gmail.com',
            'username' => 'alinaaqi2000',
            'phone_number' => '0304361256545',
        ]);
        $users = User::all();
        return view("users", compact("users"));
    }
}
