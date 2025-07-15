@extends('layouts.app')

@section('title', 'Dasbor')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item active">Dasbor</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        {{-- Filter Pendapatan --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form wire:submit.prevent="filterData">
                            <div class="row d-flex justify-content-between align-items-end">
                                <div class="col">
                                    <div class="form-group">
                                        <label for="filterBulan">Bulan</label>
                                        <select wire:model="filterBulan" class="form-control" id="filterBulan" name="filterBulan">
                                            <option value="">Pilih Bulan</option>
                                            <option value="01">Januari</option>
                                            <option value="02">Februari</option>
                                            <option value="03">Maret</option>
                                            <option value="04">April</option>
                                            <option value="05">Mei</option>
                                            <option value="06">Juni</option>
                                            <option value="07">Juli</option>
                                            <option value="08">Agustus</option>
                                            <option value="09">September</option>
                                            <option value="10">Oktober</option>
                                            <option value="11">November</option>
                                            <option value="12">Desember</option>
                                        </select>
                                        @error('filterBulan')
                                        <span class="text-danger mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label for="filterTahun">Tahun</label>
                                        <select wire:model="filterTahun" class="form-control" id="filterTahun" name="filterTahun">
                                            <option value="">Pilih Tahun</option>
                                            @for ($year = date('Y'); $year >= 2025; $year--)
                                                <option value="{{ $year }}">{{ $year }}</option>
                                            @endfor
                                        </select>
                                        @error('filterTahun')
                                        <span class="text-danger mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col d-flex align-items-end">
                                    <div class="form-group w-100">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <span wire:target="filterData" wire:loading class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            <i wire:loading.remove wire:target="filterData" class="bi bi-funnel-fill"></i>
                                            Filter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Statistik Pendapatan --}}
        <div class="row mb-4">
            <div class="col">
                <div class="card border-0">
                    <div class="card-body p-0 d-flex align-items-center shadow-sm">
                        <div class="bg-gradient-primary p-4 mfe-3 rounded-left">
                            <i class="bi bi-calendar-month font-2xl"></i>
                        </div>
                        <div>
                            <div class="text-value text-primary">{{ format_currency($monthlyRevenue) }}</div>
                            <div class="text-muted text-uppercase font-weight-bold small">Pendapatan Bulan Ini</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card border-0">
                    <div class="card-body p-0 d-flex align-items-center shadow-sm">
                        <div class="bg-gradient-success p-4 mfe-3 rounded-left">
                            <i class="bi bi-cash-coin font-2xl"></i>
                        </div>
                        <div>
                            <div class="text-value text-success">{{ format_currency($todayRevenue) }}</div>
                            <div class="text-muted text-uppercase font-weight-bold small">Pendapatan Hari Ini</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card border-0">
                    <div class="card-body p-0 d-flex align-items-center shadow-sm">
                        <div class="bg-gradient-info p-4 mfe-3 rounded-left">
                            <i class="bi bi-people font-2xl"></i>
                        </div>
                        <div>
                            <div class="text-value text-info">{{ $todayCustomers }}</div>
                            <div class="text-muted text-uppercase font-weight-bold small">Pembeli Hari Ini</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col">
                <div class="card border-0">
                    <div class="card-body p-0 d-flex align-items-center shadow-sm">
                        <div class="bg-gradient-warning p-4 mfe-3 rounded-left">
                            <i class="bi bi-people font-2xl"></i>
                        </div>
                        <div>
                            <div class="text-value text-warning">{{ $monthCustomers }}</div>
                            <div class="text-muted text-uppercase font-weight-bold small">Pembeli Bulan Ini</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Chart --}}
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Grafik Pendapatan Per Bulan</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyRevenueChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Penjualan per Kategori Produk</h5>
                    </div>
                    <div class="card-body d-flex justify-content-center">
                        <div class="chart-container" style="width:280px">
                            <canvas id="categorySalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Produk Kadaluarsa --}}
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Produk Mendekati Kadaluarsa</h5>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Kode Batch</th>
                                    <th>Stok</th>
                                    <th>Tgl. Exp.</th>
                                    <th>Cabang</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($expiringBatches as $batch)
                                @if($batch->qty >= 1)
                                    <tr>
                                        <td>{{ $batch->product->product_name ?? '-' }}</td>
                                        <td>{{ $batch->batch_code }}</td>
                                        <td>{{ $batch->qty}}</td>
                                        <td>{{ \Carbon\Carbon::parse($batch->exp_date)->translatedFormat('d M Y') }}</td>
                                        <td>{{ $batch->branch->name ?? '-' }}</td>
                                        <td>
                                            <form action="{{ route('adjustments.quick') }}" method="POST" onsubmit="return confirm('Kurangi stok batch ini?')">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $batch->product_id }}">
                                                <input type="hidden" name="product_batch_id" value="{{ $batch->id }}">
                                                <input type="hidden" name="quantity" value="{{ $batch->qty }}">
                                                <input type="hidden" name="type" value="sub">
                                                <input type="hidden" name="note" value="Auto Adjustment dari Dasbor">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Kurangi stok semua batch ini">
                                                    <i class="bi bi-box-arrow-down"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endif
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada produk yang mendekati kadaluarsa.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('third_party_scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.5.0/chart.min.js"
        integrity="sha512-asxKqQghC1oBShyhiBwA+YgotaSYKxGP1rcSYTDrB0U6DxwlJjU59B67U8+5/++uFjcuVM8Hh5cokLjZlhm3Vg=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
@endsection

@push('page_scripts')
    <script>
        const dailyRevenueData = @json($dailyRevenueChart);
        const categorySalesData = @json($categorySalesChart);

        new Chart(document.getElementById('dailyRevenueChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: dailyRevenueData.labels,
                datasets: [{
                    label: 'Pendapatan Harian',
                    data: dailyRevenueData.data,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('categorySalesChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: categorySalesData.labels,
                datasets: [{
                    data: categorySalesData.data,
                    backgroundColor: ['#FF6384','#36A2EB','#FFCE56','#4BC0C0','#9966FF','#FF9F40']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>
@endpush
