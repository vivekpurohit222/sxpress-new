@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Branch Revenue</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.branch.dashboard') }}">Branch Accounting</a></li><li class="active">Revenue</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3">{{ session('error') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif

    <div class="card-header">
        <strong>Branch Revenue Report</strong>
    </div>

    <div class="card-body">
        @include('accounting.branch._nav')
        @include('accounting.branch._filter')

        {{-- Revenue Comparison Table --}}
        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr>
                    <th>Branch</th>
                    <th class="text-right">Total Revenue</th>
                    <th class="text-right">% of Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($revenueData as $row)
                <tr>
                    <td>
                        <a href="{{ url()->current() }}?branch={{ urlencode($row->branch) }}{{ $fromDate ? '&from_date='.$fromDate : '' }}{{ $toDate ? '&to_date='.$toDate : '' }}">
                            {{ $row->branch }}
                        </a>
                    </td>
                    <td class="text-right">₹{{ number_format($row->total_revenue, 2) }}</td>
                    <td class="text-right">{{ number_format($row->percentage_of_total, 2) }}%</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-muted py-3">No revenue records found for the selected criteria.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Drill-down Section --}}
        @if($selectedBranch)
        <hr>
        <h5>Revenue Details — {{ $selectedBranch }}</h5>
        <table class="table table-bordered table-sm mt-2">
            <thead class="thead-dark">
                <tr>
                    <th>GR Number</th>
                    <th>Date</th>
                    <th>Account Name</th>
                    <th class="text-right">Credit Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branchDetail as $entry)
                <tr>
                    <td>{{ $entry->reference_id }}</td>
                    <td>{{ \Carbon\Carbon::parse($entry->date)->format('d-m-Y') }}</td>
                    <td>{{ $entry->account_name }}</td>
                    <td class="text-right">₹{{ number_format($entry->credit, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-3">No revenue records found for {{ $selectedBranch }}.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @endif

    </div>
</div></div></div></div></div>

@endsection
