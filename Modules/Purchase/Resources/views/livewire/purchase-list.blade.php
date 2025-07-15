<div>
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="card-title">Purchase List</h3>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('purchases.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Purchase
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-2">
                    <input wire:model.debounce.300ms="search" type="text" class="form-control" placeholder="Search...">
                </div>
                <div class="col-md-2">
                    <input wire:model="dateFrom" type="date" class="form-control" placeholder="Date From">
                </div>
                <div class="col-md-2">
                    <input wire:model="dateTo" type="date" class="form-control" placeholder="Date To">
                </div>
                <div class="col-md-2">
                    <select wire:model="supplier_id" class="form-control">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model="payment_status" class="form-control">
                        <option value="">All Status</option>
                        <option value="Paid">Paid</option>
                        <option value="Partial">Partial</option>
                        <option value="Unpaid">Unpaid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select wire:model="perPage" class="form-control">
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Total</th>
                            <th>Paid Amount</th>
                            <th>Due Amount</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            <tr>
                                <td>{{ $purchase->reference_no }}</td>
                                <td>{{ $purchase->date->format('d/m/Y') }}</td>
                                <td>{{ $purchase->supplier->supplier_name }}</td>
                                <td>{{ number_format($purchase->total, 2) }}</td>
                                <td>{{ number_format($purchase->paid_amount, 2) }}</td>
                                <td>{{ number_format($purchase->due_amount, 2) }}</td>
                                <td>
                                    <span class="badge badge-{{ $purchase->payment_status === 'Paid' ? 'success' : ($purchase->payment_status === 'Partial' ? 'warning' : 'danger') }}">
                                        {{ $purchase->payment_status }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('purchases.show', $purchase->id) }}" class="btn btn-info btn-sm" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($purchase->payment_status !== 'Paid')
                                            <a href="{{ route('purchases.edit', $purchase->id) }}" class="btn btn-primary btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" wire:click="deletePurchase({{ $purchase->id }})" class="btn btn-danger btn-sm" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data pembelian.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-3">
                {{ $purchases->links() }}
            </div>
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

@push('scripts')
<script>
    document.addEventListener('livewire:init', function () {
        Livewire.on('deletePurchase', function(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data pembelian yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('confirmDelete', { id: id });
                }
            });
        });
    });
</script>
@endpush 