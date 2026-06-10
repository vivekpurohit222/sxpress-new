@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Branch Details</h3>
                <div class="card-tools">
                    <a href="{{ route('branch.edit', $branch->id) }}" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i> Edit</a>
                    <a href="{{ route('branch.index') }}" class="btn btn-default btn-sm">Back</a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Branch Name</th>
                        <td>{{ $branch->branch_name }}</td>
                    </tr>
                    <tr>
                        <th>Branch Code</th>
                        <td>{{ $branch->branch_code }}</td>
                    </tr>
                    <tr>
                        <th>GR Prefix</th>
                        <td><span class="badge badge-info">{{ $branch->gr_prefix }}</span></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td>{{ $branch->address ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>City</th>
                        <td>{{ $branch->city ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>State</th>
                        <td>{{ $branch->state ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Pincode</th>
                        <td>{{ $branch->pincode ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Phone</th>
                        <td>{{ $branch->phone ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $branch->email ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($branch->status)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>GRs Created</th>
                        <td>{{ $grCount }}</td>
                    </tr>
                    <tr>
                        <th>Users Assigned</th>
                        <td>{{ $userCount }}</td>
                    </tr>
                    <tr>
                        <th>Created</th>
                        <td>{{ $branch->created_at?->format('d-m-Y H:i') ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th>Last Updated</th>
                        <td>{{ $branch->updated_at?->format('d-m-Y H:i') ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
