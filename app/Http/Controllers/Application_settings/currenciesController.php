<?php

namespace App\Http\Controllers\Application_settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storecurrencies;
use App\Models\countries;
use App\Models\currencies;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class currenciesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Countries    = countries::all();
        $currencieses = currencies::all();

        return view('settings.currencies', compact('currencieses', 'Countries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Storecurrencies $request)
    {
        // التحقق من التكرار
        if (
            currencies::where('name->ar', $request->name_ar)
                ->orWhere('name->en', $request->name_en)
                ->exists()
        ) {
            return redirect()->back()->withErrors([
                trans('currencies_trans.existes')
            ]);
        }

        try {
            $request->validated();

            $currency = new currencies();
            $currency->name = [
                'ar' => $request->name_ar,
                'en' => $request->name_en,
            ];
            $currency->default    = $request->default;
            $currency->status     = 1;
            $currency->id_country = $request->id_country;
            $currency->save();

            Alert::success('', trans('currencies_trans.savesuccess'));
            return redirect()->route('currencies.index');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Storecurrencies $request)
    {
        try {
            $request->validated();

            $currency = currencies::findOrFail($request->id);

            $currency->update([
                'name' => [
                    'ar' => $request->name_ar,
                    'en' => $request->name_en,
                ],
                'id_country' => $request->id_country,
                'status'     => $request->status,
                'default'    => $request->default,
            ]);

            Alert::success('', trans('currencies_trans.editsuccess'));
            return redirect()->route('currencies.index');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            currencies::findOrFail($request->id)->delete();

            Alert::success('', trans('currencies_trans.deletesuccess'));
            return redirect()->route('currencies.index');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }
}
