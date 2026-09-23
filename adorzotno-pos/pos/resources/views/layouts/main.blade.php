<!doctype html>
<html lang="en" data-bs-theme="dark">
@include('layouts.partials.header')

<body>
    <script src="{{ url('public/admin/dist/assets/static/js/initTheme.js') }}"></script>

    <div id="app">
        @include('layouts.partials.sidebar')



        <div id="main">
            <div class="admin-shell">
                <header class="admin-topbar">
                    <div class="topbar-left">


                        <div class="topbar-context">
                            <button type="button" class="sidebar-expand-btn" id="sidebarExpandBtn" data-sidebar-expand aria-label="Show sidebar">
                                <i class="bi bi-layout-sidebar-inset"></i>
                                <span>Open Menu</span>
                            </button>
                            <a href="#" class="action-icon-btn burger-btn d-inline-flex d-xl-none" aria-label="Open sidebar">
                                <i class="bi bi-list fs-4"></i>
                                <span class="d-block d-md-none">Open Menu</span>
                            </a>
                            <span class="topbar-context-label d-none d-md-inline-flex">
                                <i class="bi bi-lightning-charge-fill"></i>
                                Admin Workspace
                            </span>
                            <!-- <h1>Operations control center</h1>
                            <p>Track sales, inventory, and branch activity from one responsive dashboard.</p> -->
                        </div>
                    </div>

                    <div class="topbar-right ">
                        <div class="topbar-meta">
                            <span class="topbar-chip">
                                <i class="bi bi-calendar-event"></i>
                                {{ \App\Support\DateFormatter::date(now()) }}
                            </span>

                            @if($currentBranch)
                                <span class="topbar-branch">
                                    <i class="bi bi-building"></i>
                                    {{ $currentBranch->name }}{{ $currentBranch->code ? ' - ' . $currentBranch->code : '' }}
                                </span>
                            @endif

                            @if($allowedBranches->isNotEmpty())
                                <form action="{{ route('branch.switchCurrent') }}" method="POST" class="branch-switch-form">
                                    @csrf
                                    <label for="current-branch-selector">Branch</label>
                                    <select id="current-branch-selector" name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                        @foreach($allowedBranches as $branch)
                                            <option value="{{ $branch->id }}" {{ (int) ($currentBranch?->id) === (int) $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif
                        </div>

                        <div class="navbar-actions justify-end">


                            <div class="dropdown">
                                <a href="#" class="user-dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-card">
                                        <div class="text-end">
                                            <h6>{{ auth()->user()->name }}</h6>
                                            <p>Signed in</p>
                                        </div>
                                        <!-- <div class="avatar avatar-md">
                                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                        </div> -->
                                         <div class="avatar avatar-md">
                                            <img src="{{url('public/admin/dist/assets/compiled/jpg/1.jpg')}}">
                                        </div>
                                    </div>
                                </a>

                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton" style="min-width: 12rem; z-index: 99999; overflow:visible">
                                    <li>
                                        <h6 class="dropdown-header">Hello, {{ auth()->user()->name }}!</h6>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                                            <i class="icon-mid bi bi-box-arrow-left me-2"></i> Logout
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="page-shell">
                    @yield('main.content')
                </main>

                @include('layouts.partials.footer')
            </div>
        </div>
    </div>

    @include('layouts.partials.scripts')
</body>

</html>
