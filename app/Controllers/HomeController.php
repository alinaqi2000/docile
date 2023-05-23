<?php

namespace App\Controllers;

use App\Models\User;
use Docile\Http\Request;

class HomeController
{
    public function index()
    {
        return view("index");
    }
}
