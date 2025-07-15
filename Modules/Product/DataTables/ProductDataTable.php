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
            ->query($query)
            ->addColumn('action', function ($data) {
                return view('product::products.partials.actions', compact('data'));
            })
            ->addColumn('product_image', function ($data) {
                $url = Product::find($data->id)->getFirstMediaUrl('images');
                return '<img src="'.$url.'" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;" alt="Product Image">';

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
    // Subquery untuk total stock
    $stockSubquery = DB::table('product_batches')
        ->select('product_id', DB::raw('SUM(qty) as total_quantity'))
        ->groupBy('product_id');

    // Subquery untuk batch pertama berdasarkan exp_date (FIFO)
    $fifoSubquery = DB::table('product_batches as pb')
        ->select('pb.product_id', 'pb.price', 'pb.exp_date')
        ->whereRaw('pb.id = (
            SELECT id FROM product_batches 
            WHERE product_id = pb.product_id 
            AND qty > 0
            ORDER BY exp_date ASC, id ASC 
            LIMIT 1
        )');

    // Join dengan subqueries
    $products = DB::table('products')
        ->leftJoinSub($stockSubquery, 'stock', 'products.id', '=', 'stock.product_id')
        ->leftJoinSub($fifoSubquery, 'fifo', 'products.id', '=', 'fifo.product_id')
        ->select(
            'products.id',
            'products.product_name',
            'products.product_code',
            'products.product_unit',
            'products.category_id',
            DB::raw('COALESCE(stock.total_quantity, 0) as product_quantity'),
            DB::raw('fifo.price as fifo_price'),
            DB::raw('fifo.exp_date as fifo_exp_date')
        );

    return $products;
}


    // public function query(Product $model)
    // {
    //     $products = DB::table('products')
    //         ->leftJoinSub(
    //             DB::table('product_batches')
    //                 ->select('product_id', DB::raw('SUM(qty) as total_quantity'))
    //                 ->groupBy('product_id'),
    //             'pb',
    //             'products.id',
    //             '=',
    //             'pb.product_id'
    //         )
    //         ->select(
    //             'products.id',
    //             'products.product_name',
    //             'products.product_code',
    //             'products.product_unit',
    //             'products.category_id',
    //             DB::raw('COALESCE(pb.total_quantity, 0) as product_quantity')
    //         );

    //     return $products;
    // }

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
    
            Column::make('product_unit')
                ->title('Unit')
                ->className('text-center align-middle'),
    
            Column::make('total_quantity')
                ->title('Quantity')
                ->className('text-center align-middle'),
    
            Column::make('fifo_price')
                ->title('Batch Price')
                ->className('text-center align-middle'),
    
            Column::make('fifo_exp_date')
                ->title('Exp Date')
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

