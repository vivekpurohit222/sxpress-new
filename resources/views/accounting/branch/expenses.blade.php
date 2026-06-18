@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4">
        <div class="page-header float-left">
            <div class="page-title"><h1>Branch Expenses</h1></div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="page-header float-right">
            <div class="page-title">
                <ol class="breadcrumb text-right">
                    <li><a href="{{ url('/dash') }}">Dashboard</a></li>
                    <li><a href="{{ route('accounting.branch.dashboard') }}">Branch Accounting</a></li>
                    <li class="active">Expenses</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <strong>Branch Expense Report</strong>
                        <span class="text-muted ml-2">
                            ({{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} &mdash; {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }})
                        </span>
                    </div>
                    <div class="card-body">
                        @include('accounting.branch._nav')
                        @include('accounting.branch._filter')

                        {{-- Summary Table: Branches × Expense Types --}}
                        @php
                            // Collect all unique expense types across all branches
                            $allTypes = collect();
                            foreach ($expenseData as $row) {
                                foreach ($row->types as $typeData) {
                                    $allTypes->push($typeData['expense_type']);
                                }
                            }
                            $allTypes = $allTypes->unique()->sort()->values();
                        @endphp

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-sm">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Branch</th>
                                        @foreach($allTypes as $type)
                                            <th class="text-right">{{ \App\Models\Accounting\Expense::getTypeLabel($type) }}</th>
                                        @endforeach
                                        <th class="text-right">Total Count</th>
                                        <th class="text-right">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($expenseData as $row)
                                        <tr>
                                            <td>
                                                <a href="{{ url()->current() }}?branch={{ urlencode($row->branch) }}&from_date={{ $fromDate }}&to_date={{ $toDate }}">
                                                    {{ $row->branch }}
                                                </a>
                                            </td>
                                            @foreach($allTypes as $type)
                                                @php
                                                    $typeEntry = collect($row->types)->firstWhere('expense_type', $type);
                                                @endphp
                                                <td class="text-right">
                                                    @if($typeEntry)
                                                        {{ $typeEntry['count'] }} / ₹{{ number_format($typeEntry['total'], 2) }}
                                                    @else
                                                        0 / ₹0.00
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="text-right"><strong>{{ $row->count }}</strong></td>
                                            <td class="text-right"><strong>₹{{ number_format($row->total, 2) }}</strong></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $allTypes->count() + 3 }}" class="text-center text-muted">
                                                No expense records found for the selected criteria.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if($expenseData->isNotEmpty())
                                    <tfoot>
                                        <tr class="table-secondary font-weight-bold">
                                            <td><strong>Consolidated</strong></td>
                                            @foreach($allTypes as $type)
                                                @php
                                                    $typeCount = 0;
                                                    $typeTotal = 0;
                                                    foreach ($expenseData as $row) {
                                                        $entry = collect($row->types)->firstWhere('expense_type', $type);
                                                        if ($entry) {
                                                            $typeCount += $entry['count'];
                                                            $typeTotal += $entry['total'];
                                                        }
                                                    }
                                                @endphp
                                                <td class="text-right">
                                                    <strong>{{ $typeCount }} / ₹{{ number_format($typeTotal, 2) }}</strong>
                                                </td>
                                            @endforeach
                                            <td class="text-right"><strong>{{ $expenseData->sum('count') }}</strong></td>
                                            <td class="text-right"><strong>₹{{ number_format($expenseData->sum('total'), 2) }}</strong></td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>

                        {{-- Drill-down Section for Selected Branch --}}
                        @if($selectedBranch && $branchDetail)
                            <hr>
                            <h5>
                                <i class="fa fa-list"></i> Expense Details &mdash; {{ $selectedBranch }}
                            </h5>
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-hover table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Expense No</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th class="text-right">Amount</th>
                                            <th>Paid To</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($branchDetail as $expense)
                                            <tr>
                                                <td>{{ $expense->expense_no }}</td>
                                                <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</td>
                                                <td>{{ \App\Models\Accounting\Expense::getTypeLabel($expense->expense_type) }}</td>
                                                <td>{{ $expense->description ?? '-' }}</td>
                                                <td class="text-right">₹{{ number_format($expense->amount, 2) }}</td>
                                                <td>{{ $expense->paid_to ?? '-' }}</td>
                                                <td>
                                                    @if($expense->status === 'approved')
                                                        <span class="badge badge-success">Approved</span>
                                                    @elseif($expense->status === 'pending')
                                                        <span class="badge badge-warning">Pending</span>
                                                    @else
                                                        <span class="badge badge-secondary">{{ ucfirst($expense->status) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    No expense details found for {{ $selectedBranch }}.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
