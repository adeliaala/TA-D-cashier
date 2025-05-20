<?php

namespace Modules\Purchase\DataTables;

use Modules\Purchase\Entities\Purchase;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class PurchaseDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('action', function ($data) {
                return view('purchase::partials.actions', [
                    'id' => $data->id
                ]);
            })
            ->editColumn('total_amount', function ($data) {
                return format_currency($data->total_amount);
            })
            ->editColumn('paid_amount', function ($data) {
                return format_currency($data->paid_amount);
            })
            ->editColumn('due_amount', function ($data) {
                return format_currency($data->due_amount);
            })
            ->editColumn('status', function ($data) {
                return view('purchase::partials.status', [
                    'status' => $data->status
                ]);
            })
            ->editColumn('payment_status', function ($data) {
                return view('purchase::partials.payment-status', [
                    'payment_status' => $data->payment_status
                ]);
            });
    }

    public function query(Purchase $model)
    {
        return $model->newQuery();
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
                    ->text('<i class="bi bi-file-earmark-excel-fill"></i> Excel')
                    ->className('btn btn-success btn-sm no-corner'),
                Button::make('print')
                    ->text('<i class="bi bi-printer-fill"></i> Print')
                    ->className('btn btn-primary btn-sm no-corner'),
                Button::make('reset')
                    ->text('<i class="bi bi-x-circle"></i> Reset')
                    ->className('btn btn-warning btn-sm no-corner'),
                Button::make('reload')
                    ->text('<i class="bi bi-arrow-repeat"></i> Reload')
                    ->className('btn btn-info btn-sm no-corner')
            );
    }

    protected function getColumns()
    {
        return [
            Column::make('date')
                ->title('Date')
                ->className('text-center align-middle'),

            Column::make('reference')
                ->title('Reference')
                ->className('text-center align-middle'),

            Column::make('supplier_name')
                ->title('Supplier')
                ->className('text-center align-middle'),

            Column::make('status')
                ->title('Status')
                ->className('text-center align-middle'),

            Column::make('payment_status')
                ->title('Payment Status')
                ->className('text-center align-middle'),

            Column::make('total_amount')
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
