@extends('admin.layout.master')
@section('content')

 
<div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Create G R </h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            <li><a href="{{url('/dash/gr')}}">Available G R</a></li>

                            <li class="active">Create GR</li>
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
                                    <strong class="card-title">Subject to Rajkot Jurisdiction</strong>
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
                                   <form action="{{url('/dash/gr/store')}}" id="challanform" method="post" novalidate="novalidate">
                                    @csrf
                                        <div class="row">
                                          <div class="col-12 col-lg-4">
                                              <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>From</strong></label>
                                                  <select name="from_dest" id="select"class="form-control" disabled >
    <option value="Kashmore Gate" {{ $users->office == 'Kashmore Gate' ? 'selected' : '' }}>Kashmore Gate</option>
    <option value="Rajkot" {{ $users->office == 'Rajkot' ? 'selected' : '' }}>Rajkot</option>
    <option value="Dayabasti" {{ $users->office == 'Dayabasti' ? 'selected' : '' }}>Dayabasti</option>
    <option value="Swarup Nagar" {{ $users->office == 'Swarup Nagar'? 'selected' : '' }}>Swarup Nagar</option>
    <option value="Navagam" {{ $users->office == 'Navagam' ? 'selected' : '' }}>Navagam</option>
    <option value="Shapar (1)" {{ $users->office == 'Shapar (1)' ? 'selected' : '' }}>Shapar (1)</option>
    <option value="Shapar (2)" {{ $users->office == 'Shapar (2)' ? 'selected' : '' }}>Shapar (2)</option>
                                                   </select>
                                                   <select name="from_dest" hidden="true" id="select"class="form-control"  >
    <option value="Kashmore Gate" {{ $users->office == 'Kashmore Gate' ? 'selected' : '' }}>Kashmore Gate</option>
    <option value="Rajkot" {{ $users->office == 'Rajkot' ? 'selected' : '' }}>Rajkot</option>
    <option value="Dayabasti" {{ $users->office == 'Dayabasti' ? 'selected' : '' }}>Dayabasti</option>
    <option value="Swarup Nagar" {{ $users->office == 'Swarup Nagar'? 'selected' : '' }}>Swarup Nagar</option>
    <option value="Navagam" {{ $users->office == 'Navagam' ? 'selected' : '' }}>Navagam</option>
    <option value="Shapar (1)" {{ $users->office == 'Shapar (1)' ? 'selected' : '' }}>Shapar (1)</option>
    <option value="Shapar (2)" {{ $users->office == 'Shapar (2)' ? 'selected' : '' }}>Shapar (2)</option>
                                                   </select>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                          </div>
                                          <div class="col-12 col-lg-4">
                                              <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>To</strong></label>
                                                  <select name="to_dest" id="select" class="form-control">
                                                     <option value="Kashmore Gate  ">Kashmore Gate </option>
                                                    <option value="Dayabasti">Dayabasti</option>
                                                    <option value="Swarup Nagar">Swarup Nagar</option>
                                                    <option value="Navagam">Navagam</option>
                                                    <option value="Shapar (1)">Shapar (1)</option>
                                                    <option value="Shapar (2)">Shapar (2)</option>
                                                  
                                                   </select>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                          </div>
                                          <div class="col-12 col-lg-4">
                                              <label for="x_card_code" class="control-label mb-1"><strong>Date</strong></label>
                                              <div class="input-group">

                                                  <input name="copy_date" type="text" value="{{$date}}" class="form-control cc-name valid" readonly>
                                                  </div>
                                              </div>
                                          </div>
                                           <div class="form-group"><label for="company" class=" form-control-label">GR Number</label><input type="text" id="company" name='gr_no'placeholder="" value="{{$new_id}}"class="form-control"readonly></div>

                                      <div class="form-group"><label for="company" name='consignor'class=" form-control-label"><strong>Consignor</strong></label><input type="text" id="company" placeholder="" name="consignor"class="form-control"></div> 

                                     <div class="form-group"><label for="company" name="" class=" form-control-label"><strong>Address</strong></label><input type="text" id="company" name="nor_adress"placeholder="" class="form-control"></div>

                                     
                                     <div class="form-group"><label for="company" class=" form-control-label"><strong>GST No.</strong></label><input type="text" id="company"name="nor_gst_no" placeholder="" class="form-control"></div>

                                     <div class="form-group"><label for="company" class=" form-control-label"><strong>Consignee</strong></label><input type="text" id="company" name="consignee" placeholder="" class="form-control"></div>

                                     <div class="form-group"><label for="company" class=" form-control-label"><strong>Address</strong></label><input type="text" id="company" placeholder=""name="nee_adress" class="form-control"></div>

                                     <div class="form-group"><label for="company" class=" form-control-label"><strong>GST No.</strong></label><input type="text" id="company" name="nee_gst_no"placeholder="" class="form-control"></div>
                                       
                                      <table class="table table-bordered">
                                        <tdead>
                                            <tr>
                                                <th scope="col"style="width: 10%">Nugs</th>
                                                <th scope="col"style="width: 12%">Meth.</th>
                                                <th scope="col" style="width: 52%" colspan="3">Description of  Goods (As Per Iteam)</th>
                                                <th scope="col">Charge</th>
                                                <th scope="col"  style="width: 20%">Amount Rs.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                             <tr>
                                                <td scope="row" rowspan="5">
                                                    <input  class="form-control" s type="number" name="nugs">
                                                </td>
                                                <td scope="row" rowspan="5">
                                                   <select name="meth" id="select" class="form-control">
                                                   <option value="C_R" >C_R</option>
                                                    <option value="C_B">C_B</option>
                                                    <option value="Bags" >Bags</option>
                                                   
                                                  </select>
   
                                                </td>
                                            </tr>
                                            <tr>
                                                <td scope="row" colspan="3" rowspan="4"><textarea name="description" id="textarea-input" value="" rows="9" placeholder="Description" class="form-control"></textarea></td></td>
                                                <th>Freigt</th>
                                                <td><input class="form-control sum"  s type="number" name="frieght_amount"></td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Sur. Ch.</th>
                                                <td> <input id='s' class="form-control sum"  s type="number" value="" name="sur_ch"></td>
                                                
                                            </tr>
                                            <tr>
                                                <th scope="row">C/R.</th>
                                                <td><input class="form-control sum" s type="number" name="c_r"></td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Other</th>
                                                <td><input  class="form-control sum" type="number" name="other"></td>
                                            </tr>
                                            <tr>
                                                <th scope="row" colspan="2">Bill Amount : <input class="form-control" type="number" name="bill_amount"></th>
                                                <th scope="row" colspan="3">E-Way Bill No : <input type="text" class="form-control" name="eway_bill_number"></th>
                                                <th scope="row">BC</th>
                                                <td><input class="form-control sum" class="form-control"  s type="number"  name="bc_amount"></td>
                                                
                                            </tr>
                                            <tr>
                                               <th colspan="2">PM :<input class="form-control"  type="text" name="pm"></th>
                                               <th>Weight :<input class="form-control"  type="text" name="weight"></th>
                                               <th><label>To Pay:</label> <input class="form-control abc" value="1" type="checkbox" name="to_pay" default ></th>
                                               <th><label>Paid : </label><input  class="form-control abc"  type="checkbox" value="1" name="paid" default></th>
                                               <th scope="row">Total</th>
                                                <td><input  id="totalsum"class="form-control" type="number" name="total_amount"></td>
                                            </tr>
                                            <tr>
                                                <th colspan="5" rowspan="2">
                                                    Termes & Conditions :
                                                    <p>
                                                        (1) We are not responsible of goods after 6 months of booking date. (2) Goods will not be Deliver without Consignee copy. (3) We are not responsible for damage, shortage or theft in transit. (4) The receipt without date will be cancelled. 
                                                    </p>
                                                </th>
                                                <th colspan="2" rowspan="2"> {{$users->office}} Office Sign</th>
                                            </tr>
                                        </tbody>
                                        </table>
                                      </div>
                                       <div>
                                          <button id="payment-button" type="submit" class="btn btn-success btn-lg btn-block">
                                            
                                              <span id="payment-button-amount">submit</span>
                                              <span id="payment-button-sending" style="display:none;">Sending…</span>
                                          </button>
                                      </div>
                                      </div>
                                  </form>
                              </div>
                          </div>

                        </div>
                    </div> <!-- .card -->

            </div><!-- .animated -->
     
        @endsection

