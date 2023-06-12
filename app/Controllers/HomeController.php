<?php

namespace App\Controllers;

use App\Models\User;
use Docile\Http\Request;
use Illuminate\Database\Capsule\Manager as Capsule;

class HomeController
{
    public function index()
    {
        return view("index");
    }
}
