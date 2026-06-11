@extends('admin.layout.master')
@section('content')

<style>
    .dash-card { border-radius:8px; padding:18px; margin-bottom:15px; color:white; position:relative; overflow:hidden; }
    .dash-card::before { content:''; position:absolute; top:-40%; right:-40%; width:80%; height:180%; background:rgba(255,255,255,0.08); transform:rotate(25deg); }
    .dash-card .icon { font-size:36px; opacity:0.3; position:absolute; right:15px; top:50%; transform:translateY(-50%); }
    .dash-card .value { font-size:24px; font-weight:700; position:relative; }
    .dash-card .label { font-size:11px; opacity:0.9; position:relative; text-transform:uppercase; letter-spacing:0.5px; }
    .bg-g1 { background:linear-gradient(135deg,#667eea,#764ba2); }
    .bg-g2 { background:linear-gradient(135deg,#11998e,#38ef7d); }
    .bg-g3 { background:linear-gradient(135deg,#f093fb,#f5576c); }
    .bg-g4 { background:linear-gradient(135deg,#4facfe,#00f2fe); }
    .bg-g5 { background:linear-gradient(135deg,#fc5c7d,#6a82fb); }
    .bg-g6 { background:linear-gradient(135deg,#434343,#000000); }
    .bg-g7 { background:linear-gradient(135deg,#f7971e,#ffd200); }
    .bg-g8 { background:linear-gradient(135deg,#00b09b,#96c93d); }
    .chart-box { border-radius:8px; border:1px solid #eee; box-shadow:0 2px 10px rgba(0,0,0,0.04); background:#fff; margin-bottom:15px; }
    .chart-box .chart-header { padding:12px 15px; border-bottom:1px solid #f0f0f0; font-size:13px; font-weight:600; color:#333; }
    .chart-box .chart-body { padding:15px; }
    .qa-btn { display:block; border-radius:8px; padding:12px 10px; text-align:center; transition:all 0.2s; border:1px solid #e9ecef; background:#fff; text-decoration:none !important; }
    .qa-btn:hover { border-color:#667eea; transform:translateY(-2px); box-shadow:0 5px 15px rgba(102,126,234,0.15); }
    .qa-btn .qa-icon { font-size:22px; color:#667eea; margin-bottom:4px; }
    .qa-btn .qa-label { font-weight:600; color:#333; font-size:11px; }
    .recent-tbl th { background:#f8f9fa; font-size:10px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600; color:#666; padding:8px 10px !important; }
    .recent-tbl td { font-size:12px; padding:6px 10px !important; vertical-align:middle; }
    .badge-s { padding:3px 8px; border-radius:10px; font-size:10px; font-weight:600; }
</style>

<div class="content mt-3">

    {{-- Quick Actions (Top) --}}
    @hasanyrole('SuperAdmin|Admin|Manager|Staff')
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="d-flex align-items-center" style="gap:10px;flex-wrap:wrap;">
                <a href="{{ url('/gr/create') }}" class="qa-btn" style="min-width:90px">
                    <div class="qa-icon"><i class="fa fa-plus-circle"></i></div>
                    <div class="qa-label">New GR</div>
                </a>
                <a href="{{ url('/gatepass/create') }}" class="qa-btn" style="min-width:90px">
                    <div class="qa-icon"><i class="fa fa-share-square-o"></i></div>
                    <div class="qa-label">Gatepass</div>
                </a>
                <a href="{{ url('/challan/create') }}" class="qa-btn" style="min-width:90px">
                    <div class="qa-icon"><i class="fa fa-list-alt"></i></div>
                    <div class="qa-label">Challan</div>
                </a>
                @hasanyrole('SuperAdmin|Admin|Manager')
                <a href="{{ url('/frieghtmemo/create') }}" class="qa-btn" style="min-width:90px">
                    <div class="qa-icon"><i class="fa fa-file-text-o"></i></div>
                    <div class="qa-label">Freight Memo</div>
                </a>
                <a href="{{ url('/dash/reports') }}" class="qa-btn" style="min-width:90px">
                    <div class="qa-icon"><i class="fa fa-bar-chart"></i></div>
                    <div class="qa-label">Reports</div>
                </a>
                @endhasanyrole
                <div style="flex:1"></div>
                <div style="text-align:right">
                    <div style="font-size:11px;color:#888;text-transform:uppercase;letter-spacing:0.5px">Office</div>
                    <div style="font-size:16px;font-weight:700;color:#333">{{ $office }}</div>
                    <div style="font-size:11px;color:#888">{{ $today }}</div>
                </div>
            </div>
        </div>
    </div>
    @endhasanyrole

    {{-- KPI Cards Row 1 --}}
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="dash-card bg-g1">
                <i class="fa fa-file-text icon"></i>
                <div class="value">{{ $kpis['grs_today'] }}</div>
                <div class="label">GRs Today</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card bg-g2">
                <i class="fa fa-calendar icon"></i>
                <div class="value">{{ $kpis['grs_month'] }}</div>
                <div class="label">GRs This Month</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card bg-g4">
                <i class="fa fa-inr icon"></i>
                <div class="value">₹{{ number_format($kpis['revenue_month'], 0) }}</div>
                <div class="label">Revenue This Month</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="dash-card bg-g3">
                <i class="fa fa-clock-o icon"></i>
                <div class="value">₹{{ number_format($kpis['pending_topay_amount'], 0) }}</div>
                <div class="label">Pending TO-PAY ({{ $kpis['pending_topay_count'] }})</div>
            </div>
        </div>
    </div>

    {{-- KPI Cards Row 2 --}}
    <div class="row">
        <div class="col-lg-2 col-md-4">
            <div class="dash-card bg-g5">
                <i class="fa fa-truck icon"></i>
                <div class="value">{{ $kpis['pending_delivery'] }}</div>
                <div class="label">Pending Deliveries</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="dash-card bg-g6">
                <i class="fa fa-file-pdf-o icon"></i>
                <div class="value">{{ $kpis['pending_pod'] }}</div>
                <div class="label">Pending POD</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="dash-card bg-g8">
                <i class="fa fa-check icon"></i>
                <div class="value">{{ $kpis['delivered_today'] }}</div>
                <div class="label">Delivered Today</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="dash-card bg-g7">
                <i class="fa fa-check-square icon"></i>
                <div class="value">{{ $kpis['gatepass_month'] }}</div>
                <div class="label">Gatepasses (Month)</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="dash-card bg-g1">
                <i class="fa fa-car icon"></i>
                <div class="value">{{ $kpis['active_vehicles'] }}</div>
                <div class="label">Active Vehicles</div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4">
            <div class="dash-card bg-g2">
                <i class="fa fa-inr icon"></i>
                <div class="value">₹{{ number_format($kpis['collected_topay_month'], 0) }}</div>
                <div class="label">Collected (Month)</div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    @hasanyrole('SuperAdmin|Admin|Manager')
    <div class="row">
        <div class="col-lg-8">
            <div class="chart-box">
                <div class="chart-header"><i class="fa fa-bar-chart"></i> GRs Created — Last 30 Days</div>
                <div class="chart-body"><canvas id="barChart" height="90"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-box">
                <div class="chart-header"><i class="fa fa-pie-chart"></i> Payment Split (This Month)</div>
                <div class="chart-body"><canvas id="pieChart" height="160"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="chart-box">
                <div class="chart-header"><i class="fa fa-line-chart"></i> Revenue Trend (6 Months)</div>
                <div class="chart-body"><canvas id="lineChart" height="120"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-box">
                <div class="chart-header"><i class="fa fa-tasks"></i> Status Distribution (This Month)</div>
                <div class="chart-body"><canvas id="statusChart" height="120"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="chart-box">
                <div class="chart-header"><i class="fa fa-exchange"></i> Weekly Comparison</div>
                <div class="chart-body text-center" style="padding:20px">
                    <div class="row">
                        <div class="col-6">
                            <div style="font-size:28px;font-weight:700;color:#667eea">{{ $charts['this_week'] }}</div>
                            <div style="font-size:11px;color:#888;text-transform:uppercase">This Week</div>
                        </div>
                        <div class="col-6">
                            <div style="font-size:28px;font-weight:700;color:#aaa">{{ $charts['last_week'] }}</div>
                            <div style="font-size:11px;color:#888;text-transform:uppercase">Last Week</div>
                        </div>
                    </div>
                    @php
                        $diff = $charts['this_week'] - $charts['last_week'];
                        $pct = $charts['last_week'] > 0 ? round(($diff / $charts['last_week']) * 100) : 0;
                    @endphp
                    <div style="margin-top:10px;font-size:14px;font-weight:600;color:{{ $diff >= 0 ? '#11998e' : '#f5576c' }}">
                        <i class="fa fa-arrow-{{ $diff >= 0 ? 'up' : 'down' }}"></i>
                        {{ abs($pct) }}% {{ $diff >= 0 ? 'increase' : 'decrease' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            {{-- Recent GRs --}}
            <div class="chart-box">
                <div class="chart-header">
                    <i class="fa fa-list"></i> Recent GRs
                    <a href="{{ url('/gr') }}" class="btn btn-sm btn-primary float-right" style="margin-top:-3px">View All</a>
                </div>
                <div class="chart-body" style="padding:0">
                    <table class="table recent-tbl mb-0">
                        <thead><tr><th>GR No</th><th>Date</th><th>Route</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($recentGRs->take(7) as $gr)
                            <tr>
                                <td><strong>{{ $gr->gr_no }}</strong></td>
                                <td>{{ $gr->copy_date ? $gr->copy_date->format('d M') : '-' }}</td>
                                <td>{{ $gr->from_dest }} → {{ $gr->to_dest }}</td>
                                <td>₹{{ number_format($gr->total_amount, 0) }}</td>
                                <td>
                                    @php $sc = match($gr->status ?? 'created') { 'created'=>'secondary','dispatched'=>'primary','in_transit'=>'warning','delivered'=>'info','closed'=>'success','cancelled'=>'danger',default=>'light' }; @endphp
                                    <span class="badge badge-{{ $sc }} badge-s">{{ ucfirst(str_replace('_',' ',$gr->status ?? 'created')) }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No GRs found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endhasanyrole

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bar Chart — GRs per day
    var barData = @json($charts['grs_per_day']);
    if (document.getElementById('barChart')) {
        new Chart(document.getElementById('barChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: barData.map(d => { var dt = new Date(d.date); return dt.getDate()+'/'+String(dt.getMonth()+1).padStart(2,'0'); }),
                datasets: [{label:'GRs', data: barData.map(d => d.count), backgroundColor:'rgba(102,126,234,0.75)', borderRadius:3}]
            },
            options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{ticks:{font:{size:9}}}} }
        });
    }

    // Pie Chart — Paid vs To-Pay
    if (document.getElementById('pieChart')) {
        new Chart(document.getElementById('pieChart').getContext('2d'), {
            type: 'doughnut',
            data: { labels:['Paid','To-Pay'], datasets:[{data:[{{ $charts['paid_count'] }}, {{ $charts['topay_count'] }}], backgroundColor:['#11998e','#f5576c'], borderWidth:0}] },
            options: { responsive:true, cutout:'65%', plugins:{legend:{position:'bottom',labels:{font:{size:11}}}} }
        });
    }

    // Line Chart — Revenue
    var revData = @json($charts['monthly_revenue']);
    if (document.getElementById('lineChart')) {
        new Chart(document.getElementById('lineChart').getContext('2d'), {
            type: 'line',
            data: { labels: revData.map(d=>d.month), datasets:[{label:'Revenue', data:revData.map(d=>d.total), borderColor:'#667eea', backgroundColor:'rgba(102,126,234,0.1)', fill:true, tension:0.4, pointRadius:4, pointBackgroundColor:'#667eea'}] },
            options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{callback:v => '₹'+(v>=100000?(v/100000).toFixed(1)+'L':v>=1000?(v/1000).toFixed(0)+'k':v)}}} }
        });
    }

    // Status Distribution Chart
    var statusData = @json($charts['status_dist']);
    if (document.getElementById('statusChart')) {
        var labels = Object.keys(statusData).map(s => s.replace('_',' ').replace(/^\w/,c=>c.toUpperCase()));
        var colors = Object.keys(statusData).map(s => ({created:'#6c757d',dispatched:'#4a90d9',in_transit:'#f39c12',delivered:'#17a2b8',closed:'#27ae60',cancelled:'#e74c3c'}[s] || '#999'));
        new Chart(document.getElementById('statusChart').getContext('2d'), {
            type: 'bar',
            data: { labels: labels, datasets:[{label:'Count', data:Object.values(statusData), backgroundColor:colors, borderRadius:4}] },
            options: { indexAxis:'y', responsive:true, plugins:{legend:{display:false}}, scales:{x:{beginAtZero:true,ticks:{stepSize:1}}} }
        });
    }
});
</script>

@endsection
