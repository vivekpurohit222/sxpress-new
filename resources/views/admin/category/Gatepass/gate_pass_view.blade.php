@extends('admin.layout.master')
@section('content')

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <strong class="card-title">Gatepass Details</strong>
                        <div class="float-right">
                            <a href="{{ route('gatepass.print', $id) }}" class="btn btn-secondary btn-sm">
                                <i class="fa fa-print"></i> Print
                            </a>
                            @can('edit-gatepass', $gp)
                            <a href="{{ route('gatepass.edit', $id) }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            @endcan
                            <a href="{{ route('gatepass.index') }}" class="btn btn-info btn-sm">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Gatepass No</th>
                                        <td><strong>{{ $gp->gp_no }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Date</th>
                                        <td>{{ Carbon\Carbon::parse($gp->gp_date)->format('d M Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>From</th>
                                        <td>{{ $gp->from_dest }}</td>
                                    </tr>
                                    <tr>
                                        <th>To</th>
                                        <td>{{ $gp->to_dest }}</td>
                                    </tr>
                                    <tr>
                                        <th>Vehicle</th>
                                        <td>{{ $gp->vehicle ? $gp->vehicle->vehicle_number : $gp->vehicle_no }}</td>
                                    </tr>
                                    <tr>
                                        <th>Driver</th>
                                        <td>{{ $gp->driver ? $gp->driver->driver_name : $gp->driver_name }}</td>
                                    </tr>
                                    @if($gp->remarks)
                                    <tr>
                                        <th>Remarks</th>
                                        <td>{{ $gp->remarks }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Office</th>
                                        <td>{{ $gp->office }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge badge-{{ $gp->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($gp->status ?? 'Active') }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td>{{ $gp->created_at->format('d M Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated</th>
                                        <td>{{ $gp->updated_at->format('d M Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Linked GRs -->
                        <div class="mt-4">
                            <h5>Linked Goods Receipts (GRs)</h5>
                            @if($gp->grs && $gp->grs->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>GR No</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>Consignee</th>
                                            <th>Weight</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($gp->grs as $gr)
                                        <tr>
                                            <td><strong>{{ $gr->gr_no }}</strong></td>
                                            <td>{{ $gr->from_dest }}</td>
                                            <td>{{ $gr->to_dest }}</td>
                                            <td>{{ Str::limit($gr->consignee, 20) }}</td>
                                            <td class="text-right">{{ number_format($gr->weight, 2) }} kg</td>
                                            <td class="text-right">₹{{ number_format($gr->total_amount, 2) }}</td>
                                            <td>
                                                @php
                                                    $statusClass = match($gr->status) {
                                                        'created' => 'bg-secondary',
                                                        'dispatched' => 'bg-primary',
                                                        'in_transit' => 'bg-warning text-dark',
                                                        'delivered' => 'bg-info',
                                                        'closed' => 'bg-success',
                                                        'cancelled' => 'bg-danger',
                                                        default => 'bg-light',
                                                    };
                                                @endphp
                                                <span class="badge {{ $statusClass }}">{{ ucfirst($gr->status) }}</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <p class="text-muted">No GRs linked to this gatepass.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection