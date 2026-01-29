<?php


namespace App\Http\Controllers\Application_settings;
use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Requests\Storecity;
use App\models\city;
use App\models\countries;
use App\models\government;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class cityController extends Controller
{


  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index()
  {
    $citys=city::all();
    $governmentes=government::all();
    $countries=countries::all();
    return view('settings.city',compact('citys','governmentes','countries'));

  }

  /**
   * Show the form for creating a new resource.
   *
   * @return Response
   */
  public function create()
  {

  }

  /**
   * Store a newly created resource in storage.
   *
   * @return Response
   */
  public function store(Storecity $request)
  {
    if(city::where('name->ar',$request->name_ar)->orwhere('name->en',$request->name_en)->exists())
    {
        return  redirect()->back()->withErrors([trans('city_trans.existes') ]);
    }
    try
    {
        $validated = $request->validated();
        $cityes=new city();
        $cityes->name=['en'=>$request->name_en,'ar'=>$request->name_ar];
        $cityes->id_country=$request->id_country;
        $cityes->id_government=$request->id_governmentes;
        $cityes->user_add=(Auth::user()->id);
        $cityes->account_id=(Auth::user()->id_account);
        $cityes->save();
        session()->flash('add');
        return redirect()->route('city.index');

    }catch(\Exception $e)
    {
        return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
    }
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function show($id)
  {
    $Country=countries::select('name_ar','name_en')->get();
    foreach ( $Country as $var ) {
      $Country=new countries();
      $Country->name=['en'=>$var['name_en'],'ar'=>$var['name_ar']];
      $Country->user_add=(Auth::user()->id);
      $Country->save();
      echo "\n", $var['name_ar'], "\t\t", $var['name_ar'];
      echo '<br/>';
  }
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function edit($id)
  {

  }

  /**
   * Update the specified resource in storage.
   *
   * @param  int  $id
   * @return Response
   */
  public function update(Storecity $request)
  {
    try
    {
      
    $validated = $request->validated();
    $cityes= city::findorFail($request->id);
    if(isset($request->id_governmentes))
        {    
          $cityes->update([
          $cityes->name=['en'=>$request->name_en,'ar'=>$request->name_ar],
          $cityes->id_country=$request->id_country,
          $cityes->id_government=$request->id_governmentes,
          $cityes->user_add=(Auth::user()->id),
            ]);

        }else
        {
          $cityes->update([
            $cityes->name=['en'=>$request->name_en,'ar'=>$request->name_ar],
            $cityes->id_country=$request->id_country,
            $cityes->user_add=(Auth::user()->id),
              ]);
        }
        session()->flash('edit_m');
        return redirect()->route('city.index');

    }
    catch(\Exception $e)
    {
        return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
    }

  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return Response
   */
  public function destroy(Request $request)
  {
    try
    {

      $citys= city::findorFail($request->id)->delete();
      Alert::success( '', trans('city_trans.savesuccess'));
      return redirect()->route('city.index');
    }
    catch(\Exception $e)
    {
        return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
    }
  }


  public function getGovernment($id)
  {
      $governmentes = government::where("id_country", $id)->pluck("name", "id");
      return json_encode($governmentes);
  }



}

?>
