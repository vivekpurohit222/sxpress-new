@extends('admin.layout.master')

<!-- @section('title', '| Edit Permission') -->

@section('content')
<!-- 
<div class='col-lg-4 col-lg-offset-4'>

    <h1><i class='fa fa-key'></i> Edit {{$permission->name}}</h1>
    <br>
    {{ Form::model($permission, array('route' => array('permissions.update', $permission->id), 'method' => 'PUT')) }}{{-- Form model binding to automatically populate our fields with permission data --}}

    <div class="form-group">
        {{ Form::label('name', 'Permission Name') }}
        {{ Form::text('name', null, array('class' => 'form-control')) }}
    </div>
    <br>
    {{ Form::submit('Edit', array('class' => 'btn btn-primary')) }}

    {{ Form::close() }}

</div> -->

<div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>EditPermission</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            <li><a href="{{url('/dash/permissions')}}"> Available Permissions</a></li>

                            <li class="active">Edit Permission</li>
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
                            <strong class="card-title">Edit {{$permission->name}}</strong>
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

                                  {{ Form::model($permission, array('route' => array('permissions.update', $permission->id), 'method' => 'PUT')) }}{{-- Form model binding to automatically populate our fields with permission data --}}
                                    @csrf
                                        
                                      
                                       <div class="form-group"> {{ Form::label('name', 'Permission Name',array('class' => 'control-label mb-1')) }}
        {{ Form::text('name', null, array('class' => 'form-control')) }}</div>
                                       <!-- <div class="form-group"><label for="company" class=" control-label mb-1">Display Name</label><input type="text" id="display_name" name='display_name'placeholder="" value=""class="form-control"></div>
                                       <div class="form-group"><label for="company" class=" control-label mb-1">Description</label><textarea name="description" id="textarea-input" rows="9" placeholder="" class="form-control"></textarea></div> -->
                                      
                                     <div>
                                          
                                     {{ Form::submit('Edit', array('class' => 'btn btn-lg btn-info btn-block')) }}

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