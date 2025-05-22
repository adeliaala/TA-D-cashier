@extends('layouts.app')

@section('title', 'Stock Transfers')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Stock Transfers</h3>
                    <div class="card-tools">
                        @can('create_stock_transfers')
                            <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus"></i> Create Transfer
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Reference No</th>
                                    <th>Source Branch</th>
                                    <th>Destination Branch</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockTransfers as $transfer)
                                    <tr>
                                        <td>{{ $transfer->reference_no }}</td>
                                        <td>{{ $transfer->sourceBranch->name }}</td>
                                        <td>{{ $transfer->destinationBranch->name }}</td>
                                        <td>{{ $transfer->transfer_date->format('d M Y') }}</td>
                                        <td>
                                            <span class="badge badge-{{ $transfer->status === 'completed' ? 'success' : ($transfer->status === 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($transfer->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('stock-transfers.show', $transfer) }}" class="btn btn-info btn-sm">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No stock transfers found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $stockTransfers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
