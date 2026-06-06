 {{-- \resources\views\users\edit.blade.php --}}
@extends('admin.layout.master')



@section('content')


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
                            <li><a href="{{url('/admin')}}">Dashboard</a></li>
                            <li><a href="{{url('/admin/back/users')}}">Users</a></li>

                            <li class="active">Edit User</li>
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
                            <strong class="card-title">  Edit {{$user->name}}</strong>
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
                                    {{ Form::model($user, array('route' => array('users.update', $user->id), 'method' => 'PUT')) }}{{-- Form model binding to automatically populate our fields with user data --}}
                                  <!-- route('copies_update','$id') -->
                          
                                    {{csrf_field()}}
                                    <div class="form-group">
        {{ Form::label('name', 'Name',array('class' => 'control-label mb-1')) }}
        {{ Form::text('name', null, array('class' => 'form-control')) }}
    </div>

    <div class="form-group">
        {{ Form::label('email', 'Email',array('class' => 'control-label mb-1')) }}
        {{ Form::email('email', null, array('class' => 'form-control')) }}
    </div>
     <div class="form-group">
        {{ Form::label('office', 'Office name',array('class' => 'control-label mb-1')) }}
         {{Form::select('office', ['Rajkot' => 'Rajkot', 'Navagam' => 'Navagam','Shapar (1)'=>'Shapar (1)','Shapar (2)'=>'Shapar (2)','Keshmeri Gate'=>'Keshmeri Gate','Daya Basti'=>'Daya Basti','Swarup Nagar1'=>'Swarup Nagar1','Swarup Nagar2'=>'Swarup Nagar2'], $user->office,array('class' => 'form-control'))}}
    </div>
    

    <div class='form-group'>
      <h5><b>Give Role</b></h5>
        @foreach ($roles as $role)
            {{ Form::checkbox('roles[]',  $role->id, $user->roles,array('class'=>'  form-control-check-input') ) }}
            {{ Form::label($role->name, ucfirst($role->name),array('class'=>'form-control- check-label')) }}<br>

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
           </div>
                                       
                    </div> <!-- .card -->

            </div><!-- .animated -->
        </div><!-- .content -->
</div>
</div>

@endsection