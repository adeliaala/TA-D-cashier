<?php

namespace Modules\Product\DataTables;

use Modules\Product\Entities\Product;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class ProductDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('action', function ($data) {
                return view('product::products.partials.actions', compact('data'));
            })
            ->addColumn('product_image', function ($data) {
                $url = $data->getFirstMediaUrl('images');
                return '<img src="'.$url.'" class="product-img-thumb" alt="">';
            })
            ->addColumn('product_name', function ($data) {
                return '<a href="'.route('products.show', $data->id).'">'.$data->product_name.'</a>';
            })
            ->addColumn('total_quantity', function ($data) {
                return $data->product_quantity;
            })
            ->rawColumns(['product_image', 'product_name', 'action']);
    }

    public function query(Product $model)
    {
        return $model->newQuery()
            ->with('category')
            ->leftJoin(DB::raw('(SELECT product_id, SUM(quantity) as total_quantity FROM product_batches GROUP BY product_id) as pb'), 'products.id', '=', 'pb.product_id')
            ->select('products.*', DB::raw('COALESCE(pb.total_quantity, 0) as product_quantity'));
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('product-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom("<'row'<'col-md-3'l><'col-md-5 mb-2'B><'col-md-4'f>> .
                                'tr' .
                                <'row'<'col-md-5'i><'col-md-7 mt-2'p>>")
            ->orderBy(4)
            ->buttons(
                Button::make('excel')
                    ->text('<i class="bi bi-file-earmark-excel"></i> Excel'),
                Button::make('print')
                    ->text('<i class="bi bi-printer"></i> Print'),
                Button::make('reset')
                    ->text('<i class="bi bi-x-circle"></i> Reset'),
                Button::make('reload')
                    ->text('<i class="bi bi-arrow-repeat"></i> Reload')
            );
    }

    protected function getColumns()
    {
        return [
            Column::make('product_image')
                ->title('Image')
                ->className('text-center align-middle'),

            Column::make('product_name')
                ->title('Name')
                ->className('text-center align-middle'),

            Column::make('product_code')
                ->title('Code')
                ->className('text-center align-middle'),

            Column::make('category.category_name')
                ->title('Category')
                ->className('text-center align-middle'),

            Column::make('product_unit')
                ->title('Unit')
                ->className('text-center align-middle'),

            Column::make('total_quantity')
                ->title('Quantity')
                ->className('text-center align-middle'),

            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->className('text-center align-middle')
                ->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename(): string
    {
        return 'Product_' . date('YmdHis');
    }
}

