<?php

namespace App\Livewire\Barcode;

use Livewire\Component;
use Milon\Barcode\Facades\DNS1DFacade;
use App\Models\ProductBatch;

class ProductTable extends Component
{
    public $productBatchId;
    public $quantity = 1;
    public $barcodes = [];
    public $product = null;
    public $selectedBatchId = null;
    public $batches = [];
    public $maxQuantity = 0;

    protected $listeners = ['productSelected'];

    public function mount()
    {
        $this->product = null;
        $this->batches = [];
        $this->selectedBatchId = null;
        $this->maxQuantity = 0;
    }

    public function productSelected($product)
    {
        $this->product = $product;
        $this->batches = ProductBatch::where('product_id', $product['id'])
            ->where('qty', '>', 0)
            ->get();
        $this->selectedBatchId = $this->batches->first()?->id;
        $this->updateMaxQuantity();
    }

    public function updatedSelectedBatchId()
    {
        $this->updateMaxQuantity();
        if ($this->quantity > $this->maxQuantity) {
            $this->quantity = $this->maxQuantity;
        }
    }

    protected function updateMaxQuantity()
    {
        if ($this->selectedBatchId) {
            $batch = $this->batches->firstWhere('id', $this->selectedBatchId);
            $this->maxQuantity = $batch ? $batch->qty : 0;
        } else {
            $this->maxQuantity = 0;
        }
    }

    public function generateBarcodes($productBatchId, $quantity)
    {
        if ($quantity > $this->maxQuantity) {
            session()->flash('message', 'Quantity exceeds available stock!');
            return;
        }

        $batch = ProductBatch::with('product')->findOrFail($productBatchId);

        $this->barcodes = [];

        for ($i = 0; $i < $quantity; $i++) {
            $barcode = DNS1DFacade::getBarcodeHTML($batch->product->product_code, 'C128');

            $this->barcodes[] = [
                'name' => $batch->product->product_name,
                'barcode' => $barcode,
                'price' => $batch->price,
            ];
        }
    }
    
    public function getPdf()
    {
        // Logika PDF generation di sini jika diperlukan
    }

    public function render()
    {
        return view('livewire.barcode.product-table');
    }
}
