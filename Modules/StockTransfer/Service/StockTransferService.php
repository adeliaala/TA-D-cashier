// StockTransferService.php
public function createStockTransfer(array $data)
{
    return DB::transaction(function () use ($data) {
        // 1. Buat record stock transfer
        $stockTransfer = StockTransfer::create([
            'from_branch_id' => $data['from_branch_id'],
            'to_branch_id' => $data['to_branch_id'],
            'transfer_date' => $data['transfer_date'],
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);
        
        // 2. Proses setiap item yang ditransfer
        foreach ($data['items'] as $item) {
            // 2a. Kurangi stok di cabang asal
            $sourceBatch = ProductBatch::find($item['product_batch_id']);
            
            if ($sourceBatch->qty < $item['qty']) {
                throw new \Exception('Insufficient stock');
            }
            
            $sourceBatch->decrement('qty', $item['qty']);
            
            // 2b. Buat batch baru di cabang tujuan
            $newBatch = ProductBatch::create([
                'product_id' => $sourceBatch->product_id,
                'branch_id' => $data['to_branch_id'],
                'qty' => $item['qty'],
                'purchase_id' => null, // Karena ini transfer, bukan pembelian
                'stock_transfer_id' => $stockTransfer->id, // Jika kolom ini ada
                'expiry_date' => $sourceBatch->expiry_date,
                'manufacture_date' => $sourceBatch->manufacture_date,
                // ... field lainnya sesuai kebutuhan
            ]);
            
            // 2c. Catat detail transfer
            StockTransferItem::create([
                'stock_transfer_id' => $stockTransfer->id,
                'product_id' => $sourceBatch->product_id,
                'product_batch_id' => $sourceBatch->id,
                'qty' => $item['qty'],
                'destination_batch_id' => $newBatch->id,
            ]);
        }
        
        return $stockTransfer;
    });
}