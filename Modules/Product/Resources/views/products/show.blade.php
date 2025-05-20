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
            <div class="col-lg-9">
                <div class="card h-100">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <tr>
                                <th>Product Code</th>
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
                                <td>{{ $product->product_note }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Product Images</h5>
                        <div class="row">
                            @foreach($product->getMedia('images') as $media)
                                <div class="col-md-6 mb-2">
                                    <img src="{{ $media->getUrl() }}" alt="{{ $product->product_name }}" class="img-fluid">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection



