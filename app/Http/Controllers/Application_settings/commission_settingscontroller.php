<?php

namespace App\Http\Controllers\Application_settings;

use App\Http\Controllers\Controller;
use App\Models\general\CommissionSetting;
use Illuminate\Http\Request;

class commission_settingscontroller extends Controller
{
    private int $singletonId = 1;

    private function getSingleton(): CommissionSetting
    {
        return CommissionSetting::firstOrCreate(
            ['id' => $this->singletonId],
            ['calculate_commission_before_discounts' => 0]
        );
    }

    public function index()
    {
        $setting = $this->getSingleton();

        return view('settings.commission_settings', compact('setting'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'calculate_commission_before_discounts' => 'required|in:0,1',
        ]);

        CommissionSetting::updateOrCreate(
            ['id' => $this->singletonId],
            ['calculate_commission_before_discounts' => (int) $data['calculate_commission_before_discounts']]
        );

        return redirect()->to(url('settings'))
            ->with('success', trans('settings_trans.commission_settings_saved'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'calculate_commission_before_discounts' => 'required|in:0,1',
        ]);

        CommissionSetting::updateOrCreate(
            ['id' => $this->singletonId],
            ['calculate_commission_before_discounts' => (int) $data['calculate_commission_before_discounts']]
        );

        return redirect()->to(url('settings'))
            ->with('success', trans('settings_trans.commission_settings_updated'));
    }

    public function create()
    {
        return redirect()->route('commission_settings.index');
    }

    public function show($id)
    {
        return redirect()->route('commission_settings.index');
    }

    public function edit($id)
    {
        return redirect()->route('commission_settings.index');
    }

    public function destroy($id)
    {
        return redirect()->route('commission_settings.index');
    }
}
