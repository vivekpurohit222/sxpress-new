@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Balance Sheet</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('accounting.reports.index') }}">Financial Reports</a></li>
            <li class="active">Balance Sheet</li>
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
            <strong>Balance Sheet</strong>
            <small class="text-muted ms-2">As on {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }}</small>
        </div>
        <button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()">
            <i class="fa fa-print"></i> Print
        </button>
    </div>

    <div class="card-body">
        @include('accounting.reports._filters', ['date' => $asOfDate, 'asOfDate' => true])

        @include('accounting.reports._print-header', [
            'reportTitle' => 'Balance Sheet',
            'reportPeriod' => 'As on ' . \Carbon\Carbon::parse($asOfDate)->format('d M Y')
        ])

        {{-- T-Format Balance Sheet --}}
        <div class="row">
            {{-- Left Side: Assets --}}
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-primary bg-opacity-10">
                        <strong class="text-primary"><i class="fa fa-briefcase"></i> Assets</strong>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Account</th>
                                    <th class="text-end" style="width: 150px;">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assets as $parentId => $group)
                                    @foreach($group as $account)
                                        <tr>
                                            <td>{{ $account->name }}</td>
                                            <td class="text-end">{{ indianNumberFormat($account->balance) }}</td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted text-center">No assets</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="table-primary fw-bold">
                                    <td><strong>Total Assets</strong></td>
                                    <td class="text-end"><strong>₹{{ indianNumberFormat($totalAssets) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Right Side: Liabilities + Equity --}}
            <div class="col-md-6">
                {{-- Liabilities --}}
                <div class="card mb-3">
                    <div class="card-header bg-danger bg-opacity-10">
                        <strong class="text-danger"><i class="fa fa-credit-card"></i> Liabilities</strong>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Account</th>
                                    <th class="text-end" style="width: 150px;">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($liabilities as $parentId => $group)
                                    @foreach($group as $account)
                                        <tr>
                                            <td>{{ $account->name }}</td>
                                            <td class="text-end">{{ indianNumberFormat($account->balance) }}</td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted text-center">No liabilities</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Equity --}}
                <div class="card mb-3">
                    <div class="card-header bg-info bg-opacity-10">
                        <strong class="text-info"><i class="fa fa-balance-scale"></i> Equity</strong>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Account</th>
                                    <th class="text-end" style="width: 150px;">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($equity as $parentId => $group)
                                    @foreach($group as $account)
                                        <tr>
                                            <td>{{ $account->name }}</td>
                                            <td class="text-end">{{ indianNumberFormat($account->balance) }}</td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted text-center">No equity accounts</td>
                                    </tr>
                                @endforelse
                                {{-- Net Profit line item --}}
                                <tr class="table-light">
                                    <td><em>Net Profit (Current Period)</em></td>
                                    <td class="text-end">
                                        @if($netProfit >= 0)
                                            <span class="text-success">{{ indianNumberFormat($netProfit) }}</span>
                                        @else
                                            <span class="text-danger">-{{ indianNumberFormat(abs($netProfit)) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="table-info fw-bold">
                                    <td><strong>Total Liabilities + Equity</strong></td>
                                    <td class="text-end"><strong>₹{{ indianNumberFormat($totalLiabilitiesEquity) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div></div></div></div></div>

@endsection
