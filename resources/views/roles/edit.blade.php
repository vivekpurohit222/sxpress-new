@extends('admin.layout.master')

@section('content')
<!-- 
<div class='col-lg-4 col-lg-offset-4'>
    <h1><i class='fa fa-key'></i> Edit Role: {{$role->name}}</h1>
    <hr>

    {{ Form::model($role, array('route' => array('roles.update', $role->id), 'method' => 'PUT')) }}

    <div class="form-group">
        {{ Form::label('name', 'Role Name') }}
        {{ Form::text('name', null, array('class' => 'form-control')) }}
    </div>

    <h5><b>Assign Permissions</b></h5>
    @foreach ($permissions as $permission)

        {{Form::checkbox('permissions[]',  $permission->id, $role->permissions ) }}
        {{Form::label($permission->name, ucfirst($permission->name)) }}<br>

    @endforeach
    <br>
    {{ Form::submit('Edit', array('class' => 'btn btn-primary')) }}

    {{ Form::close() }}    
</div> -->

 <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Edit Role</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            <li><a href="{{url('/roles')}}">Available Roles</a></li>

                            <li class="active">Edit Role</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

 <div class="content mt-3">
            <div class="animated fadeIn">


                <div class="row">
                  <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <strong class="card-title"> Edit Role: {{$role->name}}</strong>
                        </div>
                        <div class="card-body">
                          <!-- Credit Card -->
                          <div id="pay-invoice">
                              <div class="card-body">
                                  <!-- <div class="card-title">
                                      <h3 class="text-center">SAURASHTRA EXPRESS</h3>
                                  </div> -->
                                  @if(\Session::has('flash_message'))
                                  <div class="alert alert-success">
                                    <p>{{\Session::get('flash_message')}}</p>
                                  </div>
                                  @endif
                                  
                                  
                                  @if ($errors->any())
                                      <div class="alert alert-danger">
                                          <ul>
                                              @foreach ($errors->all() as $error)
                                                  <li>{{ $error }}</li>
                                              @endforeach
                                          </ul>
                                      </div>
                                  @endif
                                  <hr>
                                    {{ Form::model($role, array('route' => array('roles.update', $role->id), 'method' => 'PUT')) }}
                                  <!-- route('copies_update','$id') -->
                          
                                    {{csrf_field()}}
                                      <div class="form-group">
        {{ Form::label('name', 'Role Name',array('class' => 'control-label mb-1')) }}
        {{ Form::text('name', null, array('class' => 'form-control')) }}
    </div>

                                       <div class="form-group">
                                        <h5><b>Assign Permissions</b></h5>
    @foreach ($permissions as $permission)

        {{Form::checkbox('permissions[]',  $permission->id, $role->permissions,array('class'=>'  form-check-input') ) }}
        {{Form::label($permission->name, ucfirst($permission->name),array('class'=>'form-check-label')) }}<br>

    @endforeach
    <br>
    {{ Form::submit('Edit', array('class' => 'btn btn-lg btn-info btn-block')) }}

    {{ Form::close() }}   </div>
                                       
                    </div> <!-- .card -->

            </div><!-- .animated -->
        </div><!-- .content -->
</div>
</div>

@endsection
