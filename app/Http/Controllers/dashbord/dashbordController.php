<?php



namespace App\Http\Controllers\dashbord;
use App\Http\Controllers\Controller;
use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Requests\Storedashbord;
use App\models\dashbord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
class dashbordController extends Controller
{

  /**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function index()
  {
   
    return view('dashbord.index');

  }

  /**
   * Show the form for creating a new resource.
   *
   * @return Response
   */
  public function create()
  {
    return view('dashbord.create');

  }

  /**
   * Store a newly created resource in storage.
   *
   * @return Response
   */
  public function store(Storedashbord $request)
  {
    
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return Response
   */
  public function show($id)
  {
    return view('dashbord.reserve');

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
  public function update(Storedashbord $request)
  {
    

  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return Response
   */
  public function destroy(Request $request)
  {
    
  }



}

?>
