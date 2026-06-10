@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4">
        <div class="page-header float-left">
            <div class="page-title"><h1>Edit Challan</h1></div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="page-header float-right">
            <div class="page-title">
                <ol class="breadcrumb text-right">
                    <li><a href="{{ url('/dash') }}">Dashboard</a></li>
                    <li><a href="{{ route('challan.index') }}">Challan</a></li>
                    <li class="active">Edit {{ $challan->challan_no }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-lg-10 offset-lg-1">
                <div class="card">
                    <div class="card-header">
                        <strong>Edit Challan — {{ $challan->challan_no }}</strong>
                    </div>
                    <div class="card-body">

                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                        @endif

                        <form action="{{ route('challan.update', $challan->id) }}" method="POST">
                            @csrf @method('PATCH')

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><strong>Challan No.</strong></label>
                                        <input type="text" class="form-control" value="{{ $challan->challan_no }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><strong>Date</strong></label>
                                        <input type="date" name="challan_date" class="form-control" value="{{ old('challan_date', $challan->challan_date?->format('Y-m-d')) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><strong>From</strong></label>
                                        <input type="text" name="from_dest" class="form-control" value="{{ old('from_dest', $challan->from_dest) }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label><strong>To</strong></label>
                                        <select name="to_dest" class="form-control" required>
                                            @foreach(\App\Models\Branch::active()->orderBy('branch_name')->pluck('branch_name') as $dest)
                                                <option value="{{ $dest }}" {{ old('to_dest', $challan->to_dest) == $dest ? 'selected' : '' }}>{{ $dest }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Vehicle</strong></label>
                                        <select name="vehicle_id" class="form-control" required>
                                            <option value="">Select Vehicle</option>
                                            @foreach($vehicles as $v)
                                                <option value="{{ $v->id }}" {{ old('vehicle_id', $challan->vehicle_id) == $v->id ? 'selected' : '' }}>{{ $v->vehicle_number }} ({{ $v->vehicle_type ?? '' }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>Driver</strong></label>
                                        <select name="driver_id" class="form-control" required>
                                            <option value="">Select Driver</option>
                                            @foreach($drivers as $d)
                                                <option value="{{ $d->id }}" {{ old('driver_id', $challan->driver_id) == $d->id ? 'selected' : '' }}>{{ $d->driver_name }} ({{ $d->license ?? '' }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5><strong>Challan Items</strong></h5>
                            <table class="table table-bordered table-sm" id="items-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th>GR No.</th>
                                        <th>Description</th>
                                        <th>Pkgs</th>
                                        <th>Weight (kg)</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($challan->items as $i => $item)
                                    <tr class="item-row">
                                        <td>
                                            <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                                            <input type="text" name="items[{{ $i }}][gr_no]" class="form-control form-control-sm" value="{{ $item->gr_no }}" required>
                                        </td>
                                        <td><input type="text" name="items[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $item->description }}" required></td>
                                        <td><input type="number" name="items[{{ $i }}][nugs]" class="form-control form-control-sm" value="{{ $item->nugs }}" min="1" required></td>
                                        <td><input type="number" name="items[{{ $i }}][weight]" class="form-control form-control-sm" value="{{ $item->weight }}" step="0.01" min="0.01" required></td>
                                        <td><button type="button" class="btn btn-danger btn-sm remove-item" {{ $challan->items->count() <= 1 ? 'disabled' : '' }}><i class="fa fa-times"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-info btn-sm mb-3" id="add-item-btn"><i class="fa fa-plus"></i> Add Item</button>

                            <hr>
                            <button type="submit" class="btn btn-primary btn-lg btn-block">Update Challan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var itemIndex = {{ $challan->items->count() }};

document.getElementById('add-item-btn').addEventListener('click', function() {
    var tbody = document.querySelector('#items-table tbody');
    var tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = '<td><input type="text" name="items[' + itemIndex + '][gr_no]" class="form-control form-control-sm" required placeholder="GR No."></td>' +
        '<td><input type="text" name="items[' + itemIndex + '][description]" class="form-control form-control-sm" required placeholder="Description"></td>' +
        '<td><input type="number" name="items[' + itemIndex + '][nugs]" class="form-control form-control-sm" min="1" required placeholder="Pkgs"></td>' +
        '<td><input type="number" name="items[' + itemIndex + '][weight]" class="form-control form-control-sm" step="0.01" min="0.01" required placeholder="Weight"></td>' +
        '<td><button type="button" class="btn btn-danger btn-sm remove-item"><i class="fa fa-times"></i></button></td>';
    tbody.appendChild(tr);
    itemIndex++;
    updateRemoveButtons();
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-item')) {
        var rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) {
            e.target.closest('tr').remove();
            updateRemoveButtons();
        }
    }
});

function updateRemoveButtons() {
    var rows = document.querySelectorAll('.item-row');
    document.querySelectorAll('.remove-item').forEach(function(btn) {
        btn.disabled = rows.length <= 1;
    });
}
</script>

@endsection
