<?php

namespace App\Http\Controllers\Application_settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Models\general\GeneralSetting;
use App\models\countries;
use App\models\currencies;

use App\Traits\store\file_storage;

class general_settingsController extends Controller
{
    use file_storage;

    public function index()
    {
        $setting = GeneralSetting::first();

        $countries = countries::select('id', 'name')->orderBy('id', 'desc')->get();
        $currencies = currencies::select('id', 'name')->orderBy('id', 'desc')->get();

        return view('settings.general_settings', compact('setting', 'countries', 'currencies'));
    }

    public function create()
    {
        return redirect()->route('general_settings.index');
    }

    public function store(Request $request)
    {
        $setting = GeneralSetting::first();
        if ($setting) {
            return $this->update($request, $setting->id);
        }

        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',

            'country_id' => 'nullable|exists:countries,id',
            'currency_id' => 'nullable|exists:currencies_settings,id',

            'commercial_register' => 'nullable|string|max:255',
            'tax_register' => 'nullable|string|max:255',

            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',

            'notes' => 'nullable|string',
            'status' => 'sometimes|accepted',

            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        DB::beginTransaction();
        try {

            $data = [
                'name' => [
                    'ar' => $request->name_ar,
                    'en' => $request->name_en,
                ],
                'country_id' => $request->country_id,
                'currency_id' => $request->currency_id,
                'commercial_register' => $request->commercial_register,
                'tax_register' => $request->tax_register,
                'phone' => $request->phone,
                'email' => $request->email,
                'website' => $request->website,
                'notes' => $request->notes,
                'status' => $request->boolean('status'),
                'user_add' => Auth::check() ? Auth::user()->id : null,
            ];

            if ($request->hasFile('logo')) {
                $data['logo'] = $this->file_storage($request->file('logo'), 'general_settings/logo');
            }

            GeneralSetting::create($data);

            DB::commit();
            return redirect()->route('general_settings.index')->with('success', trans('settings_trans.saved_success'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('settings_trans.saved_error'));
        }
    }

    public function edit($id)
    {
        return redirect()->route('general_settings.index');
    }

    public function update(Request $request, $id)
    {
        $setting = GeneralSetting::findOrFail($id);

        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',

            'country_id' => 'nullable|exists:countries,id',
            'currency_id' => 'nullable|exists:currencies_settings,id',

            'commercial_register' => 'nullable|string|max:255',
            'tax_register' => 'nullable|string|max:255',

            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',

            'notes' => 'nullable|string',
            'status' => 'sometimes|accepted',

            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        DB::beginTransaction();
        try {

            $data = [
                'name' => [
                    'ar' => $request->name_ar,
                    'en' => $request->name_en,
                ],
                'country_id' => $request->country_id,
                'currency_id' => $request->currency_id,
                'commercial_register' => $request->commercial_register,
                'tax_register' => $request->tax_register,
                'phone' => $request->phone,
                'email' => $request->email,
                'website' => $request->website,
                'notes' => $request->notes,
                'status' => $request->boolean('status'),
            ];

            if ($request->hasFile('logo')) {

                // Delete old logo from public/attachments/... (same as DB path)
                if (!empty($setting->logo)) {
                    $oldPath = public_path($setting->logo);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                // Store new logo using your trait (keeps same DB path format)
                $data['logo'] = $this->file_storage($request->file('logo'), 'general_settings/logo');
            }

            $setting->update($data);

            DB::commit();
            return redirect()->route('general_settings.index')->with('success', trans('settings_trans.updated_success'));

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('settings_trans.updated_error'));
        }
    }

    public function show($id)
    {
        return redirect()->route('general_settings.index');
    }

    public function destroy($id)
    {
        return redirect()->route('general_settings.index');
    }
}
