<?php

namespace App\Livewire\StockTransfer;

use Livewire\Component;
use Modules\Product\Entities\Product;
use App\Models\ProductBatch;


class ProductTable extends Component
{
    protected $listeners = ['productSelected'];

    public $products = [];
    public $fromBranchId;

    public function mount($fromBranchId = null)
    {
        $this->fromBranchId = $fromBranchId;
        $this->products = [];
    }

    public function render()
    {
        return view('livewire.stock-transfer.product-table');
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

    public function updateBatch($key, $batchId)
    {
        $product = $this->products[$key]['product'] ?? $this->products[$key];

        $batch = ProductBatch::where('id', $batchId)
            ->where('branch_id', $this->fromBranchId)
            ->first();
        if ($batch) {
            $this->products[$key]['selected_batch_id'] = $batch->id;
            $this->products[$key]['max_qty'] = $batch->qty;
            // Reset quantity jika melebihi stok
            if ($this->products[$key]['quantity'] > $batch->qty) {
                $this->products[$key]['quantity'] = $batch->qty;
            }
        }
    }

    public function updateQuantity($key, $qty)
    {
        $max = $this->products[$key]['max_qty'] ?? 0;
        $this->products[$key]['quantity'] = min(max(1, (int)$qty), $max);
    }

    public function removeProduct($key)
    {
        unset($this->products[$key]);
        $this->products = array_values($this->products); // reindex
    }
}
