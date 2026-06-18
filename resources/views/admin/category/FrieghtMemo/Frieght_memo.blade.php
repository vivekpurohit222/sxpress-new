@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Freight Memo</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('frieghtmemo.index') }}">Freight Memo</a></li>
            <li class="active">Create</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn">
<div class="row">
<div class="col-lg-11 offset-lg-0">
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-6"><strong>FREIGHT MEMO — Lorry Hire Settlement</strong></div>
            <div class="col-6 text-right"><strong>FM No: {{ $fmNo }}</strong></div>
        </div>
    </div>
    <div class="card-body">
        <div class="text-center mb-2">
            <h4 class="mb-0">SAURASHTRA EXPRESS</h4>
            <small>H.O.: 2- Patel Nagar, Bhoja Bhagat Street, 50ft Ring Road, Rajkot | GST: 24AFSPJ7382P1ZI</small>
        </div>
        <hr>

        @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('frieghtmemo.store') }}" method="POST" id="freightMemoForm">
            @csrf

            {{-- Challan Selection --}}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Select Challan (Truck Trip) <span class="text-danger">*</span></label>
                        <select name="challan_id" id="challan_id" class="form-control" required>
                            <option value="">-- Select a Challan --</option>
                            @foreach($challans as $ch)
                            <option value="{{ $ch->id }}" {{ old('challan_id', $selectedChallan?->id) == $ch->id ? 'selected' : '' }}>
                                {{ $ch->challan_no }} — {{ $ch->truck_no }} — {{ $ch->from_dest }} → {{ $ch->to_dest }} ({{ $ch->challan_date }})
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Challan = truck trip manifest containing the GRs carried</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>FM Date <span class="text-danger">*</span></label>
                        <input type="date" name="fm_date" class="form-control" value="{{ old('fm_date', now()->format('Y-m-d')) }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>FM Number</label>
                        <input type="text" class="form-control" value="{{ $fmNo }}" readonly>
                    </div>
                </div>
            </div>

            {{-- Trip Details (auto-filled from challan) --}}
            <div id="challan-details" style="display:none">
                <div class="card bg-light mb-3">
                    <div class="card-header"><strong>Trip Details (from Challan)</strong></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="mb-0"><strong>Truck No.</strong></label>
                                <input type="text" name="truck_no" id="truck_no" class="form-control-plaintext" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="mb-0"><strong>Owner / Broker</strong></label>
                                <input type="text" name="owner_name" id="owner_name" class="form-control form-control-sm" placeholder="Owner / broker name">
                            </div>
                            <div class="col-md-3">
                                <label class="mb-0"><strong>From</strong></label>
                                <input type="text" name="from_dest" id="from_dest" class="form-control-plaintext" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="mb-0"><strong>To</strong></label>
                                <input type="text" name="to_dest" id="to_dest" class="form-control-plaintext" readonly>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-3">
                                <label class="mb-0"><strong>Driver</strong></label>
                                <input type="text" id="driver_name" class="form-control-plaintext" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="mb-0"><strong>Total Weight</strong></label>
                                <input type="text" id="total_weight" class="form-control-plaintext" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="mb-0"><strong>No. of GRs</strong></label>
                                <input type="text" id="items_count" class="form-control-plaintext" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="mb-0"><strong>Total GR Freight</strong></label>
                                <div class="badge badge-info" style="font-size:1rem" id="total_gr_freight">₹ 0.00</div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <label class="mb-0"><strong>GR Numbers in this Trip</strong></label>
                                <input type="text" id="gr_numbers" class="form-control-plaintext" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <div class="row">
                {{-- LEFT: Settlement Breakdown --}}
                <div class="col-md-7">
                    <div class="form-section-title">Lorry Hire Settlement</div>
                    <table class="table table-bordered">
                        <tr class="table-primary">
                            <th style="width:55%">Total Lorry Hire (Agreed Freight) <span class="text-danger">*</span></th>
                            <td>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" name="truck_freight" id="truck_freight" class="form-control calc" value="{{ old('truck_freight') }}" step="0.01" min="0" required placeholder="0.00" onfocus="this.select()">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>(−) Advance Paid <small class="text-muted">(at loading, for diesel)</small></th>
                            <td>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" name="advance" id="advance" class="form-control calc" value="{{ old('advance') }}" step="0.01" min="0" placeholder="0.00" onfocus="this.select()">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>(−) Broker Commission</th>
                            <td>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" name="commission" id="commission" class="form-control calc" value="{{ old('commission') }}" step="0.01" min="0" placeholder="0.00" onfocus="this.select()">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>(−) Hamali (Loading + Unloading)</th>
                            <td>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" name="hamali" id="hamali" class="form-control calc" value="{{ old('hamali') }}" step="0.01" min="0" placeholder="0.00" onfocus="this.select()">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>(−) Detention / Halting</th>
                            <td>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" name="detention" id="detention" class="form-control calc" value="{{ old('detention') }}" step="0.01" min="0" placeholder="0.00" onfocus="this.select()">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>(−) TDS <small class="text-muted">(1% if applicable)</small> <button type="button" class="btn btn-link btn-sm p-0" onclick="autoTds()" style="font-size:11px">auto 1%</button></th>
                            <td>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" name="tds" id="tds" class="form-control calc" value="{{ old('tds') }}" step="0.01" min="0" placeholder="0.00" onfocus="this.select()">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>(−) Other Deductions</th>
                            <td>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" name="other_charges" id="other_charges" class="form-control calc" value="{{ old('other_charges') }}" step="0.01" min="0" placeholder="0.00" onfocus="this.select()">
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- RIGHT: Summary + Notes --}}
                <div class="col-md-5">
                    <div class="form-section-title">Summary</div>
                    <table class="table table-bordered">
                        <tr>
                            <th>Total Lorry Hire</th>
                            <td class="text-right" id="sum_hire">₹ 0.00</td>
                        </tr>
                        <tr class="text-danger">
                            <th>Total Deductions</th>
                            <td class="text-right" id="sum_deductions">₹ 0.00</td>
                        </tr>
                        <tr class="table-success">
                            <th><strong>Balance Payable to Owner</strong><br><small class="text-muted">(at delivery, after POD)</small></th>
                            <td class="text-right"><strong id="balance_display" style="font-size:18px">₹ 0.00</strong></td>
                        </tr>
                    </table>

                    <div class="form-group mt-2">
                        <label>Note / Remarks</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="e.g. Balance to be paid after unloading & POD submission...">{{ old('note') }}</textarea>
                    </div>
                </div>
            </div>

            <small class="fine-print d-block mb-2">
                <strong>Terms:</strong> Balance payable on delivery after POD. Shortage/damage deductible from balance.
                Subject to Rajkot jurisdiction.
            </small>

            <hr>
            <button type="submit" class="btn btn-success btn-lg btn-block">Create Freight Memo</button>
        </form>
    </div>
</div>
</div>
</div>
</div>
</div>

<script>
function num(id) { var v = parseFloat(document.getElementById(id).value); return isNaN(v) ? 0 : v; }

function calcBalance() {
    var hire = num('truck_freight');
    var deductions = num('advance') + num('commission') + num('hamali') + num('detention') + num('tds') + num('other_charges');
    var balance = hire - deductions;

    document.getElementById('sum_hire').textContent = '₹ ' + hire.toFixed(2);
    document.getElementById('sum_deductions').textContent = '₹ ' + deductions.toFixed(2);
    document.getElementById('balance_display').textContent = '₹ ' + balance.toFixed(2);
    document.getElementById('balance_display').style.color = balance >= 0 ? '#155724' : '#721c24';
}

function autoTds() {
    var hire = num('truck_freight');
    document.getElementById('tds').value = (hire * 0.01).toFixed(2);
    calcBalance();
}

// AJAX: Load challan data when selected
document.getElementById('challan_id').addEventListener('change', function() {
    var challanId = this.value;
    if (!challanId) { document.getElementById('challan-details').style.display = 'none'; return; }

    fetch('/frieghtmemo/challan-data/' + challanId)
        .then(r => r.json())
        .then(data => {
            document.getElementById('truck_no').value = data.truck_no || '';
            document.getElementById('driver_name').value = data.driver_name || '';
            document.getElementById('owner_name').value = data.owner_name || '';
            document.getElementById('from_dest').value = data.from_dest || '';
            document.getElementById('to_dest').value = data.to_dest || '';
            document.getElementById('total_weight').value = data.total_weight ? data.total_weight + ' kg' : '-';
            document.getElementById('items_count').value = data.items_count || '0';
            document.getElementById('total_gr_freight').textContent = '₹ ' + (data.total_gr_freight || 0).toFixed(2);
            document.getElementById('gr_numbers').value = data.gr_numbers || '-';

            document.getElementById('challan-details').style.display = 'block';

            // Suggest lorry hire = total GR freight only if hire is empty
            var hf = document.getElementById('truck_freight');
            if (data.total_gr_freight > 0 && (hf.value === '' || parseFloat(hf.value) === 0)) {
                hf.value = data.total_gr_freight.toFixed(2);
            }
            calcBalance();
        })
        .catch(() => alert('Failed to load challan details.'));
});

// Recalculate on any input
document.querySelectorAll('.calc').forEach(function(el) {
    el.addEventListener('input', calcBalance);
});

calcBalance();

@if($selectedChallan)
document.getElementById('challan_id').dispatchEvent(new Event('change'));
@endif
</script>

@endsection
