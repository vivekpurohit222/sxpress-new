@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Branch Outstanding</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.branch.dashboard') }}">Branch Accounting</a></li><li class="active">Outstanding</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3">{{ session('error') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif

    <div class="card-header">
        <strong>Branch Outstanding Report</strong>
    </div>

    <div class="card-body">
        @include('accounting.branch._nav')
        @include('accounting.branch._filter')

        {{-- Outstanding Summary Table --}}
        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr>
                    <th>Branch</th>
                    <th class="text-right">Total Receivable</th>
                    <th class="text-right">Total Payable</th>
                    <th class="text-right">Net Position</th>
                    <th class="text-right">Entry Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse($outstandingData as $row)
                <tr>
                    <td>
                        <a href="{{ url()->current() }}?branch={{ urlencode($row->branch) }}{{ $fromDate ? '&from_date='.$fromDate : '' }}{{ $toDate ? '&to_date='.$toDate : '' }}">
                            {{ $row->branch }}
                        </a>
                    </td>
                    <td class="text-right">₹{{ number_format($row->total_receivable, 2) }}</td>
                    <td class="text-right">₹{{ number_format($row->total_payable, 2) }}</td>
                    <td class="text-right">₹{{ number_format($row->net_position, 2) }}</td>
                    <td class="text-right">{{ $row->entry_count }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">No outstanding records found for the selected criteria.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Detail View for Selected Branch --}}
        @if($selectedBranch)
        <hr>
        <h5>Outstanding Details — {{ $selectedBranch }}</h5>
        <table class="table table-bordered table-sm mt-2">
            <thead class="thead-dark">
                <tr>
                    <th>Party Type</th>
                    <th>Party Name</th>
                    <th>Invoice Ref</th>
                    <th>Invoice Date</th>
                    <th class="text-right">Total Amount</th>
                    <th class="text-right">Paid Amount</th>
                    <th class="text-right">Pending Amount</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($branchDetail as $entry)
                <tr>
                    <td>{{ $entry->party_type }}</td>
                    <td>{{ $entry->party_name }}</td>
                    <td>{{ $entry->invoice_ref }}</td>
                    <td>{{ \Carbon\Carbon::parse($entry->invoice_date)->format('d-m-Y') }}</td>
                    <td class="text-right">₹{{ number_format($entry->total_amount, 2) }}</td>
                    <td class="text-right">₹{{ number_format($entry->paid_amount, 2) }}</td>
                    <td class="text-right">₹{{ number_format($entry->pending_amount, 2) }}</td>
                    <td>{{ $entry->due_date ? \Carbon\Carbon::parse($entry->due_date)->format('d-m-Y') : '—' }}</td>
                    <td>
                        @if($entry->status === 'pending')
                            <span class="badge badge-warning">Pending</span>
                        @elseif($entry->status === 'partial')
                            <span class="badge badge-info">Partial</span>
                        @elseif($entry->status === 'overdue')
                            <span class="badge badge-danger">Overdue</span>
                        @else
                            <span class="badge badge-secondary">{{ ucfirst($entry->status) }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-3">No outstanding records found for {{ $selectedBranch }}.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="d-flex justify-content-center mt-3">
            {{ $branchDetail->links() }}
        </div>
        @endif

    </div>
</div></div></div></div></div>

@endsection
