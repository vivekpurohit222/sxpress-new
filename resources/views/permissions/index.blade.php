

{{-- \resources\views\permissions\index.blade.php --}}
@extends('admin.layout.master')

<!-- @section('title', '| Permissions') -->

@section('content')

<!-- <div class="col-lg-10 col-lg-offset-1">
    <h1><i class="fa fa-key"></i>Available Permissions

    <a href="{{ route('users.index') }}" class="btn btn-default pull-right">Users</a>
    <a href="{{ route('roles.index') }}" class="btn btn-default pull-right">Roles</a></h1>
    <hr>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">

            <thead>
                <tr>
                    <th>Permissions</th>
                    <th>Operation</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions as $permission)
                <tr>
                    <td>{{ $permission->name }}</td> 
                    <td>
                    <a href="{{ URL::to('admin/back/permissions/'.$permission->id.'/edit') }}" class="btn btn-info pull-left" style="margin-right: 3px;">Edit</a>

                    {!! Form::open(['method' => 'DELETE', 'route' => ['permissions.destroy', $permission->id] ]) !!}
                    {!! Form::submit('Delete', ['class' => 'btn btn-danger']) !!}
                    {!! Form::close() !!}

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <a href="{{ URL::to('admin/back/permissions/create') }}" class="btn btn-success">Add Permission</a>

</div> -->



        <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Available Permissions</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            
                            <li class="active">Available Permissions</li>
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
                        
                            @if(\Session::has('flash_message'))
                        <div class="alert  alert-success alert-dismissible fade show" role="alert">
                         <span class="badge badge-pill badge-success">Success</span> {{\Session::get('flash_message')}}
                         <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                         </button>
                        
                        </div>
                        @endif
                     <!--  @if(\Session::has('success'))
                                  <div class="alert alert-success">
                                    <p>{{\Session::get('success')}}</p>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                         <span aria-hidden="true">×</span> 
                    </button>
                                  </div>
                                  @endif -->
                        <div class="card-header">
                            <strong class="card-title">Available Permissions</strong>
                            <a href="{{URL::to('permissions/create')}}" class="btn btn-primary pull-right">Add Permission</a>
                        </div>
                        <div class="card-body">
                  <table id="bootstrap-data-table" class="table table-striped table-bordered">
                    <thead>
                      <tr>
                       
                        <th>Permissions</th>
                        <th>Operation</th>
                        
                        
                        <!-- <th>TOTAL AMOUNT</th> -->
                        
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($permissions as $permission)
                      <tr>
                        <td>{{ $permission->name }}</td> 
                    <td>
                    <a href="{{ URL::to('permissions/'.$permission->id.'/edit') }}" class="btn btn-info pull-left" style="margin-right: 3px;">Edit</a>

                    {!! Form::open(['method' => 'DELETE', 'route' => ['permissions.destroy', $permission->id] ]) !!}
                    {!! Form::submit('Delete', ['class' => 'btn btn-danger']) !!}
                    {!! Form::close() !!}

                    </td>
                      </tr>
                     @endforeach
                    </tbody>
                  </table>
                        </div>
                    </div>
                </div>


                </div>
            </div><!-- .animated -->
        </div><!-- .content -->
        

 @endsection   

