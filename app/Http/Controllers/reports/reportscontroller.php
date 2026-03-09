<?php

namespace App\Http\Controllers\reports;

use App\Http\Controllers\Controller;


class reportscontroller extends Controller
{
    public function __construct()
    {
                $this->middleware('permission:reports');

    }

    public function index()
    {


        return view('reports.index');
    }
}
