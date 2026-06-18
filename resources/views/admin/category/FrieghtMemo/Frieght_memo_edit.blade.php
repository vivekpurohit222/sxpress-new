@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Edit Freight Memo</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li><a href="{{ route('frieghtmemo.index') }}">Freight Memo</a></li><li class="active">Edit</li></ol>
    </div></div></div>
</div>

<div class="content mt-3"><div class="animated fadeIn"><div class="row"><div class="col-lg-11"><div class="card">
    <div class="card-header"><strong>Edit Freight Memo — {{ $freight->fm_no }}</strong> <span class="float-right">Truck: {{ $freight->truck_no }}</span></div>
    <div class="card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

        <form action="{{ route('frieghtmemo.update', $id) }}" method="POST">
            @csrf @method('PATCH')

            <div class="row">
                <div class="col-md-3"><div class="form-group"><label>Date</label><input type="date" name="fm_date" class="form-control" value="{{ old('fm_date', $freight->fm_date ? \Carbon\Carbon::parse($freight->fm_date)->format('Y-m-d') : '') }}" required></div></div>
                <div class="col-md-3"><div class="form-group"><label>From</label>
                    <select name="from_dest" class="form-control">@foreach($destinations as $d)<option value="{{ $d }}" {{ old('from_dest', $freight->from_dest) == $d ? 'selected' : '' }}>{{ $d }}</option>@endforeach</select></div></div>
                <div class="col-md-3"><div class="form-group"><label>To</label>
                    <select name="to_dest" class="form-control">@foreach($destinations as $d)<option value="{{ $d }}" {{ old('to_dest', $freight->to_dest) == $d ? 'selected' : '' }}>{{ $d }}</option>@endforeach</select></div></div>
            </div>

            <div class="row">
                <div class="col-md-7">
                    <div class="form-section-title">Lorry Hire Settlement</div>
                    <table class="table table-bordered">
                        <tr class="table-primary">
                            <th style="width:55%">Total Lorry Hire <span class="text-danger">*</span></th>
                            <td><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                <input type="number" name="truck_freight" id="truck_freight" class="form-control calc" value="{{ old('truck_freight', $freight->truck_freight) }}" step="0.01" min="0" required onfocus="this.select()"></div></td>
                        </tr>
                        <tr><th>(−) Advance Paid</th><td><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                            <input type="number" name="advance" id="advance" class="form-control calc" value="{{ old('advance', $freight->entry_1_amount) }}" step="0.01" min="0" onfocus="this.select()"></div></td></tr>
                        <tr><th>(−) Broker Commission</th><td><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                            <input type="number" name="commission" id="commission" class="form-control calc" value="{{ old('commission', $freight->commission) }}" step="0.01" min="0" onfocus="this.select()"></div></td></tr>
                        <tr><th>(−) Hamali (Load/Unload)</th><td><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                            <input type="number" name="hamali" id="hamali" class="form-control calc" value="{{ old('hamali', $freight->entry_2_amount) }}" step="0.01" min="0" onfocus="this.select()"></div></td></tr>
                        <tr><th>(−) Detention / Halting</th><td><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                            <input type="number" name="detention" id="detention" class="form-control calc" value="{{ old('detention', $freight->entry_3_amount) }}" step="0.01" min="0" onfocus="this.select()"></div></td></tr>
                        <tr><th>(−) TDS</th><td><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                            <input type="number" name="tds" id="tds" class="form-control calc" value="{{ old('tds', $freight->entry_4_amount) }}" step="0.01" min="0" onfocus="this.select()"></div></td></tr>
                        <tr><th>(−) Other Deductions</th><td><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                            <input type="number" name="other_charges" id="other_charges" class="form-control calc" value="{{ old('other_charges', $freight->other_charges) }}" step="0.01" min="0" onfocus="this.select()"></div></td></tr>
                    </table>
                </div>
                <div class="col-md-5">
                    <div class="form-section-title">Summary</div>
                    <table class="table table-bordered">
                        <tr><th>Total Lorry Hire</th><td class="text-right" id="sum_hire">₹ 0.00</td></tr>
                        <tr class="text-danger"><th>Total Deductions</th><td class="text-right" id="sum_deductions">₹ 0.00</td></tr>
                        <tr class="table-success"><th><strong>Balance Payable</strong></th><td class="text-right"><strong id="balance_display" style="font-size:18px">₹ 0.00</strong></td></tr>
                    </table>
                    <div class="form-group mt-2"><label>Note</label><textarea name="note" class="form-control" rows="3">{{ old('note', $freight->note) }}</textarea></div>
                </div>
            </div>

            <hr>
            <button type="submit" class="btn btn-primary btn-lg btn-block">Update Freight Memo</button>
        </form>
    </div>
</div></div></div></div></div>

<script>
function num(id){var v=parseFloat(document.getElementById(id).value);return isNaN(v)?0:v;}
function calcBalance(){
    var hire=num('truck_freight');
    var ded=num('advance')+num('commission')+num('hamali')+num('detention')+num('tds')+num('other_charges');
    document.getElementById('sum_hire').textContent='₹ '+hire.toFixed(2);
    document.getElementById('sum_deductions').textContent='₹ '+ded.toFixed(2);
    var bal=hire-ded;
    document.getElementById('balance_display').textContent='₹ '+bal.toFixed(2);
    document.getElementById('balance_display').style.color=bal>=0?'#155724':'#721c24';
}
document.querySelectorAll('.calc').forEach(function(el){el.addEventListener('input',calcBalance);});
calcBalance();
</script>

@endsection
