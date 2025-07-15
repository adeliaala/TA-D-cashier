<div class="btn-group">
    <a href="{{ route('purchases.show', $id) }}" class="btn btn-info btn-sm" data-toggle="tooltip" title="Lihat Detail">
        <i class="bi bi-eye"></i>
    </a>
    <a href="{{ route('purchases.edit', $id) }}" class="btn btn-primary btn-sm" data-toggle="tooltip" title="Edit">
        <i class="bi bi-pencil"></i>
    </a>
    <a href="{{ route('purchases.pdf', $id) }}" class="btn btn-secondary btn-sm" target="_blank" data-toggle="tooltip" title="Cetak PDF">
        <i class="bi bi-file-earmark-pdf"></i>
    </a>
    <button type="button" class="btn btn-danger btn-sm" onclick="deletePurchase({{ $id }})" data-toggle="tooltip" title="Hapus">
        <i class="bi bi-trash"></i>
    </button>
</div>

<script>
    function deletePurchase(id) {
        if (confirm('Apakah Anda yakin ingin menghapus pembelian ini?')) {
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
                    toastr.error('Terjadi kesalahan saat menghapus pembelian.');
                }
            });
        }
    }

    // Inisialisasi tooltip
    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
