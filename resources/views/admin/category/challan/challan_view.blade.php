@extends('admin.layout.master')
@section('content')

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <strong class="card-title">Challan Details</strong>
                        <div class="float-right">
                            <a href="{{ route('challan.print', $challan->id) }}" class="btn btn-secondary btn-sm">
                                <i class="fa fa-print"></i> Print
                            </a>
                            @can('edit-challan', $challan)
                            <a href="{{ route('challan.edit', $challan->id) }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            @endcan
                            <a href="{{ route('challan.index') }}" class="btn btn-info btn-sm">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Challan No</th>
                                        <td><strong>{{ $challan->challan_no }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Date</th>
                                        <td>{{ Carbon\Carbon::parse($challan->challan_date)->format('d M Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>From</th>
                                        <td>{{ $challan->from_dest }}</td>
                                    </tr>
                                    <tr>
                                        <th>To</th>
                                        <td>{{ $challan->to_dest }}</td>
                                    </tr>
                                    <tr>
                                        <th>Vehicle</th>
                                        <td>{{ $challan->vehicle ? $challan->vehicle->vehicle_number : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Driver</th>
                                        <td>{{ $challan->driver ? $challan->driver->driver_name : 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="40%">Office</th>
                                        <td>{{ $challan->office }}</td>
                                    </tr>
                                    <tr>
                                        <th>Total Items</th>
                                        <td><strong>{{ $challan->total_items }}</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Total Weight</th>
                                        <td><strong>{{ number_format($challan->total_weight, 2) }} kg</strong></td>
                                    </tr>
                                    <tr>
                                        <th>Created</th>
                                        <td>{{ $challan->created_at->format('d M Y H:i') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Challan Items -->
                        <div class="mt-4">
                            <h5>Challan Items</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>GR No</th>
                                            <th>Description</th>
                                            <th class="text-center">Packages</th>
                                            <th class="text-right">Weight</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($challan->items as $item)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><strong>{{ $item->gr_no }}</strong></td>
                                            <td>{{ $item->description }}</td>
                                            <td class="text-center">{{ $item->nuggets }}</td>
                                            <td class="text-right">{{ number_format($item->weight, 2) }} kg</td>
                                            <td>{{ $item->remarks ?? '-' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                No items in this challan
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="font-weight-bold bg-light">
                                        <tr>
                                            <td colspan="3" class="text-right">TOTAL</td>
                                            <td class="text-center">{{ $challan->items->sum('nuggets') }}</td>
                                            <td class="text-right">{{ number_format($challan->items->sum('weight'), 2) }} kg</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection