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
                            <li class="active">GR List</li>
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
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                         <span class="badge badge-pill badge-success">Success</span> {{\Session::get('success')}}
                         <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                         </button>
                        </div>
                        @endif

                        <div class="card-header">
                            <strong class="card-title">GR List</strong>
                            <a href="{{url('/gr/create')}}" class="btn btn-primary pull-right">Create GR</a>
                        </div>

                        <!-- Filter Tabs -->
                        <div class="card-header">
                            <div class="nav nav-pills" id="nav-tab" role="tablist">
                                <a class="nav-item nav-link active" id="nav-all-tab" data-toggle="tab" href="#nav-all" role="tab">All <span class="badge badge-secondary">{{ $copies->count() }}</span></a>
                                <a class="nav-item nav-link" id="nav-paid-tab" data-toggle="tab" href="#nav-paid" role="tab">Paid <span class="badge badge-success">{{ $copies->where('paid', 1)->count() }}</span></a>
                                <a class="nav-item nav-link" id="nav-topay-pending-tab" data-toggle="tab" href="#nav-topay-pending" role="tab">TO-Pay Pending <span class="badge badge-warning">{{ $copies->where('to_pay', 1)->where('topay_collected', 0)->count() }}</span></a>
                                <a class="nav-item nav-link" id="nav-topay-collected-tab" data-toggle="tab" href="#nav-topay-collected" role="tab">TO-Pay Collected <span class="badge badge-info">{{ $copies->where('to_pay', 1)->where('topay_collected', 1)->count() }}</span></a>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="tab-content" id="nav-tabContent">
                                <!-- All GRs -->
                                <div class="tab-pane fade show active" id="nav-all" role="tabpanel">
                                    @include('admin.category.partials.gr_table', ['grs' => $copies])
                                </div>
                                <!-- Paid GRs -->
                                <div class="tab-pane fade" id="nav-paid" role="tabpanel">
                                    @include('admin.category.partials.gr_table', ['grs' => $copies->where('paid', 1)])
                                </div>
                                <!-- TO-Pay Pending -->
                                <div class="tab-pane fade" id="nav-topay-pending" role="tabpanel">
                                    @include('admin.category.partials.gr_table', ['grs' => $copies->where('to_pay', 1)->where('topay_collected', 0)])
                                </div>
                                <!-- TO-Pay Collected -->
                                <div class="tab-pane fade" id="nav-topay-collected" role="tabpanel">
                                    @include('admin.category.partials.gr_table', ['grs' => $copies->where('to_pay', 1)->where('topay_collected', 1)])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                </div>
            </div>
        </div>

@endsection
