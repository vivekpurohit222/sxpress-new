@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Driver Expense Report</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('accounting.reports.index') }}">Financial Reports</a></li>
            <li class="active">Driver Expenses</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <strong>Driver Expense Report</strong>
            <small class="text-muted ms-2">{{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</small>
        </div>
        <button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()">
            <i class="fa fa-print"></i> Print
        </button>
    </div>

    <div class="card-body">
        @include('accounting.reports._filters', ['fromDate' => $fromDate, 'toDate' => $toDate])

        @include('accounting.reports._print-header', [
            'reportTitle' => 'Driver Expense Report',
            'reportPeriod' => \Carbon\Carbon::parse($fromDate)->format('d M Y') . ' to ' . \Carbon\Carbon::parse($toDate)->format('d M Y')
        ])

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>Driver Name</th>
                        <th>Truck No</th>
                        <th class="text-end">Diesel (₹)</th>
                        <th class="text-end">Salary (₹)</th>
                        <th class="text-end">Repair (₹)</th>
                        <th class="text-end">Tyre (₹)</th>
                        <th class="text-end">Misc (₹)</th>
                        <th class="text-end">Total Expenses (₹)</th>
                        <th class="text-end">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drivers as $driver)
                        <tr>
                            <td>{{ $driver->driver_name }}</td>
                            <td>{{ $driver->truck_no }}</td>
                            <td class="text-end">{{ indianNumberFormat($driver->diesel) }}</td>
                            <td class="text-end">{{ indianNumberFormat($driver->driver_salary) }}</td>
                            <td class="text-end">{{ indianNumberFormat($driver->repair) }}</td>
                            <td class="text-end">{{ indianNumberFormat($driver->tyre) }}</td>
                            <td class="text-end">{{ indianNumberFormat($driver->misc) }}</td>
                            <td class="text-end fw-bold">{{ indianNumberFormat($driver->total_expenses) }}</td>
                            <td class="text-end">{{ $driver->expense_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-muted text-center">No driver expense data found for the selected period</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($drivers->count() > 0)
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td colspan="7"><strong>Total</strong></td>
                        <td class="text-end"><strong>₹{{ indianNumberFormat($totalExpenses) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div></div></div></div></div>

@endsection
