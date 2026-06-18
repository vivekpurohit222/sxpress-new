@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Day Book</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('accounting.reports.index') }}">Financial Reports</a></li>
            <li class="active">Day Book</li>
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
            <strong>Day Book</strong>
            <small class="text-muted ms-2">{{ \Carbon\Carbon::parse($date)->format('d M Y (l)') }}</small>
        </div>
        <button class="btn btn-outline-secondary btn-sm no-print" onclick="window.print()">
            <i class="fa fa-print"></i> Print
        </button>
    </div>

    <div class="card-body">
        @include('accounting.reports._filters', ['date' => $date])

        @include('accounting.reports._print-header', [
            'reportTitle' => 'Day Book',
            'reportPeriod' => \Carbon\Carbon::parse($date)->format('d M Y (l)')
        ])

        @if($vouchers->isEmpty())
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> No transactions recorded on {{ \Carbon\Carbon::parse($date)->format('d M Y') }}.
            </div>
        @else
            @foreach($vouchers as $voucher)
                <div class="card mb-3">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <div>
                            <strong>{{ $voucher->voucher_no }}</strong>
                            <span class="badge bg-{{ $voucher->voucher_type === 'receipt' ? 'success' : ($voucher->voucher_type === 'payment' ? 'danger' : ($voucher->voucher_type === 'contra' ? 'warning' : 'info')) }} ms-2">
                                {{ ucfirst($voucher->voucher_type) }}
                            </span>
                            @if($voucher->narration)
                                <span class="text-muted ms-2">{{ $voucher->narration }}</span>
                            @endif
                        </div>
                        <strong>₹{{ indianNumberFormat($voucher->total_amount ?? $voucher->entries->sum('debit')) }}</strong>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Account</th>
                                    <th class="text-end" style="width: 150px;">Debit (₹)</th>
                                    <th class="text-end" style="width: 150px;">Credit (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($voucher->entries as $entry)
                                    <tr>
                                        <td>{{ $entry->account->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ $entry->debit > 0 ? indianNumberFormat($entry->debit) : '' }}</td>
                                        <td class="text-end">{{ $entry->credit > 0 ? indianNumberFormat($entry->credit) : '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div class="table-responsive">
                <table class="table table-bordered">
                    <tfoot>
                        <tr class="table-secondary fw-bold">
                            <td><strong>Total</strong></td>
                            <td class="text-end"><strong>₹{{ indianNumberFormat($totals['debit']) }}</strong></td>
                            <td class="text-end"><strong>₹{{ indianNumberFormat($totals['credit']) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

</div></div></div></div></div>

@endsection
