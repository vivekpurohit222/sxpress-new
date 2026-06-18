@extends('admin.layout.master')
@section('content')

<style>
    .mod-section { margin-bottom: 30px; }
    .mod-section-title {
        font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px;
        color: #90a4ae; margin-bottom: 14px; padding-left: 2px;
    }
    .mod-card {
        background: #fff; border-radius: 12px; border: 1px solid #e9ecef;
        padding: 20px; position: relative; overflow: hidden;
        transition: all 0.2s ease; height: 100%;
    }
    .mod-card:hover { border-color: #667eea; box-shadow: 0 8px 25px rgba(102,126,234,0.12); transform: translateY(-3px); }
    .mod-card .mod-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; color: #fff; margin-bottom: 14px;
    }
    .mod-card .mod-title { font-size: 15px; font-weight: 700; color: #333; margin-bottom: 4px; }
    .mod-card .mod-desc { font-size: 12px; color: #999; margin-bottom: 12px; }
    .mod-card .mod-count {
        display: inline-block; background: #f0f4ff; color: #667eea;
        font-size: 12px; font-weight: 700; padding: 3px 10px; border-radius: 20px;
    }
    .mod-card .mod-actions { margin-top: 14px; }
    .mod-card .mod-actions .btn { font-size: 12px; padding: 5px 12px; border-radius: 6px; }
    .bg-booking { background: linear-gradient(135deg, #667eea, #764ba2); }
    .bg-challan { background: linear-gradient(135deg, #11998e, #38ef7d); }
    .bg-freight { background: linear-gradient(135deg, #f7971e, #ffd200); }
    .bg-import { background: linear-gradient(135deg, #4facfe, #00f2fe); }
    .bg-gatepass { background: linear-gradient(135deg, #fc5c7d, #6a82fb); }
    .bg-dds { background: linear-gradient(135deg, #434343, #000000); }
    .bg-accounting { background: linear-gradient(135deg, #a8a8a8, #d0d0d0); }
    .mod-card.disabled { opacity: 0.55; pointer-events: none; }
    .mod-card .coming-soon {
        position: absolute; top: 12px; right: 12px;
        background: #ffc107; color: #333; font-size: 9px; font-weight: 700;
        padding: 2px 8px; border-radius: 10px; text-transform: uppercase;
    }
    .dash-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 24px; flex-wrap: wrap; gap: 10px;
    }
    .dash-header .greet { font-size: 20px; font-weight: 700; color: #333; }
    .dash-header .greet span { font-weight: 400; color: #888; }
    .dash-header .office-badge {
        background: #f0f4ff; border: 1px solid #d4ddff; color: #667eea;
        padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 600;
    }
</style>

<div class="content mt-3">

    {{-- Header --}}
    <div class="dash-header">
        <div class="greet">
            Hello, {{ $user->name }} <span>— {{ now()->format('l, d M Y') }}</span>
        </div>
        @if($office)
        <div class="office-badge">
            <i class="fa fa-building"></i> {{ $office }}
        </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         BOOKING MODULE
    ═══════════════════════════════════════════════════════════ --}}
    <div class="mod-section">
        <div class="mod-section-title"><i class="fa fa-paper-plane"></i> Booking</div>
        <div class="row">

            {{-- GR --}}
            @if(auth()->user()->can_access('gr'))
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="mod-card">
                    <div class="mod-icon bg-booking"><i class="fa fa-file-text"></i></div>
                    <div class="mod-title">GR (Goods Receipt)</div>
                    <div class="mod-desc">Book new consignments for dispatch</div>
                    <div class="mod-count">{{ $counts['gr'] }} today</div>
                    <div class="mod-actions">
                        <a href="{{ url('/gr/create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> New GR</a>
                        <a href="{{ url('/gr') }}" class="btn btn-outline-secondary btn-sm">View All</a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Challan --}}
            @if(auth()->user()->can_access('challan'))
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="mod-card">
                    <div class="mod-icon bg-challan"><i class="fa fa-list-alt"></i></div>
                    <div class="mod-title">Challan</div>
                    <div class="mod-desc">Load GRs onto a truck for transport</div>
                    <div class="mod-count">{{ $counts['challan'] }} today</div>
                    <div class="mod-actions">
                        <a href="{{ url('/challan/create') }}" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> New Challan</a>
                        <a href="{{ url('/challan') }}" class="btn btn-outline-secondary btn-sm">View All</a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Freight Memo --}}
            @if(auth()->user()->can_access('freight_memo'))
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="mod-card">
                    <div class="mod-icon bg-freight"><i class="fa fa-truck"></i></div>
                    <div class="mod-title">Freight Memo</div>
                    <div class="mod-desc">Settle truck owner payments</div>
                    <div class="mod-count">{{ $counts['freight_memo'] }} today</div>
                    <div class="mod-actions">
                        <a href="{{ url('/frieghtmemo/create') }}" class="btn btn-warning btn-sm"><i class="fa fa-plus"></i> New Memo</a>
                        <a href="{{ url('/frieghtmemo') }}" class="btn btn-outline-secondary btn-sm">View All</a>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         DELIVERY MODULE
    ═══════════════════════════════════════════════════════════ --}}
    <div class="mod-section">
        <div class="mod-section-title"><i class="fa fa-inbox"></i> Delivery</div>
        <div class="row">

            {{-- Import Challan --}}
            @if(auth()->user()->can_access('import_challan'))
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="mod-card">
                    <div class="mod-icon bg-import"><i class="fa fa-download"></i></div>
                    <div class="mod-title">Import Challan</div>
                    <div class="mod-desc">Receive incoming challans at your office</div>
                    <div class="mod-count">{{ $counts['import_challan'] }} awaiting</div>
                    <div class="mod-actions">
                        <a href="{{ url('/import-challan') }}" class="btn btn-info btn-sm"><i class="fa fa-arrow-down"></i> View Incoming</a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Gate Pass --}}
            @if(auth()->user()->can_access('gate_pass'))
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="mod-card">
                    <div class="mod-icon bg-gatepass"><i class="fa fa-check-square"></i></div>
                    <div class="mod-title">Gate Pass</div>
                    <div class="mod-desc">Release goods to consignee</div>
                    <div class="mod-count">{{ $counts['gate_pass'] }} today</div>
                    <div class="mod-actions">
                        <a href="{{ url('/gatepass/create') }}" class="btn btn-danger btn-sm"><i class="fa fa-plus"></i> New Gate Pass</a>
                        <a href="{{ url('/gatepass') }}" class="btn btn-outline-secondary btn-sm">View All</a>
                    </div>
                </div>
            </div>
            @endif

            {{-- DDS --}}
            @if(auth()->user()->can_access('dds'))
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="mod-card">
                    <div class="mod-icon bg-dds"><i class="fa fa-calendar-check-o"></i></div>
                    <div class="mod-title">DDS</div>
                    <div class="mod-desc">Daily Delivery Statement</div>
                    <div class="mod-count">{{ $counts['dds'] }} deliveries today</div>
                    <div class="mod-actions">
                        <a href="{{ url('/dds') }}" class="btn btn-dark btn-sm"><i class="fa fa-eye"></i> View Today</a>
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         ACCOUNTING MODULE (Coming Soon)
    ═══════════════════════════════════════════════════════════ --}}
    <div class="mod-section">
        <div class="mod-section-title"><i class="fa fa-calculator"></i> Accounting</div>
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="mod-card disabled">
                    <span class="coming-soon">Coming Soon</span>
                    <div class="mod-icon bg-accounting"><i class="fa fa-calculator"></i></div>
                    <div class="mod-title">Accounting & Finance</div>
                    <div class="mod-desc">Ledger, Vouchers, Cash Book, GST, Reports — will be available in the next update.</div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
