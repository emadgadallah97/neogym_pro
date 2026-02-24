<?php

namespace App\Http\Controllers\hr;

use App\Http\Controllers\Controller;
use App\Models\hr\HrDevice;
use App\Models\general\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class devicescontroller extends Controller
{
    public function index()
    {
        $devices         = HrDevice::with('branch')->latest()->get();
        $branches        = Branch::where('status', 1)->orderBy('id')->get();
        $totalDevices    = $devices->count();
        $activeDevices   = $devices->where('status', 'active')->count();
        $inactiveDevices = $devices->where('status', 'inactive')->count();

        return view('hr.devices.index', compact(
            'devices',
            'branches',
            'totalDevices',
            'activeDevices',
            'inactiveDevices'
        ));
    }

    public function create()
    {
        return redirect()->route('devices.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id'     => 'required|exists:branches,id',
            'name'          => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:hr_devices,serial_number',
            'ip_address'    => ['nullable', 'regex:/^(\d{1,3}\.){3}\d{1,3}$/'],
            'status'        => 'required|in:active,inactive',
            'notes'         => 'nullable|string|max:1000',
        ], [
            'branch_id.required'     => trans('hr.validation_branch_required'),
            'branch_id.exists'       => trans('hr.validation_branch_required'),
            'name.required'          => trans('hr.validation_name_required'),
            'serial_number.required' => trans('hr.validation_serial_required'),
            'serial_number.unique'   => trans('hr.validation_serial_unique'),
            'ip_address.regex'       => trans('hr.validation_ip_invalid'),
        ]);

        $device = HrDevice::create([
            'branch_id'     => $request->branch_id,
            'name'          => $request->name,
            'serial_number' => $request->serial_number,
            'ip_address'    => $request->ip_address,
            'status'        => $request->status,
            'notes'         => $request->notes,
            'user_add'      => Auth::id(),
        ]);

        $device->load('branch');

        return response()->json([
            'success' => true,
            'message' => trans('hr.device_created_success'),
            'data'    => [
                'id'            => $device->id,
                'name'          => $device->name,
                'branch_name'   => $device->branch?->name ?? '—',
                'serial_number' => $device->serial_number,
                'ip_address'    => $device->ip_address ?? '—',
                'status'        => $device->status,
                'status_label'  => $device->status === 'active'
                    ? '<span class="badge bg-success-subtle text-success fs-11"><i class="ri-checkbox-circle-line me-1"></i>' . trans('hr.active') . '</span>'
                    : '<span class="badge bg-danger-subtle text-danger fs-11"><i class="ri-close-circle-line me-1"></i>' . trans('hr.inactive') . '</span>',
            ],
        ]);
    }

    public function show($id)
    {
        $device = HrDevice::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $device->id,
                'branch_id'     => $device->branch_id,
                'name'          => $device->name,
                'serial_number' => $device->serial_number,
                'ip_address'    => $device->ip_address,
                'status'        => $device->status,
                'notes'         => $device->notes,
            ],
        ]);
    }

    public function edit($id)
    {
        return redirect()->route('devices.index');
    }

    public function update(Request $request, $id)
    {
        $device = HrDevice::findOrFail($id);

        $request->validate([
            'branch_id'     => 'required|exists:branches,id',
            'name'          => 'required|string|max:255',
            'serial_number' => 'required|string|max:255|unique:hr_devices,serial_number,' . $id,
            'ip_address'    => ['nullable', 'regex:/^(\d{1,3}\.){3}\d{1,3}$/'],
            'status'        => 'required|in:active,inactive',
            'notes'         => 'nullable|string|max:1000',
        ], [
            'branch_id.required'     => trans('hr.validation_branch_required'),
            'branch_id.exists'       => trans('hr.validation_branch_required'),
            'name.required'          => trans('hr.validation_name_required'),
            'serial_number.required' => trans('hr.validation_serial_required'),
            'serial_number.unique'   => trans('hr.validation_serial_unique'),
            'ip_address.regex'       => trans('hr.validation_ip_invalid'),
        ]);

        $device->update([
            'branch_id'     => $request->branch_id,
            'name'          => $request->name,
            'serial_number' => $request->serial_number,
            'ip_address'    => $request->ip_address,
            'status'        => $request->status,
            'notes'         => $request->notes,
        ]);

        $device->load('branch');

        return response()->json([
            'success' => true,
            'message' => trans('hr.device_updated_success'),
            'data'    => [
                'id'            => $device->id,
                'name'          => $device->name,
                'branch_name'   => $device->branch?->name ?? '—',
                'serial_number' => $device->serial_number,
                'ip_address'    => $device->ip_address ?? '—',
                'status'        => $device->status,
                'status_label'  => $device->status === 'active'
                    ? '<span class="badge bg-success-subtle text-success fs-11"><i class="ri-checkbox-circle-line me-1"></i>' . trans('hr.active') . '</span>'
                    : '<span class="badge bg-danger-subtle text-danger fs-11"><i class="ri-close-circle-line me-1"></i>' . trans('hr.inactive') . '</span>',
            ],
        ]);
    }

    public function destroy($id)
    {
        $device = HrDevice::findOrFail($id);

        if ($device->attendanceLogs()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => trans('hr.device_has_logs_error'),
            ], 422);
        }

        $device->delete();

        return response()->json([
            'success' => true,
            'message' => trans('hr.device_deleted_success'),
        ]);
    }
}
