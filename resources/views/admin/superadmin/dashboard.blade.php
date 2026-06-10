@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <!-- KPI Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $totalGRsToday }}</h3>
                        <p>GRs Today (All Branches)</p>
                    </div>
                    <div class="icon"><i class="fas fa-truck-loading"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $totalGRsMonth }}</h3>
                        <p>GRs This Month</p>
                    </div>
                    <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>₹{{ number_format($pendingTopay, 2) }}</h3>
                        <p>Pending TO-PAY</p>
                    </div>
                    <div class="icon"><i class="fas fa-rupee-sign"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>₹{{ number_format($totalRevenue, 2) }}</h3>
                        <p>Revenue This Month</p>
                    </div>
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
        </div>

        <!-- Branch Summary -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Branch-wise Summary</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Prefix</th>
                            <th>GRs Today</th>
                            <th>Total GRs</th>
                            <th>Pending TO-PAY</th>
                            <th>Month Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branchSummary as $branch)
                        <tr>
                            <td>{{ $branch->branch_name }}</td>
                            <td><span class="badge badge-info">{{ $branch->gr_prefix ?? '-' }}</span></td>
                            <td>{{ $branch->grs_today_count }}</td>
                            <td>{{ $branch->grs_count }}</td>
                            <td>
                                @if($branch->pending_topay_count > 0)
                                    <span class="badge badge-warning">{{ $branch->pending_topay_count }} GRs</span>
                                @else
                                    <span class="badge badge-success">0</span>
                                @endif
                            </td>
                            <td>₹{{ number_format($branch->month_revenue ?? 0, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No branches found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent GRs -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent GRs (All Branches)</h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>GR No</th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Consignor</th>
                            <th>Consignee</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentGRs as $gr)
                        <tr>
                            <td><strong>{{ $gr->gr_no }}</strong></td>
                            <td>{{ $gr->copy_date ? Carbon\Carbon::parse($gr->copy_date)->format('d-m-y') : '-' }}</td>
                            <td>{{ $gr->office ?? '-' }}</td>
                            <td>{{ Str::limit($gr->consignor, 20) }}</td>
                            <td>{{ Str::limit($gr->consignee, 20) }}</td>
                            <td>{{ $gr->from_dest ?? '-' }}</td>
                            <td>{{ $gr->to_dest ?? '-' }}</td>
                            <td>₹{{ number_format($gr->total_amount ?? 0, 2) }}</td>
                            <td>
                                @if($gr->status)
                                    <span class="badge badge-{{ $gr->status == 'closed' ? 'success' : ($gr->status == 'cancelled' ? 'danger' : 'secondary') }}">
                                        {{ ucfirst(str_replace('_', ' ', $gr->status)) }}
                                    </span>
                                @else
                                    <span class="badge badge-secondary">Created</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">No GRs found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection