@extends('admin.layout.master')
@section('content')
<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">GR Serial Number Assignment</h3>
                <div class="card-tools">
                    <a href="{{ url('/dash') }}" class="btn btn-default btn-sm">Back to Dashboard</a>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>Important:</strong> Once the first GR is created for a branch, the serial cannot be changed.
                    Set the starting number carefully — it locks after the first GR is booked.
                </div>

                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Office</th>
                            <th>GR Prefix</th>
                            <th>Start From</th>
                            <th>GRs Created</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                        <tr>
                            <td>{{ $branch->branch_name }}</td>
                            <td><span class="badge badge-info">{{ $branch->gr_prefix ?? 'Not Set' }}</span></td>
                            <td>
                                @if($branch->serial)
                                    {{ str_pad($branch->serial->start_from, 5, '0', STR_PAD_LEFT) }}
                                @else
                                    <span class="text-muted">Not Set</span>
                                @endif
                            </td>
                            <td>
                                @if($branch->grs_count > 0)
                                    <span class="badge badge-secondary">{{ $branch->grs_count }}</span>
                                @else
                                    <span class="badge badge-success">0</span>
                                @endif
                            </td>
                            <td>
                                @if($branch->is_locked)
                                    <span class="badge badge-danger"><i class="fas fa-lock"></i> LOCKED</span>
                                @else
                                    <span class="badge badge-success"><i class="fas fa-unlock"></i> Open</span>
                                @endif
                            </td>
                            <td>
                                @if(!$branch->is_locked)
                                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#assignModal{{ $branch->id }}">
                                        <i class="fas fa-edit"></i> {{ $branch->serial ? 'Edit' : 'Set' }}
                                    </button>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>

                        <!-- Modal for each branch -->
                        <div class="modal fade" id="assignModal{{ $branch->id }}" tabindex="-1" role="dialog">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('serial.assign') }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Set GR Serial for {{ $branch->branch_name }}</h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="office" value="{{ $branch->branch_name }}">

                                            <div class="form-group">
                                                <label for="start_from{{ $branch->id }}">Start From Number</label>
                                                <input type="number" class="form-control" id="start_from{{ $branch->id }}"
                                                       name="start_from" min="1" max="99999"
                                                       value="{{ $branch->serial->start_from ?? 1 }}" required>
                                                <small class="text-muted">
                                                    First GR will be: <strong>{{ $branch->gr_prefix ?? 'XX' }}-{{ str_pad(($branch->serial->start_from ?? 1), 5, '0', STR_PAD_LEFT) }}</strong>
                                                </small>
                                            </div>

                                            <div class="form-group">
                                                <label for="notes{{ $branch->id }}">Notes (optional)</label>
                                                <textarea class="form-control" id="notes{{ $branch->id }}" name="notes" rows="2">{{ $branch->serial->notes ?? '' }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No branches found. <a href="{{ url('/branch/create') }}">Add a branch first.</a></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
