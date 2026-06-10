@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4">
        <div class="page-header float-left">
            <div class="page-title"><h1>Edit Gate Pass</h1></div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="page-header float-right">
            <div class="page-title">
                <ol class="breadcrumb text-right">
                    <li><a href="{{ url('/dash') }}">Dashboard</a></li>
                    <li><a href="{{ route('gatepass.index') }}">Gate Pass</a></li>
                    <li class="active">Edit {{ $gp->gp_no }}</li>
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
                        <strong class="card-title">Edit Gate Pass — {{ $gp->gp_no }}</strong>
                    </div>
                    <div class="card-body">

                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                        @endif

                        <form action="{{ route('gatepass.update', $id) }}" method="POST">
                            @csrf
                            @method('PATCH')

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>From</strong></label>
                                        <input type="text" name="from_dest" class="form-control" value="{{ old('from_dest', $gp->from_dest) }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><strong>To</strong></label>
                                        <select name="to_dest" class="form-control" required>
                                            @foreach(\App\Models\Branch::active()->orderBy('branch_name')->pluck('branch_name') as $dest)
                                                <option value="{{ $dest }}" {{ old('to_dest', $gp->to_dest) == $dest ? 'selected' : '' }}>{{ $dest }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Date</strong></label>
                                        <input type="date" name="gp_date" class="form-control" value="{{ old('gp_date', $gp->gp_date?->format('Y-m-d')) }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Vehicle</strong></label>
                                        <select name="vehicle_id" class="form-control" required>
                                            <option value="">Select Vehicle</option>
                                            @foreach($vehicles as $v)
                                                <option value="{{ $v->id }}" {{ old('vehicle_id', $gp->vehicle_id) == $v->id ? 'selected' : '' }}>{{ $v->vehicle_number }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><strong>Driver</strong></label>
                                        <select name="driver_id" class="form-control" required>
                                            <option value="">Select Driver</option>
                                            @foreach($drivers as $d)
                                                <option value="{{ $d->id }}" {{ old('driver_id', $gp->driver_id) == $d->id ? 'selected' : '' }}>{{ $d->driver_name ?? $d->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><strong>Remarks</strong></label>
                                <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $gp->remarks) }}</textarea>
                            </div>

                            <hr>
                            <h5>Linked GRs</h5>
                            @if($gp->grs && $gp->grs->count())
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr><th>GR No</th><th>Consignor</th><th>Consignee</th><th>Status</th><th>Amount</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($gp->grs as $gr)
                                    <tr>
                                        <td><strong>{{ $gr->gr_no }}</strong></td>
                                        <td>{{ $gr->consignor }}</td>
                                        <td>{{ $gr->consignee }}</td>
                                        <td><span class="badge bg-primary">{{ ucfirst($gr->status) }}</span></td>
                                        <td>₹{{ number_format($gr->total_amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @else
                            <p class="text-muted">No GRs linked.</p>
                            @endif
                            <small class="text-muted">Note: GR linking cannot be changed after creation. To modify, delete this gatepass and create a new one.</small>

                            <hr>
                            <button type="submit" class="btn btn-primary btn-lg btn-block">Update Gate Pass</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
