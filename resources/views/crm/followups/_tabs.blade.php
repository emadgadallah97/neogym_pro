{{-- resources/views/crm/followups/_tabs.blade.php --}}
@php
    $statusTabs = [
        'all'       => ['label' => trans('crm.tab_all'),        'color' => 'secondary'],
        'pending'   => ['label' => trans('crm.tab_pending'),    'color' => 'primary'  ],
        'overdue'   => ['label' => trans('crm.tab_overdue'),    'color' => 'danger'   ],
        'today'     => ['label' => trans('crm.tab_today'),      'color' => 'warning'  ],
        'prospect'  => ['label' => trans('crm.tab_prospects'),  'color' => 'success'  ],
        'done'      => ['label' => trans('crm.tab_done'),       'color' => 'success'  ],
        'cancelled' => ['label' => trans('crm.tab_cancelled'),  'color' => 'dark'     ],
    ];
@endphp

<ul class="nav nav-pills flex-wrap gap-2 mb-0">
    @foreach($statusTabs as $key => $tab)
        @php
            $isActive = ($quick === $key);
            $isDark   = in_array($tab['color'], ['warning', 'light']);
            $cnt      = $counts[$key] ?? 0;

            // ✅ ابدأ من params بدون page/partial
            $baseParams = request()->except('page', 'partial');

            // ✅ الإصلاح الأساسي:
            // إذا كنا في تاب prospect والانتقلنا لتاب آخر → احذف type من الـ params
            // إذا كان المستخدم ضبط type يدوياً في تاب آخر → احتفظ به
            if ($quick === 'prospect' && $key !== 'prospect') {
                unset($baseParams['type']);
            }

            $qParams = array_merge($baseParams, ['quick' => $key]);

            // ✅ تاب prospect يضيف type=prospect دائماً
            if ($key === 'prospect') {
                $qParams['type'] = 'prospect';
            }
        @endphp

        <li class="nav-item">
            <a href="{{ route('crm.followups.index', $qParams) }}"
               data-fu-ajax="1"
               class="nav-link py-2 px-3 {{ $isActive ? 'active bg-'.$tab['color'].($isDark?' text-dark':'') : 'text-muted' }}">
                {{ $tab['label'] }}
                @if($cnt > 0)
                    <span class="badge ms-1 {{ $isActive ? 'bg-white text-dark' : 'bg-'.$tab['color'].($isDark?' text-dark':'') }}">
                        {{ number_format($cnt) }}
                    </span>
                @endif
            </a>
        </li>
    @endforeach
</ul>
