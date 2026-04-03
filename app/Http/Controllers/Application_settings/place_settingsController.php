<?php


namespace App\Http\Controllers\Application_settings;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
class place_settingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:settings_places_view');
    }


  public function index()
  {
   
    return view('settings.places_settings');

  }

}

?>
