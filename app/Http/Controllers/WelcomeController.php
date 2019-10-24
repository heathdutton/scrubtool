<?php

namespace App\Http\Controllers;

class welcomeController extends Controller
{
    public function index()
    {
        return view('welcome');
    }
}
