<div class="btn-group">
    <a href="{{ route('purchases.show', $id) }}" class="btn btn-info btn-sm">
        <i class="fas fa-eye"></i>
    </a>
    <a href="{{ route('purchases.edit', $id) }}" class="btn btn-primary btn-sm">
        <i class="fas fa-edit"></i>
    </a>
    <a href="{{ route('purchases.pdf', $id) }}" class="btn btn-secondary btn-sm" target="_blank">
        <i class="fas fa-print"></i>
    </a>
    <button type="button" class="btn btn-danger btn-sm" onclick="deletePurchase({{ $id }})">
        <i class="fas fa-trash"></i>
    </button>
</div>

<script>
    function deletePurchase(id) {
        if (confirm('Are you sure you want to delete this purchase?')) {
            $.ajax({
                url: `/purchases/${id}`,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('#purchases-table').DataTable().ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(error) {
                    toastr.error('An error occurred while deleting the purchase.');
                }
            });
        }
    }
</script>
