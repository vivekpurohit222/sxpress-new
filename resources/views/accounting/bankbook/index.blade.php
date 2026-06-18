@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Bank Book</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li class="active">Bank Book</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">
    <div class="card-header">
        <strong>Bank Book</strong>
        <div class="float-right no-print">
            @role('SuperAdmin')<a href="{{ route('accounting.bankbook.manage') }}" class="btn btn-outline-secondary btn-sm">Manage Banks</a>@endrole
            <button onclick="window.print()" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</button>
        </div>
    </div>
    <div class="card-body">
        {{-- Filters --}}
        <form method="GET" class="mb-3 no-print">
            <div class="row">
                <div class="col-md-3">
                    <label>Bank Account</label>
                    <select name="bank_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Select Bank --</option>
                        @foreach($banks as $bank)
                        <option value="{{ $bank->id }}" {{ $selectedBankId == $bank->id ? 'selected' : '' }}>
                            {{ $bank->bank_name }} — {{ $bank->account_number }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label>From</label><input type="date" name="from_date" value="{{ $fromDate }}" class="form-control"></div>
                <div class="col-md-2"><label>To</label><input type="date" name="to_date" value="{{ $toDate }}" class="form-control"></div>
                @if($branches->count())
                <div class="col-md-2"><label>Branch</label>
                    <select name="branch" class="form-control"><option value="">All</option>
                    @foreach($branches as $b)<option value="{{ $b }}" {{ $branch == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2" style="padding-top:24px"><button type="submit" class="btn btn-primary btn-sm">View</button></div>
            </div>
        </form>

        @if($statement)
        {{-- Bank Info --}}
        <div class="alert alert-light py-2 mb-3">
            <strong>{{ $statement['bank']->bank_name }}</strong> — A/c: {{ $statement['bank']->account_number }}
            @if($statement['bank']->ifsc_code) | IFSC: {{ $statement['bank']->ifsc_code }} @endif
            | <strong>Opening: ₹ {{ number_format($statement['opening_balance'], 2) }}</strong>
            @if($statement['uncleared_count'] > 0) <span class="badge badge-warning ml-2">{{ $statement['uncleared_count'] }} uncleared cheques</span> @endif
        </div>

        {{-- Bank Statement --}}
        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr>
                    <th style="width:9%">Date</th>
                    <th style="width:12%">Voucher</th>
                    <th>Particulars</th>
                    <th style="width:10%">Chq No.</th>
                    <th class="text-right" style="width:11%">Deposit</th>
                    <th class="text-right" style="width:11%">Withdrawal</th>
                    <th class="text-right" style="width:11%">Balance</th>
                    <th style="width:8%">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table-light font-weight-bold">
                    <td colspan="4"><em>Opening Balance</em></td>
                    <td></td><td></td>
                    <td class="text-right">{{ number_format($statement['opening_balance'], 2) }}</td>
                    <td></td>
                </tr>
                @forelse($statement['entries'] as $row)
                @php $entry = $row['entry']; @endphp
                <tr>
                    <td>{{ $entry->date->format('d-m-Y') }}</td>
                    <td><small><a href="{{ route('accounting.vouchers.show', $entry->voucher_id) }}">{{ $entry->voucher->voucher_no ?? '-' }}</a></small></td>
                    <td>{{ $entry->narration ?: ($entry->voucher->narration ?? '-') }}</td>
                    <td>{{ $entry->cheque_no ?: '-' }}</td>
                    <td class="text-right text-success">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '' }}</td>
                    <td class="text-right text-danger">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '' }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($row['balance'], 2) }}</td>
                    <td>
                        @if($entry->cheque_no)
                        <span class="badge badge-{{ match($entry->reconciliation_status) { 'cleared'=>'success','bounced'=>'danger',default=>'warning' } }}" style="cursor:pointer" title="Click to toggle"
                              onclick="toggleReconcile({{ $entry->id }}, '{{ $entry->reconciliation_status }}')">
                            {{ ucfirst($entry->reconciliation_status) }}
                        </span>
                        @else
                        <span class="badge badge-secondary">Cash</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-3">No transactions in this period.</td></tr>
                @endforelse
            </tbody>
            @if(count($statement['entries']))
            <tfoot class="font-weight-bold bg-light">
                <tr>
                    <td colspan="4" class="text-right">Total</td>
                    <td class="text-right text-success">₹ {{ number_format($statement['total_deposits'], 2) }}</td>
                    <td class="text-right text-danger">₹ {{ number_format($statement['total_withdrawals'], 2) }}</td>
                    <td class="text-right">₹ {{ number_format($statement['closing_balance'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
        @else
        <div class="text-center text-muted py-5">
            <h5>Select a bank account to view the statement</h5>
            @if($banks->isEmpty())
            <p>No bank accounts configured. <a href="{{ route('accounting.bankbook.manage') }}">Add one</a>.</p>
            @endif
        </div>
        @endif
    </div>
</div></div></div></div></div>

<script>
function toggleReconcile(entryId, currentStatus) {
    var newStatus = currentStatus === 'cleared' ? 'uncleared' : 'cleared';
    if (!confirm('Mark as ' + newStatus + '?')) return;

    fetch('{{ url("/accounting/bank-book/reconcile") }}/' + entryId, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({status: newStatus})
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
    });
}
</script>
@endsection
