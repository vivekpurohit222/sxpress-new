@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4">
        <div class="page-header float-left">
            <div class="page-title">
                <h1>Create GR</h1>
            </div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="page-header float-right">
            <div class="page-title">
                <ol class="breadcrumb text-right">
                    <li><a href="{{url('/dash')}}">Dashboard</a></li>
                    <li><a href="{{url('/gr')}}">GR List</a></li>
                    <li class="active">Create GR</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content mt-3">
<div class="animated fadeIn">
<div class="row">
<div class="col-md-12">
<div class="card">
    <div class="card-header">
        <strong>Create GR</strong> &mdash; {{ $office }} &mdash; {{ $newGrNo }}
    </div>
    <div class="card-body">

        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(\Session::has('success'))
        <div class="alert alert-success">
            <p>{{\Session::get('success')}}</p>
        </div>
        @endif

        <form method="POST" action="{{ route('gr.store') }}" id="grForm">
            @csrf

            <!-- PAYMENT TYPE (first) -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="font-weight-bold">Payment Type *</label>
                    <div class="mt-1">
                        <label class="mr-4"><input type="radio" name="to_pay" value="1" {{ old('to_pay','1')=='1'?'checked':'' }}> TO PAY</label>
                        <label><input type="radio" name="to_pay" value="0" {{ old('to_pay')=='0'?'checked':'' }}> PAID</label>
                    </div>
                    <input type="hidden" name="paid" id="paid_hidden" value="{{ old('to_pay','1')=='0'?'1':'0' }}">
                </div>
            </div>

            <div class="row">
                <!-- LEFT COLUMN: Party + Goods -->
                <div class="col-md-7">

                    <!-- Date + Destinations -->
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Date *</label>
                            <input type="date" name="copy_date" class="form-control" value="{{ old('copy_date', \Carbon\Carbon::now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label>From *</label>
                            @if(isset($branches) && count($branches) > 0)
                            <select name="from_dest" id="from_dest" class="form-control" required onchange="onOfficeChange(this.value)">
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->branch_name }}" {{ $office == $branch->branch_name ? 'selected' : '' }}>
                                        {{ $branch->branch_name }}
                                    </option>
                                @endforeach
                            </select>
                            @else
                            <input type="text" class="form-control" value="{{ $office }}" readonly>
                            <input type="hidden" name="from_dest" value="{{ $office }}">
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label>To *</label>
                            <select name="to_dest" id="to_dest" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($destinations ?? [] as $dest)
                                @if($dest !== $office)
                                <option value="{{ $dest }}" {{ old('to_dest')==$dest?'selected':'' }}>{{ $dest }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- CONSIGNOR -->
                    <div class="row mb-2">
                        <div class="col-md-5">
                            <label>Consignor GST No</label>
                            <input type="text" name="consignor_gst_no" id="consignor_gst" class="form-control" value="{{ old('consignor_gst_no') }}" placeholder="Enter GST number">
                        </div>
                        <div class="col-md-5">
                            <label>Consignor Name *</label>
                            <input type="text" name="consignor" id="consignor_name" class="form-control" value="{{ old('consignor') }}">
                            <input type="hidden" name="consignor_id" id="consignor_id" value="{{ old('consignor_id') }}">
                            <input type="hidden" id="consignor_rate_nug" value="0">
                            <input type="hidden" id="consignor_rate_kg" value="0">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="{{ url('/consignor/create') }}" target="_blank" class="btn btn-outline-primary btn-sm">+ New</a>
                        </div>
                    </div>

                    <!-- CONSIGNEE -->
                    <div class="row mb-2">
                        <div class="col-md-5">
                            <label>Consignee GST No</label>
                            <input type="text" name="consignee_gst_no" id="consignee_gst" class="form-control" value="{{ old('consignee_gst_no') }}" placeholder="Enter GST number">
                        </div>
                        <div class="col-md-5">
                            <label>Consignee Name *</label>
                            <input type="text" name="consignee" id="consignee_name" class="form-control" value="{{ old('consignee') }}">
                            <input type="hidden" name="consignee_id" id="consignee_id" value="{{ old('consignee_id') }}">
                            <input type="hidden" id="consignee_rate_nug" value="0">
                            <input type="hidden" id="consignee_rate_kg" value="0">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="{{ url('/consignee/create') }}" target="_blank" class="btn btn-outline-primary btn-sm">+ New</a>
                        </div>
                    </div>

                    <hr>

                    <!-- GOODS -->
                    <div class="row mb-2">
                        <div class="col-md-3">
                            <label>Nugs *</label>
                            <input type="number" name="nugs" id="nugs" class="form-control" value="{{ old('nugs') }}" required min="1">
                        </div>
                        <div class="col-md-3">
                            <label>Method *</label>
                            <select name="meth" id="meth_select" class="form-control" required onchange="toggleOtherMeth()">
                                <option value="">--</option>
                                @foreach(['Box','Bag','Bundle','Loose','Drum','Carton','Pkt','Roll','Other'] as $m)
                                <option value="{{ $m }}" {{ old('meth')==$m?'selected':'' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="meth_other" id="meth_other" class="form-control mt-1" placeholder="Specify other method" value="{{ old('meth_other') }}" style="display:none;">
                        </div>
                        <div class="col-md-3">
                            <label>Weight (kg)</label>
                            <input type="number" name="weight" id="weight" class="form-control" value="{{ old('weight') }}" step="0.001" min="0">
                        </div>
                        <div class="col-md-3">
                            <label>PM *</label>
                            <input type="text" name="pm" class="form-control" value="{{ old('pm') }}" required>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <label>Description *</label>
                            <input type="text" name="description" class="form-control" value="{{ old('description') }}" required>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Rate Type</label>
                            <div class="mt-1">
                                <label class="mr-3"><input type="radio" name="rate_type" value="by_nugs" id="rate_by_nugs" {{ old('rate_type','by_nugs')=='by_nugs'?'checked':'' }}> By Nugs</label>
                                <label><input type="radio" name="rate_type" value="by_weight" id="rate_by_weight" {{ old('rate_type')=='by_weight'?'checked':'' }}> By Weight</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>Rate (&#8377;)</label>
                            <input type="number" name="rate" id="rate" class="form-control" value="{{ old('rate', 0) }}" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label>Freight * (auto, editable)</label>
                            <input type="number" name="frieght_amount" id="freight" class="form-control" value="{{ old('frieght_amount', 0) }}" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>E-Way Bill No</label>
                            <input type="text" name="eway_bill_number" class="form-control" value="{{ old('eway_bill_number') }}">
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: Charges -->
                <div class="col-md-5">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="font-weight-bold mb-3">Charges</h6>

                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label">Surcharge</label>
                                <div class="col-sm-7"><input type="number" name="sur_ch" class="form-control charge-input" value="{{ old('sur_ch', 0) }}" step="0.01" min="0"></div>
                            </div>
                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label">Labour</label>
                                <div class="col-sm-7"><input type="number" name="labour" class="form-control charge-input" value="{{ old('labour', 0) }}" step="0.01" min="0"></div>
                            </div>
                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label">DD (Door Delivery)</label>
                                <div class="col-sm-7"><input type="number" name="dd" class="form-control charge-input" value="{{ old('dd', 0) }}" step="0.01" min="0"></div>
                            </div>
                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label">Local Charge</label>
                                <div class="col-sm-7"><input type="number" name="c_r" class="form-control charge-input" value="{{ old('c_r', 0) }}" step="0.01" min="0"></div>
                            </div>
                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label">BC</label>
                                <div class="col-sm-7"><input type="number" name="bc_amount" class="form-control charge-input" value="{{ old('bc_amount', 0) }}" step="0.01" min="0"></div>
                            </div>
                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label">Other</label>
                                <div class="col-sm-7"><input type="number" name="other" class="form-control charge-input" value="{{ old('other', 0) }}" step="0.01" min="0"></div>
                            </div>

                            <hr>

                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label font-weight-bold">Bill Amount *</label>
                                <div class="col-sm-7"><input type="number" name="bill_amount" class="form-control" value="{{ old('bill_amount', 0) }}" step="0.01" min="0" required></div>
                            </div>

                            <hr>

                            <div class="form-group row mb-0">
                                <label class="col-sm-5 col-form-label font-weight-bold" style="font-size:16px">TOTAL &#8377;</label>
                                <div class="col-sm-7">
                                    <input type="text" id="total_display" class="form-control font-weight-bold" style="font-size:16px; background:#e8f5e9" readonly value="0.00">
                                    <input type="hidden" name="total_amount" id="total_amount" value="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-block btn-lg mt-3" id="submit-btn">
                        <i class="fa fa-save"></i> SAVE GR
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>
</div>
</div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle "Other" method text input
    toggleOtherMeth();

    // Payment type toggle
    document.querySelectorAll('input[name="to_pay"]').forEach(function(el) {
        el.addEventListener('change', function() {
            document.getElementById('paid_hidden').value = this.value == '0' ? '1' : '0';
            updateRate();
        });
    });

    // GST auto-fetch for consignor
    document.getElementById('consignor_gst').addEventListener('blur', function() {
        fetchCustomerByGst(this.value, 'consignor');
    });

    // GST auto-fetch for consignee
    document.getElementById('consignee_gst').addEventListener('blur', function() {
        fetchCustomerByGst(this.value, 'consignee');
    });

    function fetchCustomerByGst(gst, type) {
        if (!gst || gst.length < 5) return;
        fetch('/customer/fetch-by-gst?gst_no=' + encodeURIComponent(gst))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.found) {
                    document.getElementById(type + '_name').value = data.name;
                    document.getElementById(type + '_id').value = data.id;
                    document.getElementById(type + '_rate_nug').value = data.rate_per_nug || 0;
                    document.getElementById(type + '_rate_kg').value = data.rate_per_kg || 0;
                    updateRate();
                } else {
                    document.getElementById(type + '_name').value = '';
                    document.getElementById(type + '_id').value = '';
                }
            });
    }

    // Rate + Freight calculation
    function updateRate() {
        var toPayEl = document.querySelector('input[name="to_pay"]:checked');
        if (!toPayEl) return;
        var toPay = toPayEl.value;
        var rateNug, rateKg;

        if (toPay == '1') {
            // TO PAY = consignee pays, rate from consignee
            rateNug = parseFloat(document.getElementById('consignee_rate_nug').value) || 0;
            rateKg = parseFloat(document.getElementById('consignee_rate_kg').value) || 0;
        } else {
            // PAID = consignor pays, rate from consignor
            rateNug = parseFloat(document.getElementById('consignor_rate_nug').value) || 0;
            rateKg = parseFloat(document.getElementById('consignor_rate_kg').value) || 0;
        }

        var rateTypeEl = document.querySelector('input[name="rate_type"]:checked');
        if (!rateTypeEl) return;
        var rateType = rateTypeEl.value;
        var rate = (rateType === 'by_nugs') ? rateNug : rateKg;
        document.getElementById('rate').value = rate.toFixed(2);

        calculateFreight();
    }

    function calculateFreight() {
        var rateTypeEl = document.querySelector('input[name="rate_type"]:checked');
        if (!rateTypeEl) return;
        var rateType = rateTypeEl.value;
        var rate = parseFloat(document.getElementById('rate').value) || 0;
        var nugs = parseInt(document.getElementById('nugs').value) || 0;
        var weight = parseFloat(document.getElementById('weight').value) || 0;

        var freight;
        if (rateType === 'by_nugs') {
            freight = nugs * rate;
        } else {
            freight = weight * rate;
        }

        document.getElementById('freight').value = freight.toFixed(2);
        calculateTotal();
    }

    function calculateTotal() {
        var freight = parseFloat(document.getElementById('freight').value) || 0;
        var surcharge = parseFloat(document.querySelector('input[name="sur_ch"]').value) || 0;
        var labour = parseFloat(document.querySelector('input[name="labour"]').value) || 0;
        var dd = parseFloat(document.querySelector('input[name="dd"]').value) || 0;
        var localCharge = parseFloat(document.querySelector('input[name="c_r"]').value) || 0;
        var bc = parseFloat(document.querySelector('input[name="bc_amount"]').value) || 0;
        var other = parseFloat(document.querySelector('input[name="other"]').value) || 0;

        var total = freight + surcharge + labour + dd + localCharge + bc + other;
        document.getElementById('total_display').value = total.toFixed(2);
        document.getElementById('total_amount').value = total.toFixed(2);
    }

    // Event listeners for auto-calculation
    document.getElementById('nugs').addEventListener('input', calculateFreight);
    document.getElementById('weight').addEventListener('input', calculateFreight);
    document.getElementById('rate').addEventListener('input', function() { calculateFreight(); });
    document.querySelectorAll('input[name="rate_type"]').forEach(function(el) {
        el.addEventListener('change', updateRate);
    });
    document.querySelectorAll('.charge-input').forEach(function(el) {
        el.addEventListener('input', calculateTotal);
    });
    document.getElementById('freight').addEventListener('input', calculateTotal);

    // Initial calculation
    calculateTotal();

    // Form submission guard
    var submitted = false;
    document.getElementById('grForm').addEventListener('submit', function(e) {
        if (submitted) {
            e.preventDefault();
            return false;
        }
        submitted = true;
        document.getElementById('submit-btn').disabled = true;
        document.getElementById('submit-btn').innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
        return true;
    });
});

function toggleOtherMeth() {
    var sel = document.getElementById('meth_select');
    var other = document.getElementById('meth_other');
    if (sel.value === 'Other') {
        other.style.display = 'block';
        other.required = true;
    } else {
        other.style.display = 'none';
        other.required = false;
        other.value = '';
    }
}

// SuperAdmin: Update GR number when office is changed
function onOfficeChange(office) {
    if (!office) return;

    fetch('/gr/next-number/' + encodeURIComponent(office))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            // Update header display if needed
        })
        .catch(function() {});

    // Update To dropdown — remove selected office from destinations
    var toDest = document.getElementById('to_dest');
    if (toDest) {
        var options = toDest.querySelectorAll('option');
        options.forEach(function(opt) {
            if (opt.value === '') return;
            opt.style.display = (opt.value === office) ? 'none' : '';
            if (opt.value === office && opt.selected) {
                toDest.value = '';
            }
        });
    }
}
</script>

@endsection
