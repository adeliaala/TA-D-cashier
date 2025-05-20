<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
{
    return [
        'product_name' => 'required|string|max:255',
        'product_code' => 'required|string|max:255|unique:products,product_code',
        'category_id' => 'nullable|exists:categories,id',
        'product_unit' => 'nullable|string|max:50',
        'barcode_symbology' => 'nullable|string', // masih ada? kalau tidak, hapus juga
        'product_stock_alert' => 'nullable|numeric', // kalau sudah dihapus dari DB, hapus rule-nya juga
        'product_note' => 'nullable|string',
    ];
}



    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Gate::allows('create_products');
    }
}
