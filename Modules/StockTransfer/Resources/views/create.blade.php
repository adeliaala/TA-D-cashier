@extends('layouts.app')

@section('title', 'Create Stock Transfer')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Stock Transfer</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('stock-transfers.store') }}" method="POST" id="stock-transfer-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="source_branch_id">Source Branch <span class="text-danger">*</span></label>
                                    <select name="source_branch_id" id="source_branch_id" class="form-control select2" required>
                                        <option value="">Select Source Branch</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="destination_branch_id">Destination Branch <span class="text-danger">*</span></label>
                                    <select name="destination_branch_id" id="destination_branch_id" class="form-control select2" required>
                                        <option value="">Select Destination Branch</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transfer_date">Transfer Date <span class="text-danger">*</span></label>
                                    <input type="date" name="transfer_date" id="transfer_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="note">Note</label>
                                    <textarea name="note" id="note" class="form-control" rows="1"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="transfer-items-table">
                                        <thead>
                                            <tr>
                                                <th>Product <span class="text-danger">*</span></th>
                                                <th>Batch <span class="text-danger">*</span></th>
                                                <th>Available Quantity</th>
                                                <th>Transfer Quantity <span class="text-danger">*</span></th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <select name="items[0][product_id]" class="form-control select2 product-select" required>
                                                        <option value="">Select Product</option>
                                                        @foreach($products as $product)
                                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="items[0][product_batch_id]" class="form-control select2 batch-select" required disabled>
                                                        <option value="">Select Batch</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <span class="available-quantity">0</span>
                                                </td>
                                                <td>
                                                    <input type="number" name="items[0][quantity]" class="form-control transfer-quantity" min="1" required disabled>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger btn-sm remove-row">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" class="btn btn-success" id="add-row">
                                    <i class="bi bi-plus"></i> Add Item
                                </button>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Create Transfer</button>
                                <a href="{{ route('stock-transfers.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let rowCount = 1;

    // Initialize Select2
    $('.select2').select2();

    // Add new row
    $('#add-row').click(function() {
        const newRow = `
            <tr>
                <td>
                    <select name="items[${rowCount}][product_id]" class="form-control select2 product-select" required>
                        <option value="">Select Product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <select name="items[${rowCount}][product_batch_id]" class="form-control select2 batch-select" required disabled>
                        <option value="">Select Batch</option>
                    </select>
                </td>
                <td>
                    <span class="available-quantity">0</span>
                </td>
                <td>
                    <input type="number" name="items[${rowCount}][quantity]" class="form-control transfer-quantity" min="1" required disabled>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-row">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#transfer-items-table tbody').append(newRow);
        initializeRow(rowCount);
        rowCount++;
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
    });

    // Initialize row elements
    function initializeRow(index) {
        $(`select[name="items[${index}][product_id]"]`).select2();
        $(`select[name="items[${index}][product_batch_id]"]`).select2();
    }

    // Handle product selection
    $(document).on('change', '.product-select', function() {
        const row = $(this).closest('tr');
        const productId = $(this).val();
        const batchSelect = row.find('.batch-select');
        const quantityInput = row.find('.transfer-quantity');
        const availableQuantity = row.find('.available-quantity');

        if (productId) {
            const sourceBranchId = $('#source_branch_id').val();
            if (!sourceBranchId) {
                toastr.error('Please select source branch first');
                $(this).val('').trigger('change');
                return;
            }

            // Fetch batches for the selected product and branch
            $.get(`/stock-transfers/batches/${productId}/${sourceBranchId}`, function(data) {
                batchSelect.empty().append('<option value="">Select Batch</option>');
                data.forEach(batch => {
                    batchSelect.append(`<option value="${batch.id}" data-quantity="${batch.quantity}">${batch.batch_number}</option>`);
                });
                batchSelect.prop('disabled', false).trigger('change');
            });
        } else {
            batchSelect.empty().prop('disabled', true).trigger('change');
            quantityInput.prop('disabled', true).val('');
            availableQuantity.text('0');
        }
    });

    // Handle batch selection
    $(document).on('change', '.batch-select', function() {
        const row = $(this).closest('tr');
        const quantityInput = row.find('.transfer-quantity');
        const availableQuantity = row.find('.available-quantity');
        const selectedOption = $(this).find('option:selected');

        if (selectedOption.val()) {
            const quantity = selectedOption.data('quantity');
            availableQuantity.text(quantity);
            quantityInput.prop('disabled', false).attr('max', quantity);
        } else {
            quantityInput.prop('disabled', true).val('');
            availableQuantity.text('0');
        }
    });

    // Form submission
    $('#stock-transfer-form').submit(function(e) {
        e.preventDefault();
        
        // Validate source and destination branches are different
        const sourceBranch = $('#source_branch_id').val();
        const destinationBranch = $('#destination_branch_id').val();
        
        if (sourceBranch === destinationBranch) {
            toastr.error('Source and destination branches cannot be the same');
            return;
        }

        // Validate quantities
        let isValid = true;
        $('.transfer-quantity').each(function() {
            const quantity = parseInt($(this).val());
            const max = parseInt($(this).attr('max'));
            if (quantity > max) {
                toastr.error('Transfer quantity cannot exceed available quantity');
                isValid = false;
                return false;
            }
        });

        if (isValid) {
            this.submit();
        }
    });
});
</script>
@endpush 