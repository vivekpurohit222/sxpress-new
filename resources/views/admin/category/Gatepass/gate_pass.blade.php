@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Create Gate Pass</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('gatepass.index') }}">Gate Pass</a></li>
            <li class="active">Create</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <strong>Delivery Gate Pass — {{ $office }}</strong>
                        <span class="float-right"><span class="badge badge-info">GP No: {{ $gpNo }}</span> &nbsp; {{ $date }}</span>
                    </div>
                    <div class="card-body">

                        @if($errors->any())
                        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                        @endif

                        <form action="{{ route('gatepass.store') }}" method="POST">
                            @csrf

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>From (Origin) <span class="text-danger">*</span></label>
                                        <input type="text" name="from_dest" class="form-control" value="{{ $office }}" readonly placeholder="Origin branch">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>To (Destination) <span class="text-danger">*</span></label>
                                        <select name="to_dest" class="form-control" required>
                                            <option value="">-- Select destination --</option>
                                            @foreach(\App\Models\Branch::active()->orderBy('branch_name')->pluck('branch_name') as $dest)
                                                @if($dest !== $office)
                                                    <option value="{{ $dest }}" {{ old('to_dest') == $dest ? 'selected' : '' }}>{{ $dest }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Date <span class="text-danger">*</span></label>
                                        <input type="date" name="gp_date" class="form-control" value="{{ old('gp_date', now()->format('Y-m-d')) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Vehicle <span class="text-danger">*</span></label>
                                        <select name="vehicle_id" class="form-control" required>
                                            <option value="">-- Select vehicle --</option>
                                            @foreach($vehicles as $v)
                                                <option value="{{ $v->id }}" {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->vehicle_number }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Driver <span class="text-danger">*</span></label>
                                        <select name="driver_id" class="form-control" required>
                                            <option value="">-- Select driver --</option>
                                            @foreach($drivers as $d)
                                                <option value="{{ $d->id }}" {{ old('driver_id') == $d->id ? 'selected' : '' }}>{{ $d->driver_name ?? $d->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <hr style="margin:8px 0">
                            <div class="form-section-title">Select GRs to Dispatch</div>
                            <small class="text-muted d-block mb-2">Search un-dispatched GRs from your office and add them to this gate pass</small>

                            <div class="form-group">
                                <input type="text" id="gr-search" class="form-control" placeholder="Type GR number, consignor or consignee to search...">
                            </div>
                            <div id="gr-results" class="mb-2"></div>

                            <table class="table table-bordered table-sm" id="selected-grs-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th>GR No</th>
                                        <th>Consignor</th>
                                        <th>Consignee</th>
                                        <th>Route</th>
                                        <th>Amount (₹)</th>
                                        <th style="width:50px">Remove</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($preSelectedGr)
                                    <tr data-gr-id="{{ $preSelectedGr->id }}">
                                        <td>{{ $preSelectedGr->gr_no }}<input type="hidden" name="gr_ids[]" value="{{ $preSelectedGr->id }}"></td>
                                        <td>{{ $preSelectedGr->consignor }}</td>
                                        <td>{{ $preSelectedGr->consignee }}</td>
                                        <td>{{ $preSelectedGr->from_dest }} → {{ $preSelectedGr->to_dest }}</td>
                                        <td class="text-right">{{ number_format($preSelectedGr->total_amount, 2) }}</td>
                                        <td><button type="button" class="btn btn-danger btn-sm remove-gr"><i class="fa fa-times"></i></button></td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                            <p id="no-gr-msg" class="text-muted {{ $preSelectedGr ? 'd-none' : '' }}"><em>No GRs selected. Search above to add GRs to this gate pass.</em></p>

                            <div class="form-group">
                                <label>Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2" placeholder="Enter optional remarks or special instructions...">{{ old('remarks') }}</textarea>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-success btn-block">Create Gate Pass</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('gr-search');
    const resultsDiv = document.getElementById('gr-results');
    const tbody = document.querySelector('#selected-grs-table tbody');
    const noMsg = document.getElementById('no-gr-msg');
    let timer;

    searchInput.addEventListener('input', function() {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { resultsDiv.innerHTML = ''; return; }

        timer = setTimeout(() => {
            fetch(`{{ route('gr.autocomplete') }}?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.length === 0) {
                        resultsDiv.innerHTML = '<div class="alert alert-light py-1">No matching GRs found.</div>';
                        return;
                    }
                    let html = '<div class="list-group">';
                    data.forEach(gr => {
                        if (document.querySelector(`tr[data-gr-id="${gr.id}"]`)) return;
                        html += `<a href="#" class="list-group-item list-group-item-action gr-result py-1" data-id="${gr.id}" data-grno="${gr.gr_no}" data-consignor="${gr.consignor}" data-consignee="${gr.consignee}" data-from="${gr.from_dest}" data-to="${gr.to_dest}" data-amount="${gr.total_amount}">
                            <strong>${gr.gr_no}</strong> — ${gr.consignor} → ${gr.consignee} | ${gr.from_dest} → ${gr.to_dest} (₹${Number(gr.total_amount).toFixed(2)})
                        </a>`;
                    });
                    html += '</div>';
                    resultsDiv.innerHTML = html;
                });
        }, 300);
    });

    resultsDiv.addEventListener('click', function(e) {
        const item = e.target.closest('.gr-result');
        if (!item) return;
        e.preventDefault();
        const id = item.dataset.id;
        const row = `<tr data-gr-id="${id}">
            <td>${item.dataset.grno}<input type="hidden" name="gr_ids[]" value="${id}"></td>
            <td>${item.dataset.consignor}</td>
            <td>${item.dataset.consignee}</td>
            <td>${item.dataset.from} → ${item.dataset.to}</td>
            <td class="text-right">${Number(item.dataset.amount).toFixed(2)}</td>
            <td><button type="button" class="btn btn-danger btn-sm remove-gr"><i class="fa fa-times"></i></button></td>
        </tr>`;
        tbody.insertAdjacentHTML('beforeend', row);
        noMsg.classList.add('d-none');
        resultsDiv.innerHTML = '';
        searchInput.value = '';
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-gr')) {
            e.target.closest('tr').remove();
            if (tbody.children.length === 0) noMsg.classList.remove('d-none');
        }
    });
});
</script>
@endsection
