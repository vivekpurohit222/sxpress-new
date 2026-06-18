@extends('admin.layout.master')
@section('content')

<div class="breadcrumbs no-print">
    <div class="col-sm-4"><div class="page-header float-left"><div class="page-title"><h1>GST Reports</h1></div></div></div>
    <div class="col-sm-8"><div class="page-header float-right"><div class="page-title">
        <ol class="breadcrumb text-right">
            <li><a href="{{ url('/dash') }}">Dashboard</a></li>
            <li><a href="{{ route('accounting.gst.summary') }}">GST Reports</a></li>
            <li class="active">Settings</li>
        </ol>
    </div></div></div>
</div>

<div class="content mt-3">
<div class="animated fadeIn"><div class="row"><div class="col-md-12"><div class="card">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show m-3">{{ session('success') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show m-3">{{ session('error') }}<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
    @endif

    <div class="card-header">
        <strong>GST Settings</strong>
    </div>

    <div class="card-body">
        @include('accounting.gst._nav')

        <form method="POST" action="{{ route('accounting.gst.settings.update') }}" class="mt-3">
            @csrf

            <div class="form-group row mb-3">
                <label for="company_gst_number" class="col-sm-3 col-form-label">Company GST Number</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control @error('company_gst_number') is-invalid @enderror" id="company_gst_number" name="company_gst_number" value="{{ old('company_gst_number', $companyGstNumber ?? '') }}" maxlength="20" placeholder="e.g. 24AABCS1234A1Z5">
                    @error('company_gst_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-group row mb-3">
                <label for="default_gst_rate" class="col-sm-3 col-form-label">Default GST Rate</label>
                <div class="col-sm-6">
                    <select class="form-control @error('default_gst_rate') is-invalid @enderror" id="default_gst_rate" name="default_gst_rate">
                        <option value="5" {{ old('default_gst_rate', $defaultGstRate ?? '5') == '5' ? 'selected' : '' }}>5%</option>
                        <option value="12" {{ old('default_gst_rate', $defaultGstRate ?? '5') == '12' ? 'selected' : '' }}>12%</option>
                    </select>
                    @error('default_gst_rate')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-group row mb-3">
                <label for="company_state" class="col-sm-3 col-form-label">Company State</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control @error('company_state') is-invalid @enderror" id="company_state" name="company_state" value="{{ old('company_state', $companyState ?? '') }}" placeholder="e.g. Gujarat">
                    @error('company_state')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-6 offset-sm-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>

</div></div></div></div></div>
@endsection
