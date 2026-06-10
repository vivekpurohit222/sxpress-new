@extends('admin.layout.master')
@section('content')

<style>
    .stat-card { border-radius: 10px; padding: 20px; margin-bottom: 20px; color: white; position: relative; overflow: hidden; }
    .stat-card::before { content:''; position:absolute; top:-50%; right:-50%; width:100%; height:200%; background:rgba(255,255,255,0.1); transform:rotate(30deg); }
    .stat-card .icon { font-size:40px; opacity:0.3; position:absolute; right:20px; top:50%; transform:translateY(-50%); }
    .stat-card .value { font-size:28px; font-weight:700; position:relative; z-index:1; }
    .stat-card .label { font-size:13px; opacity:0.9; position:relative; z-index:1; }
    .bg-gradient-primary { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); }
    .bg-gradient-success { background:linear-gradient(135deg,#11998e 0%,#38ef7d 100%); }
    .bg-gradient-warning { background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%); }
    .bg-gradient-info { background:linear-gradient(135deg,#4facfe 0%,#00f2fe 100%); }
    .bg-gradient-danger { background:linear-gradient(135deg,#fc5c7d 0%,#6a82fb 100%); }
    .bg-gradient-dark { background:linear-gradient(135deg,#434343 0%,#000000 100%); }
    .card-chart { border-radius:10px; border:none; box-shadow:0 0 20px rgba(0,0,0,0.08); }
    .card-chart .card-header { background:white; border-bottom:1px solid #eee; padding:15px 20px; }
    .recent-table th { background:#f8f9fa; font-weight:600; color:#555; font-size:12px; text-transform:uppercase; }
    .recent-table td { border-color:#f0f0f0; vertical-align:middle; font-size:13px; }
    .badge-paid { background:#11998e; color:white; padding:4px 10px; border-radius:20px; font-size:11px; }
    .badge-topay { background:#f5576c; color:white; padding:4px 10px; border-radius:20px; font-size:11px; }
    .badge-status { padding:4px 10px; border-radius:20px; font-size:11px; }
    .quick-action-btn { border-radius:10px; padding:25px 15px; text-align:center; transition:all 0.3s; border:2px solid #eee; background:white; text-decoration:none; }
    .quick-action-btn:hover { border-color:#667eea; transform:translateY(-3px); box-shadow:0 10px 30px rgba(102,126,234,0.2); text-decoration:none; }
    .quick-action-btn .icon { font-size:32px; color:#667eea; margin-bottom:8px; }
    .quick-action-btn .label { font-weight:600; color:#333; font-size:13px; }
</style>

<div class="content mt-3">

    {{-- Welcome Banner + Branch Filter --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <div style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); padding:25px 30px; border-radius:10px; color:white;">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 style="margin:0; font-weight:700;">Welcome, {{ $user->name }}!</h3>
                        <p style="margin:5px 0 0; opacity:0.9;">
                            {{ $office }} Branch • {{ $today }}
                            <span class="badge badge-light ml-2">{{ $user->getRoleNames()->first() }}</span>
                        </p>
                    </div>
                    @if($branches->count() > 0)
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('dash') }}">
                            <select name="branch" class="form-control" onchange="this.form.submit()" style="border-radius:20px;">
                                <option value="">All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b }}" {{ request('branch') == $b ? 'selected' : '' }}>{{ $b }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="stat-card bg-gradient-primary">
                <i class="fa fa-file-text icon"></i>
                <div class="value">{{ $kpis['grs_today'] }}</div>
                <div class="label">GRs Today</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card bg-gradient-success">
                <i class="fa fa-calendar icon"></i>
                <div class="value">{{ $kpis['grs_month'] }}</div>
                <div class="label">GRs This Month</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card bg-gradient-info">
                <i class="fa fa-inr icon"></i>
                <div class="value">₹{{ number_format($kpis['revenue_month'], 0) }}</div>
                <div class="label">Revenue This Month</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card bg-gradient-warning">
                <i class="fa fa-clock-o icon"></i>
                <div class="value">₹{{ number_format($kpis['pending_topay_amount'], 0) }}</div>
                <div class="label">Pending TO-PAY ({{ $kpis['pending_topay_count'] }})</div>
            </div>
        </div>
    </div>

    {{-- Second row of KPIs — operational metrics --}}
    @hasanyrole('SuperAdmin|Admin|Manager')
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="stat-card bg-gradient-danger">
                <i class="fa fa-truck icon"></i>
                <div class="value">{{ $kpis['pending_delivery'] }}</div>
                <div class="label">Pending Deliveries</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card bg-gradient-dark">
                <i class="fa fa-file-pdf-o icon"></i>
                <div class="value">{{ $kpis['pending_pod'] }}</div>
                <div class="label">Pending POD</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card bg-gradient-success">
                <i class="fa fa-check-circle icon"></i>
                <div class="value">₹{{ number_format($kpis['collected_topay_month'], 0) }}</div>
                <div class="label">Collected This Month</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stat-card bg-gradient-info">
                <i class="fa fa-truck icon"></i>
                <div class="value">{{ $kpis['active_vehicles'] }}</div>
                <div class="label">Active Vehicles</div>
            </div>
        </div>
    </div>
    @endhasanyrole

    {{-- Charts — Manager+ --}}
    @hasanyrole('SuperAdmin|Admin|Manager')
    <div class="row mt-3">
        <div class="col-lg-8">
            <div class="card-chart">
                <div class="card-header"><h6 class="mb-0">GRs Created (Last 30 Days)</h6></div>
                <div class="card-body"><canvas id="barChart" height="100"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-chart">
                <div class="card-header"><h6 class="mb-0">Payment Split (This Month)</h6></div>
                <div class="card-body">
                    <canvas id="pieChart" height="200"></canvas>
                    <div class="text-center mt-2">
                        <span class="badge badge-paid">Paid: {{ $charts['paid_count'] }}</span>
                        <span class="badge badge-topay ml-1">To-Pay: {{ $charts['topay_count'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-lg-8">
            <div class="card-chart">
                <div class="card-header"><h6 class="mb-0">Monthly Revenue (Last 6 Months)</h6></div>
                <div class="card-body"><canvas id="lineChart" height="100"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-chart">
                <div class="card-header"><h6 class="mb-0">Quick Actions</h6></div>
                <div class="card-body">
                    <div class="row">
                        @hasanyrole('SuperAdmin|Admin|Manager|Staff')
                        <div class="col-6">
                            <a href="{{ url('/gr/create') }}" class="quick-action-btn d-block mb-2">
                                <i class="fa fa-plus-circle icon"></i>
                                <div class="label">New GR</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ url('/gatepass/create') }}" class="quick-action-btn d-block mb-2">
                                <i class="fa fa-share-square-o icon"></i>
                                <div class="label">Gatepass</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ url('/challan/create') }}" class="quick-action-btn d-block">
                                <i class="fa fa-list-alt icon"></i>
                                <div class="label">Challan</div>
                            </a>
                        </div>
                        @endhasanyrole
                        @hasanyrole('SuperAdmin|Admin|Manager')
                        <div class="col-6">
                            <a href="{{ url('/frieghtmemo/create') }}" class="quick-action-btn d-block">
                                <i class="fa fa-file-text-o icon"></i>
                                <div class="label">Freight Memo</div>
                            </a>
                        </div>
                        @endhasanyrole
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endhasanyrole

    {{-- Quick Actions — Staff+ --}}
    @hasanyrole('SuperAdmin|Admin|Manager|Staff')
    @unlessrole('SuperAdmin|Admin|Manager')
    {{-- Only show standalone quick actions for Staff (Manager+ has them in the charts section) --}}
    <div class="row mt-3">
        <div class="col-lg-12">
            <div class="card-chart">
                <div class="card-header"><h6 class="mb-0">Quick Actions</h6></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-3">
                            <a href="{{ url('/gr/create') }}" class="quick-action-btn d-block">
                                <i class="fa fa-plus-circle icon"></i>
                                <div class="label">New GR</div>
                            </a>
                        </div>
                        <div class="col-3">
                            <a href="{{ url('/gatepass/create') }}" class="quick-action-btn d-block">
                                <i class="fa fa-share-square-o icon"></i>
                                <div class="label">Gatepass</div>
                            </a>
                        </div>
                        <div class="col-3">
                            <a href="{{ url('/challan/create') }}" class="quick-action-btn d-block">
                                <i class="fa fa-list-alt icon"></i>
                                <div class="label">Challan</div>
                            </a>
                        </div>
                        <div class="col-3">
                            <a href="{{ url('/gr') }}" class="quick-action-btn d-block">
                                <i class="fa fa-list icon"></i>
                                <div class="label">GR List</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endunlessrole
    @endhasanyrole

    {{-- Recent GRs — all roles can see --}}
    <div class="row mt-3 mb-4">
        <div class="col-lg-12">
            <div class="card-chart">
                <div class="card-header">
                    <h6 class="mb-0 d-inline">Recent GRs</h6>
                    <a href="{{ url('/gr') }}" class="btn btn-sm btn-primary float-right">View All</a>
                </div>
                <div class="card-body" style="padding:0;">
                    <table class="table recent-table mb-0">
                        <thead>
                            <tr>
                                <th>GR No</th>
                                <th>Date</th>
                                <th>Consignor</th>
                                <th>From → To</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentGRs as $gr)
                            <tr>
                                <td><strong>{{ $gr->gr_no }}</strong></td>
                                <td>{{ $gr->copy_date ? $gr->copy_date->format('d M') : '-' }}</td>
                                <td>{{ Str::limit($gr->consignor, 18) }}</td>
                                <td>{{ $gr->from_dest }} → {{ $gr->to_dest }}</td>
                                <td>₹{{ number_format($gr->total_amount, 0) }}</td>
                                <td>
                                    @php
                                        $sc = match($gr->status) {
                                            'created' => 'secondary', 'dispatched' => 'primary',
                                            'in_transit' => 'warning', 'delivered' => 'info',
                                            'closed' => 'success', 'cancelled' => 'danger',
                                            default => 'light',
                                        };
                                    @endphp
                                    <span class="badge badge-{{ $sc }} badge-status">{{ ucfirst(str_replace('_',' ',$gr->status ?? 'created')) }}</span>
                                </td>
                                <td>
                                    @if($gr->paid) <span class="badge badge-paid">PAID</span>
                                    @else <span class="badge badge-topay">TO-PAY</span> @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">No GRs found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@hasanyrole('SuperAdmin|Admin|Manager')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bar Chart
    var barData = @json($charts['grs_per_day']);
    new Chart(document.getElementById('barChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: barData.map(d => { var dt = new Date(d.date); return dt.getDate()+' '+['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][dt.getMonth()]; }),
            datasets: [{label:'GRs', data: barData.map(d => d.count), backgroundColor:'rgba(102,126,234,0.8)', borderRadius:4}]
        },
        options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true, ticks:{stepSize:1}}} }
    });

    // Pie Chart
    new Chart(document.getElementById('pieChart').getContext('2d'), {
        type: 'doughnut',
        data: { labels:['Paid','To-Pay'], datasets:[{data:[{{ $charts['paid_count'] }}, {{ $charts['topay_count'] }}], backgroundColor:['#11998e','#f5576c'], borderWidth:0}] },
        options: { responsive:true, cutout:'60%', plugins:{legend:{position:'bottom'}} }
    });

    // Line Chart
    var revData = @json($charts['monthly_revenue']);
    new Chart(document.getElementById('lineChart').getContext('2d'), {
        type: 'line',
        data: { labels: revData.map(d=>d.month), datasets:[{label:'Revenue', data:revData.map(d=>d.total), borderColor:'#667eea', backgroundColor:'rgba(102,126,234,0.1)', fill:true, tension:0.4, pointRadius:4}] },
        options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true, ticks:{callback:v => '₹'+(v>=1000?(v/1000).toFixed(0)+'k':v)}}} }
    });
});
</script>
@endhasanyrole

@endsection
