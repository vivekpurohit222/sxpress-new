{{-- \resources\views\permissions\create.blade.php --}}
@extends('admin.layout.master')
<!-- @section('title', '| Create Permission') -->

@section('content')
<!-- 
<div class='col-lg-4 col-lg-offset-4'>

    <h1><i class='fa fa-key'></i> Add Permission</h1>
    <br>

    {{ Form::open(array('url' => 'admin/back/permissions')) }}

    <div class="form-group">
        {{ Form::label('name', 'Name') }}
        {{ Form::text('name', '', array('class' => 'form-control')) }}
    </div><br>
    @if(!$roles->isEmpty()) //If no roles exist yet
        <h4>Assign Permission to Roles</h4>

        @foreach ($roles as $role) 
            {{ Form::checkbox('roles[]',  $role->id ,array('class'=>'form-check-label ')) }}
            {{ Form::label($role->name, ucfirst($role->name),array('class'=>'form-check-input')) }}<br>

        @endforeach
    @endif
    <br>
    {{ Form::submit('Add', array('class' => 'btn btn-primary')) }}

    {{ Form::close() }}

</div>
 -->

<div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Add Permission</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            <li><a href="{{url('/dash/permissions')}}"> Available Permissions</a></li>

                            <li class="active">Add Permission</li>
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
                            <strong class="card-title">Add Permission</strong>
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

                                  {{ Form::open(array('url' => 'permissions')) }}
                                    @csrf
                                        
                                      
                                       <div class="form-group"> {{ Form::label('name', 'Name',array('class' => 'control-label mb-1')) }}
        {{ Form::text('name', '', array('class' => 'form-control')) }}</div>
                                       <!-- <div class="form-group"><label for="company" class=" control-label mb-1">Display Name</label><input type="text" id="display_name" name='display_name'placeholder="" value=""class="form-control"></div>
                                       <div class="form-group"><label for="company" class=" control-label mb-1">Description</label><textarea name="description" id="textarea-input" rows="9" placeholder="" class="form-control"></textarea></div> -->
                                       <div class="form-group">
@if(!$roles->isEmpty())
        <h4>Assign Permission to Roles</h4>

        @foreach ($roles as $role)
            {{ Form::checkbox('roles[]',  $role->id ,array('class'=>'  form-check-input')) }}
            {{ Form::label($role->name, ucfirst($role->name),array('class'=>'form-check-label')) }}<br>

        @endforeach
    @endif</div>
                                     <div>
                                          
                                             {{ Form::submit('Add', array('class' => 'btn btn-lg btn-info btn-block')) }}

    {{ Form::close() }}

                                              
                                          
                                     </div> 
                                  </form>
                              </div>
                          </div>

                        </div>
                    </div> <!-- .card -->

            </div><!-- .animated -->
        </div>
        @endsection