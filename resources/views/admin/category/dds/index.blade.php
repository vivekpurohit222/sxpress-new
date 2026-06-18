@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4">
        <div class="page-header float-left">
            <div class="page-title"><h1>Daily Delivery Statement</h1></div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="page-header float-right">
            <div class="page-title">
                <ol class="breadcrumb text-right">
                    <li><a href="{{ url('/dash') }}">Dashboard</a></li>
                    <li class="active">DDS</li>
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
                        <strong class="card-title">Gate Passes — {{ \Carbon\Carbon::parse($date)->format('d-m-Y') }}</strong>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('dds.index') }}" class="mb-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                                </div>
                                @if(auth()->user()->isSuperAdmin())
                                <div class="col-md-3">
                                    <select name="branch" class="form-control">
                                        <option value="">All Branches</option>
                                        @foreach($branches as $key => $val)
                                        <option value="{{ $key }}" {{ request('branch') == $key ? 'selected' : '' }}>{{ $val }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="{{ route('dds.index') }}" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>

                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>GP No.</th>
                                    <th>Date</th>
                                    <th>GR No(s)</th>
                                    <th>Consignor</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Pkgs</th>
                                    <th>Weight</th>
                                    <th>Amount (₹)</th>
                                    <th>Vehicle</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $row)
                                <tr>
                                    <td><strong>{{ $row->gp_no }}</strong></td>
                                    <td>{{ $row->gp_date ? $row->gp_date->format('d-m-Y') : '-' }}</td>
                                    <td>{{ $row->gr_no }}</td>
                                    <td>{{ $row->consignor }}</td>
                                    <td>{{ $row->from_dest }}</td>
                                    <td>{{ $row->to_dest }}</td>
                                    <td>{{ $row->nugs }}</td>
                                    <td>{{ number_format($row->weight ?? 0, 2) }}</td>
                                    <td>{{ number_format($row->total_amount ?? 0, 2) }}</td>
                                    <td>{{ $row->vehicle ? $row->vehicle->vehicle_number : '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="10" class="text-center">No gate passes found for this date</td></tr>
                                @endforelse
                            </tbody>
                            @if($items->count() > 0)
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="6" class="text-right">Totals:</td>
                                    <td>{{ $totalNugs }}</td>
                                    <td>{{ number_format($totalWeight, 2) }}</td>
                                    <td>{{ number_format($totalAmount, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
