@extends('admin.layout.master')
@section('content')
<!-- <head>  <link rel="stylesheet" href="{{asset('public/admin/css/gate-pass.css')}}"></head>
 -->
             <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Gate Pass Edit/h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                         <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            <li><a href="{{url('/dash/gatepass')}}"> Gate Pass Table</a></li>

                            <li class="active">Gate Pass Edit</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

      <div class="content mt-3">
            <div class="animated fadeIn">
                <div class="row">
                  <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-6" >
                                    <strong class="card-title">Delivery Gate Pass</strong>
                                </div>
                                <div class="col-6" >
                                    <strong style="float:right" class="card-title">GST No.: 24AFSPJ7382P1ZI</strong>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                          <!-- Credit Card -->
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
                                   <form action="{{url('/dash/gatepass/'.$id.'/update')}}" method="post" novalidate="novalidate">
                                    {{csrf_field()}}
                                     
                                    <input type="hidden" name="_method" value="PATCH"/>
                                        <div class="row">
                                            <div class="col-12">
                                                <h5 class="text-center">Patel Nagar</h5>
                                                <br>
                                            </div>
                                            <div class="col-12 col-lg-5">
                                              <label for="x_card_code" class="control-label mb-1"><strong>G. P. No.</strong></label>
                                              <div class="input-group">
                                                  <input name="gp_no" value="{{$gp->gp_no}}"type="text" class="form-control cc-name valid" readonly>
                                                  </div>
                                              </div>
                                              <div class="col-lg-2">
                                              </div>
                                              <div class="col-12 col-lg-5">
                                              <label for="x_card_code" class="control-label mb-1"><strong>Date</strong></label>
                                              <div class="input-group">
                                                 <input name="gp_date" type="text" value="{{$gp->gp_date}}" class="form-control cc-name valid" readonly>
                                                 </div>                                         
                                              </div>
                                              <div class="col-12">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>M/s.</strong></label>
                                                <input type="text" id="company" placeholder=""value="{{$gp->m_s}}" name="m_s" class="form-control">
                                            </div>
                                              </div>
                                          </div>

                                       <div class="row">
                                            <div class="col-12 col-lg-6">
                                              <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>From</strong></label>
                                                   <select name="from_dest" id="select"class="form-control">
    <option value="Kashmore Gate" {{ $gp->from_dest == 'Kashmore Gate' ? 'selected' : '' }}>Kashmore Gate</option>
    <option value="Dayabasti" {{ $gp->from_dest == 'Dayabasti' ? 'selected' : '' }}>Dayabasti</option>
    <option value="Swarup Nagar" {{ $gp->from_dest == 'Swarup Nagar'? 'Swarup Nagar' : '' }}>Swarup Nagar</option>
    <option value="Navagam" {{ $gp->from_dest == 'Navagam' ? 'selected' : '' }}>Navagam</option>
    <option value="Shapar (1)" {{ $gp->from_dest == 'Shapar (1)' ? 'selected' : '' }}>Shapar (1)</option>
    <option value="Shapar (2)" {{ $gp->from_dest == 'Shapar (2)' ? 'selected' : '' }}>Shapar (2)</option>
                                                   </select>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                          </div>
                                          <div class="col-12 col-lg-6">
                                              <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>To</strong></label>
                                                   <select name="to_dest" id="select" class="form-control">
                                                    
       <option value="Kashmore Gate" {{ $gp->to_dest == 'Kashmore Gate' ? 'selected' : '' }}>Kashmore Gate</option>
    <option value="Dayabasti" {{ $gp->to_dest == 'Dayabasti' ? 'selected' : '' }}>Dayabasti</option>
    <option value="Swarup Nagar" {{ $gp->to_dest == 'Swarup Nagar' ? 'selected' : '' }}>Swarup Nagar</option>
    <option value="Navagam" {{ $gp->to_dest == 'Navagam' ? 'selected' : '' }}>Navagam</option>
    <option value="Shapar (1)" {{ $gp->to_dest == 'Shapar (1)' ? 'selected' : '' }}>Shapar (1)</option>
    <option value="Shapar (2)" {{ $gp->to_dest == 'Shapar (2)' ? 'selected' : '' }}>Shapar (2)</option>
                
                                                   </select>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                          </div>
                                       </div>
                                      <table class="table table">
                                        <thead>
                                            <tr>
                                                <td scope="col"></td>
                                                <td scope="col"></td>
                                                <td scope="col"></td>
                                                <td scope="col" style="width: 25%"><strong>Amount Rs.</strong></td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td scope="row"><strong>G. R. No. :</strong></td>
                                                <td> <input class="form-control" style="width: 50%" type="text" value="{{$gp->gr_no}}" name="gr_no" readonly></td>
                                                <td scope="row"><strong>Frieght</strong></td>
                                                <td> <input class="form-control" style="width: 80%" type="number"  value="{{$gp->frieght_amount}}" name="frieght_amount"></td>
                                            </tr>
                                            <tr>
                                                <td scope="row"><strong>Weight :</strong></td>
                                                <td> <input class="form-control" style="width: 50%"  value="{{$gp->weight}}"type="number"  name="weight"></td>
                                                <td scope="row"><strong>Labour</strong></td>
                                                <td> <input class="form-control" style="width: 80%" type="number" value="{{$gp->labour_amount}}"  name="labour_amount"></td>
                                            </tr><tr>
                                                <td scope="row"><strong>Nugs :</strong></td>
                                                <td> <input class="form-control" style="width: 50%" type="number"   value="{{$gp->nugs}}"name="nugs"></td>
                                                <td scope="row"><strong>GST</strong></td>
                                                <td> <input class="form-control" style="width: 80%" type="number" value="{{$gp->other}}"  name="gst_amount"></td>
                                            </tr><tr>
                                                <td scope="row"><strong>P. M. :</strong></td>
                                                <td> <input class="form-control" style="width: 50%"  value="{{$gp->pm}}"type="text"  name="pm"></td>
                                                <td scope="row"><strong>D. C.</strong></td>
                                                <td> <input class="form-control" style="width: 80%" type="number"   value="{{$gp->dc_amount}}"name="dc_amount"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2"></td>
                                                <td scope="row"><strong>TOTAL</strong></td>
                                                <td><input class="form-control" style="width: 80%" type="number"  value="{{$gp->total_amount}}" name="total_amount"></td>
                                            </tr>
                                            <tr>
                                                <td colspan="4" rowspan="2"><strong>Note:</strong>
                                                    <textarea name="note" id="textarea-input"  value=""rows="3 " class="form-control">{{$gp->note}}</textarea></td>
                                               
                                            </tr>
                                        </tbody>
                                        </table>
                                        </div>
                                          <div>
                                          <button id="payment-button" type="submit" class="btn btn-success btn-lg btn-block">
                                            
                                              <span id="payment-button-amount">Update</span>
                                              <span id="payment-button-sending" style="display:none;">Sending…</span>
                                          </button>
                                      </div>
                                      </form>
                                      <br>
                                        </div>
                                      </div>
                              </div>
                          </div>
                        </div>
                    </div> <!-- .card -->
            </div><!-- .animated -->
        </div><!-- .content -->
@endsection