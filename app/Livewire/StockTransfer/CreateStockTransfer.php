<?php

namespace App\Livewire\StockTransfer;

use Livewire\Component;
use App\Models\ProductBatch;
use Modules\StockTransfer\Entities\StockTransfer;
use Modules\StockTransfer\Entities\StockTransferItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateStockTransfer extends Component
{
    public $source_branch_id;
    public $destination_branch_id;
    public $transfer_date;
    public $note;
    public $status = 'pending';
    public $products = []; // format: [['batch_id' => 1, 'qty' => 5], ...]

    public function render()
    {
        return view('StockTransfer::create');
    }

    public function handleSubmit()
    {
        $this->validate([
            'source_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id|different:source_branch_id',
            'transfer_date' => 'required|date',
            'products' => 'required|array|min:1',
            'products.*.batch_id' => 'required|exists:product_batches,id',
            'products.*.qty' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Buat transfer baru
            $transfer = StockTransfer::create([
                'reference_no' => 'TRF-' . strtoupper(Str::random(6)),
                'source_branch_id' => $this->source_branch_id,
                'destination_branch_id' => $this->destination_branch_id,
                'transfer_date' => $this->transfer_date,
                'note' => $this->note,
                'status' => $this->status,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            foreach ($this->products as $product) {
                $newBatch = ProductBatch::transferToBranch(
                    $product['batch_id'],
                    $this->destination_branch_id,
                    $product['qty'],
                    auth()->id()
                );

                // Simpan item transfer
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $newBatch->product_id,
                    'product_batch_id' => $newBatch->id,
                    'quantity' => $product['qty'],
                ]);
            }

            DB::commit();

            session()->flash('success', 'Transfer stok berhasil disimpan.');
            return redirect()->route('stock-transfers.index');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal menyimpan transfer: ' . $e->getMessage());
        }
    }
}
