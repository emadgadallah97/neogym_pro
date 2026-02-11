<?php

namespace App\Http\Controllers\accounting;

use App\Models\accounting\IncomeType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class income_typescontroller extends Controller
{
    public function index()
    {
        $IncomeTypes = IncomeType::orderByDesc('id')->get();

        return view('settings.income_types', compact('IncomeTypes'));
    }

    public function create()
    {
        return redirect()->route('income_types.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:190',
            'name_en' => 'required|string|max:190',
            'status' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $row = new IncomeType();
        $row->name = ['ar' => $data['name_ar'], 'en' => $data['name_en']];
        $row->status = (bool)($data['status'] ?? 0);
        $row->notes = $data['notes'] ?? null;
        $row->useradd = Auth::id();
        $row->save();

        return redirect()->route('income_types.index')->with('success', trans('income.saved_successfully'));
    }

    public function show($id)
    {
        return redirect()->route('income_types.index');
    }

    public function edit($id)
    {
        return redirect()->route('income_types.index');
    }

    public function update(Request $request, $id)
    {
        $row = IncomeType::findOrFail($id);

        $data = $request->validate([
            'name_ar' => 'required|string|max:190',
            'name_en' => 'required|string|max:190',
            'status' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $row->name = ['ar' => $data['name_ar'], 'en' => $data['name_en']];
        $row->status = (bool)($data['status'] ?? 0);
        $row->notes = $data['notes'] ?? null;
        $row->userupdate = Auth::id();
        $row->save();

        return redirect()->route('income_types.index')->with('success', trans('income.updated_successfully'));
    }

    public function destroy($id)
    {
        $row = IncomeType::findOrFail($id);
        $row->userupdate = Auth::id();
        $row->save();
        $row->delete();

        return redirect()->route('income_types.index')->with('success', trans('income.deleted_successfully'));
    }
}
