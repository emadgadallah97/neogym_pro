<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;


class hrprogramscontroller extends Controller
{
    public function __construct()
    {
        // permissions...
    }

    public function index()
    {


        return view('hr.index');
    }
}
