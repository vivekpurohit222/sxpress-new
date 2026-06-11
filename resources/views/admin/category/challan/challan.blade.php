@extends('admin.layout.master')
@section('content')

<style>
    .challan-form {
        font-size: 12px;
    }
    .challan-form .form-group {
        margin-bottom: 6px;
    }
    .challan-form label {
        margin-bottom: 1px;
        font-size: 11px;
    }
    .challan-form .form-control {
        padding: 3px 6px;
        font-size: 12px;
        height: 28px;
    }
    .challan-form textarea.form-control {
        height: 50px;
        resize: none;
    }
    .challan-form table {
        font-size: 11px;
        margin-bottom: 6px;
    }
    .challan-form table th,
    .challan-form table td {
        padding: 3px 5px;
        vertical-align: middle;
    }
    .challan-form table .form-control {
        height: 24px;
        padding: 2px 4px;
        font-size: 11px;
    }
    .challan-form .btn {
        padding: 5px 20px;
        font-size: 12px;
    }
    .challan-form .card {
        margin-bottom: 0;
    }
    .challan-form .card-body {
        padding: 6px 12px;
    }
    .challan-form .card-header {
        padding: 6px 15px;
    }
    .challan-form .content {
        margin-top: 0.5rem;
    }
    .challan-form .breadcrumbs {
        padding: 0.4rem 0;
        margin-bottom: 0;
    }
    .challan-form .alert {
        padding: 6px 12px;
        margin-bottom: 6px;
        font-size: 11px;
    }
    .challan-form .table-bordered th,
    .challan-form .table-bordered td {
        border: 1px solid #ccc;
    }
    .challan-form .t-c {
        font-size: 9px;
        line-height: 1.3;
        color: #666;
    }
    .gr-item-row:hover {
        background: #f8f9fa;
    }
    .gr-item-row .remove-btn {
        color: #dc3545;
        cursor: pointer;
        font-size: 16px;
        line-height: 1;
    }
    body {
        overflow-x: hidden;
    }
</style>

<div class="breadcrumbs">
    <div class="col-sm-4">
        <div class="page-header float-left">
            <div class="page-title">
                <h1>Challan</h1>
            </div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="page-header float-right">
            <div class="page-title">
                <ol class="breadcrumb text-right">
                    <li><a href="{{url('/dash')}}">Dashboard</a></li>
                    <li><a href="{{url('/challan')}}">Challan List</a></li>
                    <li class="active">Create Challan</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content mt-2 challan-form">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header" style="padding:6px 15px">
                        <div class="row">
                            <div class="col-6">
                                <strong class="card-title">Subject to Rajkot Jurisdiction</strong>
                            </div>
                            <div class="col-6 text-right">
                                <strong class="card-title">GST No.: 24AFSPJ7382P1ZI</strong>
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="padding:8px 15px">
                        <div class="card-title">
                            <h3 class="text-center" style="font-size:14px;margin:0">SAURASHTRA EXPRESS</h3>
                            <p style="text-align:center;font-size:10px;margin:1px 0 0">H. O. :- 2- Patel Nagar, Bhoja Bhagat Street, 50ft Ring Road, Rajkot. <br>Contact No. : 097279 00008, 93750 88088</p>
                        </div>
                        <hr style="margin:6px 0">

                        @if(Session::has('success'))
                        <div class="alert alert-success py-1 mb-2">{{Session::get('success')}}</div>
                        @endif
                        @if($errors->any())
                        <div class="alert alert-danger py-1 mb-2">
                            @foreach($errors->all() as $e)<p class="mb-0">{{$e}}</p>@endforeach
                        </div>
                        @endif

                        {{-- Challan Header Form --}}
                        <form action="{{ route('challan.store') }}" method="POST" id="challan-form">
                            @csrf
                            <input type="hidden" name="challan_no" value="{{ $challanNo ?? '' }}">

                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="mb-0"><strong>Office</strong></label>
                                        <input type="text" class="form-control" value="{{ $office }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="mb-0"><strong>Challan No.</strong></label>
                                        <input type="text" class="form-control" value="{{ $challanNo ?? '' }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="mb-0"><strong>Date</strong></label>
                                        <input name="challan_date" type="text" value="{{$date}}" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="mb-0"><strong>From</strong></label>
                                        <input type="text" class="form-control" value="{{ $office }}" readonly>
                                        <input type="hidden" name="from_dest" value="{{ $office }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="mb-0"><strong>To</strong></label>
                                        <select name="to_dest" class="form-control" required>
                                            <option value="">Select</option>
                                            @foreach($destinations as $dest)
                                                @if($dest !== $office)
                                                    <option value="{{ $dest }}">{{ $dest }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="mb-0"><strong>Vehicle</strong></label>
                                        <select name="vehicle_id" class="form-control" required>
                                            <option value="">Select Vehicle</option>
                                            @foreach($vehicles as $v)
                                                <option value="{{ $v->id }}">{{ $v->vehicle_number }} ({{ $v->vehicle_type }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="mb-0"><strong>Driver</strong></label>
                                        <select name="driver_id" class="form-control" required>
                                            <option value="">Select Driver</option>
                                            @foreach($drivers as $d)
                                                <option value="{{ $d->id }}">{{ $d->driver_name }} ({{ $d->license }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- GR Auto-Suggest Section --}}
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="card" style="background:#f8f9fa">
                                        <div class="card-body" style="padding:8px">
                                            <div class="row">
                                                <div class="col-md-5" style="position:relative">
                                                    <label class="mb-0"><strong>Add GR</strong></label>
                                                    <input type="text" id="gr-search-input" class="form-control" placeholder="Start typing GR no, consignor or consignee..." autocomplete="off">
                                                    <div id="gr-search-results" style="position:absolute;left:15px;right:15px;top:100%;z-index:1050;background:#fff;border:1px solid #ddd;border-radius:0 0 4px 4px;max-height:200px;overflow-y:auto;display:none;box-shadow:0 4px 12px rgba(0,0,0,0.15)"></div>
                                                </div>
                                                <div class="col-md-7 d-flex align-items-end">
                                                    <small class="text-muted">Type at least 2 characters — suggestions will appear automatically</small>
                                                </div>
                                            </div>
                                            <div id="gr-not-found" class="text-danger mt-1" style="font-size:11px;display:none">No matching GR found. Check the number or try consignor/consignee name.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- GR Items Table --}}
                            <table class="table table-bordered table-sm mt-2" id="gr-items-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:12%">GR No.</th>
                                        <th style="width:6%">Nugs</th>
                                        <th style="width:8%">Meth.</th>
                                        <th style="width:25%">Description</th>
                                        <th style="width:8%">Weight</th>
                                        <th style="width:8%">Freight</th>
                                        <th style="width:6%">Sur.Ch.</th>
                                        <th style="width:6%">C/R</th>
                                        <th style="width:6%">Other</th>
                                        <th style="width:8%">Total</th>
                                        <th style="width:5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="gr-items-body">
                                    <tr id="no-items-row">
                                        <td colspan="11" class="text-center text-muted" style="font-size:11px">No items added. Search GR above to add.</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="row">
                                <div class="col-md-6">
                                    <textarea name="note" rows="2" class="form-control" placeholder="Note / Remarks" style="height:40px"></textarea>
                                </div>
                                <div class="col-md-3 text-center">
                                    <small class="t-c">T&C: (1) Not responsible after 6 months. (2) No delivery without Consignee copy. (3) No responsibility for damage/theft.</small>
                                </div>
                                <div class="col-md-3 text-right">
                                    <button type="submit" id="submit-challan" class="btn btn-success btn-lg px-5" disabled>
                                        <span>Submit Challan</span>
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
var addedItems = [];
var searchTimer = null;

function renderItemsTable() {
    var tbody = document.getElementById('gr-items-body');
    var submitBtn = document.getElementById('submit-challan');

    if (addedItems.length === 0) {
        tbody.innerHTML = '<tr id="no-items-row"><td colspan="11" class="text-center text-muted" style="font-size:11px">No items added. Type a GR number above to add.</td></tr>';
        submitBtn.disabled = true;
        return;
    }

    submitBtn.disabled = false;
    tbody.innerHTML = '';

    addedItems.forEach(function(item, index) {
        var tr = document.createElement('tr');
        tr.className = 'gr-item-row';
        tr.innerHTML = '<td>' + item.gr_no + '<input type="hidden" name="items[' + index + '][gr_no]" value="' + item.gr_no + '"></td>' +
            '<td>' + item.nugs + '<input type="hidden" name="items[' + index + '][nugs]" value="' + item.nugs + '"></td>' +
            '<td>' + item.meth + '<input type="hidden" name="items[' + index + '][meth]" value="' + item.meth + '"></td>' +
            '<td>' + item.description + '<input type="hidden" name="items[' + index + '][description]" value="' + item.description + '"></td>' +
            '<td>' + item.weight + '<input type="hidden" name="items[' + index + '][weight]" value="' + item.weight + '"></td>' +
            '<td>' + item.frieght_amount + '</td>' +
            '<td>' + item.sur_ch + '</td>' +
            '<td>' + item.c_r + '</td>' +
            '<td>' + item.other + '</td>' +
            '<td>' + item.total_amount + '</td>' +
            '<td><span class="remove-btn" onclick="removeItem(' + index + ')">&times;</span></td>';
        tbody.appendChild(tr);
    });
}

function removeItem(index) {
    addedItems.splice(index, 1);
    renderItemsTable();
}

function addGrToChallan(gr) {
    for (var i = 0; i < addedItems.length; i++) {
        if (addedItems[i].gr_no === gr.gr_no) {
            alert('GR ' + gr.gr_no + ' is already added!');
            return;
        }
    }

    addedItems.push({
        gr_no: gr.gr_no,
        nugs: gr.nugs || 0,
        meth: gr.meth || '',
        description: gr.description || (gr.consignor + ' → ' + gr.consignee),
        weight: gr.weight || 0,
        frieght_amount: gr.frieght_amount || 0,
        sur_ch: gr.sur_ch || 0,
        c_r: gr.c_r || 0,
        other: gr.other || 0,
        total_amount: gr.total_amount || 0,
    });

    renderItemsTable();
}

// Auto-suggest: triggers on typing (no button needed)
var grInput = document.getElementById('gr-search-input');
var resultsDiv = document.getElementById('gr-search-results');
var notFound = document.getElementById('gr-not-found');

grInput.addEventListener('input', function() {
    var q = this.value.trim();
    clearTimeout(searchTimer);
    notFound.style.display = 'none';

    if (q.length < 2) {
        resultsDiv.style.display = 'none';
        return;
    }

    searchTimer = setTimeout(function() {
        fetch('/gr/autocomplete?q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                resultsDiv.innerHTML = '';

                if (data.length === 0) {
                    resultsDiv.style.display = 'none';
                    notFound.style.display = 'block';
                    return;
                }

                data.forEach(function(gr) {
                    // Skip already-added GRs
                    for (var i = 0; i < addedItems.length; i++) {
                        if (addedItems[i].gr_no === gr.gr_no) return;
                    }

                    var div = document.createElement('div');
                    div.style.cssText = 'padding:6px 10px;cursor:pointer;border-bottom:1px solid #f0f0f0;font-size:12px';
                    div.innerHTML = '<strong>' + gr.gr_no + '</strong> — ' +
                        '<span style="color:#555">' + (gr.consignor || '') + ' → ' + (gr.consignee || '') + '</span>' +
                        ' <span style="color:#888;font-size:10px">| ' + (gr.from_dest || '') + ' → ' + (gr.to_dest || '') +
                        ' | ₹' + (gr.total_amount || 0) + ' | ' + (gr.weight || 0) + 'kg</span>';

                    div.addEventListener('mouseenter', function() { this.style.background = '#f0f7ff'; });
                    div.addEventListener('mouseleave', function() { this.style.background = '#fff'; });
                    div.addEventListener('click', function() {
                        addGrToChallan(gr);
                        resultsDiv.style.display = 'none';
                        grInput.value = '';
                        grInput.focus();
                    });
                    resultsDiv.appendChild(div);
                });

                if (resultsDiv.children.length > 0) {
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.style.display = 'none';
                    notFound.style.display = 'block';
                }
            })
            .catch(function() {
                resultsDiv.style.display = 'none';
            });
    }, 250);
});

// Hide dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!grInput.contains(e.target) && !resultsDiv.contains(e.target)) {
        resultsDiv.style.display = 'none';
    }
});

// Prevent Enter from submitting form while typing in search
grInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
    }
});

// Form submit validation
document.getElementById('challan-form').addEventListener('submit', function(e) {
    if (addedItems.length === 0) {
        e.preventDefault();
        alert('Please add at least one GR item.');
        return;
    }
});
</script>

@endsection
