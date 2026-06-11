@extends('admin.layout.master')
@section('content')

<style>
    .gr-form {
        font-size: 12px;
    }
    .gr-form .form-group {
        margin-bottom: 6px;
    }
    .gr-form label {
        margin-bottom: 1px;
        font-size: 11px;
    }
    .gr-form .form-control {
        padding: 3px 6px;
        font-size: 12px;
        height: 28px;
    }
    .gr-form textarea.form-control {
        height: 52px;
        resize: none;
    }
    .gr-form table {
        font-size: 11px;
        margin-bottom: 6px;
    }
    .gr-form table th,
    .gr-form table td {
        padding: 3px 5px;
        vertical-align: middle;
    }
    .gr-form table .form-control {
        height: 24px;
        padding: 2px 4px;
        font-size: 11px;
    }
    .gr-form .btn {
        padding: 5px 20px;
        font-size: 12px;
    }
    .gr-form .card {
        margin-bottom: 0;
    }
    .gr-form .card-body {
        padding: 6px 12px;
    }
    .gr-form .card-header {
        padding: 6px 15px;
    }
    .gr-form .card-title h3 {
        font-size: 14px;
        margin: 0;
    }
    .gr-form .card-title p {
        font-size: 10px;
        margin: 1px 0 0;
    }
    .gr-form hr {
        margin: 6px 0;
    }
    .gr-form .page-title h1 {
        font-size: 18px;
    }
    .gr-form .page-title {
        line-height: 1.2;
    }
    .gr-form .content {
        margin-top: 0.5rem;
    }
    .gr-form .breadcrumbs {
        padding: 0.4rem 0;
        margin-bottom: 0;
    }
    .gr-form .alert {
        padding: 6px 12px;
        margin-bottom: 6px;
        font-size: 11px;
    }
    .gr-form .table-bordered th,
    .gr-form .table-bordered td {
        border: 1px solid #ccc;
    }
    .gr-form .submit-section {
        margin-top: 6px;
    }
    .gr-form .t-c {
        font-size: 9px;
        line-height: 1.3;
        color: #666;
    }
    body {
        overflow-x: hidden;
    }
</style>

<div class="breadcrumbs">
    <div class="col-sm-4">
        <div class="page-header float-left">
            <div class="page-title">
                <h1>GR Edit</h1>
            </div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="page-header float-right">
            <div class="page-title">
                <ol class="breadcrumb text-right">
                    <li><a href="{{url('/dash')}}">Dashboard</a></li>
                    <li><a href="{{url('/gr')}}">GR List</a></li>
                    <li class="active">GR Edit</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content mt-2 gr-form">
    <div class="animated fadeIn">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-6">
                                <strong class="card-title">Subject to Rajkot Jurisdiction</strong>
                            </div>
                            <div class="col-6">
                                <strong style="float:right" class="card-title">GST No.: 24AFSPJ7382P1ZI</strong>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="pay-invoice">
                            <div class="card-body">
                                <div class="card-title">
                                    <h3 class="text-center">SAURASHTRA EXPRESS</h3>
                                    <p style="text-align: center;">H. O. :- 2- Patel Nagar, Bhoja Bhagat Street, 50ft Ring Road, Rajkot. <br>
                                    Contact No. : 097279 00008, 93750 88088</p>
                                </div>
                                <hr>
                                @if(\Session::has('success'))
                                <div class="alert alert-success">
                                    <p>{{\Session::get('success')}}</p>
                                </div>
                                @endif

                                @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif

                                <form action="{{url('/gr/'.$id.'/update')}}" method="post" novalidate="novalidate">
                                    {{csrf_field()}}
                                    <input type="hidden" name="_method" value="PATCH"/>
                                    <div class="row">
                                        <div class="col-12 col-lg-4">
                                            <div class="form-group">
                                                <label class="control-label mb-1"><strong>From</strong></label>
                                                <input type="text" class="form-control" value="{{ $gr->from_dest }}" readonly>
                                                <input type="hidden" name="from_dest" value="{{ $gr->from_dest }}">
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-4">
                                            <div class="form-group">
                                                <label class="control-label mb-1"><strong>To</strong></label>
                                                <select name="to_dest" class="form-control">
                                                    <option value="">Select Destination</option>
                                                    @foreach($destinations as $dest)
                                                        @if($dest !== $gr->from_dest)
                                                            <option value="{{ $dest }}" {{ $gr->to_dest == $dest ? 'selected' : '' }}>{{ $dest }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-4">
                                            <label class="control-label mb-1"><strong>Date</strong></label>
                                            <div class="input-group">
                                                <input name="copy_date" value="{{$gr->copy_date}}" type="text" class="form-control" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group" style="margin-bottom:6px">
                                        <label class="form-control-label"><strong>GR Number</strong></label>
                                        <input type="text" name="gr_no" value="{{$gr->gr_no}}" class="form-control" readonly>
                                    </div>

                                    <div class="row" style="margin-bottom:6px">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="form-control-label"><strong>Consignor</strong></label>
                                                <input type="text" value="{{$gr->consignor}}" name="consignor" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label"><strong>Address</strong></label>
                                                <input type="text" value="{{$gr->consignor_address}}" name="consignor_address" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="form-control-label"><strong>GST No.</strong></label>
                                                <input type="text" value="{{$gr->consignor_gst_no}}" name="consignor_gst_no" class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row" style="margin-bottom:6px">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="form-control-label"><strong>Consignee</strong></label>
                                                <input type="text" value="{{$gr->consignee}}" name="consignee" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label"><strong>Address</strong></label>
                                                <input type="text" value="{{$gr->consignee_address}}" name="consignee_address" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label class="form-control-label"><strong>GST No.</strong></label>
                                                <input type="text" value="{{$gr->consignee_gst_no}}" name="consignee_gst_no" class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                    <table class="table table-bordered table-sm">
                                        <tbody>
                                            <tr>
                                                <td style="width:8%"><input class="form-control" type="number" name="nugs" value="{{$gr->nugs}}" required></td>
                                                <td style="width:10%">
                                                    <select name="meth" class="form-control" required>
                                                        <option value="">Meth</option>
                                                        <option value="Bag" {{ $gr->meth == 'Bag' ? 'selected' : '' }}>Bag</option>
                                                        <option value="Box" {{ $gr->meth == 'Box' ? 'selected' : '' }}>Box</option>
                                                        <option value="Bundle" {{ $gr->meth == 'Bundle' ? 'selected' : '' }}>Bundle</option>
                                                        <option value="Drum" {{ $gr->meth == 'Drum' ? 'selected' : '' }}>Drum</option>
                                                        <option value="Roll" {{ $gr->meth == 'Roll' ? 'selected' : '' }}>Roll</option>
                                                        <option value="Carton" {{ $gr->meth == 'Carton' ? 'selected' : '' }}>Carton</option>
                                                        <option value="Loose" {{ $gr->meth == 'Loose' ? 'selected' : '' }}>Loose</option>
                                                        <option value="Other" {{ $gr->meth == 'Other' ? 'selected' : '' }}>Other</option>
                                                    </select>
                                                </td>
                                                <td style="width:30%"><textarea name="description" rows="2" class="form-control" style="height:36px;resize:none">{{$gr->description}}</textarea></td>
                                                <td style="width:10%"><input class="form-control" type="text" name="pm" value="{{ $gr->pm }}"></td>
                                                <td style="width:10%"><input class="form-control" type="text" name="weight" value="{{ $gr->weight }}"></td>
                                                <td style="width:8%"><input class="form-control sum" type="number" name="frieght_amount" value="{{$gr->frieght_amount}}"></td>
                                                <td style="width:6%"><input class="form-control sum" type="number" name="sur_ch" value="{{$gr->sur_ch}}"></td>
                                                <td style="width:6%"><input class="form-control sum" type="number" name="c_r" value="{{$gr->c_r}}"></td>
                                                <td style="width:6%"><input class="form-control sum" type="number" name="other" value="{{$gr->other}}"></td>
                                                <td style="width:6%"><input class="form-control sum" type="number" name="bc_amount" value="{{$gr->bc_amount}}"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2">
                                                    Bill: <input class="form-control" type="number" name="bill_amount" value="{{$gr->bill_amount}}" style="width:80px;display:inline">
                                                </td>
                                                <td colspan="2">
                                                    E-Way: <input type="text" class="form-control" name="eway_bill_number" value="{{$gr->eway_bill_number}}" style="width:120px;display:inline">
                                                </td>
                                                <td colspan="2" class="text-center">
                                                    To Pay: <input type="checkbox" value="1" name="to_pay" {{$gr->to_pay == 1 ? 'checked' : ''}}>
                                                    &nbsp; Paid: <input type="checkbox" value="1" name="paid" {{$gr->paid == 1 ? 'checked' : ''}}>
                                                </td>
                                                <td colspan="2">
                                                    Total: <input id="totalsum" class="form-control" type="number" name="total_amount" value="{{$gr->total_amount}}" readonly style="background:#eee;width:80px;display:inline">
                                                </td>
                                                <td>{{$gr->from_dest}}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="10">
                                                    <small class="t-c">T&C: (1) Not responsible after 6 months. (2) No delivery without Consignee copy. (3) No responsibility for damage/theft in transit. (4) Receipt without date cancelled.</small>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <div class="submit-section">
                                        <button id="payment-button" type="submit" class="btn btn-success btn-lg btn-block">
                                            <span id="payment-button-amount">Update GR</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- POD Section -->
                    <div class="card mt-2">
                        <div class="card-header" style="padding:6px 15px">
                            <strong class="card-title" style="font-size:13px">Delivery Status & POD</strong>
                        </div>
                        <div class="card-body" style="padding:8px 12px">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm mb-0" style="font-size:11px">
                                        <tr>
                                            <th>Status:</th>
                                            <td>
                                                @php
                                                    $editStatus = $gr->status ?? 'created';
                                                    $editStatusClass = match($editStatus) {
                                                        'created'    => 'bg-secondary',
                                                        'dispatched' => 'bg-primary',
                                                        'in_transit' => 'bg-warning text-dark',
                                                        'delivered'  => 'bg-info',
                                                        'closed'     => 'bg-success',
                                                        'cancelled'  => 'bg-danger',
                                                        default      => 'bg-light text-dark',
                                                    };
                                                    $editStatusLabel = match($editStatus) {
                                                        'created'    => 'Created',
                                                        'dispatched' => 'Dispatched',
                                                        'in_transit' => 'In Transit',
                                                        'delivered'  => 'Delivered',
                                                        'closed'     => 'Closed',
                                                        'cancelled'  => 'Cancelled',
                                                        default      => ucfirst($editStatus),
                                                    };
                                                @endphp
                                                <span class="badge {{ $editStatusClass }}">{{ $editStatusLabel }}</span>
                                            </td>
                                        </tr>
                                        @if($gr->delivered_at)
                                        <tr>
                                            <th>Delivered At:</th>
                                            <td>{{ \Carbon\Carbon::parse($gr->delivered_at)->format('d M Y H:i') }}</td>
                                        </tr>
                                        @endif
                                        @if($gr->pod_file)
                                        <tr>
                                            <th>POD:</th>
                                            <td><a href="{{ url('/gr/'.$gr->id.'/pod') }}" target="_blank" class="btn btn-sm btn-info">View POD</a></td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                                <div class="col-md-6 text-right">
                                    <select name="delivery_status" id="delivery_status" class="form-control" style="width:auto;display:inline" onchange="updateDeliveryStatus({{ $gr->id }}, this.value)">
                                        <option value="created" {{ ($gr->status ?? '') == 'created' ? 'selected' : '' }}>Created</option>
                                        <option value="dispatched" {{ ($gr->status ?? '') == 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                                        <option value="in_transit" {{ ($gr->status ?? '') == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                                        <option value="delivered" {{ ($gr->status ?? '') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                    </select>
                                    <a href="{{ url('/gr/'.$gr->id.'/upload-pod') }}" class="btn btn-primary btn-sm">
                                        <i class="fa fa-upload"></i> Upload POD
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateDeliveryStatus(id, status) {
    $.ajax({
        url: '/gr/' + id + '/update-delivery-status',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            status: status
        },
        success: function(response) {
            alert('Delivery status updated');
            location.reload();
        },
        error: function(xhr) {
            alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update status'));
        }
    });
}

// Auto-calculate total
(function() {
    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.sum').forEach(function(input) {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('totalsum').value = total.toFixed(2);
    }
    document.querySelectorAll('.sum').forEach(function(input) {
        input.addEventListener('input', calculateTotal);
    });
})();
</script>

@endsection
