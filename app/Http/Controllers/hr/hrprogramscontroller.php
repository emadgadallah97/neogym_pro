<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;


class hrprogramscontroller extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:human_resources');
    }

    public function index()
    {


        return view('hr.index');
    }
}
