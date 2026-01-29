<?php



namespace App\Http\Controllers\Application_settings;
use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Requests\Storearea;
use App\models\city;
use App\models\countries;
use App\models\government;
use App\models\area;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
class areaController extends Controller
{

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index()
  {
    $city=city::all();
    $governmentes=government::all();
    $countries=countries::all();
    $areaes=area::all();
    return view('settings.area',compact('areaes','governmentes','countries','city'));

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
  public function store(Storearea $request)
  {
    if(area::where('name->ar',$request->name_ar)->orwhere('name->en',$request->name_en)->exists())
    {
        return  redirect()->back()->withErrors([trans('area_trans.existes') ]);
    }
    try
    {
        $validated = $request->validated();
        $areaes=new area();
        $areaes->name=['en'=>$request->name_en,'ar'=>$request->name_ar];
        $areaes->id_country=$request->id_country;
        $areaes->id_government=$request->id_governmentes;
        $areaes->id_city=$request->id_city;
        $areaes->account_id=(Auth::user()->id_account);
        $areaes->user_add=(Auth::user()->id);
        $areaes->save();
        session()->flash('add');
        return redirect()->route('area.index');

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
  public function update(Storearea $request)
  {
    try
    {
    $validated = $request->validated();
    $areaes= area::findorFail($request->id);
    $areaes->update([
        $areaes->name=['en'=>$request->name_en,'ar'=>$request->name_ar],
        $areaes->id_country=$request->id_country,
        $areaes->id_government=$request->id_governmentes,
        $areaes->id_city=$request->id_city,
        $areaes->notes=$request->notes,
        $areaes->user_add=(Auth::user()->id),
    ]);
    Alert::success( '', trans('area_trans.savesuccess'));
    return redirect()->route('area.index');

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

      $areas= area::findorFail($request->id)->delete();
      Alert::success( '', trans('area_trans.savesuccess'));
    return redirect()->route('area.index');
    }
    catch(\Exception $e)
    {
        return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
    }
  }



  public function getcity($id)
  {
      $city = city::where("id_government", $id)->pluck("name", "id");
      return json_encode($city);
  }


}

?>
