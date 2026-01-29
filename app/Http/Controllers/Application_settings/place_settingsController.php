<?php


namespace App\Http\Controllers\Application_settings;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
class place_settingsController extends Controller
{


  public function index()
  {
   
    return view('settings.places_settings');

  }

}

?>
