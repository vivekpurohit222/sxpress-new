@extends('admin.layout.master')
@section('content')



        <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>{{$truckdriver_list_page}}</h1>
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
                            <strong class="card-title">Truck Driver Details</strong>
                            <form action="{{url('/dash/truckdriver/create')}}" method="GET" novalidate="novalidate" style="display:inline;">
                               <button type="submit" class="btn btn-primary pull-right">
                                    Add Truck Deatils
                                </button>
                       
                            </form>
                        </div>
                        <div class="card-body">
                  <table id="bootstrap-data-table" class="table table-striped table-bordered">
                    <thead>
                      <tr>
                      
                        <th>Truck No</th>
                        <th>Driver Name</th>
                       
                        <th>License No</th>
                        <th>Moblie No.</th>
                        

                        
                                       
                        <!-- <th>TOTAL AMOUNT</th> -->
                        <th>OPTION</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($truck_driver as $i=>$row)
                      <tr>
                       <td>{{$row->truck_no}}</td>
                        <td>{{$row->driver_name}}</td>
                         <td >{{$row->license}}</td>
                        <td>
                          {{$row->mobile_no1}}<br>
                          {{$row->mobile_no2}}
                        </ul>
                        <!-- <td>{{$row->total_amount}}</td> -->

                        <td>
                        <a href="{{url('/dash/truckdriver/'.$row->id.'/edit')}}" class="btn btn-primary">Edit</a>
                            <form action="{{url('/dash/truckdriver/'.$row->id.'/delete')}}" method="POST" novalidate="novalidate" style="display:inline;">
                               @method('DELETE')
                                 {{ csrf_field() }} 
                                 <button type="submit" class="btn btn-danger">
                                    Delete
                                </button>
                            </form>
                        <a href="{{url('/dash/truckdriver/'.$row->id.'/view')}}" class="btn btn-warning">view</a>
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