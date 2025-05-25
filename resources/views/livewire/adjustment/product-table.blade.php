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
                    <th>Product Name</th>
                    <th>Code</th>
                    <th>Batch</th>
                    <th>Stock</th>
                    <th>Quantity</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @if (!empty($products))
                    @foreach ($products as $key => $product)
                        @php
                            $productData = $product['product'] ?? $product;
                            $batches = $product['batches'] ?? [];
                        @endphp
                        <tr>
                            <td class="align-middle text-center">{{ $key + 1 }}</td>

                            {{-- Product Name --}}
                            <td class="align-middle">{{ $productData['product_name'] }}</td>

                            {{-- Product Code --}}
                            <td class="align-middle">{{ $productData['product_code'] }}</td>

                            {{-- Batch Select --}}
                            <td class="align-middle">
                                <select name="product_batch_ids[]" class="form-control" required>
                                    <option value="">Pilih Batch</option>
                                    @foreach ($batches as $batch)
                                        <option value="{{ $batch['id'] }}">
                                            {{ $batch['batch_code'] ?? '-' }} | Qty: {{ $batch['qty'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            {{-- Stock (dari batch, default ambil batch pertama jika ada) --}}
                            <td class="align-middle text-center">
                                @php
                                    $firstBatch = $batches[0] ?? null;
                                @endphp
                                <span class="badge badge-info">
                                    {{ $firstBatch['qty'] ?? '0' }} {{ $productData['product_unit'] }}
                                </span>
                            </td>

                            {{-- Quantity Input --}}
                            <td class="align-middle">
                                <input type="number" name="quantities[]" min="1" class="form-control"
                                       value="{{ $product['quantity'] ?? 1 }}">
                            </td>

                            {{-- Type Select --}}
                            <td class="align-middle">
                                <select name="types[]" class="form-control">
                                    <option value="add" {{ ($product['type'] ?? '') === 'add' ? 'selected' : '' }}>(+) Addition</option>
                                    <option value="sub" {{ ($product['type'] ?? '') === 'sub' ? 'selected' : '' }}>(-) Subtraction</option>
                                </select>
                            </td>

                            {{-- Hidden Product ID --}}
                            <input type="hidden" name="product_ids[]" value="{{ $productData['id'] }}">

                            {{-- Action Button --}}
                            <td class="align-middle text-center">
                                <button type="button" class="btn btn-danger" wire:click="removeProduct({{ $key }})">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="8" class="text-center">
                            <span class="text-danger">Please search & select products!</span>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
