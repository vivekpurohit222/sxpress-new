@extends('admin.layout.master')


@section('content')

<!-- <div class='col-lg-4 col-lg-offset-4'>

    <h1><i class='fa fa-key'></i> Add Role</h1>
    <hr>

    {{ Form::open(array('url' => 'roles')) }}

    <div class="form-group">
        {{ Form::label('name', 'Name') }}
        {{ Form::text('name', null, array('class' => 'form-control')) }}
    </div>

    <h5><b>Assign Permissions</b></h5>

    <div class='form-group'>
        @foreach ($permissions as $permission)
            {{ Form::checkbox('permissions[]',  $permission->id ) }}
            {{ Form::label($permission->name, ucfirst($permission->name)) }}<br>

        @endforeach
    </div>

    {{ Form::submit('Add', array('class' => 'btn btn-primary')) }}

    {{ Form::close() }}

</div>
-->
<div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1> Add Role</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            <li><a href="{{url('/roles')}}"> Available Roles</a></li>

                            <li class="active">Create Roles</li>
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
                            <strong class="card-title">Create Roles</strong>
                        </div>
                        <div class="card-body">
                          <!-- Credit Card -->
                          <div id="pay-invoice">
                              <div class="card-body">
                                 <!--  <div class="card-title">
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
                                  {{ Form::open(array('url' => 'roles')) }}
                                 
                                    @csrf
                                        
                                       <div class="form-group">
        {{ Form::label('name', 'Name',array('class' => 'control-label mb-1')) }}
        {{ Form::text('name', null, array('class' => 'form-control')) }}
    </div>
     <h5><b>Assign Permissions</b></h5>  
<div class='form-group'>
        @foreach ($permissions as $permission)
            {{ Form::checkbox('permissions[]',  $permission->id ,array('class'=>'  form-check-input')) }}
            {{ Form::label($permission->name, ucfirst($permission->name),array('class'=>'form-check-label')) }}<br>

        @endforeach
    </div>
{{ Form::submit('Add', array('class' => 'btn btn-lg btn-info btn-block')) }}

    {{ Form::close() }}


@endsection
