<div>
    {{-- Alert Message --}}
    @if (session()->has('message'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="alert-body">
                <span>{{ session('message') }}</span>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Loading Overlay --}}
    <div wire:loading.flex class="col-12 position-absolute justify-content-center align-items-center"
         style="top:0;right:0;left:0;bottom:0;background-color: rgba(255,255,255,0.5);z-index: 99;">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>

    {{-- Product Table --}}
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr class="align-middle text-center">
                    <th>#</th>
                    <th>Nama Produk</th>
                    <th>Kode</th>
                    <th>Batch (Cabang Pengirim)</th>
                    <th>Stok Batch</th>
                    <th>Jumlah Transfer</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @if (!empty($products))
                    @foreach ($products as $key => $product)
                        @php
                            $productData = $product['product'] ?? $product;
                            $batches = $product['batches'] ?? [];
                            $selectedBatchId = $product['selected_batch_id'] ?? null;
                            $selectedBatch = collect($batches)->firstWhere('id', $selectedBatchId) ?? ($batches[0] ?? null);
                        @endphp
                        <tr>
                            <td class="align-middle text-center">{{ $key + 1 }}</td>

                            {{-- Nama Produk --}}
                            <td class="align-middle">{{ $productData['product_name'] }}</td>

                            {{-- Kode Produk --}}
                            <td class="align-middle">{{ $productData['product_code'] }}</td>

                            {{-- Pilih Batch --}}
                            <td class="align-middle">
                                <select name="product_batch_ids[]" class="form-control" required
                                    wire:change="updateBatch({{ $key }}, $event.target.value)">
                                    <option value="">Pilih Batch</option>
                                    @foreach ($batches as $batch)
                                        <option value="{{ $batch['id'] }}" {{ $selectedBatchId == $batch['id'] ? 'selected' : '' }}>
                                            {{ $batch['batch_code'] ?? '-' }} | Qty: {{ $batch['qty'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            {{-- Stok Batch --}}
                            <td class="align-middle text-center">
                                <span class="badge badge-info">
                                    {{ $selectedBatch['qty'] ?? '0' }} {{ $productData['product_unit'] ?? '' }}
                                </span>
                            </td>

                            {{-- Input Jumlah Transfer --}}
                            <td class="align-middle">
                                <input type="number" name="quantities[]" min="1"
                                       max="{{ $selectedBatch['qty'] ?? 0 }}"
                                       class="form-control"
                                       value="{{ $product['quantity'] ?? 1 }}"
                                       wire:change="updateQuantity({{ $key }}, $event.target.value)"
                                       {{ empty($selectedBatch) ? 'disabled' : '' }}>
                            </td>

                            {{-- Hidden Product ID --}}
                            <input type="hidden" name="product_ids[]" value="{{ $productData['id'] }}">

                            {{-- Aksi --}}
                            <td class="align-middle text-center">
                                <button type="button" class="btn btn-danger" wire:click="removeProduct({{ $key }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="text-center">
                            <span class="text-danger">Silakan cari & pilih produk!</span>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

