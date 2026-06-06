@extends('admin.layout.master')
@section('content')



        <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>{{$copies_list_page}}</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            
                            <li class="active">GR table</li>
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
                            <strong class="card-title">Copies Table</strong>
                            <a href="{{url('/dash/gr/create')}}" class="btn btn-primary pull-right">Create GR</a>
                        </div>
                        <div class="card-body">
                  <table id="bootstrap-data-table" class="table table-striped table-bordered">
                    <thead>
                      <tr>
                        <th>Date</th>

                        <th>GR NO.</th>
                        <th>FROM</th>
                        <th>TO</th>

                        
                                       
                        <!-- <th>TOTAL AMOUNT</th> -->
                        <th>OPTION</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($copies as $i=>$row)
                      <tr>
                        <td >{{$row->copy_date}}</td>
                        <td>{{$row->gr_no}}</td>
                        <td>{{$row->from_dest}}</td>
                        <td>{{$row->to_dest}}</td>
                        
                        <!-- <td>{{$row->total_amount}}</td> -->

                        <td>
                        <a href="{{url('/dash/gr/'.$row->id.'/edit')}}" class="btn btn-primary">Edit</a>
                            <form action="{{url('/dash/gr/'.$row->id.'/delete')}}" method="POST" novalidate="novalidate" style="display:inline;">
                               @method('DELETE')
                                 {{ csrf_field() }} 
                                 <button type="submit" class="btn btn-danger">
                                    Delete
                                </button>
                            </form>
                        <a href="{{url('/dash/gr/'.$row->id.'/print')}}" class="btn btn-warning">Print</a>
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