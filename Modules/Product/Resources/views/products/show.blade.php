@extends('layouts.app')

@section('title', 'Product Details')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
        <li class="breadcrumb-item active">Details</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid mb-4">
        <div class="row">
            <!-- Informasi Produk -->
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Product Information</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <tr>
                                <th width="30%">Product Code</th>
                                <td>{{ $product->product_code }}</td>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <td>{{ $product->product_name }}</td>
                            </tr>
                            <tr>
                                <th>Category</th>
                                <td>{{ $product->category->category_name }}</td>
                            </tr>
                            <tr>
                                <th>Unit</th>
                                <td>{{ $product->product_unit }}</td>
                            </tr>
                            <tr>
                                <th>Stock Alert</th>
                                <td>{{ $product->product_stock_alert }}</td>
                            </tr>
                            <tr>
                                <th>Note</th>
                                <td>{{ $product->product_note ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Batch Details -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Batch Details (Qty > 0)</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm mb-0 text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>Batch Code</th>
                                    <th>Harga Beli</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Expiry Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($product->batches as $batch)
                                    <tr>
                                        <td>{{ $batch->batch_code ?? '-' }}</td>
                                        <td>Rp {{ number_format($batch->unit_price, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($batch->price, 0, ',', '.') }}</td>
                                        <td>{{ $batch->qty }}</td>
                                        <td>{{ $batch->exp_date ? \Carbon\Carbon::parse($batch->exp_date)->format('d M Y') : '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4">No available batches.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Gambar Produk -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Product Images</h5>
                        <div class="row">
                            @forelse($product->getMedia('images') as $media)
                                <div class="col-md-6 mb-2">
                                    <img src="{{ $media->getUrl() }}" alt="{{ $product->product_name }}" class="img-fluid rounded border shadow-sm" style="max-height: 120px; object-fit: cover;">
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-muted">No images uploaded.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
