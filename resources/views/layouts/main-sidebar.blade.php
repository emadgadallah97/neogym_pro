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
                <li class="nav-item">
                    <a class="nav-link menu-link font  @if (Route::currentRouteName() == 'dashbord.create' || Route::currentRouteName() == 'dashbord.index') active @endif"
                        href="{{ url('/' . ($page = 'dashbord')) }}">
                        <i class="mdi mdi-speedometer"></i> <span data-key="t-widgets">
                            {{ trans('main_trans.dashboards') }}</span>
                    </a>
                </li>
                {{-- الحضور --}}
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'attendances.create' || Route::currentRouteName() == 'attendances.index') active @endif"
                        href="{{ url('/' . ($page = 'attendances')) }}">

                        <i class="mdi mdi-account-check"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.attendances') }}</span>
                    </a>
                </li>
                {{-- الموظفين --}}
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'employees.create' || Route::currentRouteName() == 'employees.index') active @endif"
                        href="{{ url('/' . ($page = 'employees')) }}">

                        <i class="mdi mdi-account-multiple"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.employees') }}</span>
                    </a>
                </li>
                {{-- الاعضاء --}}
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'members.create' || Route::currentRouteName() == 'members.index') active @endif"
                        href="{{ url('/' . ($page = 'members')) }}">

                        <i class="mdi mdi-account-group"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.members') }}</span>
                    </a>
                </li>
                {{-- الاشتراكات --}}
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'subscriptions_plans.create' || Route::currentRouteName() == 'subscriptions_plans.index') active @endif"
                        href="{{ url('/' . ($page = 'subscriptions_plans')) }}">

                        <i class="mdi mdi-calendar-check"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.subscriptions') }}</span>
                    </a>
                </li>
                {{-- المبيعات --}}
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'sales.create' || Route::currentRouteName() == 'sales.index') active @endif"
                        href="{{ url('/' . ($page = 'sales')) }}">

                        <i class="mdi mdi-cart"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.sales') }}</span>
                    </a>
                </li>
                {{-- الكبونات --}}
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'coupons_offers.create' || Route::currentRouteName() == 'coupons_offers.index') active @endif"
                        href="{{ url('/' . ($page = 'coupons')) }}">

                        <i class="mdi mdi-ticket-percent"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.coupons') }}</span>
                    </a>
                </li>
                {{-- العروض --}}
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'coupons_offers.create' || Route::currentRouteName() == 'coupons_offers.index') active @endif"
                        href="{{ url('/' . ($page = 'offers')) }}">

                        <i class="mdi mdi-sale"></i>

                        <span data-key="t-widgets">{{ trans('main_trans.offers') }}</span>
                    </a>
                </li>
                {{-- الحسابات --}}
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'accounting.create' || Route::currentRouteName() == 'accounting.index') active @endif"
                        href="{{ url('/' . ($page = 'accounting')) }}">

                        <i class="mdi mdi-cash-multiple"></i>

                        <span data-key="t-widgets">{{ trans('main_trans.accounting') }}</span>
                    </a>
                </li>
                <!--user management-->
                <li class="menu-title"><i class="ri-more-fill"></i> <span
                        data-key="t-components">{{ trans('main_trans.user_management') }}</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link font" href="#sidebaruser_management" data-bs-toggle="collapse"
                        role="button" aria-expanded="false" aria-controls="sidebarAuth">
                        <i class="mdi mdi-account-lock-outline"></i> <span
                            data-key="t-authentication">{{ trans('main_trans.users') }}</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebaruser_management">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false"
                                    aria-controls="sidebarSignIn"
                                    data-key="t-signin">{{ trans('main_trans.user_add') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="collapse" role="button" aria-expanded="false"
                                    aria-controls="sidebarSignUp"
                                    data-key="t-signup">{{ trans('main_trans.user_management') }}</a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!--reports-->
                <li class="menu-title"><i class="ri-more-fill"></i> <span
                        data-key="t-components">{{ trans('main_trans.reports') }}</span></li>
                {{-- التقارير --}}
                <li class="nav-item">
                    <a class="nav-link menu-link font
                        @if (Route::currentRouteName() == 'reports.create' || Route::currentRouteName() == 'reports.index') active @endif"
                        href="{{ url('/' . ($page = 'reports')) }}">

                        <i class="mdi mdi-chart-bar"></i>
                        <span data-key="t-widgets">{{ trans('main_trans.reports') }}</span>
                    </a>
                </li>
                <!--settings-->
                <li class="menu-title"><i class="ri-more-fill"></i> <span
                        data-key="t-components">{{ trans('main_trans.settings') }}</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link font @if (Route::currentRouteName() == 'settings.create' || Route::currentRouteName() == 'settings.index') active @endif"
                        href="{{ url('/' . ($page = 'settings')) }}">
                        <i class="mdi mdi-cog-outline"></i> <span
                            data-key="t-widgets">{{ trans('main_trans.settings') }}</span>
                    </a>
                </li>

            </ul>
        </div>
        <!-- Sidebar -->
    </div>

    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
