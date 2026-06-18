@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>GST Reports</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('accounting.gst.summary') }}">GST Reports</a></li>
            <li class="active">Summary</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3">{{ session('error') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show m-3">{{ session('success') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif

    <div class="card-header">
        <strong>GST Summary</strong>
        <small class="text-muted ml-2">{{ $fromDate }} to {{ $toDate }}</small>
        @if(!empty($companyGst))
            <span class="badge bg-secondary ms-2">GSTIN: {{ $companyGst }}</span>
        @else
            <span class="badge bg-warning text-dark ms-2">Company GST Number not configured</span>
        @endif
    </div>

    <div class="card-body">
        @include('accounting.gst._nav')
        @include('accounting.gst._filter')

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>Component</th>
                        <th class="text-right">Output Tax</th>
                        <th class="text-right">Input Tax</th>
                        <th class="text-right">Net Liability</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>CGST</td>
                        <td class="text-right">₹{{ number_format($summaryData['output']['cgst'] ?? 0, 2) }}</td>
                        <td class="text-right">₹{{ number_format($summaryData['input']['cgst'] ?? 0, 2) }}</td>
                        <td class="text-right">
                            @if(($summaryData['net']['cgst'] ?? 0) < 0)
                                <span class="text-success">Credit Available: ₹{{ number_format(abs($summaryData['net']['cgst']), 2) }}</span>
                            @else
                                ₹{{ number_format($summaryData['net']['cgst'] ?? 0, 2) }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>SGST</td>
                        <td class="text-right">₹{{ number_format($summaryData['output']['sgst'] ?? 0, 2) }}</td>
                        <td class="text-right">₹{{ number_format($summaryData['input']['sgst'] ?? 0, 2) }}</td>
                        <td class="text-right">
                            @if(($summaryData['net']['sgst'] ?? 0) < 0)
                                <span class="text-success">Credit Available: ₹{{ number_format(abs($summaryData['net']['sgst']), 2) }}</span>
                            @else
                                ₹{{ number_format($summaryData['net']['sgst'] ?? 0, 2) }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>IGST</td>
                        <td class="text-right">₹{{ number_format($summaryData['output']['igst'] ?? 0, 2) }}</td>
                        <td class="text-right">₹{{ number_format($summaryData['input']['igst'] ?? 0, 2) }}</td>
                        <td class="text-right">
                            @if(($summaryData['net']['igst'] ?? 0) < 0)
                                <span class="text-success">Credit Available: ₹{{ number_format(abs($summaryData['net']['igst']), 2) }}</span>
                            @else
                                ₹{{ number_format($summaryData['net']['igst'] ?? 0, 2) }}
                            @endif
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold table-secondary">
                        <td><strong>Total</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($summaryData['output']['total'] ?? 0, 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($summaryData['input']['total'] ?? 0, 2) }}</strong></td>
                        <td class="text-right">
                            @if(($summaryData['net']['total'] ?? 0) < 0)
                                <strong class="text-success">Credit Available: ₹{{ number_format(abs($summaryData['net']['total']), 2) }}</strong>
                            @else
                                <strong>₹{{ number_format($summaryData['net']['total'] ?? 0, 2) }}</strong>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div></div></div></div></div>
@endsection
