<header>
    <nav class="navbar navbar-expand navbar-light navbar-top">
        <div class="container-fluid">
            <a href="#" class="burger-btn d-block d-xl-none">
                <i class="bi bi-justify fs-3"></i>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <div class="d-flex align-items-center gap-3 ms-auto">
                    @if($allowedBranches->isNotEmpty())
                        <form action="{{ route('branch.switchCurrent') }}" method="POST" class="d-flex align-items-center gap-2">
                            @csrf
                            <label class="text-sm text-gray-600 mb-0">Branch</label>
                            <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach($allowedBranches as $branch)
                                    <option value="{{ $branch->id }}" {{ (int) ($currentBranch?->id) === (int) $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif

                    @if($currentBranch)
                        <div class="text-end d-none d-lg-block">
                            <div class="fw-semibold text-gray-700">{{ $currentBranch->name }}</div>
                            <div class="text-sm text-gray-600">{{ $currentBranch->code }}</div>
                        </div>
                    @endif

                <div class="dropdown ms-auto">
                    <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-menu d-flex">
                            <div class="user-name text-end me-3">
                                <h6 class="mb-0 text-gray-600">{{ auth()->user()->name }}</h6>
                                <p class="mb-0 text-sm text-gray-600">Administrator</p>
                            </div>
                            <div class="user-img d-flex align-items-center">
                                <div class="avatar avatar-md">
                                    <img src="{{url('public/assets/compiled/jpg/1.jpg')}}">
                                </div>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton"
                        style="min-width: 11rem;">
                        <li>
                            <h6 class="dropdown-header">Hello, {{ auth()->user()->name }}!</h6>
                        </li>
                        <li><a class="dropdown-item" href="#"><i class="icon-mid bi bi-person me-2"></i> My
                                Profile</a></li>
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
        </div>
    </nav>
</header>
