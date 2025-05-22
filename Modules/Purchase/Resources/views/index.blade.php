@extends('layouts.app')

@section('title', 'Purchases')

@section('third_party_stylesheets')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item active">Purchases</li>
    </ol>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Purchases</h3>
            <div class="card-tools">
                <a href="{{ route('purchases.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Purchase
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Supplier ID</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $purchases = DB::table('purchases')->get();
                        @endphp
                        
                        @forelse($purchases as $purchase)
                            <tr>
                                <td>{{ $purchase->id }}</td>
                                <td>{{ $purchase->date }}</td>
                                <td>{{ $purchase->reference_no }}</td>
                                <td>{{ $purchase->supplier_id }}</td>
                                <td>{{ $purchase->total / 100 }}</td>
                                <td>{{ $purchase->paid_amount / 100 }}</td>
                                <td>{{ $purchase->due_amount / 100 }}</td>
                                <td>{{ $purchase->payment_status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No purchases found in database.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Debug Info -->
    {{-- <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">Debug Info</h3>
        </div>
        <div class="card-body">
            <p>Active Branch: {{ session('active_branch') ?? 'Not set' }}</p>
            <p>Database Connection: {{ config('database.default') }}</p>
            <p>Purchases Table Exists: {{ Schema::hasTable('purchases') ? 'Yes' : 'No' }}</p>
            <p>Purchases Count: {{ DB::table('purchases')->count() }}</p>
            <div id="ajax-response-debug"></div>
        </div>
    </div> --}}
@endsection

@push('scripts')
    {{ $dataTable->scripts() }}
    <script>
        $(document).ready(function() {
            console.log('DataTable scripts loaded');
            
            // Log any DataTable errors
            $.fn.dataTable.ext.errMode = function(settings, helpPage, message) {
                console.error('DataTable Error:', message);
                $('#ajax-response-debug').html('<div class="alert alert-danger">DataTable Error: ' + message + '</div>');
            };
            
            // Check if DataTable is initialized
            if ($.fn.dataTable.isDataTable('#purchases-table')) {
                console.log('DataTable initialized successfully');
            } else {
                console.log('DataTable not initialized');
                $('#ajax-response-debug').html('<div class="alert alert-warning">DataTable not initialized</div>');
            }
            
            // Intercept AJAX requests
            $(document).ajaxSend(function(event, jqxhr, settings) {
                console.log('AJAX Request:', settings.url);
            });
            
            // Monitor AJAX responses
            $(document).ajaxComplete(function(event, jqxhr, settings) {
                console.log('AJAX Response:', jqxhr.status, jqxhr.responseJSON);
                
                // Display the response in the debug area
                if (settings.url.includes('purchases-table')) {
                    var responseData = jqxhr.responseJSON;
                    var debugInfo = '<h5>AJAX Response:</h5>';
                    debugInfo += '<p>Status: ' + jqxhr.status + '</p>';
                    
                    if (responseData) {
                        debugInfo += '<p>Records Total: ' + responseData.recordsTotal + '</p>';
                        debugInfo += '<p>Records Filtered: ' + responseData.recordsFiltered + '</p>';
                        debugInfo += '<p>Data Count: ' + (responseData.data ? responseData.data.length : 0) + '</p>';
                    } else {
                        debugInfo += '<p class="text-danger">No response data</p>';
                    }
                    
                    $('#ajax-response-debug').html(debugInfo);
                }
            });
            
            // Monitor AJAX errors
            $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
                console.error('AJAX Error:', thrownError, jqxhr.responseText);
                $('#ajax-response-debug').html('<div class="alert alert-danger">AJAX Error: ' + thrownError + '</div><pre>' + jqxhr.responseText + '</pre>');
            });
            
            // Manual check for purchases data
            $.ajax({
                url: '/purchases?draw=1&columns[0][data]=date&columns[0][name]=date&columns[0][searchable]=true&columns[0][orderable]=true&columns[0][search][value]=&columns[0][search][regex]=false&columns[1][data]=reference_no&columns[1][name]=reference_no&columns[1][searchable]=true&columns[1][orderable]=true&columns[1][search][value]=&columns[1][search][regex]=false&columns[2][data]=supplier_name&columns[2][name]=supplier_name&columns[2][searchable]=true&columns[2][orderable]=true&columns[2][search][value]=&columns[2][search][regex]=false&columns[3][data]=payment_status&columns[3][name]=payment_status&columns[3][searchable]=true&columns[3][orderable]=true&columns[3][search][value]=&columns[3][search][regex]=false&columns[4][data]=total&columns[4][name]=total&columns[4][searchable]=true&columns[4][orderable]=true&columns[4][search][value]=&columns[4][search][regex]=false&columns[5][data]=paid_amount&columns[5][name]=paid_amount&columns[5][searchable]=true&columns[5][orderable]=true&columns[5][search][value]=&columns[5][search][regex]=false&columns[6][data]=due_amount&columns[6][name]=due_amount&columns[6][searchable]=true&columns[6][orderable]=true&columns[6][search][value]=&columns[6][search][regex]=false&columns[7][data]=action&columns[7][name]=action&columns[7][searchable]=false&columns[7][orderable]=false&columns[7][search][value]=&columns[7][search][regex]=false&order[0][column]=0&order[0][dir]=asc&start=0&length=10&search[value]=&search[regex]=false&_=1716306781000',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Manual AJAX check:', response);
                    
                    var manualCheckInfo = '<h5>Manual AJAX Check:</h5>';
                    if (response) {
                        manualCheckInfo += '<p>Records Total: ' + response.recordsTotal + '</p>';
                        manualCheckInfo += '<p>Records Filtered: ' + response.recordsFiltered + '</p>';
                        manualCheckInfo += '<p>Data Count: ' + (response.data ? response.data.length : 0) + '</p>';
                        
                        if (response.data && response.data.length > 0) {
                            manualCheckInfo += '<p>First Record: ' + JSON.stringify(response.data[0]) + '</p>';
                        } else {
                            manualCheckInfo += '<p class="text-warning">No records found</p>';
                        }
                    } else {
                        manualCheckInfo += '<p class="text-danger">No response data</p>';
                    }
                    
                    $('#ajax-response-debug').append(manualCheckInfo);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Manual AJAX check error:', textStatus, errorThrown);
                    $('#ajax-response-debug').append('<div class="alert alert-danger">Manual AJAX Error: ' + errorThrown + '</div>');
                }
            });
        });
    </script>
@endpush
