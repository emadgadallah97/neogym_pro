<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="index.html" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ URL::asset('assets/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ URL::asset('assets/images/logo.png') }}" alt=""
                    style="height: 70px;width: 100% !important;">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="index.html" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ URL::asset('assets/images/logo-sm.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ URL::asset('assets/images/logo.png') }}" alt="" height="17"
                    style="height: 70px;width:100% !important;">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>

            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span data-key="t-menu">Menu</span></li>

                {{-- dashboard --}}
                @can('dashboard')
                <li class="nav-item">
                    <a class="nav-link menu-link font @if (Route::currentRouteName() == 'dashboard.create' || Route::currentRouteName() == 'dashboard.index') active @endif"
                        href="{{ url('/' . ($page = 'dashboard')) }}">
                        <i class="mdi mdi-speedometer"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.dashboards') }}</span>
                    </a>
                </li>
                @endcan

                {{-- الحضور --}}
                @can('attendance')
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'attendances.create' || Route::currentRouteName() == 'attendances.index') active @endif"
                        href="{{ url('/' . ($page = 'attendances')) }}">
                        <i class="mdi mdi-account-check"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.attendances') }}</span>
                    </a>
                </li>
                @endcan

                {{-- الموظفين --}}
                @can('employees')
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'employees.create' || Route::currentRouteName() == 'employees.index') active @endif"
                        href="{{ url('/' . ($page = 'employees')) }}">
                        <i class="mdi mdi-account-multiple"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.employees') }}</span>
                    </a>
                </li>
                @endcan

                {{-- الاعضاء --}}
                @can('members')
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'members.create' || Route::currentRouteName() == 'members.index') active @endif"
                        href="{{ url('/' . ($page = 'members')) }}">
                        <i class="mdi mdi-account-group"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.members') }}</span>
                    </a>
                </li>
                @endcan

                {{-- الاشتراكات --}}
                @can('subscriptions')
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'subscriptions_plans.create' || Route::currentRouteName() == 'subscriptions_plans.index') active @endif"
                        href="{{ url('/' . ($page = 'subscriptions_plans')) }}">
                        <i class="mdi mdi-calendar-check"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.subscriptions') }}</span>
                    </a>
                </li>
                @endcan

                {{-- المبيعات --}}
                @can('sales')
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'sales.create' || Route::currentRouteName() == 'sales.index') active @endif"
                        href="{{ url('/' . ($page = 'sales')) }}">
                        <i class="mdi mdi-cart"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.sales') }}</span>
                    </a>
                </li>
                @endcan

                {{-- الكبونات --}}
                @can('coupons')
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'coupons_offers.create' || Route::currentRouteName() == 'coupons_offers.index') active @endif"
                        href="{{ url('/' . ($page = 'coupons')) }}">
                        <i class="mdi mdi-ticket-percent"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.coupons') }}</span>
                    </a>
                </li>
                @endcan

                {{-- العروض --}}
                @can('offers')
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'coupons_offers.create' || Route::currentRouteName() == 'coupons_offers.index') active @endif"
                        href="{{ url('/' . ($page = 'offers')) }}">
                        <i class="mdi mdi-sale"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.offers') }}</span>
                    </a>
                </li>
                @endcan

                {{-- الحسابات --}}
                @can('accounting')
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'accounting.create' || Route::currentRouteName() == 'accounting.index') active @endif"
                        href="{{ url('/' . ($page = 'accounting')) }}">
                        <i class="mdi mdi-cash-multiple"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.accounting') }}</span>
                    </a>
                </li>
                @endcan

                {{-- الموارد البشريه --}}
                @can('human_resources')
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'hr.create' || Route::currentRouteName() == 'hr.index') active @endif"
                        href="{{ url('/' . ($page = 'hr')) }}">
                        <i class="mdi mdi-clipboard-account"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.hr') }}</span>
                    </a>
                </li>
                @endcan

                {{-- اداره علاقات العملاء --}}
                @can('crm')
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'crm.create' || Route::currentRouteName() == 'crm.index') active @endif"
                        href="{{ url('/' . ($page = 'crm/dashboard')) }}">
                        <i class="mdi mdi-handshake"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.crm') }}</span>
                    </a>
                </li>
                @endcan

                {{-- user management section --}}
                @canany(['user_management', 'security_control'])
                <li class="menu-title">
                    <i class="ri-more-fill"></i>
                    <span data-key="t-components">{{ trans('main_trans.user_management') }}</span>
                </li>
                @endcanany

                {{-- إدارة المستخدمين --}}
                @can('user_management')
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'users.index' || Route::currentRouteName() == 'users.create' || Route::currentRouteName() == 'users.edit') active @endif"
                        href="{{ route('users.index') }}">
                        <i class="mdi mdi-account-multiple-outline"></i>
                        <span data-key="t-users">{{ trans('main_trans.user_management') }}</span>
                    </a>
                </li>
                @endcan

                {{-- إدارة الصلاحيات --}}
                @can('security_control')
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'roles.index' || Route::currentRouteName() == 'roles.create' || Route::currentRouteName() == 'roles.edit') active @endif"
                        href="{{ route('roles.index') }}">
                        <i class="mdi mdi-account-lock-outline"></i>
                        <span data-key="t-roles">{{ trans('main_trans.roles_management') }}</span>
                    </a>
                </li>
                @endcan

                {{-- reports section --}}
                @can('reports')
                <li class="menu-title">
                    <i class="ri-more-fill"></i>
                    <span data-key="t-components">{{ trans('main_trans.reports') }}</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'reports.create' || Route::currentRouteName() == 'reports.index') active @endif"
                        href="{{ url('/' . ($page = 'reports')) }}">
                        <i class="mdi mdi-chart-bar"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.reports') }}</span>
                    </a>
                </li>
                @endcan

                {{-- settings section --}}
                @can('settings')
                <li class="menu-title">
                    <i class="ri-more-fill"></i>
                    <span data-key="t-components">{{ trans('main_trans.settings') }}</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'settings.create' || Route::currentRouteName() == 'settings.index') active @endif"
                        href="{{ url('/' . ($page = 'settings')) }}">
                        <i class="mdi mdi-cog-outline"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.settings') }}</span>
                    </a>
                </li>
                @endcan

            </ul>
        </div>
        <!-- Sidebar -->
    </div>

    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>