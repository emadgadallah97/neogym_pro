{{-- resources/views/crm/followups/_table.blade.php --}}
@php
    use Illuminate\Support\Str;
@endphp

@if($followups->isEmpty())
    <div class="text-center py-5">
        <h5 class="text-muted mb-1">{{ trans('crm.no_followups') }}</h5>
        <p class="text-muted small mb-0">{{ trans('crm.try_change_filters') }}</p>
    </div>
@else
    <div class="px-4 pt-3 pb-2 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold">{{ trans('crm.followups_list') }}</span>
            <span class="text-muted small">{{ number_format($followups->total()) }} {{ trans('crm.followup') }}</span>
        </div>
        <small class="text-muted">{{ trans('crm.page_x_of_y', ['current' => $followups->currentPage(), 'last' => $followups->lastPage()]) }}</small>
    </div>

    <div class="table-responsive">
        <table class="fu-table" id="fu-main-table">
            <thead>
                <tr>
                    <th class="ps-3" style="width:40px">#</th>
                    <th style="min-width:200px">{{ trans('crm.th_member') }}</th>
                    <th style="min-width:110px">{{ trans('crm.th_branch') }}</th>
                    <th style="min-width:170px">{{ trans('crm.th_type_priority_status') }}</th>
                    <th style="min-width:150px">{{ trans('crm.th_followup_date') }}</th>
                    <th style="min-width:300px">{{ trans('crm.th_notes') }}</th>
                    <th class="text-center pe-3" style="min-width:310px">{{ trans('crm.th_actions') }}</th>
                </tr>
            </thead>
            <tbody id="fu-tbody">
            @foreach($followups as $fu)
                @php
                    $rowNo = ($followups->currentPage()-1) * $followups->perPage() + $loop->iteration;

                    $isOverdue = $fu->status === 'pending'
                        && $fu->next_action_at
                        && \Carbon\Carbon::parse($fu->next_action_at)->lt(now());

                    $statusBadge = match($fu->status) {
                        'done'      => 'success',
                        'cancelled' => 'secondary',
                        default     => $isOverdue ? 'danger' : 'primary',
                    };

                    $typeBadge = match($fu->type) {
                        'renewal'   => 'primary',
                        'freeze'    => 'info',
                        'inactive'  => 'warning',
                        'debt'      => 'danger',
                        'prospect'  => 'success',
                        default     => 'secondary',
                    };

                    $prioBadge = match($fu->priority) {
                        'high'   => 'danger',
                        'medium' => 'warning',
                        default  => 'secondary',
                    };

                    $memberName  = $fu->member?->full_name ?? '—';
                    $memberCode  = $fu->member?->member_code;
                    $memberPhone = $fu->member?->phone ?? '';

                    $memberIsProspect = ($fu->member?->type === 'prospect');
                    $memberUrl = null;
                    if ($fu->member_id) {
                        $memberUrl = $memberIsProspect
                            ? route('crm.prospects.show', $fu->member_id)
                            : route('crm.members.show', $fu->member_id);
                    }

                    $branchRaw  = $fu->branch?->name;
                    $branchDisp = is_array($branchRaw)
                        ? ($branchRaw[app()->getLocale()] ?? $branchRaw['ar'] ?? '—')
                        : ($branchRaw ?? '—');

                    $nextAt = $fu->next_action_at ? \Carbon\Carbon::parse($fu->next_action_at) : null;

                    $ints      = $interactionsByFollowup[$fu->id] ?? collect();
                    $intCount  = (int)($interactionCounts[$fu->id] ?? 0);
                    $rowClass  = $intCount > 0 ? 'fu-has-int' : '';

                    $memberTextForSelect = $memberName;
                    if (!empty($memberCode)) {
                        $memberTextForSelect .= ' — ' . $memberCode;
                    } elseif ($memberIsProspect) {
                        $memberTextForSelect .= ' — ' . trans('crm.prospect_member');
                    }
                @endphp

                <tr id="fu-row-{{ $fu->id }}" class="{{ $rowClass }}">
                    <td class="ps-3 text-muted">{{ $rowNo }}</td>

                    <td>
                        <div class="fw-semibold lh-sm">
                            @if($memberUrl)
                                <a href="{{ $memberUrl }}" class="text-decoration-none text-dark">
                                    {{ $memberName }}
                                </a>
                            @else
                                {{ $memberName }}
                            @endif
                        </div>

                        <div class="fu-muted">
                            @if(!empty($memberCode))
                                <span class="me-2">#{{ $memberCode }}</span>
                            @elseif($memberIsProspect)
                                <span class="badge bg-success-subtle text-success border me-2">{{ trans('crm.prospect_member') }}</span>
                            @else
                                <span class="badge bg-light text-muted border me-2">{{ trans('crm.no_code') }}</span>
                            @endif

                            @if($memberPhone)<span>{{ $memberPhone }}</span>@endif
                        </div>
                    </td>

                    <td>
                        <span class="badge bg-light text-dark border">{{ $branchDisp }}</span>
                    </td>

                    <td>
                        <div class="fu-badges">
                            <span class="badge bg-{{ $typeBadge }}">{{ $fu->type_label }}</span>
                            <span class="badge bg-{{ $prioBadge }}">{{ $fu->priority_label }}</span>
                            <span class="badge bg-{{ $statusBadge }}">
                                {{ $fu->status === 'pending' && $isOverdue ? trans('crm.late_badge') : $fu->status_label }}
                            </span>
                        </div>
                    </td>

                    <td>
                        @if($nextAt)
                            <div class="fw-semibold">{{ $nextAt->format('d/m/Y H:i') }}</div>
                            <div class="fu-muted">{{ $nextAt->diffForHumans() }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    <td class="fu-note">
                        {{ $fu->notes ? Str::limit($fu->notes, 120) : '—' }}
                        @if($fu->result)
                            <div class="fu-muted mt-1">
                                <span class="badge bg-light text-dark border">{{ trans('crm.result_badge') }}</span>
                                {{ $fu->result }}
                            </div>
                        @endif
                    </td>

                    <td class="pe-3">
                        <div class="fu-actions">

                            {{-- تمت --}}
                            @if($fu->status === 'pending')
                                <button type="button" class="btn btn-success btn-sm" onclick="fuMarkDone({{ $fu->id }})">
                                    {{ trans('crm.done_btn') }}
                                </button>
                            @else
                                <button class="btn btn-outline-success btn-sm" disabled>{{ trans('crm.done_btn') }}</button>
                            @endif

                            {{-- تفاعل --}}
                            <button type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#fuInt{{ $fu->id }}">
                                {{ trans('crm.interaction') }}
                                @if($intCount > 0)
                                    <span class="badge bg-info text-dark ms-1">{{ $intCount }}</span>
                                @endif
                            </button>

                            {{-- تعديل --}}
                            <button type="button"
                                    class="btn btn-outline-warning btn-sm"
                                    data-id="{{ $fu->id }}"
                                    data-member-id="{{ $fu->member_id }}"
                                    data-member-text="{{ $memberTextForSelect }}"
                                    data-branch-id="{{ $fu->branch_id }}"
                                    data-type="{{ $fu->type }}"
                                    data-status="{{ $fu->status }}"
                                    data-priority="{{ $fu->priority }}"
                                    data-next-action="{{ $nextAt?->format('Y-m-d\TH:i') ?? '' }}"
                                    data-notes="{{ e($fu->notes) }}"
                                    data-result="{{ e($fu->result) }}"
                                    onclick="fuOpenEditModal(this)">
                                {{ trans('crm.edit') }}
                            </button>

                            {{-- حذف --}}
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="fuDeleteFollowup({{ $fu->id }})">
                                {{ trans('crm.delete') }}
                            </button>
                        </div>

                        {{-- Interaction Collapse --}}
                        <div class="collapse" id="fuInt{{ $fu->id }}">
                            <div class="fu-int-box">

                                {{-- Add interaction (no form) --}}
                                <div class="row g-2 align-items-end mb-3"
                                     id="fuIntForm{{ $fu->id }}"
                                     data-member="{{ $fu->member_id }}"
                                     data-followup="{{ $fu->id }}">

                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold mb-1">{{ trans('crm.channel') }}</label>
                                        <select class="form-select form-select-sm fu-int-channel">
                                            <option value="call">{{ trans('crm.ch_call') }}</option>
                                            <option value="whatsapp">{{ trans('crm.ch_whatsapp') }}</option>
                                            <option value="visit">{{ trans('crm.ch_visit') }}</option>
                                            <option value="email">{{ trans('crm.ch_email') }}</option>
                                            <option value="sms">SMS</option>
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold mb-1">{{ trans('crm.direction') }}</label>
                                        <select class="form-select form-select-sm fu-int-direction">
                                            <option value="outbound">{{ trans('crm.dir_outbound') }}</option>
                                            <option value="inbound">{{ trans('crm.dir_inbound') }}</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold mb-1">{{ trans('crm.int_result') }}</label>
                                        <select class="form-select form-select-sm fu-int-result">
                                            <option value="answered">{{ trans('crm.res_answered') }}</option>
                                            <option value="no_answer">{{ trans('crm.res_no_answer') }}</option>
                                            <option value="interested">{{ trans('crm.res_interested') }}</option>
                                            <option value="not_interested">{{ trans('crm.res_not_interested') }}</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">{{ trans('crm.date') }}</label>
                                        <input type="datetime-local" class="form-control form-control-sm fu-int-date">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label small fw-semibold mb-1">{{ trans('crm.notes') }}</label>
                                        <textarea class="form-control form-control-sm fu-int-notes" rows="2" placeholder="{{ trans('crm.what_happened_ph') }}"></textarea>
                                    </div>

                                    <div class="col-12 d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm"
                                                onclick="fuSaveInteraction({{ $fu->id }}, this)">
                                            <i class="fas fa-save me-1"></i> {{ trans('crm.save_interaction') }}
                                        </button>

                                        @if($fu->status === 'pending')
                                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                                    onclick="fuCancelFollowup({{ $fu->id }})">
                                                {{ trans('crm.cancel_followup_btn') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Interactions list --}}
                                @if($ints->isNotEmpty())
                                    <hr class="my-2">
                                    <div class="fu-muted fw-semibold mb-2">{{ trans('crm.last_interactions') }}</div>
                                    <div class="table-responsive">
                                        <table class="fu-table" style="font-size:0.82rem">
                                            <thead>
                                                <tr>
                                                    <th>{{ trans('crm.th_time') }}</th>
                                                    <th>{{ trans('crm.th_channel') }}</th>
                                                    <th>{{ trans('crm.th_direction') }}</th>
                                                    <th>{{ trans('crm.th_result') }}</th>
                                                    <th>{{ trans('crm.th_notes') }}</th>
                                                    <th class="text-center">{{ trans('crm.th_int_cancel') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($ints as $it)
                                                    <tr id="int-row-{{ $it->id }}">
                                                        <td>{{ $it->interacted_at ? \Carbon\Carbon::parse($it->interacted_at)->format('d/m/Y H:i') : '—' }}</td>
                                                        <td>{{ $it->channel }}</td>
                                                        <td>{{ $it->direction }}</td>
                                                        <td>{{ $it->result }}</td>
                                                        <td style="white-space:normal;min-width:200px">{{ $it->notes ?? '—' }}</td>
                                                        <td class="text-center">
                                                            <button type="button"
                                                                    class="btn btn-outline-danger btn-sm"
                                                                    onclick="fuDeleteInteraction({{ $it->id }})">
                                                                {{ trans('crm.th_int_cancel') }}
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-muted small mb-0">{{ trans('crm.no_interactions') }}</div>
                                @endif

                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if($followups->hasPages())
        <div class="fu-pagination px-4 py-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">
                {{ trans('crm.showing_x_to_y', ['from' => $followups->firstItem(), 'to' => $followups->lastItem(), 'total' => number_format($followups->total())]) }}
            </small>
            <div>
                {!! $followups->appends(request()->except('page','partial'))->onEachSide(1)->links('pagination::bootstrap-5') !!}
                <script>
                    document.querySelectorAll('.pagination a.page-link').forEach(function(a){
                        a.setAttribute('data-fu-ajax','1');
                    });
                </script>
            </div>
        </div>
    @endif
@endif
