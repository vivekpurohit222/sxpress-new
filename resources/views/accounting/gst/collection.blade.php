@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>GST Reports</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('accounting.gst.summary') }}">GST Reports</a></li>
            <li class="active">Collection</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3">{{ session('error') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif

    <div class="card-header">
        <strong>GST Collection Report (Output Tax)</strong>
        <small class="text-muted ml-2">{{ $fromDate }} to {{ $toDate }}</small>
    </div>

    <div class="card-body">
        @include('accounting.gst._nav')
        @include('accounting.gst._filter')

        {{-- Additional GST Type Filter --}}
        <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-end gap-2 mb-3 flex-wrap no-print">
            @foreach(request()->except(['gst_type']) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <div>
                <label for="gst_type" class="form-label mb-0 small">GST Type</label>
                <select id="gst_type" name="gst_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="cgst_sgst" {{ request('gst_type') == 'cgst_sgst' ? 'selected' : '' }}>CGST + SGST</option>
                    <option value="igst" {{ request('gst_type') == 'igst' ? 'selected' : '' }}>IGST</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-filter"></i> Apply
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>GR No</th>
                        <th>Date</th>
                        <th>Consignee</th>
                        <th>Consignee GST No</th>
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
                        <td>{{ $entry->party_gst_number ?? '-' }}</td>
                        <td class="text-right">₹{{ number_format($entry->taxable_value, 2) }}</td>
                        <td class="text-right">₹{{ number_format($entry->cgst_amount, 2) }}</td>
                        <td class="text-right">₹{{ number_format($entry->sgst_amount, 2) }}</td>
                        <td class="text-right">₹{{ number_format($entry->igst_amount, 2) }}</td>
                        <td class="text-right">₹{{ number_format($entry->total_tax, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">No GST collection records found for the selected period.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if($entries->count() > 0)
                <tfoot>
                    <tr class="font-weight-bold table-secondary">
                        <td colspan="4"><strong>Total</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($totals['taxable_value'] ?? 0, 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($totals['cgst'] ?? 0, 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($totals['sgst'] ?? 0, 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($totals['igst'] ?? 0, 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($totals['total_tax'] ?? 0, 2) }}</strong></td>
                    </tr>
                </tfoot>
                @endif
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
