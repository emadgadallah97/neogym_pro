<?php

namespace App\Http\Controllers\accounting;

use App\Http\Controllers\Controller;
use App\Models\accounting\ExpensesType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class expenses_typescontroller extends Controller
{
    public function index()
    {
        $ExpensesTypes = ExpensesType::orderByDesc('id')->get();
        return view('settings.expenses_types', compact('ExpensesTypes'));
    }

    public function create()
    {
        return redirect()->route('expenses_types.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'status'  => ['sometimes', 'accepted'],
        ]);

        $nameAr = trim((string) $request->name_ar);
        $nameEn = trim((string) $request->name_en);

        $exists = ExpensesType::where('name->ar', $nameAr)
            ->orWhere('name->en', $nameEn)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', trans('accounting.expenses_type_exists'));
        }

        DB::beginTransaction();
        try {
            ExpensesType::create([
                'name' => [
                    'ar' => $nameAr,
                    'en' => $nameEn,
                ],
                'status'   => $request->boolean('status'),
                'useradd'  => Auth::check() ? Auth::user()->id : null,
                'userupdate' => null,
            ]);

            DB::commit();
            return redirect()->back()->with('success', trans('accounting.saved_success'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('accounting.saved_error'));
        }
    }

    public function show($id)
    {
        return redirect()->route('expenses_types.index');
    }

    public function edit($id)
    {
        return redirect()->route('expenses_types.index');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id'      => ['required', 'integer', 'exists:expenses_types,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'status'  => ['sometimes', 'accepted'],
        ]);

        $rowId = (int) $request->id;
        $nameAr = trim((string) $request->name_ar);
        $nameEn = trim((string) $request->name_en);

        $ExpenseType = ExpensesType::findOrFail($rowId);

        $exists = ExpensesType::where('id', '!=', $ExpenseType->id)
            ->where(function ($q) use ($nameAr, $nameEn) {
                $q->where('name->ar', $nameAr)
                  ->orWhere('name->en', $nameEn);
            })
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', trans('accounting.expenses_type_exists'));
        }

        DB::beginTransaction();
        try {
            $ExpenseType->update([
                'name' => [
                    'ar' => $nameAr,
                    'en' => $nameEn,
                ],
                'status'     => $request->boolean('status'),
                'userupdate' => Auth::check() ? Auth::user()->id : null,
            ]);

            DB::commit();
            return redirect()->back()->with('success', trans('accounting.updated_success'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('accounting.updated_error'));
        }
    }

    public function destroy(Request $request, $id)
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:expenses_types,id'],
        ]);

        DB::beginTransaction();
        try {
            $ExpenseType = ExpensesType::findOrFail((int) $request->id);
            $ExpenseType->delete();

            DB::commit();
            return redirect()->back()->with('success', trans('accounting.deleted_success'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', trans('accounting.deleted_error'));
        }
    }
}
