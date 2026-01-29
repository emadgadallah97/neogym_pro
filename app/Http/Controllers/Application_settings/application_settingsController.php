<?php


namespace App\Http\Controllers\Application_settings;
use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storeapplication_settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\application_settings;
header('Content-Type: application/json;charset=utf-8');
use App\Http\Controllers\log\LogController;

class application_settingsController extends Controller
{

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index()
  {
   
    return view('settings.settings');

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
  public function store(Storeapplication_settings $request)
  {
      if(Application_settings::where('name->ar',$request->name_ar)->orwhere('name->en',$request->name_en)->exists())
      {
          return  redirect()->back()->withErrors([trans('application_settings_trans.existes') ]);
      }
      try
      {
          $validated = $request->validated();
          $Application_settings=new Application_settings();
          $Application_settings->name=['en'=>$request->name_en,'ar'=>$request->name_ar];
          $Application_settings->id_settings_type=$request->Id_settings_type;
          $Application_settings->user_add=(Auth::user()->id);
          $Application_settings->save();
          $LogController = new LogController;
          $LogController->store('1','Application settings','اضافة اعدادات جديدة','Add');
          Alert::success( '', trans('application_settings_trans.savesuccess'));
          return redirect()->route('application_setting.index');

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
  public function update(Storeapplication_settings $request)
  {
    try
    {
        $validated = $request->validated();
        $Application_settings= Application_settings::find($request->id);
        $event=
              [

                'id'=>$request->id,
                'name_ar_old'=>$Application_settings->getTranslation('name','ar'),
                'name_en_old'=>$Application_settings->getTranslation('name','en'),
                'Id_settings_type_old'=>$Application_settings->id_settings_type,
                'name_ar_new'=>$request->name_ar,
                'name_en_new'=>$request->name_en,
                'Id_settings_type_new'=>$request->Id_settings_type,
               ];
        $myJSONevent=json_encode($event,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        $Application_settings= Application_settings::findorFail($request->id);
        $Application_settings->update([
        $Application_settings->name=['en'=>$request->name_en,'ar'=>$request->name_ar],
        $Application_settings->id_settings_type=$request->Id_settings_type,
        $Application_settings->user_add=(Auth::user()->id),
    ]);

    $LogController = new LogController;
    $LogController->store('1','Settings type',$myJSONevent,'update');
    Alert::success( '', trans('application_settings_trans.savesuccess'));
    return redirect()->route('application_setting.index');

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
      $LogController = new LogController;
      $event=
      [

        'id'=>$request->id,
       ];
      $myJSONevent=json_encode($event,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
      $Application_settings= Application_settings::findorFail($request->id)->delete();

      $LogController->store('1','Application settings',$myJSONevent,'delete');
      Alert::success( '', trans('application_settings_trans.savesuccess'));
       return redirect()->route('application_setting.index');
    }
    catch(\Exception $e)
    {
        return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
    }
  }


}

?>
