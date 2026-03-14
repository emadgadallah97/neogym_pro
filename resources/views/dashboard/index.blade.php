@extends('layouts.master_table')

@section('title')
{{ trans('dashboard.dashboard') }}
@stop

@section('css')
<style>
    .kpi-card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
        background: #fff;
    }
    .kpi-title {
        font-size: 14px;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 10px;
    }
    .kpi-value {
        font-size: 24px;
        font-weight: 700;
        color: #343a40;
    }
    .kpi-icon {
        font-size: 30px;
        color: #007bff;
        opacity: 0.8;
    }
    .chart-container {
        height: 350px;
        position: relative;
    }
</style>
@stop

@section('content')

<!-- start page title -->
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">{{ trans('dashboard.dashboard') }}</h4>

            <div class="page-title-right">
                <form method="GET" action="{{ route('dashboard.index') }}" class="d-flex align-items-center">
                    <label class="me-2 mb-0">{{ trans('dashboard.filter_by_branch') }}:</label>
                    <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ trans('dashboard.all_branches') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string)$selectedBranch === (string)$branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<!-- SECTION A: TOP KPI CARDS -->
<div class="row">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-title">{{ trans('dashboard.total_active_members') }}</div>
                    <div class="kpi-value">{{ number_format($kpis['total_active_members']) }}</div>
                </div>
                <div class="kpi-icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-title">{{ trans('dashboard.today_attendance') }}</div>
                    <div class="kpi-value">{{ number_format($kpis['today_attendance']) }}</div>
                </div>
                <div class="kpi-icon"><i class="fas fa-check-circle text-primary"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-title">{{ trans('dashboard.today_revenue') }}</div>
                    <div class="kpi-value">${{ number_format($kpis['today_revenue'], 2) }}</div>
                </div>
                <div class="kpi-icon"><i class="fas fa-money-bill-wave text-success"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-title">{{ trans('dashboard.today_expenses') }}</div>
                    <div class="kpi-value">${{ number_format($kpis['today_expenses'], 2) }}</div>
                </div>
                <div class="kpi-icon"><i class="fas fa-file-invoice-dollar text-danger"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-title">{{ trans('dashboard.active_subscriptions') }}</div>
                    <div class="kpi-value">{{ number_format($kpis['active_subscriptions']) }}</div>
                </div>
                <div class="kpi-icon"><i class="fas fa-id-card text-info"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-title">{{ trans('dashboard.pending_renewals') }}</div>
                    <div class="kpi-value">{{ number_format($kpis['pending_renewals']) }}</div>
                </div>
                <div class="kpi-icon"><i class="fas fa-clock text-warning"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-title">{{ trans('dashboard.total_employees') }}</div>
                    <div class="kpi-value">{{ number_format($kpis['total_employees']) }}</div>
                </div>
                <div class="kpi-icon"><i class="fas fa-user-tie text-secondary"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="kpi-title">{{ trans('dashboard.invoice_summary') }}</div>
                    <div style="font-size: 14px;">
                        <span class="text-muted">{{ trans('dashboard.total_invoices_month') }}:</span> <b>{{ number_format($kpis['total_invoices_month']) }}</b><br>
                        <span class="text-muted">{{ trans('dashboard.total_invoiced_amount') }}:</span> <b>${{ number_format($kpis['total_invoiced_amount'], 2) }}</b>
                    </div>
                </div>
                <div class="kpi-icon"><i class="fas fa-receipt text-dark"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION C & E: CHARTS -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header border-0 pb-0">
                <h5 class="card-title mb-0">{{ trans('dashboard.revenue_vs_expenses') }}</h5>
            </div>
            <div class="card-body">
                <div id="revenueExpensesChart" class="chart-container"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header border-0 pb-0">
                <h5 class="card-title mb-0">{{ trans('dashboard.subscription_type_distribution') }}</h5>
            </div>
            <div class="card-body">
                <div id="subscriptionTypesChart" class="chart-container"></div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header border-0 pb-0">
                <h5 class="card-title mb-0">{{ trans('dashboard.attendance_chart') }}</h5>
            </div>
            <div class="card-body">
                <div id="attendanceChart" class="chart-container" style="height: 300px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION B & D & F: TABLES & LISTS -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header border-0 pb-0">
                <h5 class="card-title mb-0">{{ trans('dashboard.branch_comparison') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>{{ trans('dashboard.branch') }}</th>
                                <th>{{ trans('dashboard.active_members') }}</th>
                                <th>{{ trans('dashboard.today_attendance') }}</th>
                                <th>{{ trans('dashboard.mtd_revenue') }}</th>
                                <th>{{ trans('dashboard.mtd_expenses') }}</th>
                                <th>{{ trans('dashboard.net') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branchesData as $b)
                            <tr>
                                <td>{{ $b['name'] }}</td>
                                <td>{{ number_format($b['active_members']) }}</td>
                                <td>{{ number_format($b['today_attendance']) }}</td>
                                <td class="text-success">${{ number_format($b['mtd_revenue'], 2) }}</td>
                                <td class="text-danger">${{ number_format($b['mtd_expenses'], 2) }}</td>
                                <td class="{{ $b['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    <b>${{ number_format($b['net'], 2) }}</b>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header border-0 pb-0">
                <h5 class="card-title mb-0">{{ trans('dashboard.membership_subscriptions') }}</h5>
            </div>
            <div class="card-body">
                <p><b>{{ trans('dashboard.new_members_this_month') }}:</b> {{ number_format($newMembersThisMonth) }}</p>
                <p><b>{{ trans('dashboard.new_members_last_month') }}:</b> {{ number_format($newMembersLastMonth) }}</p>
                <hr>
                <h6>{{ trans('dashboard.members_by_status') }}</h6>
                <ul class="list-group list-group-flush">
                    @foreach($membersByStatus as $status => $count)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ trans("dashboard.$status") }}
                        <span class="badge bg-primary rounded-pill">{{ number_format($count) }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header border-0 pb-0">
                <h5 class="card-title mb-0">{{ trans('dashboard.top_performers') }}</h5>
            </div>
            <div class="card-body" id="topPerformersContainer">
                <!-- Loaded via AJAX -->
                <div class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>
</div>

<!-- Load ApexCharts if not loaded by the layout -->
<script src="{{asset('assets/libs/apexcharts/apexcharts.min.js')}}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const branchId = '{{ $selectedBranch }}';
        
        // Fetch charts data via AJAX
        fetch(`{{ route('dashboard.ajaxCharts') }}?branch_id=${branchId}`)
            .then(response => response.json())
            .then(data => {
                
                // 1. Revenue vs Expenses Chart (ApexCharts)
                if (document.querySelector("#revenueExpensesChart")) {
                    var revExpOptions = {
                        series: [{
                            name: '{{ trans("dashboard.revenue") }}',
                            data: data.revenue_expenses.revenue
                        }, {
                            name: '{{ trans("dashboard.expenses") }}',
                            data: data.revenue_expenses.expenses
                        }],
                        chart: {
                            type: 'bar',
                            height: 350,
                            toolbar: { show: false }
                        },
                        colors: ['#28a745', '#dc3545'],
                        plotOptions: {
                            bar: { horizontal: false, columnWidth: '55%', endingShape: 'rounded' },
                        },
                        dataLabels: { enabled: false },
                        stroke: { show: true, width: 2, colors: ['transparent'] },
                        xaxis: { categories: data.revenue_expenses.months },
                        yaxis: { title: { text: '$' } },
                        fill: { opacity: 1 },
                        tooltip: {
                            y: { formatter: function (val) { return "$" + val } }
                        }
                    };
                    var revExpChart = new ApexCharts(document.querySelector("#revenueExpensesChart"), revExpOptions);
                    revExpChart.render();
                }

                // 2. Subscription Type Distribution Chart
                if (document.querySelector("#subscriptionTypesChart")) {
                    var subTypeOptions = {
                        series: data.subscription_types.series,
                        chart: { type: 'donut', height: 350 },
                        labels: data.subscription_types.labels,
                        dataLabels: { enabled: true },
                        legend: { position: 'bottom' }
                    };
                    var subTypeChart = new ApexCharts(document.querySelector("#subscriptionTypesChart"), subTypeOptions);
                    subTypeChart.render();
                }

                // 3. Attendance Chart
                if (document.querySelector("#attendanceChart")) {
                    var attOptions = {
                        series: [{
                            name: '{{ trans("dashboard.today_attendance") }}',
                            data: data.attendance.totals
                        }],
                        chart: {
                            type: 'area',
                            height: 300,
                            toolbar: { show: false }
                        },
                        colors: ['#007bff'],
                        dataLabels: { enabled: false },
                        stroke: { curve: 'smooth' },
                        xaxis: { categories: data.attendance.dates },
                    };
                    var attChart = new ApexCharts(document.querySelector("#attendanceChart"), attOptions);
                    attChart.render();
                }

                // 4. Render Top Performers
                let topPerformersHtml = `<h6>{{ trans("dashboard.top_5_branches_revenue") }}</h6><ul class="list-group list-group-flush mb-3">`;
                if(data.top_branches.length > 0) {
                    data.top_branches.forEach(b => {
                        topPerformersHtml += `<li class="list-group-item d-flex justify-content-between"><span>${b.name}</span> <b class="text-success">$${Number(b.total).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</b></li>`;
                    });
                } else {
                    topPerformersHtml += `<li class="list-group-item text-muted">No data</li>`;
                }
                topPerformersHtml += `</ul>`;

                topPerformersHtml += `<h6>{{ trans("dashboard.best_selling_packages") }}</h6><ul class="list-group list-group-flush">`;
                if(data.top_packages.length > 0) {
                    data.top_packages.forEach(p => {
                        topPerformersHtml += `<li class="list-group-item d-flex justify-content-between"><span>${p.name}</span> <span class="badge bg-success rounded-pill">${p.total}</span></li>`;
                    });
                } else {
                    topPerformersHtml += `<li class="list-group-item text-muted">No data</li>`;
                }
                topPerformersHtml += `</ul>`;

                document.getElementById('topPerformersContainer').innerHTML = topPerformersHtml;

            }).catch(error => {
                console.error('Error fetching chart data:', error);
                document.getElementById('topPerformersContainer').innerHTML = '<div class="text-danger">Failed to load data.</div>';
            });
    });
</script>
@endsection