<?php
namespace App\Http\Controllers\Application_settings;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCountry;
use App\models\countries;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CountriesController extends Controller
{

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index()
  {
    $Countries=Countries::all();
    return view('settings.countries',compact('Countries'));

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
  public function store(StoreCountry $request)
  {
    

   
    if(Countries::where('name->ar',$request->name_ar)->orwhere('name->en',$request->name_en)->exists())
    {
        return  redirect()->back()->withErrors([trans('Country_trans.existes') ]);
    }
    try
    {
        $validated = $request->validated();
        $Country=new Countries();
        $Country->name=['en'=>$request->name_en,'ar'=>$request->name_ar];
        $Country->notes=$request->notes;
        $Country->user_add=(Auth::user()->id);
        $Country->account_id=(Auth::user()->id_account);
        $Country->save();
        session()->flash('add');
        return redirect()->route('countries.index');
       
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
  public function update(StoreCountry $request)
  {
        try
        {
        $validated = $request->validated();
        $Country= Countries::findorFail($request->id);
        $Country->update([
            $Country->name=['en'=>$request->name_en,'ar'=>$request->name_ar],
            $Country->notes=$request->notes,
            $Country->user_add=(Auth::user()->id),
        ]);
        return redirect()->route('countries.index')->with('success', 'User Deleted successfully.');

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

      $Country= Countries::findorFail($request->id)->delete();
      return redirect()->route('countries.index')->with('success', 'User Deleted successfully.');

    }
    catch(\Exception $e)
    {
        return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
    }
  }

}

?>
