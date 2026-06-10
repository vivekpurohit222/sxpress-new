@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs">
    <div class="col-sm-4">
        <div class="page-header float-left">
            <div class="page-title"><h1>Upload POD</h1></div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="page-header float-right">
            <div class="page-title">
                <ol class="breadcrumb text-right">
                    <li><a href="{{ url('/dash') }}">Dashboard</a></li>
                    <li><a href="{{ route('gr.index') }}">GR List</a></li>
                    <li class="active">Upload POD</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content mt-3">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="card">
                    <div class="card-header">
                        <strong class="card-title">Upload Proof of Delivery (POD)</strong>
                    </div>
                    <div class="card-body">

                        @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                        @endif

                        <div class="row">
                            {{-- GR Info --}}
                            <div class="col-md-5">
                                <table class="table table-bordered table-sm">
                                    <tr><th>GR No:</th><td><strong>{{ $gr->gr_no }}</strong></td></tr>
                                    <tr><th>Date:</th><td>{{ $gr->copy_date ? $gr->copy_date->format('d-m-Y') : '-' }}</td></tr>
                                    <tr><th>Consignor:</th><td>{{ $gr->consignor }}</td></tr>
                                    <tr><th>Consignee:</th><td>{{ $gr->consignee }}</td></tr>
                                    <tr><th>From → To:</th><td>{{ $gr->from_dest }} → {{ $gr->to_dest }}</td></tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <span class="badge badge-{{ $gr->status === 'delivered' ? 'success' : ($gr->status === 'in_transit' ? 'warning' : 'primary') }}">
                                                {{ ucfirst(str_replace('_', ' ', $gr->status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>

                                {{-- Show existing POD if already uploaded --}}
                                @if($gr->pod_file)
                                <div class="alert alert-info mt-2">
                                    <strong><i class="fa fa-check-circle"></i> POD Already Uploaded</strong><br>
                                    <small>Uploaded: {{ $gr->pod_date ? $gr->pod_date->format('d-m-Y') : '-' }}</small><br>
                                    <a href="{{ Storage::url($gr->pod_file) }}" target="_blank" class="btn btn-sm btn-success mt-1">
                                        <i class="fa fa-eye"></i> View Current POD
                                    </a>
                                    <p class="mt-1 mb-0"><small class="text-muted">Uploading a new file will replace the existing POD.</small></p>
                                </div>
                                @endif
                            </div>

                            {{-- Upload Form --}}
                            <div class="col-md-7">
                                @if(in_array($gr->status, ['dispatched', 'in_transit']))
                                <form action="{{ url('/gr/'.$gr->id.'/upload-pod') }}" method="POST" enctype="multipart/form-data">
                                    @csrf

                                    <div class="form-group">
                                        <label><strong>POD File (PDF/JPG/PNG) <span class="text-danger">*</span></strong></label>
                                        <input type="file" name="pod_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                        <small class="form-text text-muted">Maximum file size: 5MB</small>
                                    </div>

                                    <div class="form-group">
                                        <label><strong>Delivery Date <span class="text-danger">*</span></strong></label>
                                        <input type="date" name="pod_date" class="form-control" value="{{ old('pod_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                                    </div>

                                    <div class="form-group">
                                        <label><strong>Notes (Optional)</strong></label>
                                        <textarea name="pod_note" class="form-control" rows="2" placeholder="Any notes about the delivery">{{ old('pod_note') }}</textarea>
                                    </div>

                                    <button type="submit" class="btn btn-success btn-lg btn-block">
                                        <i class="fa fa-upload"></i> Upload POD & Mark Delivered
                                    </button>
                                </form>

                                <hr>
                                <div class="text-center">
                                    <p class="text-muted mb-1">Or mark as delivered without uploading a file:</p>
                                    <button type="button" class="btn btn-primary" id="btn-mark-delivered">
                                        <i class="fa fa-check"></i> Mark as Delivered
                                    </button>
                                </div>
                                @elseif($gr->status === 'delivered')
                                <div class="alert alert-success text-center">
                                    <i class="fa fa-check-circle fa-2x"></i>
                                    <h5 class="mt-2">GR Already Delivered</h5>
                                    <p>This GR was marked as delivered{{ $gr->pod_date ? ' on '.$gr->pod_date->format('d M Y') : '' }}.</p>
                                </div>
                                @else
                                <div class="alert alert-warning text-center">
                                    <i class="fa fa-exclamation-triangle fa-2x"></i>
                                    <h5 class="mt-2">Cannot Upload POD</h5>
                                    <p>GR must be in <strong>Dispatched</strong> or <strong>In Transit</strong> status to upload POD.<br>
                                    Current status: <strong>{{ ucfirst($gr->status) }}</strong></p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('btn-mark-delivered')?.addEventListener('click', function() {
    if (!confirm('Mark this GR as delivered without uploading a POD file?')) return;

    var btn = this;
    btn.disabled = true;
    btn.textContent = 'Processing...';

    fetch('/gr/{{ $gr->id }}/mark-delivered', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('GR marked as delivered successfully.');
            window.location.href = '{{ route("gr.edit", $gr->id) }}';
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-check"></i> Mark as Delivered';
        }
    })
    .catch(() => {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-check"></i> Mark as Delivered';
    });
});
</script>

@endsection
