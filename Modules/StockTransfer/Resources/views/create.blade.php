@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Tambah Transfer Stok</h4>

    @php
        use Modules\Branch\Entities\Branch;
        $branch = Branch::find(session('branch_id'));
    @endphp

    <form action="{{ route('stock-transfers.store') }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-12 mb-3">
                <livewire:search-product />
            </div>
        </div>
        <div class="bg-white p-3 rounded shadow-sm">
        <div class="row mt-3">

            <div class="col-md-4">
                <div class="form-group">
                    <label for="source_branch_id" style="color: #212529;">Cabang Pengirim</label>
                    <select name="source_branch_id" id="from_branch_id" class="form-control" readonly>
                        @if($branch)
                            <option value="{{ $branch->id }}" selected>{{ $branch->name }}</option>
                        @else
                            <option value="" selected>Pilih Cabang</option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="destination_branch_id" style="color: #212529;">Cabang Penerima</label>
                    <select name="destination_branch_id" id="to_branch_id" class="form-control" required>
                        <option value="">Pilih Cabang</option>
                        @foreach(App\Models\Branch::where('id', '!=', 1)->get() as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="transfer_date" style="color: #212529;">Tanggal Transfer</label>
                    <input type="date" name="transfer_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>
        </div>

        {{-- Komponen pencarian produk --}}
        

        <div class="row mt-3">
            <div class="col-md-12">
                <livewire:stock-transfer.product-table />
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="note" style="color: #212529;">Catatan</label>
                    <textarea name="note" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="status" style="color: #212529;">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Simpan Transfer</button>
        </div>
        </div>
    </form>
</div>
@endsection

