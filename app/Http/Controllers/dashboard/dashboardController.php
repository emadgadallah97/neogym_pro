<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class dashboardController extends Controller
{
  function __construct()
  {
    $this->middleware('permission:dashboard');
  }


  public function index()
  {

    return view('dashboard.index');
  }
}
