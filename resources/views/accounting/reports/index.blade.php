@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>Financial Reports</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li class="active">Financial Reports</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn">

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fa fa-book fa-2x text-primary mb-3"></i>
                    <h5 class="card-title">Day Book</h5>
                    <p class="card-text text-muted">Daily transaction register</p>
                    <a href="{{ route('accounting.reports.day-book') }}" class="btn btn-primary btn-sm">View Report</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fa fa-balance-scale fa-2x text-success mb-3"></i>
                    <h5 class="card-title">Trial Balance</h5>
                    <p class="card-text text-muted">Verify accounts balance</p>
                    <a href="{{ route('accounting.reports.trial-balance') }}" class="btn btn-success btn-sm">View Report</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fa fa-line-chart fa-2x text-info mb-3"></i>
                    <h5 class="card-title">Profit & Loss</h5>
                    <p class="card-text text-muted">Income vs expenses</p>
                    <a href="{{ route('accounting.reports.profit-and-loss') }}" class="btn btn-info btn-sm">View Report</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fa fa-file-text fa-2x text-warning mb-3"></i>
                    <h5 class="card-title">Balance Sheet</h5>
                    <p class="card-text text-muted">Financial position</p>
                    <a href="{{ route('accounting.reports.balance-sheet') }}" class="btn btn-warning btn-sm">View Report</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fa fa-truck fa-2x text-danger mb-3"></i>
                    <h5 class="card-title">Vehicle Profitability</h5>
                    <p class="card-text text-muted">Revenue vs expenses per vehicle</p>
                    <a href="{{ route('accounting.reports.vehicle-profitability') }}" class="btn btn-danger btn-sm">View Report</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fa fa-users fa-2x text-secondary mb-3"></i>
                    <h5 class="card-title">Driver Expenses</h5>
                    <p class="card-text text-muted">Expenses by driver</p>
                    <a href="{{ route('accounting.reports.driver-expenses') }}" class="btn btn-secondary btn-sm">View Report</a>
                </div>
            </div>
        </div>
    </div>

</div>
</div>

@endsection
