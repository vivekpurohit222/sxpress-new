@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Cash Flow Report</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.cashbook.daily') }}">Cash Book</a></li><li class="active">Cash Flow</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>Cash Flow — Where Money Came From & Where It Went</strong>
        <div class="float-right no-print">
            <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
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
                <div class="col-md-3" style="padding-top:24px"><button type="submit" class="btn btn-primary btn-sm">Generate</button></div>
            </div>
        </form>

        <div class="row">
            {{-- Cash Inflows (money received) --}}
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white py-2"><strong>Cash Inflows (Sources)</strong></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light"><tr><th>From Account</th><th class="text-right">Amount (₹)</th></tr></thead>
                            <tbody>
                                @forelse($inflows as $item)
                                <tr>
                                    <td>{{ $item['account']->code }} — {{ $item['account']->name }}</td>
                                    <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-center text-muted py-2">No inflows</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="font-weight-bold bg-light">
                                <tr><td class="text-right">Total Inflow</td><td class="text-right text-success">₹ {{ number_format($totalIn, 2) }}</td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Cash Outflows (money spent) --}}
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white py-2"><strong>Cash Outflows (Uses)</strong></div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light"><tr><th>To Account</th><th class="text-right">Amount (₹)</th></tr></thead>
                            <tbody>
                                @forelse($outflows as $item)
                                <tr>
                                    <td>{{ $item['account']->code }} — {{ $item['account']->name }}</td>
                                    <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-center text-muted py-2">No outflows</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="font-weight-bold bg-light">
                                <tr><td class="text-right">Total Outflow</td><td class="text-right text-danger">₹ {{ number_format($totalOut, 2) }}</td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Net Flow --}}
        <div class="alert {{ ($totalIn - $totalOut) >= 0 ? 'alert-success' : 'alert-danger' }} text-center mt-3">
            <strong>Net Cash Flow: ₹ {{ number_format($totalIn - $totalOut, 2) }}</strong>
            ({{ ($totalIn - $totalOut) >= 0 ? 'Positive — more cash received than spent' : 'Negative — more cash spent than received' }})
        </div>
    </div>
</div></div></div></div></div>
@endsection
