<?php

namespace App\Http\Controllers\log;
use App\Models\log ;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use RealRashid\SweetAlert\Facades\Alert;



class LogController extends Controller
{

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index()
  {

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
  public function store($id_screen=null,$event_screen=null,$event=null,$event_type=null,$user_id=null)
  {
    if($user_id!=null)
    {
          try
        {
            $method=Request::method();
            $fullUrl=Request::fullUrl();
            $ip =request()->ip();
            $mac = substr(shell_exec('getmac'), 159,20);
            $agent= Request::header('user-agent');
            $log=new log();
            $log->id_screen=$id_screen;
            $log->event_screen=$event_screen;
            $log->event_type=$event_type;
            $log->event_type=$event_type;
            $log->method=$method;
            $log->fullurl=$fullUrl;
            $log->mac=$mac;
            $log->agent=$agent;
            $log->ip=$ip;
            $log->event=$event;
            $log->user=$user_id;
            $log->save();

            return '1';

        }catch(\Exception $e)
        {
            return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
        }
    }else
    {
      try
      {
          $method=Request::method();
          $fullUrl=Request::fullUrl();
          $ip =request()->ip();
          $mac = substr(shell_exec('getmac'), 159,20);
          $agent= Request::header('user-agent');
          $log=new log();
          $log->id_screen=$id_screen;
          $log->event_screen=$event_screen;
          $log->event_type=$event_type;
          $log->event_type=$event_type;
          $log->method=$method;
          $log->fullurl=$fullUrl;
          $log->mac=$mac;
          $log->agent=$agent;
          $log->ip=$ip;
          $log->event=$event;
          $log->user=(Auth::user()->id);;
          $log->save();

          return '1';

      }catch(\Exception $e)
      {
          return redirect()->back()->withErrors(['error'=>$e->getMessage()]);
      }
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
  public function update($id)
  {

  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return Response
   */
  public function destroy($id)
  {

  }

}

?>
