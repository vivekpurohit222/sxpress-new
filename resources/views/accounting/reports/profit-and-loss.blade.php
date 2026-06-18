@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Profit & Loss Statement</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('accounting.reports.index') }}">Financial Reports</a></li>
            <li class="active">Profit & Loss</li>
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
            <strong>Profit & Loss Statement</strong>
            <small class="text-muted ms-2">{{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</small>
        </div>
        <button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()">
            <i class="fa fa-print"></i> Print
        </button>
    </div>

    <div class="card-body">
        @include('accounting.reports._filters', ['fromDate' => $fromDate, 'toDate' => $toDate])

        @include('accounting.reports._print-header', [
            'reportTitle' => 'Profit & Loss Statement',
            'reportPeriod' => \Carbon\Carbon::parse($fromDate)->format('d M Y') . ' to ' . \Carbon\Carbon::parse($toDate)->format('d M Y')
        ])

        {{-- Income Section --}}
        <div class="card mb-4">
            <div class="card-header bg-success bg-opacity-10">
                <strong class="text-success"><i class="fa fa-arrow-down"></i> Income</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Account</th>
                            <th class="text-end" style="width: 180px;">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($income as $parentId => $group)
                            @foreach($group as $account)
                                <tr>
                                    <td>{{ $account->name }}</td>
                                    <td class="text-end">{{ indianNumberFormat($account->balance) }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="2" class="text-muted text-center">No income recorded</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-success fw-bold">
                            <td><strong>Total Income</strong></td>
                            <td class="text-end"><strong>₹{{ indianNumberFormat($totalIncome) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Expenses Section --}}
        <div class="card mb-4">
            <div class="card-header bg-danger bg-opacity-10">
                <strong class="text-danger"><i class="fa fa-arrow-up"></i> Expenses</strong>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Account</th>
                            <th class="text-end" style="width: 180px;">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $parentId => $group)
                            @foreach($group as $account)
                                <tr>
                                    <td>{{ $account->name }}</td>
                                    <td class="text-end">{{ indianNumberFormat($account->balance) }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="2" class="text-muted text-center">No expenses recorded</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-danger fw-bold">
                            <td><strong>Total Expenses</strong></td>
                            <td class="text-end"><strong>₹{{ indianNumberFormat($totalExpenses) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Net Profit / Loss --}}
        <div class="card">
            <div class="card-body text-center py-4">
                <h4>
                    @if($netProfit >= 0)
                        <span class="text-success">
                            <i class="fa fa-check-circle"></i> Net Profit: ₹{{ indianNumberFormat($netProfit) }}
                        </span>
                    @else
                        <span class="text-danger">
                            <i class="fa fa-exclamation-circle"></i> Net Loss: ₹{{ indianNumberFormat(abs($netProfit)) }}
                        </span>
                    @endif
                </h4>
            </div>
        </div>
    </div>

</div></div></div></div></div>

@endsection
