@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>{{ $statement['account']->name }}</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.ledger.index') }}">Ledger</a></li><li class="active">{{ $statement['account']->code }}</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>Ledger: {{ $statement['account']->code }} — {{ $statement['account']->name }}</strong>
        <span class="badge badge-{{ match($statement['account']->type) { 'asset'=>'primary','liability'=>'warning','income'=>'success','expense'=>'danger','equity'=>'info',default=>'secondary' } }} ml-2">{{ ucfirst($statement['account']->type) }}</span>
        <div class="float-right no-print">
            <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        {{-- Date Filter --}}
        <form method="GET" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-3"><label>From</label><input type="date" name="from_date" value="{{ $fromDate }}" class="form-control"></div>
                <div class="col-md-3"><label>To</label><input type="date" name="to_date" value="{{ $toDate }}" class="form-control"></div>
                @if($branches->count())
                <div class="col-md-3"><label>Branch</label>
                    <select name="branch" class="form-control"><option value="">All</option>
                    @foreach($branches as $b)<option value="{{ $b }}" {{ $branch == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-3" style="padding-top:24px">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </div>
            </div>
        </form>

        {{-- Summary --}}
        <div class="row mb-3">
            <div class="col-md-3"><div class="card border-secondary"><div class="card-body text-center py-2"><h5 class="mb-0">₹ {{ number_format($statement['opening_balance'], 2) }}</h5><small>Opening Balance</small></div></div></div>
            <div class="col-md-3"><div class="card border-success"><div class="card-body text-center py-2"><h5 class="mb-0 text-success">₹ {{ number_format($statement['total_debit'], 2) }}</h5><small>Total Debit</small></div></div></div>
            <div class="col-md-3"><div class="card border-danger"><div class="card-body text-center py-2"><h5 class="mb-0 text-danger">₹ {{ number_format($statement['total_credit'], 2) }}</h5><small>Total Credit</small></div></div></div>
            <div class="col-md-3"><div class="card border-primary"><div class="card-body text-center py-2"><h5 class="mb-0">₹ {{ number_format($statement['closing_balance'], 2) }}</h5><small>Closing Balance</small></div></div></div>
        </div>

        {{-- Ledger Entries Table --}}
        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr>
                    <th style="width:10%">Date</th>
                    <th style="width:12%">Voucher No</th>
                    <th>Narration</th>
                    <th class="text-right" style="width:12%">Debit (₹)</th>
                    <th class="text-right" style="width:12%">Credit (₹)</th>
                    <th class="text-right" style="width:12%">Balance (₹)</th>
                </tr>
            </thead>
            <tbody>
                {{-- Opening Balance Row --}}
                <tr class="table-light font-weight-bold">
                    <td>{{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</td>
                    <td>—</td>
                    <td><em>Opening Balance</em></td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ number_format($statement['opening_balance'], 2) }}</td>
                </tr>

                @forelse($statement['entries'] as $row)
                <tr>
                    <td>{{ $row['entry']->date->format('d-m-Y') }}</td>
                    <td><small>{{ $row['entry']->voucher->voucher_no ?? '-' }}</small></td>
                    <td>{{ $row['entry']->narration ?: ($row['entry']->voucher->narration ?? '-') }}</td>
                    <td class="text-right">{{ $row['entry']->debit > 0 ? number_format($row['entry']->debit, 2) : '' }}</td>
                    <td class="text-right">{{ $row['entry']->credit > 0 ? number_format($row['entry']->credit, 2) : '' }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($row['balance'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">No transactions in this period.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="font-weight-bold bg-light">
                <tr>
                    <td colspan="3" class="text-right">Closing Balance</td>
                    <td class="text-right">{{ number_format($statement['total_debit'], 2) }}</td>
                    <td class="text-right">{{ number_format($statement['total_credit'], 2) }}</td>
                    <td class="text-right">₹ {{ number_format($statement['closing_balance'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div></div></div></div></div>
@endsection
