@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Serial Number Assignment (FY {{ $fyYear }})</h3>
                <div class="card-tools">
                    <a href="{{ url('/dash') }}" class="btn btn-default btn-sm">Back to Dashboard</a>
                </div>
            </div>
            <div class="card-body">

                @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
                @endif

                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i>
                    <strong>Serial Number Ranges:</strong> Each branch gets a range of numbers per module per financial year.
                    Numbers are generated in format: <code>{{ $fyYear }} - 000001</code>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th>Branch</th>
                                <th>Module</th>
                                <th>Range Start</th>
                                <th>Range End</th>
                                <th>Current Value</th>
                                <th>Remaining</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branches as $branch)
                                @foreach($modules as $module)
                                    @php
                                        $serial = isset($serials[$branch->id]) ? $serials[$branch->id]->where('module', $module)->first() : null;
                                        $remaining = $serial ? ($serial->current_value > 0 ? max(0, $serial->range_end - $serial->current_value) : $serial->range_end - $serial->range_start + 1) : 0;
                                        $warningClass = ($serial && $remaining <= 50) ? 'table-warning' : '';
                                    @endphp
                                    <tr class="{{ $warningClass }}">
                                        @if($loop->first)
                                        <td rowspan="{{ count($modules) }}"><strong>{{ $branch->branch_name }}</strong></td>
                                        @endif
                                        <td>{{ $moduleLabels[$module] }}</td>
                                        <td>{{ $serial ? $serial->range_start : '-' }}</td>
                                        <td>{{ $serial ? $serial->range_end : '-' }}</td>
                                        <td>{{ $serial ? $serial->current_value : 0 }}</td>
                                        <td>
                                            @if($serial && $remaining <= 50)
                                                <span class="badge badge-danger">{{ $remaining }}</span>
                                            @elseif($serial)
                                                <span class="badge badge-success">{{ number_format($remaining) }}</span>
                                            @else
                                                <span class="badge badge-secondary">Not Set</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#editModal_{{ $branch->id }}_{{ $module }}">
                                                <i class="fa fa-edit"></i> Edit
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal_{{ $branch->id }}_{{ $module }}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('serial.assign') }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">{{ $moduleLabels[$module] }} Range — {{ $branch->branch_name }}</h5>
                                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="branch_id" value="{{ $branch->id }}">
                                                        <input type="hidden" name="module" value="{{ $module }}">

                                                        <div class="form-group">
                                                            <label>Range Start</label>
                                                            <input type="number" class="form-control" name="range_start" min="1" max="999999" value="{{ $serial ? $serial->range_start : 1 }}" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Range End</label>
                                                            <input type="number" class="form-control" name="range_end" min="1" max="999999" value="{{ $serial ? $serial->range_end : 999999 }}" required>
                                                        </div>

                                                        @if($serial && $serial->current_value > 0)
                                                        <div class="alert alert-warning mt-2 mb-0">
                                                            <small><i class="fa fa-exclamation-triangle"></i> Current value is <strong>{{ $serial->current_value }}</strong>. Range cannot be set below this.</small>
                                                        </div>
                                                        @endif
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Save Range</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
