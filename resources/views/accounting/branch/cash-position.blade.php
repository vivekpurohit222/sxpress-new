@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Branch Cash Position</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.branch.dashboard') }}">Branch Accounting</a></li><li class="active">Cash Position</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>Cash Position by Branch</strong>
    </div>
    <div class="card-body">
        @include('accounting.branch._nav')
        @include('accounting.branch._filter')

        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr>
                    <th>Branch</th>
                    <th class="text-right">Opening Balance</th>
                    <th class="text-right">Receipts</th>
                    <th class="text-right">Payments</th>
                    <th class="text-right">Closing Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cashData as $row)
                    @if($row->branch === 'Total')
                        <tr class="font-weight-bold" style="background-color: #f8f9fa;">
                            <td><strong>{{ $row->branch }}</strong></td>
                            <td class="text-right"><strong>₹{{ number_format($row->opening_balance, 2) }}</strong></td>
                            <td class="text-right"><strong>₹{{ number_format($row->receipts, 2) }}</strong></td>
                            <td class="text-right"><strong>₹{{ number_format($row->payments, 2) }}</strong></td>
                            <td class="text-right"><strong>₹{{ number_format($row->closing_balance, 2) }}</strong></td>
                        </tr>
                    @else
                        <tr>
                            <td>{{ $row->branch }}</td>
                            <td class="text-right">₹{{ number_format($row->opening_balance, 2) }}</td>
                            <td class="text-right">₹{{ number_format($row->receipts, 2) }}</td>
                            <td class="text-right">₹{{ number_format($row->payments, 2) }}</td>
                            <td class="text-right">₹{{ number_format($row->closing_balance, 2) }}</td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No cash position data found for the selected period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div></div></div></div></div>

@endsection
