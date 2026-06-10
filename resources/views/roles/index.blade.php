{{-- \resources\views\roles\index.blade.php --}}
@extends('admin.layout.master')



@section('content')
<!-- 
<div class="col-lg-10 col-lg-offset-1">
    <h1><i class="fa fa-key"></i> Roles

    <a href="{{ route('users.index') }}" class="btn btn-default pull-right">Users</a>
    <a href="{{ route('permissions.index') }}" class="btn btn-default pull-right">Permissions</a></h1>
    <hr>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Permissions</th>
                    <th>Operation</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($roles as $role)
                <tr>

                    <td>{{ $role->name }}</td>

                    <td>{{ str_replace(array('[',']','"'),'', $role->permissions()->pluck('name')) }}</td>{{-- Retrieve array of permissions associated to a role and convert to string --}}
                    <td>
                    <a href="{{ URL::to('roles/'.$role->id.'/edit') }}" class="btn btn-info pull-left" style="margin-right: 3px;">Edit</a>

                    {!! Form::open(['method' => 'DELETE', 'route' => ['roles.destroy', $role->id] ]) !!}
                    {!! Form::submit('Delete', ['class' => 'btn btn-danger']) !!}
                    {!! Form::close() !!}

                    </td>
                </tr>
                @endforeach
            </tbody>

        </table>
    </div>

    <a href="{{ URL::to('roles/create') }}" class="btn btn-success">Add Role</a>

</div>


 -->
        <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Roles</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            
                            <li class="active">Available Roles</li>
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
                            <strong class="card-title">Available Roles</strong>
                            <a href="{{URL::to('roles/create')}}" class="btn btn-primary pull-right">Add Role</a>
                        </div>
                        <div class="card-body">
                  <table id="bootstrap-data-table" class="table table-striped table-bordered">
                    <thead>
                      <tr>
                       
                        <th>Role</th>
                    <th>Permissions</th>
                    <th>Operation</th>
                        
                    
                      </tr>
                    </thead>
                    <tbody>
                       @foreach ($roles as $role)
                      <tr>
                        <td>{{ $role->name }}</td> 
                        
                        <td>
                            
                            {{ str_replace(array('[',']','"'),'', $role->permissions()->pluck('name')) }}</td>{{-- Retrieve array of permissions associated to a role and convert to string --}}
                  
                    <td>
                    <a href="{{ URL::to('roles/'.$role->id.'/edit') }}" class="btn btn-info pull-left" style="margin-right: 3px;">Edit</a>

                    {!! Form::open(['method' => 'DELETE', 'route' => ['roles.destroy', $role->id] ]) !!}
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