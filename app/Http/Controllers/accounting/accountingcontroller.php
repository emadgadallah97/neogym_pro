<?php

namespace App\Http\Controllers\accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class accountingcontroller extends Controller
{
    public function index()
    {
        return view('accounting.programs.index');
    }

}
