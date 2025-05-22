@push('scripts')
<script>
    document.addEventListener('livewire:init', function () {
        // Success Message
        Livewire.on('showSuccessMessage', function(data) {
            console.log('Success message received:', data);
            Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed && data.redirect) {
                    window.location.href = data.redirect;
                }
            });
        });

        // Error Message
        Livewire.on('showErrorMessage', function(data) {
            console.log('Error message received:', data);
            Swal.fire({
                title: 'Error!',
                text: data.message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        });

        // Confirmation Dialog
        Livewire.on('showConfirmDialog', function(data) {
            console.log('Confirmation dialog received:', data);
            Swal.fire({
                title: data.title,
                text: data.text,
                icon: data.icon,
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: data.confirmButtonText,
                cancelButtonText: data.cancelButtonText
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('confirmed');
                }
            });
        });
        
        // Debug Event
        Livewire.on('debug', function(data) {
            console.log('DEBUG EVENT:', data);
        });
    });
    
    // Debug form submission
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[wire\\:submit\\.prevent="save"]');
        if (form) {
            console.log('Form found, adding submit event listener');
            form.addEventListener('submit', function(e) {
                console.log('Form submit event triggered');
            });
        } else {
            console.error('Form not found!');
        }
    });
</script>
@endpush

<div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create Purchase</h3>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="save">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Reference No</label>
                            <input type="text" class="form-control" wire:model="reference_no" readonly>
                            @error('reference_no') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Supplier</label>
                            <select class="form-control" wire:model="supplier_id">
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                                @endforeach
                            </select>
                            @error('supplier_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" class="form-control" wire:model="date">
                            @error('date') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select class="form-control" wire:model="payment_method">
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash</option>
                                <option value="transfer">Transfer</option>
                                <option value="credit">Credit</option>
                            </select>
                            @error('payment_method') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Discount Percentage</label>
                            <input type="number" class="form-control" wire:model="discount_percentage" min="0" max="100" step="0.01">
                            @error('discount_percentage') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Discount Amount</label>
                            <input type="number" class="form-control" wire:model="discount" min="0" step="0.01">
                            @error('discount') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>Products</h4>
                            <button type="button" class="btn btn-primary" wire:click="addItem">
                                Add Product
                            </button>
                        </div>

                        @error('items') <span class="text-danger">{{ $message }}</span> @enderror

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Purchase Price</th>
                                        <th>Sell Price</th>
                                        <th>Discount</th>
                                        <th>Tax</th>
                                        <th>Subtotal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $index => $item)
                                        <tr>
                                            <td>
                                                <select class="form-control" wire:model="items.{{ $index }}.product_id">
                                                    <option value="">Select Product</option>
                                                    @foreach($products as $product)
                                                        <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                                                    @endforeach
                                                </select>
                                                @error("items.{$index}.product_id") <span class="text-danger">{{ $message }}</span> @enderror
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" wire:model="items.{{ $index }}.qty" min="1">
                                                @error("items.{$index}.qty") <span class="text-danger">{{ $message }}</span> @enderror
                                            </td>
                                            {{-- saya ganti jadi price --}}
                                            <td>
                                                <input type="number" class="form-control" wire:model="items.{{ $index }}.unit_price" min="0" step="0.01">
                                                @error("items.{$index}.unit_price") <span class="text-danger">{{ $message }}</span> @enderror
                                            </td>
                                            {{-- bagian ini saya ganti ke unit_price --}}
                                            <td>
                                                <input type="number" class="form-control" wire:model="items.{{ $index }}.price" min="0" step="0.01">
                                                @error("items.{$index}.price") <span class="text-danger">{{ $message }}</span> @enderror
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="number" class="form-control" wire:model="items.{{ $index }}.discount" min="0" step="0.01">
                                                    <select class="form-control" wire:model="items.{{ $index }}.discount_type">
                                                        <option value="">Type</option>
                                                        <option value="percentage">%</option>
                                                        <option value="fixed">Fixed</option>
                                                    </select>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" wire:model="items.{{ $index }}.tax" min="0" step="0.01">
                                            </td>
                                            <td>
                                                {{ number_format($item['qty'] * $item['unit_price'], 2) }}
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm" wire:click="removeItem({{ $index }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6" class="text-right"><strong>Total:</strong></td>
                                        <td colspan="2">
                                            <strong>{{ number_format($total_amount, 2) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="6" class="text-right"><strong>Discount:</strong></td>
                                        <td colspan="2">
                                            <strong>{{ number_format($discount, 2) }}</strong>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="6" class="text-right"><strong>Total After Discount:</strong></td>
                                        <td colspan="2">
                                            <strong>{{ number_format($total_after_discount, 2) }}</strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Paid Amount</label>
                            <input type="number" class="form-control" wire:model="paid_amount" min="0" step="0.01">
                            @error('paid_amount') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Due Amount</label>
                            <input type="text" class="form-control" value="{{ number_format($due_amount, 2) }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Payment Status</label>
                            <input type="text" class="form-control" value="{{ $paid_amount >= $total_after_discount ? 'Paid' : 'Due' }}" readonly>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Note</label>
                            <textarea class="form-control" wire:model="note" rows="3"></textarea>
                            @error('note') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Create Purchase</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div> 