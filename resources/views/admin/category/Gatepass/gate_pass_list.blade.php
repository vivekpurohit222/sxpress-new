@extends('admin.layout.master')
@section('content')



        <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>{{$gatepass_list_page}}</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            
                            <li class="active">Gate Pass table</li>
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
                            <strong class="card-title">Gate Pass Table</strong>
                            <form action="{{url('/dash/gatepass/create')}}" method="GET" novalidate="novalidate" style="display:inline;">
                               <button type="submit" class="btn btn-primary pull-right">
                                    Create Gate Pass
                                </button>
                         <input class="form-control pull-right" style="width: 20% ;margin-right: 1%" s type="text" placeholder="Enter G R No."value="{{$gr_no}}" name="gr_no">
                            </form>
                        </div>
                        <div class="card-body">
                  <table id="bootstrap-data-table" class="table table-striped table-bordered">
                    <thead>
                      <tr>
                      
                        <th>GP No.</th>
                        <th>GR NO.</th>
                       
                        <th>Date</th>
                        <th>FROM</th>
                        <th>TO</th>

                        
                                       
                        <!-- <th>TOTAL AMOUNT</th> -->
                        <th>OPTION</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($gatepass as $i=>$row)
                      <tr>
                       <td>{{$row->gp_no}}</td>
                        <td>{{$row->gr_no}}</td>
                         <td >{{$row->gp_date}}</td>
                        <td>{{$row->from_dest}}</td>
                        <td>{{$row->to_dest}}</td>
                        
                        <!-- <td>{{$row->total_amount}}</td> -->

                        <td>
                        <a href="{{url('/dash/gatepass/'.$row->id.'/edit')}}" class="btn btn-primary">Edit</a>
                            <form action="{{url('/dash/gatepass/'.$row->id.'/delete')}}" method="POST" novalidate="novalidate" style="display:inline;">
                               @method('DELETE')
                                 {{ csrf_field() }} 
                                 <button type="submit" class="btn btn-danger">
                                    Delete
                                </button>
                            </form>
                        <a href="{{url('/dash/gatepass/'.$row->id.'/print')}}" class="btn btn-warning">Print</a>
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