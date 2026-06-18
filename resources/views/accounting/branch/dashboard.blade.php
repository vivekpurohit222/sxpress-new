@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Branch Accounting</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('accounting.branch.dashboard') }}">Branch Accounting</a></li>
            <li class="active">Dashboard</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3">{{ session('error') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif

    <div class="card-header">
        <strong>Branch Dashboard</strong>
        <small class="text-muted ml-2">{{ $fromDate }} to {{ $toDate }}</small>
    </div>

    <div class="card-body">
        @include('accounting.branch._nav')
        @include('accounting.branch._filter')

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>Branch</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Expenses</th>
                        <th class="text-right">Profitability</th>
                        <th class="text-right">Cash Position</th>
                        <th class="text-right">Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($branches as $branchName => $metrics)
                    <tr>
                        <td>{{ $branchName }}</td>
                        <td class="text-right">₹{{ number_format($metrics['revenue'], 2) }}</td>
                        <td class="text-right">₹{{ number_format($metrics['expenses'], 2) }}</td>
                        <td class="text-right {{ $metrics['profitability'] > 0 ? 'text-success' : ($metrics['profitability'] < 0 ? 'text-danger' : '') }}">
                            ₹{{ number_format($metrics['profitability'], 2) }}
                        </td>
                        <td class="text-right">₹{{ number_format($metrics['cash_position'], 2) }}</td>
                        <td class="text-right">₹{{ number_format($metrics['outstanding'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold table-secondary">
                        <td><strong>Consolidated Total</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($totals['revenue'], 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($totals['expenses'], 2) }}</strong></td>
                        <td class="text-right {{ $totals['profitability'] > 0 ? 'text-success' : ($totals['profitability'] < 0 ? 'text-danger' : '') }}">
                            <strong>₹{{ number_format($totals['profitability'], 2) }}</strong>
                        </td>
                        <td class="text-right"><strong>₹{{ number_format($totals['cash_position'], 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($totals['outstanding'], 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div></div></div></div></div>
@endsection
