<?php
namespace App\Http\Controllers\Application_settings;
use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGovernment;
use App\models\countries;
use App\models\government ;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


class GovernmentController extends Controller
{

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index()
  {
    $countries=countries::all();
    $governmentes=Government::all();
  return view('settings.government',compact('governmentes','countries'));

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
  public function store(StoreGovernment $request)
  {
    if(government::where('name->ar',$request->name_ar)->orwhere('name->en',$request->name_en)->exists())
    {
        return  redirect()->back()->withErrors([trans('Government_trans.existes') ]);
    }
    try
    {
        $validated = $request->validated();
        $Governmentes=new government();
        $Governmentes->name=['en'=>$request->name_en,'ar'=>$request->name_ar];
        $Governmentes->id_country=$request->id_country;
        $Governmentes->user_add=(Auth::user()->id);
        $Governmentes->account_id=(Auth::user()->id_account);
        $Governmentes->save();
        session()->flash('add');
        return redirect()->route('government.index');

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
  public function update(StoreGovernment $request)
  {
    try
    {
    $validated = $request->validated();
    $Governmentes= government::findorFail($request->id);
    $Governmentes->update([
        $Governmentes->Name=['en'=>$request->name_en,'ar'=>$request->name_ar],
        $Governmentes->id_country=$request->id_country,
        $Governmentes->user_add=(Auth::user()->id),
    ]);
    session()->flash('edit_m');
    return redirect()->route('government.index');

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

      $Governmentes= government::findorFail($request->id)->delete();
      Alert::success( '', trans('Government_trans.savesuccess'));
      return redirect()->route('government.index');
    }
    catch(\Exception $e)
    {
        return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
    }
  }

}

?>
