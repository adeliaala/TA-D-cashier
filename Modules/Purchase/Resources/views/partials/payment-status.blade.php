@if (strtolower($payment_status) == 'partial')
    <span class="badge badge-warning">
        Partial
    </span>
@elseif (strtolower($payment_status) == 'paid')
    <span class="badge badge-success">
        Paid
    </span>
@else
    <span class="badge badge-danger">
        {{ $payment_status ?? 'Unpaid' }}
    </span>
@endif
