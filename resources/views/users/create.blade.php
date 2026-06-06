{{-- \resources\views\users\create.blade.php --}}
@extends('admin.layout.master')


@section('content')
<!-- 
<div class='col-lg-4 col-lg-offset-4'>

    <h1><i class='fa fa-user-plus'></i> Add User</h1>
    <hr>

    {{ Form::open(array('url' => 'users')) }}

    <div class="form-group">
        {{ Form::label('name', 'Name') }}
        {{ Form::text('name', '', array('class' => 'form-control')) }}
    </div>

    <div class="form-group">
        {{ Form::label('email', 'Email') }}
        {{ Form::email('email', '', array('class' => 'form-control')) }}
    </div>

    <div class='form-group'>
        @foreach ($roles as $role)
            {{ Form::checkbox('roles[]',  $role->id ) }}
            {{ Form::label($role->name, ucfirst($role->name)) }}<br>

        @endforeach
    </div>

    <div class="form-group">
        {{ Form::label('password', 'Password') }}<br>
        {{ Form::password('password', array('class' => 'form-control')) }}

    </div>

    <div class="form-group">
        {{ Form::label('password', 'Confirm Password') }}<br>
        {{ Form::password('password_confirmation', array('class' => 'form-control')) }}

    </div>

    {{ Form::submit('Add', array('class' => 'btn btn-primary')) }}

    {{ Form::close() }}

</div>
 -->
<div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Add User</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/admin')}}">Dashboard</a></li>
                            <li><a href="{{url('/admin/back/users')}}"> Available Add Users</a></li>

                            <li class="active">Add User</li>
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
                            <strong class="card-title">Add User</strong>
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
                                   {{ Form::open(array('url' => 'admin/back/users')) }}
                                 
                                    {{csrf_field()}}
                                        
                                    <div class="form-group">
                                        {{ Form::label('name', 'Name',array('class' => 'control-label mb-1')) }}
                                    {{ Form::text('name', '', array('class' => 'form-control')) }}
     
                                    </div>
                                     <div class="form-group">
                                    {{ Form::label('email', 'Email',array('class' => 'control-label mb-1')) }}
                                     {{ Form::email('email', '', array('class' => 'form-control')) }}
     
                                    </div>

                                     
                                     <div class="form-group">
                                    {{ Form::label('office', 'Office name',array('class' => 'control-label mb-1')) }}
                                   
                                    {{Form::select('office', ['Rajkot' => 'Rajkot', 'Navagam' => 'Navagam','Shapar (1)'=>'Shapar (1)','Shapar (2)'=>'Shapar (2)','Keshmeri Gate'=>'Keshmeri Gate','Daya Basti'=>'Daya Basti','Swarup Nagar1'=>'Swarup Nagar1','Swarup Nagar2'=>'Swarup Nagar2'], null,array('class' => 'form-control'), ['placeholder' => 'select office'])}}
                                    </div>
                                    
                                     <div class='form-group'>
        @foreach ($roles as $role)
            {{ Form::checkbox('roles[]',  $role->id ,array('class'=>'  form-check-input')) }}
            {{ Form::label($role->name, ucfirst($role->name),array('class'=>'form-check-label')) }}<br>

        @endforeach
    </div>
    
    <div class="form-group">
        {{ Form::label('password', 'Password',array('class' => 'control-label mb-1')) }}<br>
        {{ Form::password('password', array('class' => 'form-control')) }}

    </div>

    <div class="form-group">
        {{ Form::label('password', 'Confirm Password',array('class' => 'control-label mb-1')) }}<br>
        {{ Form::password('password_confirmation', array('class' => 'form-control')) }}

    </div>

    {{ Form::submit('Add', array('class' => 'btn btn-lg btn-info btn-block')) }}

    {{ Form::close() }}

    


@endsection
