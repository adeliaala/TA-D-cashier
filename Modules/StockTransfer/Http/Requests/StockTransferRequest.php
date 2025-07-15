<?php

namespace Modules\StockTransfer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProductBatch;
use Illuminate\Validation\Rule;
use Modules\StockTransfer\Entities\StockTransfer;
use Modules\Product\Entities\Product;

class StockTransferRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return auth()->check() 
    //         && auth()->user()->can('create', StockTransfer::class);
    // }

    public function rules()
    {
        return [
            'source_branch_id' => [
                'required',
                'exists:branches,id',
                'different:destination_branch_id',
                Rule::exists('branch_user', 'branch_id')->where('user_id', auth()->id())
            ],
            'destination_branch_id' => [
                'required',
                'exists:branches,id',
                'different:source_branch_id'
            ],
            'transfer_date' => [
                'required',
                'date',
                'after_or_equal:today'
            ],
            'note' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                'exists:products,id',
                function ($attribute, $value, $fail) {
                    if (!Product::where('id', $value)->where('is_active', true)->exists()) {
                        $fail('Selected product is not active');
                    }
                }
            ],
            'items.*.product_batch_id' => [
                'required',
                'exists:product_batches,id',
                function ($attribute, $value, $fail) {
                    $index = explode('.', $attribute)[1];
                    $sourceBranchId = $this->input('source_branch_id');
                    $productId = $this->input("items.{$index}.product_id");
                    $qty = $this->input("items.{$index}.qty");

                    $batch = ProductBatch::where('id', $value)
                        ->where('product_id', $productId)
                        ->where('branch_id', $sourceBranchId)
                        ->where('qty', '>', 0)
                        ->first();

                    if (!$batch) {
                        $fail('Selected batch is not available for transfer');
                        return;
                    }

                    if ($batch->qty < $qty) {
                        $fail("Insufficient stock (Available: {$batch->qty})");
                    }
                }
            ],
            'items.*.qty' => 'required|integer|min:1|max:10000'
        ];
    }

    public function messages()
    {
        return [
            'source_branch_id.required' => 'The source branch is required',
            'source_branch_id.exists' => 'The selected source branch is invalid',
            'source_branch_id.different' => 'Source and destination branches must be different',
            
            'destination_branch_id.required' => 'The destination branch is required',
            'destination_branch_id.exists' => 'The selected destination branch is invalid',
            
            'transfer_date.required' => 'The transfer date is required',
            'transfer_date.date' => 'Invalid date format',
            'transfer_date.after_or_equal' => 'Transfer date cannot be in the past',
            
            'items.required' => 'At least one item is required',
            'items.min' => 'At least one item is required',
            
            'items.*.product_id.required' => 'Product selection is required',
            'items.*.product_id.exists' => 'The selected product is invalid',
            
            'items.*.product_batch_id.required' => 'Batch selection is required',
            'items.*.product_batch_id.exists' => 'The selected batch is invalid',
            
            'items.*.qty.required' => 'Quantity is required',
            'items.*.qty.integer' => 'Quantity must be a whole number',
            'items.*.qty.min' => 'Quantity must be at least 1',
            'items.*.qty.max' => 'Quantity exceeds maximum allowed'
        ];
    }

    public function attributes()
    {
        return [
            'source_branch_id' => 'source branch',
            'destination_branch_id' => 'destination branch',
            'transfer_date' => 'transfer date',
            'items.*.product_id' => 'product',
            'items.*.product_batch_id' => 'batch',
            'items.*.qty' => 'quantity'
        ];
    }
}