<?php
// app/Http/Controllers/crm/CrmInteractionsController.php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CrmInteractionsController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id'     => 'required|integer|exists:members,id',
            'followup_id'   => 'nullable|integer|exists:crm_followups,id',
            'channel'       => 'required|in:call,whatsapp,visit,email,sms',
            'direction'     => 'required|in:inbound,outbound',
            'notes'         => 'nullable|string',
            'result'        => 'required|in:answered,no_answer,interested,not_interested',
            'interacted_at' => 'nullable|date',
        ]);

        $id = DB::table('crm_interactions')->insertGetId([
            'member_id'     => $data['member_id'],
            'followup_id'   => $data['followup_id'] ?? null,
            'channel'       => $data['channel'],
            'direction'     => $data['direction'],
            'notes'         => $data['notes'] ?? null,
            'result'        => $data['result'],
            'interacted_at' => $data['interacted_at'] ?? now(),
            'created_by'    => Auth::id(),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        if ($request->ajax()) {
            $row = DB::table('crm_interactions')->where('id', $id)->first();
            return response()->json([
                'success'     => true,
                'message'     => trans('crm.save_interaction'),
                'interaction' => $row,
            ]);
        }

        return redirect()->back()->with('success', trans('crm.save_interaction'));
    }

    public function destroy(Request $request, $id)
    {
        DB::table('crm_interactions')->where('id', $id)->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => trans('crm.followup_deleted_msg')]);
        }

        return redirect()->back()->with('success', trans('crm.followup_deleted_msg'));
    }
}
