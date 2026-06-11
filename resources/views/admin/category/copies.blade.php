@extends('admin.layout.master')
@section('content')

<style>
    .gr-form {
        font-size: 12px;
    }
    .gr-form .form-group {
        margin-bottom: 6px;
    }
    .gr-form label {
        margin-bottom: 1px;
        font-size: 11px;
    }
    .gr-form .form-control {
        padding: 3px 6px;
        font-size: 12px;
        height: 28px;
    }
    .gr-form textarea.form-control {
        height: 52px;
        resize: none;
    }
    .gr-form table {
        font-size: 11px;
        margin-bottom: 6px;
    }
    .gr-form table th,
    .gr-form table td {
        padding: 3px 5px;
        vertical-align: middle;
    }
    .gr-form table .form-control {
        height: 24px;
        padding: 2px 4px;
        font-size: 11px;
    }
    .gr-form .btn {
        padding: 5px 20px;
        font-size: 12px;
    }
    .gr-form .card {
        margin-bottom: 0;
    }
    .gr-form .card-body {
        padding: 6px 12px;
    }
    .gr-form .card-header {
        padding: 6px 15px;
    }
    .gr-form .card-title h3 {
        font-size: 14px;
        margin: 0;
    }
    .gr-form .card-title p {
        font-size: 10px;
        margin: 1px 0 0;
    }
    .gr-form hr {
        margin: 6px 0;
    }
    .gr-form .page-title h1 {
        font-size: 18px;
    }
    .gr-form .page-title {
        line-height: 1.2;
    }
    .gr-form .content {
        margin-top: 0.5rem;
        overflow: hidden;
    }
    .gr-form .breadcrumbs {
        padding: 0.4rem 0;
        margin-bottom: 0;
    }
    .gr-form .content.mt-2 {
        overflow-x: auto;
    }
    body {
        overflow-x: hidden;
    }
    .autocomplete-wrapper {
        position: relative;
    }
    .autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        max-height: 120px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .autocomplete-dropdown.show {
        display: block;
    }
    .autocomplete-item {
        padding: 5px 8px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        font-size: 11px;
    }
    .autocomplete-item:last-child {
        border-bottom: none;
    }
    .autocomplete-item:hover {
        background: #f8f9ff;
    }
    .autocomplete-item .name {
        font-weight: 600;
        color: #333;
    }
    .autocomplete-item .details {
        font-size: 9px;
        color: #888;
    }
    .has-error {
        border-color: #dc3545 !important;
    }
    .gr-form .alert {
        padding: 6px 12px;
        margin-bottom: 6px;
        font-size: 11px;
    }
    .gr-form .table-bordered th,
    .gr-form .table-bordered td {
        border: 1px solid #ccc;
    }
    .gr-form .submit-section {
        margin-top: 6px;
    }
    .gr-form .t-c {
        font-size: 9px;
        line-height: 1.3;
        color: #666;
    }
</style>

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

<div class="content mt-2 gr-form">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-6">
                                <strong class="card-title">Subject to Rajkot Jurisdiction</strong>
                            </div>
                            <div class="col-6">
                                <strong style="float:right" class="card-title">GST No.: 24AFSPJ7382P1ZI</strong>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="pay-invoice">
                            <div class="card-body">
                                <div class="card-title">
                                    <h3 class="text-center">SAURASHTRA EXPRESS</h3>
                                    <p style="text-align: center;">H. O. :- 2- Patel Nagar, Bhoja Bhagat Street, 50ft Ring Road, Rajkot. <br>
                                    Contact No. : 097279 00008, 93750 88088</p>
                                </div>
                                <hr>
                                @if(\Session::has('success'))
                                <div class="alert alert-success">
                                    <p>{{\Session::get('success')}}</p>
                                </div>
                                @endif

                                @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif

                                <form action="{{url('/gr/store')}}" id="gr-form" method="post" novalidate="novalidate">
                                    @csrf
                                    <div class="row">
                                        <div class="col-12 col-lg-4">
                                            <div class="form-group">
                                                <label for="from_dest" class="control-label mb-1"><strong>From (Office)</strong></label>
                                                @if(isset($branches) && count($branches) > 0)
                                                {{-- SuperAdmin: selectable office dropdown --}}
                                                <select name="from_dest" id="from_dest" class="form-control" required onchange="onOfficeChange(this.value)">
                                                    @foreach($branches as $branch)
                                                        <option value="{{ $branch->branch_name }}" {{ $office == $branch->branch_name ? 'selected' : '' }}>
                                                            {{ $branch->branch_name }} ({{ $branch->gr_prefix }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @else
                                                {{-- Staff/Manager/Admin: locked to own office --}}
                                                <input type="text" class="form-control" value="{{ $office }}" readonly>
                                                <input type="hidden" name="from_dest" value="{{ $office }}">
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-4">
                                            <div class="form-group">
                                                <label for="to_dest" class="control-label mb-1"><strong>To</strong></label>
                                                <select name="to_dest" id="to_dest" class="form-control" required>
                                                    <option value="">Select Destination</option>
                                                    @foreach($destinations as $dest)
                                                        @if($dest !== $office)
                                                            <option value="{{ $dest }}" {{ old('to_dest') == $dest ? 'selected' : '' }}>{{ $dest }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-4">
                                            <label for="copy_date" class="control-label mb-1"><strong>Date</strong></label>
                                            <div class="input-group">
                                                <input name="copy_date" type="text" value="{{$date}}" class="form-control" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin-bottom:6px">
                                        <label for="gr_no" class="form-control-label"><strong>GR Number</strong></label>
                                        <input type="text" id="gr_no" name="gr_no" value="{{ $newGrNo ?? '' }}" class="form-control" readonly>
                                    </div>

                                    <!-- Consignor Section with Autocomplete -->
                                    <div class="autocomplete-wrapper">
                                        <div class="form-group">
                                            <label for="consignor" class="form-control-label"><strong>Consignor</strong></label>
                                            <input type="text" id="consignor" name="consignor" placeholder="Start typing to search..." class="form-control" value="{{ old('consignor') }}" autocomplete="off">
                                            <div id="consignor-dropdown" class="autocomplete-dropdown"></div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="consignor_address" class="form-control-label"><strong>Consignor Address</strong></label>
                                        <input type="text" id="consignor_address" name="consignor_address" placeholder="" class="form-control" value="{{ old('consignor_address') }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="consignor_gst_no" class="form-control-label"><strong>Consignor GST No.</strong></label>
                                        <input type="text" id="consignor_gst_no" name="consignor_gst_no" placeholder="" class="form-control" value="{{ old('consignor_gst_no') }}">
                                    </div>

                                    <!-- Consignee Section with Autocomplete -->
                                    <div class="autocomplete-wrapper">
                                        <div class="form-group">
                                            <label for="consignee" class="form-control-label"><strong>Consignee</strong></label>
                                            <input type="text" id="consignee" name="consignee" placeholder="Start typing to search..." class="form-control" value="{{ old('consignee') }}" autocomplete="off">
                                            <div id="consignee-dropdown" class="autocomplete-dropdown"></div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="consignee_address" class="form-control-label"><strong>Consignee Address</strong></label>
                                        <input type="text" id="consignee_address" name="consignee_address" placeholder="" class="form-control" value="{{ old('consignee_address') }}">
                                    </div>

                                    <div class="form-group">
                                        <label for="consignee_gst_no" class="form-control-label"><strong>Consignee GST No.</strong></label>
                                        <input type="text" id="consignee_gst_no" name="consignee_gst_no" placeholder="" class="form-control" value="{{ old('consignee_gst_no') }}">
                                    </div>

                                    <table class="table table-bordered table-sm">
                                        <tbody>
                                            <tr>
                                                <td style="width:8%"><input class="form-control" type="number" name="nugs" value="{{ old('nugs') }}" required placeholder="Nugs"></td>
                                                <td style="width:10%">
                                                    <select name="meth" id="meth" class="form-control" required>
                                                        <option value="">Meth</option>
                                                        <option value="Bag" {{ old('meth') == 'Bag' ? 'selected' : '' }}>Bag</option>
                                                        <option value="Box" {{ old('meth') == 'Box' ? 'selected' : '' }}>Box</option>
                                                        <option value="Bundle" {{ old('meth') == 'Bundle' ? 'selected' : '' }}>Bundle</option>
                                                        <option value="Drum" {{ old('meth') == 'Drum' ? 'selected' : '' }}>Drum</option>
                                                        <option value="Roll" {{ old('meth') == 'Roll' ? 'selected' : '' }}>Roll</option>
                                                        <option value="Carton" {{ old('meth') == 'Carton' ? 'selected' : '' }}>Carton</option>
                                                        <option value="Loose" {{ old('meth') == 'Loose' ? 'selected' : '' }}>Loose</option>
                                                        <option value="Other" {{ old('meth') == 'Other' ? 'selected' : '' }}>Other</option>
                                                    </select>
                                                </td>
                                                <td style="width:30%"><textarea name="description" id="description" rows="2" class="form-control" style="height:36px;resize:none" placeholder="Description">{{ old('description') }}</textarea></td>
                                                <td style="width:10%"><input class="form-control" type="text" name="pm" value="{{ old('pm') }}" placeholder="PM"></td>
                                                <td style="width:10%"><input class="form-control" type="text" name="weight" value="{{ old('weight') }}" placeholder="Weight"></td>
                                                <td style="width:8%"><input class="form-control sum" type="number" name="frieght_amount" value="{{ old('frieght_amount') }}" placeholder="Freight"></td>
                                                <td style="width:6%"><input class="form-control sum" type="number" name="sur_ch" value="{{ old('sur_ch') }}" placeholder="Sur.Ch"></td>
                                                <td style="width:6%"><input class="form-control sum" type="number" name="c_r" value="{{ old('c_r') }}" placeholder="C/R"></td>
                                                <td style="width:6%"><input class="form-control sum" type="number" name="other" value="{{ old('other') }}" placeholder="Other"></td>
                                                <td style="width:6%"><input class="form-control sum" type="number" name="bc_amount" value="{{ old('bc_amount') }}" placeholder="BC"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                                    Bill: <input class="form-control" type="number" name="bill_amount" value="{{ old('bill_amount') }}" style="width:80px;display:inline" placeholder="0">
                                                </td>
                                                <td colspan="2">
                                                    E-Way: <input type="text" class="form-control" name="eway_bill_number" value="{{ old('eway_bill_number') }}" style="width:120px;display:inline" placeholder="E-Way Bill No.">
                                                </td>
                                                <td colspan="2" class="text-center">
                                                    To Pay: <input id="to_pay_checkbox" type="checkbox" value="1" name="to_pay">
                                                    &nbsp; Paid: <input id="paid_checkbox" type="checkbox" value="1" name="paid">
                                                </td>
                                                <td colspan="2">
                                                    Total: <input id="totalsum" class="form-control" type="number" name="total_amount" value="{{ old('total_amount') }}" readonly style="background:#eee;width:80px;display:inline" placeholder="0">
                                                </td>
                                                <td>{{$office}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="10">
                                                    <small class="t-c">T&C: (1) Not responsible after 6 months. (2) No delivery without Consignee copy. (3) No responsibility for damage/theft in transit. (4) Receipt without date cancelled.</small>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <div>
                                        <button id="payment-button" type="submit" class="btn btn-success btn-lg btn-block">
                                            <span id="payment-button-amount">Submit</span>
                                            <span id="payment-button-sending" style="display:none;">Sending…</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Autocomplete functionality for consignor
(function() {
    const consignorInput = document.getElementById('consignor');
    const consignorDropdown = document.getElementById('consignor-dropdown');
    const consignorAddress = document.getElementById('consignor_address');
    const consignorGst = document.getElementById('consignor_gst_no');

    let timeout = null;

    consignorInput.addEventListener('input', function() {
        const q = this.value.trim();
        clearTimeout(timeout);

        if (q.length < 2) {
            consignorDropdown.classList.remove('show');
            return;
        }

        timeout = setTimeout(function() {
            fetch('/gr/autocomplete/consignor?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    consignorDropdown.innerHTML = '';
                    if (data.length === 0) {
                        consignorDropdown.classList.remove('show');
                        return;
                    }
                    data.forEach(function(item) {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerHTML = '<div class="name">' + item.name + '</div><div class="details">' + (item.address || '') + ' ' + (item.gst_no || '') + '</div>';
                        div.addEventListener('click', function() {
                            consignorInput.value = item.name;
                            consignorAddress.value = item.address || '';
                            consignorGst.value = item.gst_no || '';
                            consignorDropdown.classList.remove('show');
                        });
                        consignorDropdown.appendChild(div);
                    });
                    consignorDropdown.classList.add('show');
                });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!consignorInput.contains(e.target) && !consignorDropdown.contains(e.target)) {
            consignorDropdown.classList.remove('show');
        }
    });
})();

// Autocomplete functionality for consignee
(function() {
    const consigneeInput = document.getElementById('consignee');
    const consigneeDropdown = document.getElementById('consignee-dropdown');
    const consigneeAddress = document.getElementById('consignee_address');
    const consigneeGst = document.getElementById('consignee_gst_no');

    let timeout = null;

    consigneeInput.addEventListener('input', function() {
        const q = this.value.trim();
        clearTimeout(timeout);

        if (q.length < 2) {
            consigneeDropdown.classList.remove('show');
            return;
        }

        timeout = setTimeout(function() {
            fetch('/gr/autocomplete/consignee?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    consigneeDropdown.innerHTML = '';
                    if (data.length === 0) {
                        consigneeDropdown.classList.remove('show');
                        return;
                    }
                    data.forEach(function(item) {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-item';
                        div.innerHTML = '<div class="name">' + item.name + '</div><div class="details">' + (item.address || '') + ' ' + (item.gst_no || '') + '</div>';
                        div.addEventListener('click', function() {
                            consigneeInput.value = item.name;
                            consigneeAddress.value = item.address || '';
                            consigneeGst.value = item.gst_no || '';
                            consigneeDropdown.classList.remove('show');
                        });
                        consigneeDropdown.appendChild(div);
                    });
                    consigneeDropdown.classList.add('show');
                });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!consigneeInput.contains(e.target) && !consigneeDropdown.contains(e.target)) {
            consigneeDropdown.classList.remove('show');
        }
    });
})();

// Auto-calculate total
(function() {
    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.sum').forEach(function(input) {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('totalsum').value = total.toFixed(2);
    }

    document.querySelectorAll('.sum').forEach(function(input) {
        input.addEventListener('input', calculateTotal);
    });
})();

// Paid / To-Pay mutual exclusivity
(function() {
    const paidCb = document.getElementById('paid_checkbox');
    const toPayCb = document.getElementById('to_pay_checkbox');

    function mutualExclusivity() {
        if (this === paidCb && this.checked) {
            toPayCb.checked = false;
        } else if (this === toPayCb && this.checked) {
            paidCb.checked = false;
        }
    }

    paidCb.addEventListener('change', mutualExclusivity);
    toPayCb.addEventListener('change', mutualExclusivity);
})();

// Route rate autofill (per LOGIC_SKILL section 11)
(function() {
    const fromDest = document.getElementById('from_dest');
    const toDest = document.getElementById('to_dest');
    const freightInput = document.getElementById('frieght_amount');
    const weightInput = document.querySelector('input[name="weight"]');

    if (!fromDest || !toDest || !freightInput) return;

    let routeTimeout = null;

    function fetchRouteRate() {
        const from = fromDest.value;
        const to = toDest.value;
        const weight = parseFloat(weightInput?.value) || 0;

        // Skip if either is not set or they are the same
        if (!from || !to || from === to) return;

        clearTimeout(routeTimeout);
        routeTimeout = setTimeout(function() {
            fetch('/route-rate?from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to))
                .then(r => r.json())
                .then(data => {
                    if (data.rate_per_kg && weight > 0) {
                        const suggestedFreight = (weight * parseFloat(data.rate_per_kg)).toFixed(2);
                        freightInput.value = suggestedFreight;
                        // Recalculate total
                        calculateTotal();
                    }
                })
                .catch(function() {
                    // Silently fail - route not found is ok
                });
        }, 500);
    }

    // Listen for to_dest changes and weight changes
    if (toDest) {
        toDest.addEventListener('change', fetchRouteRate);
    }
    if (weightInput) {
        weightInput.addEventListener('input', fetchRouteRate);
    }
})();

// Form submission guard
(function() {
    var form = document.getElementById('gr-form');
    var btn = document.getElementById('payment-button');
    var submitted = false;

    form.addEventListener('submit', function(e) {
        if (submitted) {
            e.preventDefault();
            return false;
        }
        submitted = true;
        btn.disabled = true;
        btn.innerHTML = '<span>Submitting...</span>';
        return true;
    });
})();

// SuperAdmin: Update GR number when office is changed
function onOfficeChange(office) {
    if (!office) return;

    var grInput = document.getElementById('gr_no');
    if (grInput) {
        grInput.value = 'Loading...';
    }

    fetch('/gr/next-number/' + encodeURIComponent(office))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.gr_no && grInput) {
                grInput.value = data.gr_no;
            }
        })
        .catch(function() {
            if (grInput) grInput.value = '??-?????';
        });

    // Also update To dropdown — remove selected office from destinations
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
