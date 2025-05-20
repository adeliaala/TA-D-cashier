<div>
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="card-title">Purchase Details</h3>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('purchases.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    @if($purchase->payment_status !== 'Paid')
                        <a href="{{ route('purchases.edit', $purchase->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Purchase Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Purchase Information</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>Reference No</th>
                            <td>{{ $purchase->reference_no }}</td>
                        </tr>
                        <tr>
                            <th>Date</th>
                            <td>{{ $purchase->date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <th>Supplier</th>
                            <td>{{ $purchase->supplier->name }}</td>
                        </tr>
                        <tr>
                            <th>Created By</th>
                            <td>{{ $purchase->created_by }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Payment Information</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>Total Amount</th>
                            <td>{{ number_format($purchase->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Paid Amount</th>
                            <td>{{ number_format($purchase->paid_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Due Amount</th>
                            <td>{{ $purchase->due_amount ? number_format($purchase->due_amount, 2) : '0.00' }}</td>
                        </tr>
                        <tr>
                            <th>Payment Status</th>
                            <td>
                                <span class="badge badge-{{ $purchase->payment_status === 'Paid' ? 'success' : ($purchase->payment_status === 'Partial' ? 'warning' : 'danger') }}">
                                    {{ $purchase->payment_status }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Purchase Details -->
            <h5>Purchase Details</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchase->purchaseDetails as $detail)
                            <tr>
                                <td>{{ $detail->product_name }}</td>
                                <td>{{ $detail->quantity }}</td>
                                <td>{{ number_format($detail->price, 2) }}</td>
                                <td>{{ number_format($detail->sub_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Payment History -->
            <h5 class="mt-4">Payment History</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Note</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchase->purchasePayments as $payment)
                            <tr>
                                <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->payment_method }}</td>
                                <td>{{ $payment->note }}</td>
                                <td>{{ $payment->created_by }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No payments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Add Payment Form -->
            @if($purchase->payment_status !== 'Paid')
                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Add Payment</h5>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="addPayment">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Amount</label>
                                        <input type="number" wire:model="payment_amount" class="form-control" step="0.01" min="0" max="{{ $purchase->due_amount }}">
                                        @error('payment_amount') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Payment Method</label>
                                        <select wire:model="payment_method" class="form-control">
                                            <option value="Cash">Cash</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Credit Card">Credit Card</option>
                                        </select>
                                        @error('payment_method') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Note</label>
                                        <input type="text" wire:model="payment_note" class="form-control">
                                        @error('payment_note') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            Add Payment
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
            {{ session('message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
</div> 