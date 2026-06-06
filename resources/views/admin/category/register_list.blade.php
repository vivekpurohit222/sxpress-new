@extends('admin.layout.master')
@section('content')



        <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>{{$register_list_page}}</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/admin')}}">Dashboard</a></li>
                            
                            <li class="active">User table</li>
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
                        <div class="alert  alert-success alert-dismissible fade show" role="alert">
                         <span class="badge badge-pill badge-success">Success</span> {{\Session::get('success')}}
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
                            <strong class="card-title">User Table</strong>
                            <a href="{{url('/admin/back/register/create')}}" class="btn btn-primary pull-right">Create User</a>
                        </div>
                        <div class="card-body">
                  <table id="bootstrap-data-table" class="table table-striped table-bordered">
                    <thead>
                      <tr>
                       <th> <span type='hidden'>#</span></th> 
                        <th>NAME</th>
                        <th>EMAIL</th>
                        <th>TYPE</th>
                        <th>OPTION</th>
                       
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($register as $i=>$row)
                      <tr>
                        <td>{{$row->id}}</td>
                        <td>{{$row->name}}</td>
                        <td>{{$row->email}}</td>
                        @if(!$row->is_admin )
                         <td>Admin</td>
                        
                        @else
                          <td>User</td>
                        
                        @endif

                        <td>
                       <!--  <a href="{{url('/admin/back/register/'.$row->id.'/edit')}}" class="btn btn-primary">Edit</a> -->
                            <form action="{{url('/admin/back/register/'.$row->id.'/delete')}}" method="POST" novalidate="novalidate" style="display:inline;">
                               @method('DELETE')
                                 {{ csrf_field() }} 
                                 <button type="submit" class="btn btn-danger">
                                    Delete
                                </button>
                            </form>
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