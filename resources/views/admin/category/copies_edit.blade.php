@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Edit GR</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{url('/dash')}}">Dashboard</a></li>
            <li><a href="{{url('/gr')}}">GR List</a></li>
            <li class="active">Edit GR</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn">
<div class="row">
<div class="col-md-12">
<div class="card">
    <div class="card-header">
        <strong>Edit GR</strong> &mdash; {{ $gr->gr_no }}
    </div>
    <div class="card-body">

        @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ url('/gr/'.$id.'/update') }}" id="grForm">
            @csrf
            @method('PATCH')

            <!-- PAYMENT TYPE -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="font-weight-bold">Payment Type *</label>
                    <div class="mt-1">
                        <label class="mr-4"><input type="radio" name="to_pay" value="1" {{ old('to_pay', $gr->to_pay ? '1' : '0') == '1' ? 'checked' : '' }}> TO PAY</label>
                        <label><input type="radio" name="to_pay" value="0" {{ old('to_pay', $gr->to_pay ? '1' : '0') == '0' ? 'checked' : '' }}> PAID</label>
                    </div>
                    <input type="hidden" name="paid" id="paid_hidden" value="{{ $gr->paid ? '1' : '0' }}">
                </div>
            </div>

            <div class="row">
                <!-- LEFT COLUMN -->
                <div class="col-md-7">

                    <!-- Date + Destinations -->
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Date</label>
                            <input type="text" class="form-control" value="{{ $gr->copy_date ? $gr->copy_date->format('Y-m-d') : '' }}" readonly>
                            <input type="hidden" name="copy_date" value="{{ $gr->copy_date ? $gr->copy_date->format('Y-m-d') : '' }}">
                        </div>
                        <div class="col-md-4">
                            <label>From</label>
                            <input type="text" class="form-control" value="{{ $gr->from_dest }}" readonly>
                            <input type="hidden" name="from_dest" value="{{ $gr->from_dest }}">
                        </div>
                        <div class="col-md-4">
                            <label>To *</label>
                            <select name="to_dest" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($destinations as $dest)
                                @if($dest !== $gr->from_dest)
                                <option value="{{ $dest }}" {{ old('to_dest', $gr->to_dest)==$dest?'selected':'' }}>{{ $dest }}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- CONSIGNOR -->
                    <div class="row mb-2">
                        <div class="col-md-5">
                            <label>Consignor GST No</label>
                            <input type="text" name="consignor_gst_no" id="consignor_gst" class="form-control" value="{{ old('consignor_gst_no', $gr->consignor_gst_no) }}">
                        </div>
                        <div class="col-md-5">
                            <label>Consignor Name *</label>
                            <input type="text" name="consignor" id="consignor_name" class="form-control" value="{{ old('consignor', $gr->consignor) }}">
                            <input type="hidden" name="consignor_id" id="consignor_id" value="{{ old('consignor_id', $gr->consignor_id) }}">
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
                            <input type="text" name="consignee_gst_no" id="consignee_gst" class="form-control" value="{{ old('consignee_gst_no', $gr->consignee_gst_no) }}">
                        </div>
                        <div class="col-md-5">
                            <label>Consignee Name *</label>
                            <input type="text" name="consignee" id="consignee_name" class="form-control" value="{{ old('consignee', $gr->consignee) }}">
                            <input type="hidden" name="consignee_id" id="consignee_id" value="{{ old('consignee_id', $gr->consignee_id) }}">
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
                            <input type="number" name="nugs" id="nugs" class="form-control" value="{{ old('nugs', $gr->nugs) }}" required min="1">
                        </div>
                        <div class="col-md-3">
                            <label>Method *</label>
                            <select name="meth" id="meth_select" class="form-control" required onchange="toggleOtherMeth()">
                                <option value="">--</option>
                                @foreach(['Box','Bag','Bundle','Loose','Drum','Carton','Pkt','Roll','Other'] as $m)
                                <option value="{{ $m }}" {{ old('meth', $gr->meth)==$m?'selected':'' }}>{{ $m }}</option>
                                @endforeach
                            </select>
                            @php
                                $isOtherMeth = !in_array($gr->meth, ['Box','Bag','Bundle','Loose','Drum','Carton','Pkt','Roll','Other','']);
                            @endphp
                            <input type="text" name="meth_other" id="meth_other" class="form-control mt-1" placeholder="Specify other method" value="{{ old('meth_other', $isOtherMeth ? $gr->meth : '') }}" style="{{ old('meth', $gr->meth)=='Other' || $isOtherMeth ? '' : 'display:none;' }}">
                        </div>
                        <div class="col-md-3">
                            <label>Weight (kg)</label>
                            <input type="number" name="weight" id="weight" class="form-control" value="{{ old('weight', $gr->weight) }}" step="0.001" min="0">
                        </div>
                        <div class="col-md-3">
                            <label>PM *</label>
                            <input type="text" name="pm" class="form-control" value="{{ old('pm', $gr->pm) }}" required>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <label>Description *</label>
                            <input type="text" name="description" class="form-control" value="{{ old('description', $gr->description) }}" required>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>Rate Type</label>
                            <div class="mt-1">
                                <label class="mr-3"><input type="radio" name="rate_type" value="by_nugs" id="rate_by_nugs" {{ old('rate_type', $gr->rate_type ?? 'by_nugs')=='by_nugs'?'checked':'' }}> By Nugs</label>
                                <label><input type="radio" name="rate_type" value="by_weight" id="rate_by_weight" {{ old('rate_type', $gr->rate_type)=='by_weight'?'checked':'' }}> By Weight</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>Rate (&#8377;)</label>
                            <input type="number" name="rate" id="rate" class="form-control" value="{{ old('rate', $gr->rate ?? 0) }}" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label>Freight * (auto, editable)</label>
                            <input type="number" name="frieght_amount" id="freight" class="form-control" value="{{ old('frieght_amount', $gr->frieght_amount) }}" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label>E-Way Bill No</label>
                            <input type="text" name="eway_bill_number" class="form-control" value="{{ old('eway_bill_number', $gr->eway_bill_number) }}">
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
                                <div class="col-sm-7"><input type="number" name="sur_ch" class="form-control charge-input" value="{{ old('sur_ch', $gr->sur_ch) }}" step="0.01" min="0"></div>
                            </div>
                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label">Labour</label>
                                <div class="col-sm-7"><input type="number" name="labour" class="form-control charge-input" value="{{ old('labour', $gr->labour ?? 0) }}" step="0.01" min="0"></div>
                            </div>
                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label">DD (Door Delivery)</label>
                                <div class="col-sm-7"><input type="number" name="dd" class="form-control charge-input" value="{{ old('dd', $gr->dd ?? 0) }}" step="0.01" min="0"></div>
                            </div>
                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label">Local Charge</label>
                                <div class="col-sm-7"><input type="number" name="c_r" class="form-control charge-input" value="{{ old('c_r', $gr->c_r) }}" step="0.01" min="0"></div>
                            </div>
                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label">BC</label>
                                <div class="col-sm-7"><input type="number" name="bc_amount" class="form-control charge-input" value="{{ old('bc_amount', $gr->bc_amount) }}" step="0.01" min="0"></div>
                            </div>
                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label">Other</label>
                                <div class="col-sm-7"><input type="number" name="other" class="form-control charge-input" value="{{ old('other', $gr->other) }}" step="0.01" min="0"></div>
                            </div>

                            <hr>

                            <div class="form-group row mb-2">
                                <label class="col-sm-5 col-form-label font-weight-bold">Bill Amount *</label>
                                <div class="col-sm-7"><input type="number" name="bill_amount" class="form-control" value="{{ old('bill_amount', $gr->bill_amount) }}" step="0.01" min="0" required></div>
                            </div>

                            <hr>

                            <div class="form-group row mb-0">
                                <label class="col-sm-5 col-form-label font-weight-bold" style="font-size:16px">TOTAL &#8377;</label>
                                <div class="col-sm-7">
                                    <input type="text" id="total_display" class="form-control font-weight-bold" style="font-size:16px; background:#e8f5e9" readonly value="{{ number_format($gr->total_amount, 2) }}">
                                    <input type="hidden" name="total_amount" id="total_amount" value="{{ $gr->total_amount }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg mt-3">
                        <i class="fa fa-save"></i> UPDATE GR
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
        });
    });

    // GST auto-fetch
    document.getElementById('consignor_gst').addEventListener('blur', function() {
        fetchCustomerByGst(this.value, 'consignor');
    });
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
                }
            });
    }

    // Freight + Total calculation
    function calculateFreight() {
        var rateTypeEl = document.querySelector('input[name="rate_type"]:checked');
        if (!rateTypeEl) return;
        var rate = parseFloat(document.getElementById('rate').value) || 0;
        var nugs = parseInt(document.getElementById('nugs').value) || 0;
        var weight = parseFloat(document.getElementById('weight').value) || 0;
        var freight = (rateTypeEl.value === 'by_nugs') ? nugs * rate : weight * rate;
        document.getElementById('freight').value = freight.toFixed(2);
        calculateTotal();
    }

    function calculateTotal() {
        var freight = parseFloat(document.getElementById('freight').value) || 0;
        var total = freight;
        document.querySelectorAll('.charge-input').forEach(function(el) {
            total += parseFloat(el.value) || 0;
        });
        document.getElementById('total_display').value = total.toFixed(2);
        document.getElementById('total_amount').value = total.toFixed(2);
    }

    document.getElementById('nugs').addEventListener('input', calculateFreight);
    document.getElementById('weight').addEventListener('input', calculateFreight);
    document.getElementById('rate').addEventListener('input', calculateFreight);
    document.querySelectorAll('input[name="rate_type"]').forEach(function(el) { el.addEventListener('change', calculateFreight); });
    document.querySelectorAll('.charge-input').forEach(function(el) { el.addEventListener('input', calculateTotal); });
    document.getElementById('freight').addEventListener('input', calculateTotal);

    calculateTotal();
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
</script>
@endsection
