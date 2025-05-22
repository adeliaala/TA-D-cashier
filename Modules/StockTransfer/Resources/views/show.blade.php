@extends('layouts.app')

@section('title', 'Stock Transfer Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Stock Transfer Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 200px;">Reference No</th>
                                    <td>{{ $stockTransfer->reference_no }}</td>
                                </tr>
                                <tr>
                                    <th>Source Branch</th>
                                    <td>{{ $stockTransfer->sourceBranch->name }}</td>
                                </tr>
                                <tr>
                                    <th>Destination Branch</th>
                                    <td>{{ $stockTransfer->destinationBranch->name }}</td>
                                </tr>
                                <tr>
                                    <th>Transfer Date</th>
                                    <td>{{ $stockTransfer->transfer_date->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge badge-{{ $stockTransfer->status === 'completed' ? 'success' : ($stockTransfer->status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($stockTransfer->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Note</th>
                                    <td>{{ $stockTransfer->note ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <h4>Transfer Items</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Batch</th>
                                            <th>Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($stockTransfer->items as $item)
                                            <tr>
                                                <td>{{ $item->product->name }}</td>
                                                <td>{{ $item->productBatch->batch_number }}</td>
                                                <td>{{ $item->quantity }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 