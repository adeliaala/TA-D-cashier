<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Product;

class ProductBatchRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
        ];

        // Get product category
        $product = Product::find($this->input('product_id'));
        
        // If product is in make-up category, require batch_code and expired_date
        if ($product && $product->category === 'make up') {
            $rules['batch_code'] = 'required|string';
            $rules['expired_date'] = 'required|date|after:today';
        } else {
            $rules['batch_code'] = 'nullable|string';
            $rules['expired_date'] = 'nullable|date|after:today';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'batch_code.required' => 'Batch code is required for make-up products',
            'expired_date.required' => 'Expiry date is required for make-up products',
            'expired_date.after' => 'Expiry date must be a future date',
        ];
    }
} 