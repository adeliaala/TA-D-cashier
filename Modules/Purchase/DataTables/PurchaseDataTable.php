<?php

namespace Modules\Purchase\DataTables;

use Modules\Purchase\Entities\Purchase;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PurchaseDataTable extends DataTable
{
    public function dataTable($query)
    {
        Log::info('PurchaseDataTable::dataTable called', [
            'query_type' => get_class($query)
        ]);
        
        // Gunakan query builder DB langsung untuk memastikan data diambil
        $purchases = DB::table('purchases')
            ->select([
                'purchases.id',
                'purchases.date',
                'purchases.reference_no',
                'purchases.supplier_id',
                'purchases.total',
                'purchases.paid_amount',
                'purchases.due_amount',
                'purchases.payment_status',
                'suppliers.supplier_name as supplier_name'
            ])
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->get();
        
        Log::info('Direct DB Query Results', [
            'count' => $purchases->count(),
            'data' => $purchases->take(3)->toArray()
        ]);
        
        return datatables()
            ->of($purchases)
            ->addColumn('action', function ($data) {
                return view('purchase::partials.actions', [
                    'id' => $data->id
                ]);
            })
            ->editColumn('total', function ($data) {
                return format_currency($data->total / 100);
            })
            ->editColumn('paid_amount', function ($data) {
                return format_currency($data->paid_amount / 100);
            })
            ->editColumn('due_amount', function ($data) {
                return format_currency($data->due_amount / 100);
            })
            ->editColumn('payment_status', function ($data) {
                return view('purchase::partials.payment-status', [
                    'payment_status' => $data->payment_status
                ]);
            })
            ->rawColumns(['action', 'payment_status']);
    }

    public function query(Purchase $model)
    {
        $branch_id = session('branch_id');
        
        $query = $model->newQuery()
            ->when($branch_id, function($q) use ($branch_id) {
                return $q->where('branch_id', $branch_id);
            });
        
        return $query;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('purchases-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom("<'row'<'col-md-3'l><'col-md-5 mb-2'B><'col-md-4'f>> .
                                'tr' .
                                <'row'<'col-md-5'i><'col-md-7 mt-2'p>>")
            ->orderBy(0)
            ->buttons(
                Button::make('excel')
                    ->text('<i class="bi bi-file-earmark-excel-fill"></i> Excel'),
                Button::make('print')
                    ->text('<i class="bi bi-printer-fill"></i> Print'),
                Button::make('reset')
                    ->text('<i class="bi bi-x-circle"></i> Reset'),
                Button::make('reload')
                    ->text('<i class="bi bi-arrow-repeat"></i> Reload')
            );
    }

    protected function getColumns()
    {
        return [
            Column::make('date')
                ->title('Date')
                ->className('text-center align-middle'),

            Column::make('reference_no')
                ->title('Reference')
                ->className('text-center align-middle'),

            Column::make('supplier_name')
                ->title('Supplier')
                ->className('text-center align-middle'),

            Column::make('payment_status')
                ->title('Payment Status')
                ->className('text-center align-middle'),

            Column::make('total')
                ->title('Total')
                ->className('text-center align-middle'),

            Column::make('paid_amount')
                ->title('Paid')
                ->className('text-center align-middle'),

            Column::make('due_amount')
                ->title('Due')
                ->className('text-center align-middle'),

            Column::computed('action')
                ->title('Action')
                ->exportable(false)
                ->printable(false)
                ->className('text-center align-middle')
                ->addClass('text-center'),
        ];
    }

    protected function filename(): string {
        return 'Purchase_' . date('YmdHis');
    }
}
