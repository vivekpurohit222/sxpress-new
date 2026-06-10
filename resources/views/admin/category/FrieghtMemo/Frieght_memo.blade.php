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
<div class="col-lg-10 offset-lg-1">
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-6"><strong>FREIGHT MEMO — Truck Owner Settlement</strong></div>
            <div class="col-6 text-right"><strong>FM No: {{ $fmNo }}</strong></div>
        </div>
    </div>
    <div class="card-body">
        <div class="text-center mb-2">
            <h4 class="mb-0">SAURASHTRA EXPRESS</h4>
            <small>H.O.: 2- Patel Nagar, Bhoja Bhagat Street, 50ft Ring Road, Rajkot | GST: 24AFSPJ7382P1ZI</small>
        </div>
        <hr>

        <div class="alert alert-info">
            <strong>Indian Transport Flow:</strong> Freight Memo is linked to a <strong>Challan</strong> (truck trip).
            Select a challan below to auto-fill truck, driver, route, and calculate total GR freight.
        </div>

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
                        <small class="text-muted">Challan = truck trip manifest containing GRs</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>FM Date <span class="text-danger">*</span></label>
                        <input type="date" name="fm_date" class="form-control" value="{{ old('fm_date', now()->format('Y-m-d')) }}" required placeholder="Select date">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>FM Number</label>
                        <input type="text" class="form-control" value="{{ $fmNo }}" readonly>
                    </div>
                </div>
            </div>

            {{-- Auto-filled Trip Details (read-only display) --}}
            <div id="challan-details" style="display: none;">
                <div class="card bg-light mb-3">
                    <div class="card-header"><strong>Trip Details (from Challan)</strong></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label><strong>Truck No.</strong></label>
                                <input type="text" name="truck_no" id="truck_no" class="form-control-plaintext" readonly>
                            </div>
                            <div class="col-md-3">
                                <label><strong>Driver</strong></label>
                                <input type="text" id="driver_name" class="form-control-plaintext" readonly>
                            </div>
                            <div class="col-md-3">
                                <label><strong>From</strong></label>
                                <input type="text" name="from_dest" id="from_dest" class="form-control-plaintext" readonly>
                            </div>
                            <div class="col-md-3">
                                <label><strong>To</strong></label>
                                <input type="text" name="to_dest" id="to_dest" class="form-control-plaintext" readonly>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-3">
                                <label><strong>Owner</strong></label>
                                <input type="text" id="owner_name" class="form-control-plaintext" readonly>
                            </div>
                            <div class="col-md-3">
                                <label><strong>Total Weight</strong></label>
                                <input type="text" id="total_weight" class="form-control-plaintext" readonly>
                            </div>
                            <div class="col-md-3">
                                <label><strong>Items Count</strong></label>
                                <input type="text" id="items_count" class="form-control-plaintext" readonly>
                            </div>
                            <div class="col-md-3">
                                <label><strong>Total GR Freight</strong></label>
                                <div class="badge badge-success" style="font-size: 1rem;" id="total_gr_freight">₹ 0.00</div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <label><strong>GR Numbers</strong></label>
                                <input type="text" id="gr_numbers" class="form-control-plaintext" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <hr>
            {{-- Charge Entries (standard for all truck trips) --}}
            <div class="form-section-title">Deductions / Charges</div>
            <table class="table table-bordered table-sm">
                <thead class="thead-light">
                    <tr><th style="width:5%">#</th><th>Description</th><th style="width:25%">Amount (₹)</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><input type="text" name="entry_1" class="form-control form-control-sm" value="{{ old('entry_1', 'Loading / Hamali') }}" placeholder="Enter charge description"></td>
                        <td><input type="number" name="entry_1_amount" class="form-control form-control-sm charge-input" value="{{ old('entry_1_amount', 0) }}" step="0.01" min="0" placeholder="Amount"></td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td><input type="text" name="entry_2" class="form-control form-control-sm" value="{{ old('entry_2', 'Unloading') }}" placeholder="Enter charge description"></td>
                        <td><input type="number" name="entry_2_amount" class="form-control form-control-sm charge-input" value="{{ old('entry_2_amount', 0) }}" step="0.01" min="0" placeholder="Amount"></td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td><input type="text" name="entry_3" class="form-control form-control-sm" value="{{ old('entry_3', 'Advance / Diesel') }}" placeholder="Enter charge description"></td>
                        <td><input type="number" name="entry_3_amount" class="form-control form-control-sm charge-input" value="{{ old('entry_3_amount', 0) }}" step="0.01" min="0" placeholder="Amount"></td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td><input type="text" name="entry_4" class="form-control form-control-sm" value="{{ old('entry_4', 'Toll / Octroi') }}" placeholder="Enter charge description"></td>
                        <td><input type="number" name="entry_4_amount" class="form-control form-control-sm charge-input" value="{{ old('entry_4_amount', 0) }}" step="0.01" min="0" placeholder="Amount"></td>
                    </tr>
                </tbody>
            </table>

            {{-- Settlement Calculation --}}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Note / Terms</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Enter additional terms or remarks for this memo...">{{ old('note') }}</textarea>
                    </div>
                    <small class="fine-print">
                        <strong>Terms:</strong> If goods not delivered within agreed time, penalty from freight.
                        Not responsible for damage in transit. Subject to Rajkot jurisdiction.
                    </small>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th>Truck Freight (Hire) <span class="text-danger">*</span></th>
                            <td><input type="number" name="truck_freight" id="truck_freight" class="form-control" value="{{ old('truck_freight', 0) }}" step="0.01" min="0" required placeholder="Enter truck hire amount"></td>
                        </tr>
                        <tr>
                            <th>Commission</th>
                            <td><input type="number" name="commission" id="commission" class="form-control charge-input" value="{{ old('commission', 0) }}" step="0.01" min="0" placeholder="Enter commission amount"></td>
                        </tr>
                        <tr>
                            <th>Other Charges</th>
                            <td><input type="number" name="other_charges" id="other_charges" class="form-control charge-input" value="{{ old('other_charges', 0) }}" step="0.01" min="0" placeholder="Enter other charges"></td>
                        </tr>
                        <tr>
                            <th>Extra</th>
                            <td><input type="number" name="extra" id="extra" class="form-control charge-input" value="{{ old('extra', 0) }}" step="0.01" min="0" placeholder="Enter extra deductions"></td>
                        </tr>
                        <tr class="table-success">
                            <th><strong>Balance Payable to Owner</strong></th>
                            <td><strong id="balance_display" style="font-size:16px">₹ 0.00</strong></td>
                        </tr>
                    </table>
                </div>
            </div>

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
// AJAX: Load challan data when selected
document.getElementById('challan_id').addEventListener('change', function() {
    var challanId = this.value;
    if (!challanId) {
        document.getElementById('challan-details').style.display = 'none';
        return;
    }

    fetch('/frieghtmemo/challan-data/' + challanId)
        .then(response => response.json())
        .then(data => {
            // Auto-fill form fields
            document.getElementById('truck_no').value = data.truck_no || '';
            document.getElementById('driver_name').value = data.driver_name || '';
            document.getElementById('owner_name').value = data.owner_name || '';
            document.getElementById('from_dest').value = data.from_dest || '';
            document.getElementById('to_dest').value = data.to_dest || '';
            document.getElementById('total_weight').value = data.total_weight ? data.total_weight + ' kg' : '';
            document.getElementById('items_count').value = data.items_count || '';
            document.getElementById('total_gr_freight').textContent = '₹ ' + (data.total_gr_freight || 0).toFixed(2);
            document.getElementById('gr_numbers').value = data.gr_numbers || '';

            // Show the details section
            document.getElementById('challan-details').style.display = 'block';

            // Pre-fill truck freight with total GR freight as a suggestion
            if (data.total_gr_freight > 0 && document.getElementById('truck_freight').value == 0) {
                document.getElementById('truck_freight').value = data.total_gr_freight.toFixed(2);
            }

            // Recalculate balance
            calcBalance();
        })
        .catch(error => {
            console.error('Error fetching challan data:', error);
            alert('Failed to load challan details.');
        });
});

// Balance calculation
function calcBalance() {
    var freight = parseFloat(document.getElementById('truck_freight').value) || 0;
    var commission = parseFloat(document.getElementById('commission').value) || 0;
    var other = parseFloat(document.getElementById('other_charges').value) || 0;
    var extra = parseFloat(document.getElementById('extra').value) || 0;
    var entries = 0;
    document.querySelectorAll('.charge-input').forEach(function(el) {
        if (el.id !== 'commission' && el.id !== 'other_charges' && el.id !== 'extra') {
            entries += parseFloat(el.value) || 0;
        }
    });
    var totalDeductions = commission + other + extra + entries;
    var balance = freight - totalDeductions;
    document.getElementById('balance_display').textContent = '₹ ' + balance.toFixed(2);
    document.getElementById('balance_display').style.color = balance >= 0 ? '#155724' : '#721c24';
}

// Attach event listeners
document.getElementById('truck_freight').addEventListener('input', calcBalance);
document.getElementById('commission').addEventListener('input', calcBalance);
document.getElementById('other_charges').addEventListener('input', calcBalance);
document.getElementById('extra').addEventListener('input', calcBalance);
document.querySelectorAll('.charge-input').forEach(function(el) { el.addEventListener('input', calcBalance); });

// Initial calculation
calcBalance();

// If pre-selected challan, trigger load on page load
@if($selectedChallan)
document.getElementById('challan_id').dispatchEvent(new Event('change'));
@endif
</script>

@endsection
