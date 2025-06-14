<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'product_code' => ['required', 'string', 'max:255', 'unique:products,product_code,' . $this->product->id],
            'product_barcode_symbology' => ['nullable', 'string', 'max:255'],
            'product_unit' => ['required', 'string', 'max:255'],
            'product_quantity' => ['nullable', 'integer', 'min:0'],
            'product_cost' => ['nullable', 'numeric', 'max:2147483647'],
            'product_price' => ['nullable', 'numeric', 'max:2147483647'],
            'min_quantity_for_wholesale' => ['nullable', 'integer', 'min:0'],
            'wholesale_discount_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'product_stock_alert' => ['nullable', 'integer', 'min:0'],
            'product_order_tax' => ['nullable', 'integer', 'min:0', 'max:100'],
            'product_tax_type' => ['nullable', 'integer'],
            'product_note' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['required', 'integer']
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('edit_products');
    }
}
