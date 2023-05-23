<?php

namespace App\Controllers;

use App\Models\User;
use Docile\Http\Request;

class HomeController
{
    public function index()
    {
        echo 'Hello, world!';
    }
    public function users()
    {
        echo 'Hello, users!';
    }
    public function show(Request $request, User $user, $car)
    {

        return view("index");
    }
}
