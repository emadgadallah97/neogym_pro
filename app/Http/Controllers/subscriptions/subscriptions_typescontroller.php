<?php


namespace App\Http\Controllers\subscriptions;


use App\Http\Controllers\Controller;
use App\Models\subscriptions\subscriptions_type;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class subscriptions_typescontroller extends Controller
{
    public function index()
    {
        $SubscriptionsTypes = subscriptions_type::orderByDesc('id')->get();
        return view('subscriptions.settings.subscriptions_types.index', compact('SubscriptionsTypes'));
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable',
        ]);

        $validator->after(function ($validator) use ($request) {

            $exists_ar = subscriptions_type::whereRaw(
                "json_unquote(json_extract(`name`, '$.\"ar\"')) = ?",
                [$request->name_ar]
            )->exists();

            if ($exists_ar) {
                $validator->errors()->add('name_ar', trans('validation.unique', ['attribute' => trans('subscriptions.name_ar')]));
            }

            $exists_en = subscriptions_type::whereRaw(
                "json_unquote(json_extract(`name`, '$.\"en\"')) = ?",
                [$request->name_en]
            )->exists();

            if ($exists_en) {
                $validator->errors()->add('name_en', trans('validation.unique', ['attribute' => trans('subscriptions.name_en')]));
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', $validator->errors()->first());
        }

        $validated = $validator->validated();

        subscriptions_type::create([
            'name' => [
                'ar' => $validated['name_ar'],
                'en' => $validated['name_en'],
            ],
            'description' => $validated['description'] ?? null,
            'status' => $request->has('status') ? 1 : 0,
            'created_by' => Auth::id(),
        ]);


        return redirect()->back()->with('success', trans('subscriptions.added_successfully'));
    }


    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:subscriptions_types,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable',
        ]);

        $validator->after(function ($validator) use ($request) {

            $exists_ar = subscriptions_type::where('id', '!=', $request->id)
                ->whereRaw("json_unquote(json_extract(`name`, '$.\"ar\"')) = ?", [$request->name_ar])
                ->exists();

            if ($exists_ar) {
                $validator->errors()->add('name_ar', trans('validation.unique', ['attribute' => trans('subscriptions.name_ar')]));
            }

            $exists_en = subscriptions_type::where('id', '!=', $request->id)
                ->whereRaw("json_unquote(json_extract(`name`, '$.\"en\"')) = ?", [$request->name_en])
                ->exists();

            if ($exists_en) {
                $validator->errors()->add('name_en', trans('validation.unique', ['attribute' => trans('subscriptions.name_en')]));
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', $validator->errors()->first());
        }

        $validated = $validator->validated();

        $SubscriptionsType = subscriptions_type::findOrFail($validated['id']);


        $SubscriptionsType->update([
            'name' => [
                'ar' => $validated['name_ar'],
                'en' => $validated['name_en'],
            ],
            'description' => $validated['description'] ?? null,
            'status' => $request->has('status') ? 1 : 0,
        ]);


        return redirect()->back()->with('success', trans('subscriptions.updated_successfully'));
    }


    public function destroy(Request $request, $id)
    {
        $request->validate([
            'id' => 'required|integer|exists:subscriptions_types,id',
        ]);


        $SubscriptionsType = subscriptions_type::findOrFail($request->id);
        $SubscriptionsType->delete();


        return redirect()->back()->with('success', trans('subscriptions.deleted_successfully'));
    }
}
