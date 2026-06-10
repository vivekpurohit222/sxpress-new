@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Reports</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right"><li><a href="{{ url('/dash') }}">Dashboard</a></li><li class="active">Reports</li></ol>
    </div></div></div>
</div>

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <!-- GR Reports -->
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card border-primary h-100">
                    <div class="card-header bg-primary text-white py-2"><strong><i class="fa fa-file-text"></i> GR Reports</strong></div>
                    <div class="card-body py-2">
                        <a href="{{ route('reports.gr_register') }}" class="btn btn-outline-primary btn-sm btn-block text-left mb-1"><i class="fa fa-list"></i> GR Register</a>
                        <a href="{{ route('reports.daily_booking') }}" class="btn btn-outline-primary btn-sm btn-block text-left mb-1"><i class="fa fa-calendar"></i> Daily Booking</a>
                    </div>
                </div>
            </div>

            <!-- Financial Reports -->
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card border-success h-100">
                    <div class="card-header bg-success text-white py-2"><strong><i class="fa fa-inr"></i> Financial</strong></div>
                    <div class="card-body py-2">
                        <a href="{{ route('reports.revenue') }}" class="btn btn-outline-success btn-sm btn-block text-left mb-1"><i class="fa fa-line-chart"></i> Revenue</a>
                        <a href="{{ route('reports.pending_topay') }}" class="btn btn-outline-success btn-sm btn-block text-left mb-1"><i class="fa fa-clock-o"></i> Pending TO-PAY</a>
                        <a href="{{ route('reports.freight') }}" class="btn btn-outline-success btn-sm btn-block text-left mb-1"><i class="fa fa-file-text-o"></i> Freight Memo</a>
                    </div>
                </div>
            </div>

            <!-- Operations Reports -->
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card border-warning h-100">
                    <div class="card-header bg-warning text-dark py-2"><strong><i class="fa fa-truck"></i> Operations</strong></div>
                    <div class="card-body py-2">
                        <a href="{{ route('reports.pending_delivery') }}" class="btn btn-outline-warning btn-sm btn-block text-left mb-1"><i class="fa fa-exclamation-triangle"></i> Pending Delivery</a>
                        <a href="{{ route('reports.pending_pod') }}" class="btn btn-outline-warning btn-sm btn-block text-left mb-1"><i class="fa fa-file-pdf-o"></i> Pending POD</a>
                        <a href="{{ route('reports.branch_performance') }}" class="btn btn-outline-warning btn-sm btn-block text-left mb-1"><i class="fa fa-building"></i> Branch Performance</a>
                    </div>
                </div>
            </div>

            <!-- Master Reports -->
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card border-info h-100">
                    <div class="card-header bg-info text-white py-2"><strong><i class="fa fa-cube"></i> Masters</strong></div>
                    <div class="card-body py-2">
                        <a href="{{ route('reports.vehicle') }}" class="btn btn-outline-info btn-sm btn-block text-left mb-1"><i class="fa fa-truck"></i> Vehicle Report</a>
                        <a href="{{ route('reports.driver') }}" class="btn btn-outline-info btn-sm btn-block text-left mb-1"><i class="fa fa-user"></i> Driver Report</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
