@extends('admin.layout.master')
@section('content')
<div class="content mt-3"><div class="animated fadeIn"><div class="row"><div class="col-md-10 offset-md-1"><div class="card">
    <div class="card-header">
        <strong>Freight Memo — {{ $freight->fm_no }}</strong>
        <div class="float-right">
            <a href="{{ route('frieghtmemo.print', $id) }}" class="btn btn-secondary btn-sm"><i class="fa fa-print"></i> Print</a>
            @hasanyrole('SuperAdmin|Admin')
            <a href="{{ route('frieghtmemo.edit', $id) }}" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
            @endhasanyrole
            <a href="{{ route('frieghtmemo.index') }}" class="btn btn-info btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered table-sm">
                    <tr><th width="35%">FM No</th><td><strong>{{ $freight->fm_no }}</strong></td></tr>
                    <tr><th>Date</th><td>{{ $freight->fm_date ? \Carbon\Carbon::parse($freight->fm_date)->format('d M Y') : '-' }}</td></tr>
                    <tr><th>Truck No</th><td><strong>{{ $freight->truck_no }}</strong></td></tr>
                    <tr><th>From → To</th><td>{{ $freight->from_dest }} → {{ $freight->to_dest }}</td></tr>
                    <tr><th>Office</th><td>{{ $freight->office }}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr><th>Truck Freight (Hire)</th><td class="text-right"><strong>₹ {{ number_format($freight->truck_freight ?? 0, 2) }}</strong></td></tr>
                    <tr><th>Commission</th><td class="text-right text-danger">- ₹ {{ number_format($freight->commission ?? 0, 2) }}</td></tr>
                    <tr><th>Other Charges</th><td class="text-right text-danger">- ₹ {{ number_format($freight->other_charges ?? 0, 2) }}</td></tr>
                    <tr><th>Extra</th><td class="text-right text-danger">- ₹ {{ number_format($freight->extra ?? 0, 2) }}</td></tr>
                    <tr class="table-success"><th><strong>Balance Due to Owner</strong></th><td class="text-right"><strong style="font-size:18px">₹ {{ number_format($freight->balance_due ?? 0, 2) }}</strong></td></tr>
                </table>
            </div>
        </div>

        @if($freight->entry_1 || $freight->entry_2 || $freight->entry_3 || $freight->entry_4)
        <h6><strong>Charge Entries</strong></h6>
        <table class="table table-bordered table-sm">
            @if($freight->entry_1)<tr><td>1. {{ $freight->entry_1 }}</td><td class="text-right" width="20%">₹ {{ number_format($freight->entry_1_amount ?? 0, 2) }}</td></tr>@endif
            @if($freight->entry_2)<tr><td>2. {{ $freight->entry_2 }}</td><td class="text-right">₹ {{ number_format($freight->entry_2_amount ?? 0, 2) }}</td></tr>@endif
            @if($freight->entry_3)<tr><td>3. {{ $freight->entry_3 }}</td><td class="text-right">₹ {{ number_format($freight->entry_3_amount ?? 0, 2) }}</td></tr>@endif
            @if($freight->entry_4)<tr><td>4. {{ $freight->entry_4 }}</td><td class="text-right">₹ {{ number_format($freight->entry_4_amount ?? 0, 2) }}</td></tr>@endif
        </table>
        @endif

        @if($freight->note)<div class="mt-3"><strong>Note:</strong><p>{{ $freight->note }}</p></div>@endif
    </div>
</div></div></div></div></div>
@endsection
