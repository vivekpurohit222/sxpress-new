@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>GST Reports</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('accounting.gst.summary') }}">GST Reports</a></li>
            <li class="active">Output Tax</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3">{{ session('error') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif

    <div class="card-header">
        <strong>GST Output Tax Report</strong>
        <small class="text-muted ml-2">{{ $fromDate }} to {{ $toDate }}</small>
    </div>

    <div class="card-body">
        @include('accounting.gst._nav')
        @include('accounting.gst._filter')

        {{-- Additional GST Rate Filter --}}
        <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-end gap-2 mb-3 flex-wrap no-print">
            @foreach(request()->except(['rate']) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <div>
                <label for="rate" class="form-label mb-0 small">GST Rate</label>
                <select id="rate" name="rate" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="5" {{ request('rate') == '5' ? 'selected' : '' }}>5%</option>
                    <option value="12" {{ request('rate') == '12' ? 'selected' : '' }}>12%</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-filter"></i> Apply
                </button>
            </div>
        </form>

        {{-- Summary by Rate --}}
        @if(!empty($summary) && count($summary) > 0)
        <div class="mb-4">
            <h6 class="font-weight-bold">Summary by GST Rate</h6>
            <table class="table table-bordered table-sm" style="max-width: 500px;">
                <thead class="thead-light">
                    <tr>
                        <th>GST Rate</th>
                        <th class="text-right">Taxable Value</th>
                        <th class="text-right">Total Tax</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summary as $item)
                    <tr>
                        <td>{{ number_format($item['gst_rate'], 0) }}%</td>
                        <td class="text-right">₹{{ number_format($item['taxable_value'], 2) }}</td>
                        <td class="text-right">₹{{ number_format($item['total_tax'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Main Table --}}
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>GR No</th>
                        <th>Date</th>
                        <th>Party</th>
                        <th>GST Type</th>
                        <th class="text-right">Taxable Value</th>
                        <th class="text-right">CGST</th>
                        <th class="text-right">SGST</th>
                        <th class="text-right">IGST</th>
                        <th class="text-right">Total Tax</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr>
                        <td>{{ $entry->gr_no ?? $entry->taxable->gr_no ?? '-' }}</td>
                        <td>{{ \Carbon\Carbon::parse($entry->transaction_date)->format('d-m-Y') }}</td>
                        <td>{{ $entry->party_name }}</td>
                        <td>{{ $entry->gst_type === 'igst' ? 'IGST' : 'CGST + SGST' }}</td>
                        <td class="text-right">₹{{ number_format($entry->taxable_value, 2) }}</td>
                        <td class="text-right">₹{{ number_format($entry->cgst_amount, 2) }}</td>
                        <td class="text-right">₹{{ number_format($entry->sgst_amount, 2) }}</td>
                        <td class="text-right">₹{{ number_format($entry->igst_amount, 2) }}</td>
                        <td class="text-right">₹{{ number_format($entry->total_tax, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No GST output tax records found for the selected period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($entries->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $entries->withQueryString()->links() }}
            </div>
        @endif
    </div>

</div></div></div></div></div>
@endsection
