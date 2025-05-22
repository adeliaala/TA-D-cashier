<?php

namespace Modules\StockTransfer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProductBatch;

class StockTransferRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'source_branch_id' => [
                'required',
                'exists:branches,id',
                'different:destination_branch_id'
            ],
            'destination_branch_id' => [
                'required',
                'exists:branches,id',
                'different:source_branch_id'
            ],
            'transfer_date' => 'required|date',
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_batch_id' => [
                'required',
                'exists:product_batches,id',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $sourceBranchId = $this->input('source_branch_id');
                    $productId = $this->input("items.{$index}.product_id");
                    $quantity = $this->input("items.{$index}.quantity");

                    // Check if batch belongs to source branch
                    $batch = ProductBatch::where('id', $value)
                        ->where('product_id', $productId)
                        ->where('branch_id', $sourceBranchId)
                        ->first();

                    if (!$batch) {
                        $fail('Selected batch does not belong to source branch.');
                        return;
                    }

                    // Check if quantity is available
                    if ($batch->quantity < $quantity) {
                        $fail("Insufficient quantity in batch. Available: {$batch->quantity}");
                    }
                }
            ],
            'items.*.quantity' => 'required|integer|min:1'
        ];
    }

    public function messages()
    {
        return [
            'source_branch_id.required' => 'Source branch is required',
            'source_branch_id.exists' => 'Selected source branch is invalid',
            'source_branch_id.different' => 'Source branch must be different from destination branch',
            'destination_branch_id.required' => 'Destination branch is required',
            'destination_branch_id.exists' => 'Selected destination branch is invalid',
            'destination_branch_id.different' => 'Destination branch must be different from source branch',
            'transfer_date.required' => 'Transfer date is required',
            'transfer_date.date' => 'Invalid transfer date format',
            'items.required' => 'At least one product is required',
            'items.min' => 'At least one product is required',
            'items.*.product_id.required' => 'Product is required',
            'items.*.product_id.exists' => 'Selected product is invalid',
            'items.*.product_batch_id.required' => 'Product batch is required',
            'items.*.product_batch_id.exists' => 'Selected product batch is invalid',
            'items.*.quantity.required' => 'Quantity is required',
            'items.*.quantity.integer' => 'Quantity must be a whole number',
            'items.*.quantity.min' => 'Quantity must be at least 1'
        ];
    }
} 