<?php

namespace App\Controllers;

class HomeController
{
    public function index()
    {
        return view("index");
    }
    public function personal()
    {
        dd("Personal route works!");
    }
}
