@extends('admin.layout.master')
@section('content')

        <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Truck Driver Details</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            <li class="active">Truck Driver Details</li>
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
                        @if(\Session::has('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                         <span class="badge badge-pill badge-success">Success</span> {{\Session::get('success')}}
                         <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                         </button>
                        </div>
                        @endif
                        <div class="card-header">
                            <strong class="card-title">Truck Driver Details</strong>
                            <a href="{{ url('/dash/truckdriver/create') }}" class="btn btn-primary pull-right">
                                Add Truck Details
                            </a>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="{{ url('/dash/truckdriver') }}" class="mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <input type="text" name="search" class="form-control" placeholder="Search truck driver..." value="{{ request('search') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                    </div>
                                </div>
                            </form>
                  <table id="bootstrap-data-table" class="table table-striped table-bordered">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Truck No</th>
                        <th>Driver Name</th>
                        <th>License No</th>
                        <th>Mobile No.</th>
                        <th>Status</th>
                        <th>Option</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse($truck_drivers as $row)
                      <tr>
                       <td>{{$row->id}}</td>
                       <td>{{$row->truck_no}}</td>
                        <td>{{$row->driver_name}}</td>
                         <td >{{$row->license}}</td>
                        <td>
                          {{$row->mobile_no1}}<br>
                          {{$row->mobile_no2}}
                        </td>
                        <td>
                            @if($row->status)
                            <span class="badge badge-success">Active</span>
                            @else
                            <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                        <a href="{{url('/dash/truckdriver/'.$row->id.'/edit')}}" class="btn btn-primary btn-sm">Edit</a>
                        <a href="{{url('/dash/truckdriver/'.$row->id.'/view')}}" class="btn btn-info btn-sm">View</a>
                            <form action="{{url('/dash/truckdriver/'.$row->id.'/delete')}}" method="POST" style="display:inline;">
                               @method('DELETE')
                                 {{ csrf_field() }}
                                 <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        </td>
                      </tr>
                     @empty
                      <tr>
                          <td colspan="7" class="text-center">No truck drivers found</td>
                      </tr>
                     @endforelse
                    </tbody>
                  </table>
                  <div class="mt-3">
                      {{ $truck_drivers->withQueryString()->links() }}
                  </div>
                        </div>
                    </div>
                </div>

                </div>
            </div><!-- .animated -->
        </div><!-- .content -->
@endsection