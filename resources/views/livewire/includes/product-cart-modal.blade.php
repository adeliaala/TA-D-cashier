<div class="d-inline-block">
    <!-- Button trigger Discount Modal -->
    <span wire:click="$dispatch('discountModalRefresh', { product_id: {{ $cart_item->id }}, row_id: '{{ $cart_item->rowId }}' })" role="button" class="badge badge-warning pointer-event" data-toggle="modal" data-target="#discountModal{{ $cart_item->id }}">
        <i class="bi bi-pencil-square text-white"></i>
    </span>
    <!-- Discount Modal -->
    <div wire:ignore.self class="modal fade" id="discountModal{{ $cart_item->id }}" tabindex="-1" role="dialog" aria-labelledby="discountModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="discountModalLabel">
                        {{ $cart_item->name }}
                        <br>
                        <span class="badge badge-success">
                        {{ $cart_item->options->code }}
                    </span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if (session()->has('discount_message' . $cart_item->id))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="alert-body">
                                <span>{{ session('discount_message' . $cart_item->id) }}</span>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                        </div>
                    @endif
                    <div class="form-group">
                        <label>Jenis Diskon <span class="text-danger">*</span></label>
                        <select wire:model.live="discount_type.{{ $cart_item->id }}" class="form-control" required>
                            <option value="fixed">Nominal</option>
                            <option value="percentage">Persentase</option>
                        </select>
                    </div>
                    <div class="form-group">
                        @if($discount_type[$cart_item->id] == 'percentage')
                            <label>Diskon(%) <span class="text-danger">*</span></label>
                            <input wire:model="item_discount.{{ $cart_item->id }}" type="number" class="form-control" value="{{ $item_discount[$cart_item->id] }}" min="0" max="100">
                        @elseif($discount_type[$cart_item->id] == 'fixed')
                            <label>Diskon <span class="text-danger">*</span></label>
                            <input wire:model="item_discount.{{ $cart_item->id }}" type="number" class="form-control" value="{{ $item_discount[$cart_item->id] }}">
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button wire:click="setProductDiscount('{{ $cart_item->rowId }}', {{ $cart_item->id }})" type="button" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </div>
        </div>
    </div>
</div>
