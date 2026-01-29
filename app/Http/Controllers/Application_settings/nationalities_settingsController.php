<?php
namespace App\Http\Controllers\Application_settings;
use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storenationalities;
use App\models\countries;
use App\models\nationalities ;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


class nationalities_settingsController extends Controller
{

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index()
  {
    $Countries=Countries::all();
    $nationalitieses=nationalities::all();
    return view('settings.nationalities',compact('nationalitieses','Countries'));

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
  public function store(Storenationalities $request)
  {
    if(nationalities::where('name->ar',$request->name_ar)->orwhere('name->en',$request->name_en)->exists())
    {
        return  redirect()->back()->withErrors([trans('nationalities_trans.existes') ]);
    }
    try
    {
        $validated = $request->validated();
        $nationalitieses=new nationalities();
        $nationalitieses->name=['en'=>$request->name_en,'ar'=>$request->name_ar];
        $nationalitieses->default=$request->default;
        $nationalitieses->status=1;
        $nationalitieses->id_country=$request->id_country;
        $nationalitieses->account_id=(Auth::user()->id_account);
        $nationalitieses->save();
        session()->flash('add');
        return redirect()->route('nationalities.index');

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
  public function update(Storenationalities $request)
  {
    try
    {
    $validated = $request->validated();
    $nationalitieses= nationalities::findorFail($request->id);
    $nationalitieses->update([
        $nationalitieses->name=['en'=>$request->name_en,'ar'=>$request->name_ar],
        $nationalitieses->id_country=$request->id_country,
        $nationalitieses->status=$request->status,
        $nationalitieses->default=$request->default,


    ]);
    session()->flash('edit_m');
    return redirect()->route('nationalities.index');

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

      $nationalitieses= nationalities::findorFail($request->id)->delete();
      Alert::success( '', trans('nationalities_trans.savesuccess'));
      return redirect()->route('nationalities.index');
    }
    catch(\Exception $e)
    {
        return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
    }
  }

}

?>
