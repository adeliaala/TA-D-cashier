<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>POS Invoice - {{ $sale->reference }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            margin: 0;
            size: 80mm auto;
        }
        
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            width: 80mm;
            margin: 0;
            padding: 0;
        }
        
        .page-wrapper {
            width: 100%;
            padding: 8mm;
        }
        
        .max-w-sm {
            max-width: 100%;
            margin: 0 auto;
        }
        
        .space-y-8 > * + * {
            margin-top: 2rem;
        }
        
        .text-center {
            text-align: center;
        }
        
        .font-bold {
            font-weight: bold;
        }
        
        .text-xl {
            font-size: 1.25rem;
            line-height: 1.75rem;
        }
        
        .w-full {
            width: 100%;
        }
        
        .w-full td {
            padding: 2px 0;
            vertical-align: top;
        }
        
        .w-full td:first-child {
            width: 40%;
        }
        
        .w-full td:nth-child(2) {
            width: 5%;
            text-align: center;
        }
        
        .space-y-2 > * + * {
            margin-top: 0.5rem;
        }
        
        .flex {
            display: flex;
        }
        
        .flex-col {
            flex-direction: column;
        }
        
        .justify-between {
            justify-content: space-between;
        }
        
        small {
            font-size: 0.875rem;
            color: #666;
        }
        
        .item-name {
            font-weight: 500;
            margin-bottom: 4px;
        }
        
        .item-details {
            font-size: 0.9rem;
            color: #555;
        }
        
        .total-section {
            border-top: 1px solid #000;
            padding-top: 8px;
            margin-top: 1rem;
        }
        
        @media print {
            body {
                width: 80mm;
            }
            .page-wrapper {
                padding: 5mm;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="max-w-sm space-y-8">
            <!-- Header Store Info -->
            <div class="text-center">
                <h3 class="font-bold text-xl">{{ $sale->branch->name ?? config('app.name') }}</h3>
                <p>{{ $sale->branch->address ?? 'Alamat Toko' }}</p>
                @if($sale->branch && $sale->branch->phone)
                <p>Telp: {{ $sale->branch->phone }}</p>
                @endif
            </div>

            <!-- Transaction Info -->
            <div>
                <table class="w-full">
                    <tr>
                        <td>Kode transaksi</td>
                        <td>:</td>
                        <td>{{ $sale->reference }}</td>
                    </tr>
                    <tr>
                        <td>Tanggal</td>
                        <td>:</td>
                        <td>{{ \Carbon\Carbon::parse($sale->created_at)->format('d F Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td>Customer</td>
                        <td>:</td>
                        <td>{{ $sale->customer_name ?? 'Walk In Customer' }}</td>
                    </tr>
                    <tr>
                        <td>Kasir</td>
                        <td>:</td>
                        <td>{{ Auth::user()->name ?? 'Admin' }}</td>
                    </tr>
                    <tr>
                        <td>Payment</td>
                        <td>:</td>
                        <td>{{ $sale->payment_method }}</td>
                    </tr>
                </table>
            </div>
            <div> &nbsp; </div>

            <!-- Items -->
            <div class="space-y-2">
                @forelse($sale->saleDetails as $item)
                <div class="flex flex-col">
                    <div class="item-name">{{ $item->product_name }}</div>
                    <div class="flex justify-between item-details">
                        <div>{{ number_format($item->unit_price, 0, ',', '.') }} x {{ $item->quantity }}</div>
                        <div>{{ number_format($item->sub_total, 0, ',', '.') }}</div>
                    </div>
                    {{-- @if($item->product_code)
                    <div style="font-size: 0.8rem; color: #888;">{{ $item->product_code }}</div>
                    @endif
                    @if($item->product_discount_amount > 0)
                    <div class="flex justify-between" style="font-size: 0.8rem; color: #666;">
                        <div>Diskon {{ $item->product_discount_type == 'percentage' ? $item->product_discount_amount.'%' : 'Rp '.number_format($item->product_discount_amount, 0, ',', '.') }}</div>
                        <div>-{{ number_format($item->product_discount_amount, 0, ',', '.') }}</div>
                    </div>
                    @endif --}}
                </div>
                @empty
                <div class="flex flex-col">
                    <div class="item-name">Tidak ada item</div>
                </div>
                @endforelse
            </div>

            <!-- Totals -->
            <div class="total-section">
                @php
                    $subtotal = $sale->total_amount + $sale->discount_amount;
                @endphp
                
                @if($sale->discount_amount > 0)
                <div class="flex justify-between" style="margin-bottom: 4px;">
                    <div>Subtotal</div>
                    <div>Rp {{ number_format($subtotal, 2, ',', '.') }}</div>
                </div>
                <div class="flex justify-between" style="margin-bottom: 8px;">
                    <div>Diskon @if($sale->discount_percentage > 0)({{ $sale->discount_percentage }}%)@endif</div>
                    <div>-Rp {{ number_format($sale->discount_amount, 2, ',', '.') }}</div>
                </div>
                @endif
                
                <small>Total bayar</small>
                <div class="text-xl font-bold">Rp {{ number_format($sale->total_amount, 2, ',', '.') }}</div>
                
                <div class="flex justify-between" style="margin-top: 8px;">
                    <div>Bayar</div>
                    <div> &nbsp;</div>
                    <div> Rp {{ number_format($sale->paid_amount, 2, ',', '.') }}</div>
                </div>
                
                @if($sale->due_amount != 0)
                <div class="flex justify-between">
                    <div>{{ $sale->due_amount > 0 ? 'Kembali' : 'Kurang' }}</div>
                    <div> Rp {{ number_format(abs($sale->due_amount), 2, ',', '.') }}</div>
                </div>
                @endif
            </div>
            <div></div>

            <!-- Footer -->
            <div class="text-center" style="font-size: 0.8rem; color: #666; border-top: 1px dashed #000; padding-top: 8px;">
                <div style="margin-bottom: 4px; font-weight: bold; color: #000;">{{ $sale->reference }}</div>
                <div style="margin-bottom: 2px;">Terima kasih atas kunjungan Anda</div>
                <div>Barang yang sudah dibeli </div>
                <div>tidak dapat dikembalikan</div>
            </div>
            <div> &nbsp;</div>
        </div>
    </div>
</body>
</html>