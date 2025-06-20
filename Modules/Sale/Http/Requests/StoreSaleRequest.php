<?php

namespace Modules\Sale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreSaleRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'customer_id' => 'required|numeric',
            'reference' => 'required|string|max:255',
            'tax_percentage' => 'nullable|integer|min:0|max:100',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'shipping_amount' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'paid_amount' => 'required|numeric',
            'status' => 'nullable|string|max:255',
            'payment_method' => 'required|string|max:255',
            'note' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('create_sales');
    }
}
