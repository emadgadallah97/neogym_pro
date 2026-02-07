<?php

namespace App\Http\Controllers\Application_settings;

use App\Http\Controllers\Controller;
use App\Models\general\TrainerSessionPricing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class trainer_session_pricingcontroller extends Controller
{
    public function index(Request $request)
    {
        // Ajax for DataTables
        if ($request->ajax()) {
            $draw   = (int) $request->input('draw', 1);
            $start  = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 10);
            $search = (string) data_get($request->input('search'), 'value', '');

            // Total trainers count (coaches only, excluding soft deleted)
            $totalQuery = DB::table('employees as e')
                ->where('e.is_coach', 1)
                ->whereNull('e.deleted_at');

            $recordsTotal = (int) $totalQuery->count();

            // Filtered query (with joins)
            $query = DB::table('employees as e')
                ->leftJoin('trainer_session_pricings as p', 'p.trainer_id', '=', 'e.id')
                ->leftJoin('users as u', 'u.id', '=', 'p.updated_by')
                ->where('e.is_coach', 1)
                ->whereNull('e.deleted_at')
                ->select([
                    'e.id as trainer_id',
                    'e.first_name',
                    'e.last_name',
                    'e.phone_1',
                    'e.phone_2',
                    'e.whatsapp',
                    'e.email',

                    'p.session_price',
                    'p.updated_at',
                    'u.name as updated_by_name',
                ]);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('e.first_name', 'like', "%{$search}%")
                      ->orWhere('e.last_name', 'like', "%{$search}%")
                      ->orWhere('e.email', 'like', "%{$search}%")
                      ->orWhere('e.phone_1', 'like', "%{$search}%")
                      ->orWhere('e.phone_2', 'like', "%{$search}%")
                      ->orWhere('e.whatsapp', 'like', "%{$search}%");
                });
            }

            // recordsFiltered
            $filteredCountQuery = DB::table('employees as e')
                ->where('e.is_coach', 1)
                ->whereNull('e.deleted_at');

            if ($search !== '') {
                $filteredCountQuery->where(function ($q) use ($search) {
                    $q->where('e.first_name', 'like', "%{$search}%")
                      ->orWhere('e.last_name', 'like', "%{$search}%")
                      ->orWhere('e.email', 'like', "%{$search}%")
                      ->orWhere('e.phone_1', 'like', "%{$search}%")
                      ->orWhere('e.phone_2', 'like', "%{$search}%")
                      ->orWhere('e.whatsapp', 'like', "%{$search}%");
                });
            }

            $recordsFiltered = (int) $filteredCountQuery->count();

            // Ordering (safe mapping) - UPDATED indexes due to added phone column
            $orderColIndex = (int) data_get($request->input('order'), '0.column', 0);
            $orderDir = strtolower((string) data_get($request->input('order'), '0.dir', 'asc'));
            $orderDir = in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : 'asc';

            $columnsMap = [
                0 => 'e.id',
                1 => 'e.first_name',
                2 => 'e.phone_1',
                3 => 'e.email',
                4 => 'p.session_price',
                5 => 'u.name',
                6 => 'p.updated_at',
            ];

            $orderBy = $columnsMap[$orderColIndex] ?? 'e.id';
            $query->orderBy($orderBy, $orderDir);

            // Pagination
            if ($length !== -1) {
                $query->skip($start)->take($length);
            }

            $rows = $query->get();

            $data = [];
            foreach ($rows as $r) {
                $fullName = trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
                if ($fullName === '') {
                    $fullName = '-';
                }

                $phone = $r->phone_1 ?? '';
                $phone = trim((string) $phone);
                if ($phone === '') {
                    $phone = '-';
                }

                $data[] = [
                    'trainer_id'      => $r->trainer_id,
                    'name'            => $fullName,
                    'phone'           => $phone,
                    'email'           => $r->email ?? '-',
                    'session_price'   => $r->session_price,
                    'updated_by_name' => $r->updated_by_name ?? '-',
                    'updated_at'      => $r->updated_at ? date('Y-m-d H:i', strtotime($r->updated_at)) : '-',
                ];
            }

            return response()->json([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data,
            ]);
        }

        return view('settings.trainer_session_pricing');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'trainer_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(function ($q) {
                    $q->where('is_coach', 1)->whereNull('deleted_at');
                }),
            ],
            'session_price' => 'nullable|numeric|min:0|max:999999.99',
        ]);

        $pricing = TrainerSessionPricing::updateOrCreate(
            ['trainer_id' => (int) $data['trainer_id']],
            [
                'session_price' => array_key_exists('session_price', $data) ? $data['session_price'] : null,
                'updated_by' => auth()->check() ? auth()->id() : null,
            ]
        );

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => trans('settings_trans.trainer_session_pricing_saved'),
                'trainer_id' => $pricing->trainer_id,
            ]);
        }

        return redirect()->back()->with('success', trans('settings_trans.trainer_session_pricing_saved'));
    }

    // Resource placeholders
    public function update(Request $request, $id) { return $this->store($request); }
    public function create()  { return redirect()->route('trainer_session_pricing.index'); }
    public function show($id) { return redirect()->route('trainer_session_pricing.index'); }
    public function edit($id) { return redirect()->route('trainer_session_pricing.index'); }
    public function destroy($id) { return redirect()->route('trainer_session_pricing.index'); }
}
