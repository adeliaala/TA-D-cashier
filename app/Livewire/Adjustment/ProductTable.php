<?php

namespace App\Livewire\Adjustment;

use Illuminate\Support\Collection;
use Livewire\Component;
use Modules\Product\Entities\Product;

class ProductTable extends Component
{

    protected $listeners = ['productSelected'];

    public $products;
    public $hasAdjustments;

    public function mount($adjustedProducts = null) {
        $this->products = [];

        if ($adjustedProducts) {
            $this->hasAdjustments = true;
            $this->products = $adjustedProducts;
        } else {
            $this->hasAdjustments = false;
        }
    }

    public function render() {
        return view('livewire.adjustment.product-table');
    }

    public function productSelected($product) {
        $branch_id = session('branch_id'); // pastikan session branch_id sudah ada
        $productModel = Product::with(['batches' => function($q) use ($branch_id) {
            $q->where('branch_id', $branch_id)->where('qty', '>', 0);
        }])->find($product['id']);
    
        if (!$productModel) {
            return session()->flash('message', 'Produk tidak ditemukan!');
        }
    
        // Cek duplikasi
        foreach ($this->products as $p) {
            if ($p['id'] == $productModel->id) {
                return session()->flash('message', 'Already exists in the product list!');
            }
        }
    
        $productArr = $productModel->toArray();
        $productArr['batches'] = $productModel->batches->toArray();
    
        $this->products[] = $productArr;
    }

    public function removeProduct($key) {
        unset($this->products[$key]);
    }
}
