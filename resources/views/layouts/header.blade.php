@php
    use Modules\Branch\Entities\Branch;
    $branch = Branch::find(session('branch_id'));
@endphp
<button class="c-header-toggler c-class-toggler d-lg-none mfe-auto" type="button" data-target="#sidebar" data-class="c-sidebar-show">
    <i class="bi bi-list" style="font-size: 2rem;"></i>
</button>

<button class="c-header-toggler c-class-toggler mfs-3 d-md-down-none" type="button" data-target="#sidebar" data-class="c-sidebar-lg-show" responsive="true">
    <i class="bi bi-list" style="font-size: 2rem;"></i>
</button>

<ul class="c-header-nav ml-auto">

</ul>
<ul class="c-header-nav ml-auto mr-4">
    @can('access_branches')
    <li class="c-header-nav-item mr-3">
        <div class="dropdown">
            <button class="btn btn-outline-primary btn-pill dropdown-toggle" type="button" id="branchDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-building mr-1"></i> 
                {{ $branch ? $branch->name : 'Pilih Cabang' }}
            </button>
            <div class="dropdown-menu" aria-labelledby="branchDropdown">
                @foreach(Branch::all() as $b)
                    <a class="dropdown-item {{ session('branch_id') == $b->id ? 'active' : '' }}" 
                       href="{{ route('branch.switch', $b->id) }}">
                        {{ $b->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </li>
    @endcan

    @can('create_pos_sales')
    <li class="c-header-nav-item mr-3">
        <a class="btn btn-primary btn-pill {{ request()->routeIs('app.pos.index') ? 'disabled' : '' }}" href="{{ route('app.pos.index') }}">
            <i class="bi bi-cart mr-1"></i> Sistem POS
        </a>
    </li>
    @endcan

    @can('show_notifications')
    <li class="c-header-nav-item dropdown d-md-down-none mr-2">
        <a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
            <i class="bi bi-bell" style="font-size: 20px;"></i>
            <span class="badge badge-pill badge-danger">
            @php
                $low_quantity_products = \Modules\Product\Entities\Product::select('id', 'product_code', 'product_name', 'product_stock_alert')
                    ->withSum('batches', 'qty', 'batches_sum_qty')
                    ->having('batches_sum_qty', '<=', DB::raw('product_stock_alert'))
                    ->get();
                echo $low_quantity_products->count();
            @endphp
            </span>
        </a>
        <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg pt-0">
            <div class="dropdown-header bg-light">
                <strong>{{ $low_quantity_products->count() }} Notifikasi</strong>
            </div>
            @forelse($low_quantity_products as $product)
                <a class="dropdown-item" href="{{ route('products.show', $product->id) }}">
                    <i class="bi bi-hash mr-1 text-primary"></i> Produk: "{{ $product->product_name }}" stok menipis!
                </a>
            @empty
                <a class="dropdown-item" href="#">
                    <i class="bi bi-app-indicator mr-2 text-danger"></i> Tidak ada notifikasi.
                </a>
            @endforelse
        </div>
    </li>
    @endcan

    <li class="c-header-nav-item dropdown">
        <a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button"
           aria-haspopup="true" aria-expanded="false">
            <div class="c-avatar mr-2">
                <img class="c-avatar rounded-circle" src="{{ auth()->user()->getFirstMediaUrl('avatars') }}" alt="Profile Image">
            </div>
            <div class="d-flex flex-column">
                <span class="font-weight-bold">{{ auth()->user()->name }}</span>
                <span class="font-italic">Online <i class="bi bi-circle-fill text-success" style="font-size: 11px;"></i></span>
            </div>
        </a>
        <div class="dropdown-menu dropdown-menu-right pt-0">
            <div class="dropdown-header bg-light py-2"><strong>Akun</strong></div>
            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                <i class="mfe-2  bi bi-person" style="font-size: 1.2rem;"></i> Profil
            </a>
            <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="mfe-2  bi bi-box-arrow-left" style="font-size: 1.2rem;"></i> Keluar
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </li>
</ul>

{{-- <button class="c-header-toggler c-class-toggler d-lg-none mfe-auto" type="button" data-target="#sidebar" data-class="c-sidebar-show">
    <i class="bi bi-list" style="font-size: 2rem;"></i>
</button>

<button class="c-header-toggler c-class-toggler mfs-3 d-md-down-none" type="button" data-target="#sidebar" data-class="c-sidebar-lg-show" responsive="true">
    <i class="bi bi-list" style="font-size: 2rem;"></i>
</button>

<ul class="c-header-nav ml-auto">

</ul>
<ul class="c-header-nav ml-auto mr-4">
    @can('access_branches')
    <li class="c-header-nav-item mr-3">
        <div class="dropdown">
            <button class="btn btn-outline-primary btn-pill dropdown-toggle" type="button" id="branchDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-building mr-1"></i> {{ auth()->user()->active_branch ? auth()->user()->active_branch->name : 'Pilih Cabang' }}
            </button>
            <div class="dropdown-menu" aria-labelledby="branchDropdown">
                @foreach(\Modules\Branch\Entities\Branch::all() as $branch)
                    <a class="dropdown-item {{ auth()->user()->active_branch && auth()->user()->active_branch->id === $branch->id ? 'active' : '' }}" 
                       href="{{ route('branch.switch', $branch->id) }}">
                        {{ $branch->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </li>
    @endcan

    @can('create_pos_sales')
    <li class="c-header-nav-item mr-3">
        <a class="btn btn-primary btn-pill {{ request()->routeIs('app.pos.index') ? 'disabled' : '' }}" href="{{ route('app.pos.index') }}">
            <i class="bi bi-cart mr-1"></i> POS System
        </a>
    </li>
    @endcan

    @can('show_notifications')
    <li class="c-header-nav-item dropdown d-md-down-none mr-2">
        <a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
            <i class="bi bi-bell" style="font-size: 20px;"></i>
            <span class="badge badge-pill badge-danger">
            @php
                $low_quantity_products = \Modules\Product\Entities\Product::select('id', 'product_code', 'product_name', 'product_stock_alert')
                    ->withSum('batches', 'qty', 'batches_sum_qty')
                    ->having('batches_sum_qty', '<=', DB::raw('product_stock_alert'))
                    ->get();
                echo $low_quantity_products->count();
            @endphp
            </span>
        </a>
        <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg pt-0">
            <div class="dropdown-header bg-light">
                <strong>{{ $low_quantity_products->count() }} Notifications</strong>
            </div>
            @forelse($low_quantity_products as $product)
                <a class="dropdown-item" href="{{ route('products.show', $product->id) }}">
                    <i class="bi bi-hash mr-1 text-primary"></i> Product: "{{ $product->product_name }}" is low in quantity!
                </a>
            @empty
                <a class="dropdown-item" href="#">
                    <i class="bi bi-app-indicator mr-2 text-danger"></i> No notifications available.
                </a>
            @endforelse
        </div>
    </li>
    @endcan

    <li class="c-header-nav-item dropdown">
        <a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button"
           aria-haspopup="true" aria-expanded="false">
            <div class="c-avatar mr-2">
                <img class="c-avatar rounded-circle" src="{{ auth()->user()->getFirstMediaUrl('avatars') }}" alt="Profile Image">
            </div>
            <div class="d-flex flex-column">
                <span class="font-weight-bold">{{ auth()->user()->name }}</span>
                <span class="font-italic">Online <i class="bi bi-circle-fill text-success" style="font-size: 11px;"></i></span>
            </div>
        </a>
        <div class="dropdown-menu dropdown-menu-right pt-0">
            <div class="dropdown-header bg-light py-2"><strong>Account</strong></div>
            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                <i class="mfe-2  bi bi-person" style="font-size: 1.2rem;"></i> Profile
            </a>
            <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="mfe-2  bi bi-box-arrow-left" style="font-size: 1.2rem;"></i> Logout
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </li>
</ul> --}}
