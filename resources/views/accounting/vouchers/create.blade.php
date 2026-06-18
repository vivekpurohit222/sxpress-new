@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>{{ \App\Models\Accounting\Voucher::getTypeLabel($type) }}</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('accounting.vouchers.index') }}">Vouchers</a></li><li class="active">Create</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-lg-10 offset-lg-1"><div class="card">
    <div class="card-header">
        <strong>Create {{ \App\Models\Accounting\Voucher::getTypeLabel($type) }}</strong>
        <span class="badge badge-{{ match($type) { 'receipt'=>'success','payment'=>'danger','contra'=>'info','journal'=>'warning',default=>'secondary' } }} ml-2">{{ strtoupper($type) }}</span>
        <span class="float-right">Office: <strong>{{ $office }}</strong></span>
    </div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

        <form method="POST" action="{{ route('accounting.vouchers.store') }}" id="voucherForm">
            @csrf
            <input type="hidden" name="voucher_type" value="{{ $type }}">

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" name="voucher_date" class="form-control" value="{{ old('voucher_date', now()->format('Y-m-d')) }}" required max="{{ now()->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Narration / Description <span class="text-danger">*</span></label>
                        <input type="text" name="narration" class="form-control" value="{{ old('narration') }}" required placeholder="e.g. Freight received from consignee for GR AA-00123">
                    </div>
                </div>
            </div>

            <hr>
            <div class="form-section-title">Voucher Entries (Double-Entry)</div>
            <small class="text-muted d-block mb-2">Total Debit must equal Total Credit. Minimum 2 entries required.</small>

            <table class="table table-bordered table-sm" id="entriesTable">
                <thead class="thead-light">
                    <tr>
                        <th style="width:40%">Account</th>
                        <th style="width:22%">Debit (₹)</th>
                        <th style="width:22%">Credit (₹)</th>
                        <th style="width:16%"></th>
                    </tr>
                </thead>
                <tbody id="entriesBody">
                    <tr class="entry-row">
                        <td>
                            <select name="entries[0][account_id]" class="form-control form-control-sm" required>
                                <option value="">Select Account</option>
                                @foreach($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="entries[0][debit]" class="form-control form-control-sm calc-debit" step="0.01" min="0" placeholder="0.00" onfocus="this.select()"></td>
                        <td><input type="number" name="entries[0][credit]" class="form-control form-control-sm calc-credit" step="0.01" min="0" placeholder="0.00" onfocus="this.select()"></td>
                        <td></td>
                    </tr>
                    <tr class="entry-row">
                        <td>
                            <select name="entries[1][account_id]" class="form-control form-control-sm" required>
                                <option value="">Select Account</option>
                                @foreach($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="entries[1][debit]" class="form-control form-control-sm calc-debit" step="0.01" min="0" placeholder="0.00" onfocus="this.select()"></td>
                        <td><input type="number" name="entries[1][credit]" class="form-control form-control-sm calc-credit" step="0.01" min="0" placeholder="0.00" onfocus="this.select()"></td>
                        <td><button type="button" class="btn btn-danger btn-sm remove-entry"><i class="fa fa-times"></i></button></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-light font-weight-bold">
                        <td class="text-right">TOTAL</td>
                        <td class="text-right" id="totalDebit">₹ 0.00</td>
                        <td class="text-right" id="totalCredit">₹ 0.00</td>
                        <td><span id="balanceStatus" class="badge badge-secondary">—</span></td>
                    </tr>
                </tfoot>
            </table>

            <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="addEntryBtn"><i class="fa fa-plus"></i> Add Entry Row</button>

            <hr>
            <button type="submit" class="btn btn-success btn-lg btn-block" id="submitBtn" disabled>Create Voucher</button>
        </form>
    </div>
</div></div></div></div></div>

<script>
var entryIndex = 2;
var accountOptions = `<option value="">Select Account</option>@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>@endforeach`;

document.getElementById('addEntryBtn').addEventListener('click', function() {
    var tbody = document.getElementById('entriesBody');
    var tr = document.createElement('tr');
    tr.className = 'entry-row';
    tr.innerHTML = '<td><select name="entries[' + entryIndex + '][account_id]" class="form-control form-control-sm" required>' + accountOptions + '</select></td>' +
        '<td><input type="number" name="entries[' + entryIndex + '][debit]" class="form-control form-control-sm calc-debit" step="0.01" min="0" placeholder="0.00" onfocus="this.select()"></td>' +
        '<td><input type="number" name="entries[' + entryIndex + '][credit]" class="form-control form-control-sm calc-credit" step="0.01" min="0" placeholder="0.00" onfocus="this.select()"></td>' +
        '<td><button type="button" class="btn btn-danger btn-sm remove-entry"><i class="fa fa-times"></i></button></td>';
    tbody.appendChild(tr);
    entryIndex++;
    attachCalcListeners();
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-entry')) {
        var rows = document.querySelectorAll('.entry-row');
        if (rows.length > 2) {
            e.target.closest('tr').remove();
            calculateTotals();
        }
    }
});

function calculateTotals() {
    var totalDebit = 0, totalCredit = 0;
    document.querySelectorAll('.calc-debit').forEach(function(el) { totalDebit += parseFloat(el.value) || 0; });
    document.querySelectorAll('.calc-credit').forEach(function(el) { totalCredit += parseFloat(el.value) || 0; });

    document.getElementById('totalDebit').textContent = '₹ ' + totalDebit.toFixed(2);
    document.getElementById('totalCredit').textContent = '₹ ' + totalCredit.toFixed(2);

    var balanced = Math.abs(totalDebit - totalCredit) < 0.01 && totalDebit > 0;
    var statusEl = document.getElementById('balanceStatus');
    var submitBtn = document.getElementById('submitBtn');

    if (balanced) {
        statusEl.textContent = '✓ Balanced';
        statusEl.className = 'badge badge-success';
        submitBtn.disabled = false;
    } else if (totalDebit === 0 && totalCredit === 0) {
        statusEl.textContent = '—';
        statusEl.className = 'badge badge-secondary';
        submitBtn.disabled = true;
    } else {
        var diff = Math.abs(totalDebit - totalCredit).toFixed(2);
        statusEl.textContent = '✗ Diff: ₹' + diff;
        statusEl.className = 'badge badge-danger';
        submitBtn.disabled = true;
    }
}

function attachCalcListeners() {
    document.querySelectorAll('.calc-debit, .calc-credit').forEach(function(el) {
        el.removeEventListener('input', calculateTotals);
        el.addEventListener('input', calculateTotals);
    });
}

attachCalcListeners();
</script>
@endsection
