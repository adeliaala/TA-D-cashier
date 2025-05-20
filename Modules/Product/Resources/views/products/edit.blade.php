@extends('layouts.app')

@section('title', 'Edit Product')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid mb-4">
        <form id="product-form" action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('patch')
            <div class="row">
                <div class="col-lg-12">
                    @include('utils.alerts')
                    <div class="form-group">
                        <button class="btn btn-primary">Update Product <i class="bi bi-check"></i></button>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="form-row">
                                <div class="col-md-7">
                                    <div class="form-group">
                                        <label for="product_name">Product Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="product_name" required value="{{ $product->product_name }}">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="product_code">Code <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="product_code" required value="{{ $product->product_code }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_id">Category <span class="text-danger">*</span></label>
                                        <select class="form-control" name="category_id" id="category_id" required>
                                            @foreach(\Modules\Product\Entities\Category::all() as $category)
                                                <option {{ $category->id == $product->category->id ? 'selected' : '' }} value="{{ $category->id }}">{{ $category->category_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="product_unit">Unit <i class="bi bi-question-circle-fill text-info" data-toggle="tooltip" data-placement="top" title="This short text will be placed after Product Quantity."></i> <span class="text-danger">*</span></label>
                                        <select class="form-control" name="product_unit" id="product_unit" required>
                                            <option value="" selected >Select Unit</option>
                                            @foreach(\Modules\Setting\Entities\Unit::all() as $unit)
                                                <option {{ $product->product_unit == $unit->short_name ? 'selected' : '' }} value="{{ $unit->short_name }}">{{ $unit->name . ' | ' . $unit->short_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="product_stock_alert">Stock Alert <i class="bi bi-question-circle-fill text-info" data-toggle="tooltip" data-placement="top" title="Minimum stock level to trigger alert"></i></label>
                                        <input type="number" class="form-control" name="product_stock_alert" value="{{ old('product_stock_alert', $product->product_stock_alert) }}" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="product_note">Note</label>
                                <textarea name="product_note" id="product_note" rows="4 " class="form-control">{{ $product->product_note }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="image">Product Images <i class="bi bi-question-circle-fill text-info" data-toggle="tooltip" data-placement="top" title="Max Files: 3, Max File Size: 1MB, Image Size: 400x400"></i></label>
                                <div class="dropzone d-flex flex-wrap align-items-center justify-content-center" id="document-dropzone">
                                    <div class="dz-message" data-dz-message>
                                        <i class="bi bi-cloud-arrow-up"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('third_party_scripts')
    <script src="{{ asset('js/dropzone.js') }}"></script>
@endsection

@push('page_scripts')
    <script>
        var uploadedDocumentMap = {}
        Dropzone.options.documentDropzone = {
            url: "{{ route('dropzone.upload') }}",
            maxFilesize: 1,
            acceptedFiles: ".jpeg,.jpg,.png,.gif",
            maxFiles: 3,
            addRemoveLinks: true,
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            success: function (file, response) {
                $('form').append('<input type="hidden" name="document[]" value="' + response.name + '">')
                uploadedDocumentMap[file.name] = response.name
            },
            removedfile: function (file) {
                file.previewElement.remove()
                var name = ''
                if (typeof file.file_name !== 'undefined') {
                    name = file.file_name
                } else {
                    name = uploadedDocumentMap[file.name]
                }
                $('form').find('input[name="document[]"][value="' + name + '"]').remove()
            },
            init: function () {
                @if(isset($product) && $product->getMedia('images'))
                var files = {!! json_encode($product->getMedia('images')) !!};
                for (var i in files) {
                    var file = files[i];
                    this.options.addedfile.call(this, file);
                    this.options.thumbnail.call(this, file, file.original_url);
                    file.previewElement.classList.add('dz-complete');
                    $('form').append('<input type="hidden" name="document[]" value="' + file.file_name + '">');
                }
                @endif
            }
        }
    </script>
@endpush

