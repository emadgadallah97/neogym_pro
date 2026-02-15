<?php

namespace App\Http\Controllers\reports;

use App\Http\Controllers\Controller;


class reportscontroller extends Controller
{
    public function __construct()
    {
        // permissions...
    }

    public function index()
    {


        return view('reports.index');
    }
}
