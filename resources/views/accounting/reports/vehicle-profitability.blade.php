@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Vehicle Profitability Report</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('accounting.reports.index') }}">Financial Reports</a></li>
            <li class="active">Vehicle Profitability</li>
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
            <strong>Vehicle Profitability Report</strong>
            <small class="text-muted ms-2">{{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</small>
        </div>
        <button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()">
            <i class="fa fa-print"></i> Print
        </button>
    </div>

    <div class="card-body">
        @include('accounting.reports._filters', ['fromDate' => $fromDate, 'toDate' => $toDate])

        @include('accounting.reports._print-header', [
            'reportTitle' => 'Vehicle Profitability Report',
            'reportPeriod' => \Carbon\Carbon::parse($fromDate)->format('d M Y') . ' to ' . \Carbon\Carbon::parse($toDate)->format('d M Y')
        ])

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>Vehicle No</th>
                        <th class="text-end">Total Revenue (₹)</th>
                        <th class="text-end">Diesel (₹)</th>
                        <th class="text-end">Repair (₹)</th>
                        <th class="text-end">Tyre (₹)</th>
                        <th class="text-end">Driver Salary (₹)</th>
                        <th class="text-end">Misc (₹)</th>
                        <th class="text-end">Total Expenses (₹)</th>
                        <th class="text-end">Net Profit/Loss (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                        <tr>
                            <td>{{ $vehicle->vehicle_number }}</td>
                            <td class="text-end">{{ indianNumberFormat($vehicle->total_revenue) }}</td>
                            <td class="text-end">{{ indianNumberFormat($vehicle->diesel) }}</td>
                            <td class="text-end">{{ indianNumberFormat($vehicle->repair) }}</td>
                            <td class="text-end">{{ indianNumberFormat($vehicle->tyre) }}</td>
                            <td class="text-end">{{ indianNumberFormat($vehicle->driver_salary) }}</td>
                            <td class="text-end">{{ indianNumberFormat($vehicle->misc) }}</td>
                            <td class="text-end">{{ indianNumberFormat($vehicle->total_expenses) }}</td>
                            <td class="text-end">
                                @if($vehicle->net_profit >= 0)
                                    <span class="text-success fw-bold">{{ indianNumberFormat($vehicle->net_profit) }}</span>
                                @else
                                    <span class="text-danger fw-bold">-{{ indianNumberFormat(abs($vehicle->net_profit)) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-muted text-center">No vehicle data found for the selected period</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($vehicles->count() > 0)
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td><strong>Total</strong></td>
                        <td class="text-end"><strong>₹{{ indianNumberFormat($totals['revenue']) }}</strong></td>
                        <td colspan="6"></td>
                        <td class="text-end">
                            @if($totals['net_profit'] >= 0)
                                <strong class="text-success">₹{{ indianNumberFormat($totals['net_profit']) }}</strong>
                            @else
                                <strong class="text-danger">-₹{{ indianNumberFormat(abs($totals['net_profit'])) }}</strong>
                            @endif
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div></div></div></div></div>

@endsection
