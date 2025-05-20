@extends('layouts.app')

@section('title', 'Purchases')

@section('third_party_stylesheets')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item active">Purchases</li>
    </ol>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Purchases</h3>
            <div class="card-tools">
                <a href="{{ route('purchases.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Purchase
                </a>
            </div>
        </div>
        <div class="card-body">
            {{ $dataTable->table() }}
        </div>
    </div>
@endsection

@push('scripts')
    {{ $dataTable->scripts() }}
@endpush
