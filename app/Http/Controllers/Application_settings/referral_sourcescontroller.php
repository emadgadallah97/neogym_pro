<?php

namespace App\Http\Controllers\Application_settings;

use App\Http\Controllers\Controller;
use App\Models\ReferralSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class referral_sourcescontroller extends Controller
{
    public function __construct()
    {
        $this->middleware('can:settings_referral_sources_view');
    }

    public function index()
    {
        $ReferralSources = ReferralSource::orderBy('sort_order')->orderByDesc('id')->get();

        return view('settings.referral_sources.index', compact('ReferralSources'));
    }

    public function create()
    {
        return redirect()->route('referral_sources.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:190',
            'name_en' => 'required|string|max:190',
            'status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'notes' => 'nullable|string',
        ]);

        $row = new ReferralSource();
        $row->name = ['ar' => $data['name_ar'], 'en' => $data['name_en']];
        $row->status = $request->boolean('status', true);
        $row->sort_order = (int) ($data['sort_order'] ?? 0);
        $row->notes = $data['notes'] ?? null;
        $row->useradd = Auth::id();
        $row->save();

        return redirect()->route('referral_sources.index')->with('success', trans('settings_trans.saved_success'));
    }

    public function show($id)
    {
        return redirect()->route('referral_sources.index');
    }

    public function edit($id)
    {
        return redirect()->route('referral_sources.index');
    }

    public function update(Request $request, $id)
    {
        $row = ReferralSource::findOrFail($id);

        $data = $request->validate([
            'name_ar' => 'required|string|max:190',
            'name_en' => 'required|string|max:190',
            'status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:999999',
            'notes' => 'nullable|string',
        ]);

        $row->name = ['ar' => $data['name_ar'], 'en' => $data['name_en']];
        $row->status = $request->boolean('status');
        $row->sort_order = (int) ($data['sort_order'] ?? 0);
        $row->notes = $data['notes'] ?? null;
        $row->userupdate = Auth::id();
        $row->save();

        return redirect()->route('referral_sources.index')->with('success', trans('settings_trans.updated_success'));
    }

    public function destroy($id)
    {
        $row = ReferralSource::findOrFail($id);
        $row->userupdate = Auth::id();
        $row->save();
        $row->delete();

        return redirect()->route('referral_sources.index')->with('success', trans('settings_trans.deleted_success'));
    }
}
