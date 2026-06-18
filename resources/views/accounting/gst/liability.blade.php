@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>GST Reports</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('accounting.gst.summary') }}">GST Reports</a></li>
            <li class="active">Liability</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3">{{ session('error') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif

    <div class="card-header">
        <strong>GST Liability Report</strong>
        <small class="text-muted ml-2">{{ $fromDate }} to {{ $toDate }}</small>
    </div>

    <div class="card-body">
        @include('accounting.gst._nav')
        @include('accounting.gst._filter')

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="thead-dark">
                    <tr>
                        <th rowspan="2" class="align-middle">Month</th>
                        <th colspan="4" class="text-center">Output Tax</th>
                        <th colspan="4" class="text-center">Input Tax</th>
                        <th colspan="4" class="text-center">Net Liability</th>
                    </tr>
                    <tr>
                        <th class="text-right">CGST</th>
                        <th class="text-right">SGST</th>
                        <th class="text-right">IGST</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">CGST</th>
                        <th class="text-right">SGST</th>
                        <th class="text-right">IGST</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">CGST</th>
                        <th class="text-right">SGST</th>
                        <th class="text-right">IGST</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyData ?? [] as $month => $data)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($month . '-01')->format('M Y') }}</td>
                        {{-- Output --}}
                        <td class="text-right">₹{{ number_format($data['output']['cgst'] ?? 0, 2) }}</td>
                        <td class="text-right">₹{{ number_format($data['output']['sgst'] ?? 0, 2) }}</td>
                        <td class="text-right">₹{{ number_format($data['output']['igst'] ?? 0, 2) }}</td>
                        <td class="text-right">₹{{ number_format($data['output']['total'] ?? 0, 2) }}</td>
                        {{-- Input --}}
                        <td class="text-right">₹{{ number_format($data['input']['cgst'] ?? 0, 2) }}</td>
                        <td class="text-right">₹{{ number_format($data['input']['sgst'] ?? 0, 2) }}</td>
                        <td class="text-right">₹{{ number_format($data['input']['igst'] ?? 0, 2) }}</td>
                        <td class="text-right">₹{{ number_format($data['input']['total'] ?? 0, 2) }}</td>
                        {{-- Net --}}
                        <td class="text-right">
                            @if(($data['net']['cgst'] ?? 0) < 0)
                                <span class="text-success">Credit: ₹{{ number_format(abs($data['net']['cgst']), 2) }}</span>
                            @else
                                ₹{{ number_format($data['net']['cgst'] ?? 0, 2) }}
                            @endif
                        </td>
                        <td class="text-right">
                            @if(($data['net']['sgst'] ?? 0) < 0)
                                <span class="text-success">Credit: ₹{{ number_format(abs($data['net']['sgst']), 2) }}</span>
                            @else
                                ₹{{ number_format($data['net']['sgst'] ?? 0, 2) }}
                            @endif
                        </td>
                        <td class="text-right">
                            @if(($data['net']['igst'] ?? 0) < 0)
                                <span class="text-success">Credit: ₹{{ number_format(abs($data['net']['igst']), 2) }}</span>
                            @else
                                ₹{{ number_format($data['net']['igst'] ?? 0, 2) }}
                            @endif
                        </td>
                        <td class="text-right">
                            @if(($data['net']['total'] ?? 0) < 0)
                                <span class="text-success">Credit: ₹{{ number_format(abs($data['net']['total']), 2) }}</span>
                            @else
                                ₹{{ number_format($data['net']['total'] ?? 0, 2) }}
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="text-center text-muted">No GST liability records found for the selected period.</td>
                    </tr>
                    @endforelse
                </tbody>
                @if(!empty($grandTotal))
                <tfoot>
                    <tr class="font-weight-bold table-secondary">
                        <td><strong>Grand Total</strong></td>
                        {{-- Output Totals --}}
                        <td class="text-right"><strong>₹{{ number_format($grandTotal['output']['cgst'] ?? 0, 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($grandTotal['output']['sgst'] ?? 0, 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($grandTotal['output']['igst'] ?? 0, 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($grandTotal['output']['total'] ?? 0, 2) }}</strong></td>
                        {{-- Input Totals --}}
                        <td class="text-right"><strong>₹{{ number_format($grandTotal['input']['cgst'] ?? 0, 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($grandTotal['input']['sgst'] ?? 0, 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($grandTotal['input']['igst'] ?? 0, 2) }}</strong></td>
                        <td class="text-right"><strong>₹{{ number_format($grandTotal['input']['total'] ?? 0, 2) }}</strong></td>
                        {{-- Net Totals --}}
                        <td class="text-right">
                            @if(($grandTotal['net']['cgst'] ?? 0) < 0)
                                <strong class="text-success">Credit: ₹{{ number_format(abs($grandTotal['net']['cgst']), 2) }}</strong>
                            @else
                                <strong>₹{{ number_format($grandTotal['net']['cgst'] ?? 0, 2) }}</strong>
                            @endif
                        </td>
                        <td class="text-right">
                            @if(($grandTotal['net']['sgst'] ?? 0) < 0)
                                <strong class="text-success">Credit: ₹{{ number_format(abs($grandTotal['net']['sgst']), 2) }}</strong>
                            @else
                                <strong>₹{{ number_format($grandTotal['net']['sgst'] ?? 0, 2) }}</strong>
                            @endif
                        </td>
                        <td class="text-right">
                            @if(($grandTotal['net']['igst'] ?? 0) < 0)
                                <strong class="text-success">Credit: ₹{{ number_format(abs($grandTotal['net']['igst']), 2) }}</strong>
                            @else
                                <strong>₹{{ number_format($grandTotal['net']['igst'] ?? 0, 2) }}</strong>
                            @endif
                        </td>
                        <td class="text-right">
                            @if(($grandTotal['net']['total'] ?? 0) < 0)
                                <strong class="text-success">Credit: ₹{{ number_format(abs($grandTotal['net']['total']), 2) }}</strong>
                            @else
                                <strong>₹{{ number_format($grandTotal['net']['total'] ?? 0, 2) }}</strong>
                            @endif
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div></div></div></div></div>
@endsection
