@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Edit Freight Memo</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('frieghtmemo.index') }}">Freight Memo</a></li><li class="active">Edit</li></ol>
    </div></div></div>
</div>

<div class="content mt-3"><div class="animated fadeIn"><div class="row"><div class="col-lg-10 offset-lg-1"><div class="card">
    <div class="card-header"><strong>Edit Freight Memo — {{ $freight->fm_no }}</strong></div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

        <form action="{{ route('frieghtmemo.update', $id) }}" method="POST">
            @csrf @method('PATCH')

            <div class="row">
                <div class="col-md-3"><div class="form-group"><label><strong>Date</strong></label><input type="date" name="fm_date" class="form-control" value="{{ old('fm_date', $freight->fm_date ? \Carbon\Carbon::parse($freight->fm_date)->format('Y-m-d') : '') }}" required></div></div>
                <div class="col-md-3"><div class="form-group"><label><strong>From</strong></label>
                    <select name="from_dest" class="form-control">@foreach($destinations as $d)<option value="{{ $d }}" {{ old('from_dest', $freight->from_dest) == $d ? 'selected' : '' }}>{{ $d }}</option>@endforeach</select></div></div>
                <div class="col-md-3"><div class="form-group"><label><strong>To</strong></label>
                    <select name="to_dest" class="form-control">@foreach($destinations as $d)<option value="{{ $d }}" {{ old('to_dest', $freight->to_dest) == $d ? 'selected' : '' }}>{{ $d }}</option>@endforeach</select></div></div>
            </div>

            <h6><strong>Entries</strong></h6>
            <table class="table table-bordered table-sm">
                <thead class="thead-light"><tr><th>#</th><th>Description</th><th style="width:25%">Amount (₹)</th></tr></thead>
                <tbody>
                    <tr><td>1</td><td><input type="text" name="entry_1" class="form-control form-control-sm" value="{{ old('entry_1', $freight->entry_1) }}"></td><td><input type="number" name="entry_1_amount" class="form-control form-control-sm" value="{{ old('entry_1_amount', $freight->entry_1_amount) }}" step="0.01"></td></tr>
                    <tr><td>2</td><td><input type="text" name="entry_2" class="form-control form-control-sm" value="{{ old('entry_2', $freight->entry_2) }}"></td><td><input type="number" name="entry_2_amount" class="form-control form-control-sm" value="{{ old('entry_2_amount', $freight->entry_2_amount) }}" step="0.01"></td></tr>
                    <tr><td>3</td><td><input type="text" name="entry_3" class="form-control form-control-sm" value="{{ old('entry_3', $freight->entry_3) }}"></td><td><input type="number" name="entry_3_amount" class="form-control form-control-sm" value="{{ old('entry_3_amount', $freight->entry_3_amount) }}" step="0.01"></td></tr>
                    <tr><td>4</td><td><input type="text" name="entry_4" class="form-control form-control-sm" value="{{ old('entry_4', $freight->entry_4) }}"></td><td><input type="number" name="entry_4_amount" class="form-control form-control-sm" value="{{ old('entry_4_amount', $freight->entry_4_amount) }}" step="0.01"></td></tr>
                </tbody>
            </table>

            <div class="row">
                <div class="col-md-6"><div class="form-group"><label><strong>Note</strong></label><textarea name="note" class="form-control" rows="3">{{ old('note', $freight->note) }}</textarea></div></div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr><th>Truck Freight</th><td><input type="number" name="truck_freight" class="form-control" value="{{ old('truck_freight', $freight->truck_freight) }}" step="0.01" required></td></tr>
                        <tr><th>Commission</th><td><input type="number" name="commission" class="form-control" value="{{ old('commission', $freight->commission) }}" step="0.01"></td></tr>
                        <tr><th>Other Charges</th><td><input type="number" name="other_charges" class="form-control" value="{{ old('other_charges', $freight->other_charges) }}" step="0.01"></td></tr>
                        <tr><th>Extra</th><td><input type="number" name="extra" class="form-control" value="{{ old('extra', $freight->extra) }}" step="0.01"></td></tr>
                        <tr class="table-success"><th>Balance Due</th><td><strong>₹ {{ number_format($freight->balance_due ?? 0, 2) }}</strong></td></tr>
                    </table>
                </div>
            </div>

            <hr>
            <button type="submit" class="btn btn-primary btn-lg btn-block">Update Freight Memo</button>
        </form>
    </div>
</div></div></div></div></div>
@endsection
