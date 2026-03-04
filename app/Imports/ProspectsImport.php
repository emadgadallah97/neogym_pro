<?php

namespace App\Imports;

use App\Models\crm\CrmFollowup;
use App\Models\members\Member;
use App\Models\Scopes\ExcludeProspectsScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProspectsImport implements ToCollection, WithHeadingRow
{
    protected int $successCount = 0;
    protected int $skippedCount = 0;

    protected bool $createFollowup;
    protected string $followupType;

    /**
     * موعد المتابعة (تاريخ + وقت) إن تم تمريره من الفورم
     * صيغة input datetime-local: Y-m-d\TH:i
     */
    protected ?Carbon $followupAt;

    protected ?int $userId;

    public function __construct(
        bool $createFollowup = false,
        ?string $followupType = null,
        ?string $followupDatetime = null,
        ?int $userId = null
    ) {
        $this->createFollowup = $createFollowup;

        $types = array_keys(CrmFollowup::typeLabels());

        $this->followupType = ($followupType && in_array($followupType, $types, true))
            ? $followupType
            : 'general';

        if (!in_array($this->followupType, $types, true)) {
            $this->followupType = $types[0] ?? 'general';
        }

        // ✅ datetime-local comes as: 2026-03-04T16:20
        $this->followupAt = $followupDatetime
            ? Carbon::createFromFormat('Y-m-d\TH:i', $followupDatetime)
            : null;

        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        $userId = $this->userId ?? Auth::id();

        foreach ($rows as $row) {
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $validator = Validator::make($row->toArray(), [
                'branch_id'  => 'required|exists:branches,id',
                'first_name' => 'required|string|max:100',
                'last_name'  => 'required|string|max:100',
                'phone'      => 'required|string|max:20',
                'address'    => 'required|string|max:255',
                'phone2'     => 'nullable|string|max:20',
                'whatsapp'   => 'nullable|string|max:20',
                'email'      => 'nullable|email|max:150',
                'gender'     => 'nullable|in:male,female',
                'notes'      => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                $this->skippedCount++;
                continue;
            }

            $prospect = Member::withoutGlobalScope(ExcludeProspectsScope::class)->create([
                'branch_id'   => $row['branch_id'],
                'first_name'  => $row['first_name'],
                'last_name'   => $row['last_name'],
                'phone'       => $row['phone'],
                'address'     => $row['address'],
                'phone2'      => $row['phone2'] ?? null,
                'whatsapp'    => $row['whatsapp'] ?? null,
                'email'       => $row['email'] ?? null,
                'gender'      => $row['gender'] ?? null,
                'notes'       => $row['notes'] ?? null,
                'type'        => 'prospect',
                'status'      => 'active',
                'join_date'   => Carbon::today(),
                'user_add'    => $userId,
                'user_update' => $userId,
            ]);

            if ($this->createFollowup) {
                // ✅ لو المستخدم اختار datetime من الفورم استخدمه، وإلا fallback
                $dateTime = $this->followupAt ?: Carbon::tomorrow()->setTime(10, 0);

                CrmFollowup::create([
                    'member_id'      => $prospect->id,
                    'branch_id'      => $prospect->branch_id,
                    'type'           => $this->followupType,
                    'status'         => 'pending',
                    'priority'       => 'medium',
                    'notes'          => 'متابعة أولى - تم الرفع من Excel',
                    'next_action_at' => $dateTime,
                    'created_by'     => $userId,
                    'updated_by'     => $userId,
                ]);
            }

            $this->successCount++;
        }
    }

    protected function isRowEmpty($row): bool
    {
        $arr = is_array($row) ? $row : $row->toArray();
        $filtered = array_filter($arr, fn($v) => !($v === null || $v === ''));
        return count($filtered) === 0;
    }

    public function getStats(): array
    {
        return [
            'success' => $this->successCount,
            'skipped' => $this->skippedCount,
        ];
    }
}
