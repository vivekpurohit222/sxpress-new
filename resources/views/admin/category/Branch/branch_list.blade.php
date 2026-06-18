@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Branch Management</h3>
                <div class="card-tools">
                    <a href="{{ route('branch.create') }}" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Add Branch</a>
                </div>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible m-3">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger alert-dismissible m-3">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            @endif

            <div class="card-body">
                <form method="GET" action="{{ route('branch.index') }}" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search branch name, code, city..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <a href="{{ route('branch.index') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>

                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Branch Name</th>
                            <th>Code</th>
                            <th>GR Range</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                        <tr class="{{ !$branch->status ? 'text-muted' : '' }}">
                            <td><strong>{{ $branch->branch_name }}</strong></td>
                            <td>{{ $branch->branch_code }}</td>
                            <td>
                                @php
                                    $serial = \App\Models\BranchSerial::where('branch_id', $branch->id)->where('module', 'gr')->where('fy_year', \App\Services\SerialNumberService::currentFyPrefix())->first();
                                @endphp
                                @if($serial)
                                    <span class="badge badge-info">{{ str_pad($serial->range_start, 6, '0', STR_PAD_LEFT) }} - {{ str_pad($serial->range_end, 6, '0', STR_PAD_LEFT) }}</span>
                                @else
                                    <span class="badge badge-secondary">Not Set</span>
                                @endif
                            </td>
                            <td>{{ $branch->city ?? '-' }}</td>
                            <td>{{ $branch->state ?? '-' }}</td>
                            <td>{{ $branch->phone ?? '-' }}</td>
                            <td>
                                @if($branch->status)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('branch.show', $branch->id) }}" class="btn btn-secondary btn-sm" title="View"><i class="fa fa-eye"></i></a>
                                <a href="{{ route('branch.edit', $branch->id) }}" class="btn btn-info btn-sm" title="Edit"><i class="fa fa-edit"></i></a>
                                <form method="POST" action="{{ route('branch.destroy', $branch->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete branch {{ $branch->branch_name }}? This cannot be undone.')"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No branches found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $branches->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
