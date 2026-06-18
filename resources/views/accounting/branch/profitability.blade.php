@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Branch Profitability</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.branch.dashboard') }}">Branch Accounting</a></li><li class="active">Profitability</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>Branch Profitability Report</strong>
    </div>
    <div class="card-body">
        @include('accounting.branch._nav')
        @include('accounting.branch._filter')

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        @if($profitabilityData->isEmpty() || $profitabilityData->every(fn($item) => $item->revenue == 0 && $item->expenses == 0))
            <div class="alert alert-info text-center">
                <i class="fa fa-info-circle"></i> No profitability data available for the selected period.
            </div>
        @else
            <table class="table table-bordered table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>Branch</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Expenses</th>
                        <th class="text-right">Net Profit/Loss</th>
                        <th class="text-right">Profit Margin %</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($profitabilityData as $row)
                        <tr class="{{ $row->net_profit > 0 ? 'table-success' : 'table-danger' }}">
                            <td>{{ $row->branch }}</td>
                            <td class="text-right">₹{{ number_format($row->revenue, 2) }}</td>
                            <td class="text-right">₹{{ number_format($row->expenses, 2) }}</td>
                            <td class="text-right font-weight-bold">₹{{ number_format($row->net_profit, 2) }}</td>
                            <td class="text-right">{{ number_format($row->profit_margin, 2) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div></div></div></div></div>
@endsection
