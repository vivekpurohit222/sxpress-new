@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Trial Balance</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('accounting.reports.index') }}">Financial Reports</a></li>
            <li class="active">Trial Balance</li>
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
            <strong>Trial Balance</strong>
            <small class="text-muted ms-2">{{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</small>
        </div>
        <button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()">
            <i class="fa fa-print"></i> Print
        </button>
    </div>

    <div class="card-body">
        @include('accounting.reports._filters', ['fromDate' => $fromDate, 'toDate' => $toDate])

        @include('accounting.reports._print-header', [
            'reportTitle' => 'Trial Balance',
            'reportPeriod' => \Carbon\Carbon::parse($fromDate)->format('d M Y') . ' to ' . \Carbon\Carbon::parse($toDate)->format('d M Y')
        ])

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>Account Code</th>
                        <th>Account Name</th>
                        <th class="text-end">Debit (₹)</th>
                        <th class="text-end">Credit (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $typeLabels = [
                            'asset' => 'Assets',
                            'liability' => 'Liabilities',
                            'income' => 'Income',
                            'expense' => 'Expenses',
                            'equity' => 'Equity',
                        ];
                    @endphp

                    @foreach($typeLabels as $type => $label)
                        @if($accounts->has($type))
                            <tr class="table-secondary">
                                <td colspan="4"><strong>{{ $label }}</strong></td>
                            </tr>
                            @foreach($accounts->get($type) as $account)
                                <tr>
                                    <td>{{ $account->code }}</td>
                                    <td>{{ $account->name }}</td>
                                    <td class="text-end">{{ $account->debit > 0 ? indianNumberFormat($account->debit) : '' }}</td>
                                    <td class="text-end">{{ $account->credit > 0 ? indianNumberFormat($account->credit) : '' }}</td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td colspan="2"><strong>Total</strong></td>
                        <td class="text-end"><strong>₹{{ indianNumberFormat($totals['debit']) }}</strong></td>
                        <td class="text-end"><strong>₹{{ indianNumberFormat($totals['credit']) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div></div></div></div></div>

@endsection
