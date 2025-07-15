<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Purchase Details</title>
    <link rel="stylesheet" href="{{ public_path('b3/bootstrap.min.css') }}">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-xs-12">
            <div style="text-align: center;margin-bottom: 25px;">
                <img width="180" src="{{ public_path('images/logo-dark.png') }}" alt="Logo">
                <h4 style="margin-bottom: 20px;">
                    <span>Reference:</span> <strong>{{ $purchase->reference }}</strong>
                </h4>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-xs-4 mb-3 mb-md-0">
                            <h4 class="mb-2" style="border-bottom: 1px solid #dddddd;padding-bottom: 10px;">Informasi Perusahaan:</h4>
                            <div style="margin-bottom: 5px;"><strong>{{ settings()->company_name }}</strong></div>
                            <div style="margin-bottom: 5px;">{{ settings()->company_address }}</div>
                            <div style="margin-bottom: 5px;">Email: {{ settings()->company_email }}</div>
                            <div style="margin-bottom: 5px;">Phone: {{ settings()->company_phone }}</div>
                        </div>

                        <div class="col-xs-4 mb-3 mb-md-0">
                            <h4 class="mb-2" style="border-bottom: 1px solid #dddddd;padding-bottom: 10px;">Informasi Supplier:</h4>
                            <div style="margin-bottom: 5px;"><strong>{{ $supplier->supplier_name }}</strong></div>
                            <div style="margin-bottom: 5px;">{{ $supplier->address }}</div>
                            <div style="margin-bottom: 5px;">Email: {{ $supplier->supplier_email }}</div>
                            <div style="margin-bottom: 5px;">Phone: {{ $supplier->supplier_phone }}</div>
                        </div>

                        <div class="col-xs-4 mb-3 mb-md-0">
                            <h4 class="mb-2" style="border-bottom: 1px solid #dddddd;padding-bottom: 10px;">Informasi Invoice:</h4>
                            <div style="margin-bottom: 5px;">Invoice: <strong>INV/{{ $purchase->reference }}</strong></div>
                            <div style="margin-bottom: 5px;">Tanggal: {{ \Carbon\Carbon::parse($purchase->date)->format('d M, Y') }}</div>
                            <div style="margin-bottom: 5px;">
                                Status: <strong>{{ $purchase->status }}</strong>
                            </div>
                            <div style="margin-bottom: 5px;">
                                Status Pembayaran: <strong>{{ $purchase->payment_status }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive-sm" style="margin-top: 30px;">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th class="align-middle" style="width: 40%;">Produk</th>
                                <th class="align-middle" style="width: 20%;">Harga Satuan</th>
                                <th class="align-middle" style="width: 20%;">Jumlah</th>
                                <th class="align-middle" style="width: 20%;">Subtotal</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($purchase->purchaseDetails as $item)
                                <tr>
                                    <td class="align-middle">
                                        {{ $item->product_name }} <br>
                                        <span class="badge badge-success">
                                            {{ $item->product_code }}
                                        </span>
                                    </td>

                                    <td class="align-middle">{{ format_currency($item->unit_price * 100) }}</td>

                                    <td class="align-middle">
                                        {{ $item->qty }}
                                    </td>

                                    <td class="align-middle">
                                        {{ format_currency($item->subtotal * 100) }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="row">
                        <div class="col-xs-4 col-xs-offset-8">
                            <table class="table">
                                <tbody>
                                <tr>
                                    <td class="left"><strong>Total</strong></td>
                                    <td class="right"><strong>{{ format_currency($purchase->total * 100) }}</strong></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 25px;">
                        <div class="col-xs-12">
                            <p style="font-style: italic;text-align: center">{{ settings()->company_name }} &copy; {{ date('Y') }}.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
