@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Consignor List</h3>
                <div class="card-tools">
                    <a href="{{ url('/dash/consignor/create') }}" class="btn btn-success btn-sm">Add New</a>
                </div>
            </div>
            @if (session('success'))
            <div class="alert alert-success m-3">
                {{ session('success') }}
            </div>
            @endif
            <div class="card-body">
                <form method="GET" action="{{ url('/dash/consignor') }}" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search consignor..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </form>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Consignor Name</th>
                            <th>Code</th>
                            <th>GST No</th>
                            <th>City</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consignors as $consignor)
                        <tr>
                            <td>{{ $consignor->id }}</td>
                            <td>{{ $consignor->consignor_name }}</td>
                            <td>{{ $consignor->consignor_code }}</td>
                            <td>{{ $consignor->gst_no ?? '-' }}</td>
                            <td>{{ $consignor->city ?? '-' }}</td>
                            <td>{{ $consignor->phone ?? '-' }}</td>
                            <td>
                                @if($consignor->status)
                                <span class="badge badge-success">Active</span>
                                @else
                                <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ url('/dash/consignor/'.$consignor->id.'/edit') }}" class="btn btn-info btn-sm">Edit</a>
                                <a href="{{ url('/dash/consignor/'.$consignor->id) }}" class="btn btn-secondary btn-sm">View</a>
                                <form method="POST" action="{{ url('/dash/consignor/'.$consignor->id) }}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No consignors found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $consignors->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection