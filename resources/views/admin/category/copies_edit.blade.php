@extends('admin.layout.master')
@section('content')
<head><link rel="stylesheet" href="{{asset('public/admin/css/copies.css')}}">
 </head>
      <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Copy Edit</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            <li><a href="{{url('/dash/gr')}}">Copy List Table</a></li>

                            <li class="active">Copy Edit</li>
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
                                  <hr>
                                  <!-- route('copies_update','$id') -->
                          <form action="{{url('/dash/gr/'.$id.'/update')}}" method="post" novalidate="novalidate">
                                    {{csrf_field()}}
                                    <input type="hidden" name="_method" value="PATCH"/>
                                        <div class="row">
                                          <div class="col-12 col-lg-4">
                                              <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>From</strong></label>
                                                  <select name="from_dest" id="select"class="form-control">
      <option value="Rajkot" {{ $copy->from_dest == 'Rajkot' ? 'selected' : '' }}>Rajkot </option>                                              
    <option value="Kashmore Gate" {{ $copy->from_dest == 'Kashmore Gate' ? 'selected' : '' }}>Kashmore Gate</option>
    <option value="Dayabasti" {{ $copy->from_dest == 'Dayabasti' ? 'selected' : '' }}>Dayabasti</option>
    <option value="Swarup Nagar" {{ $copy->from_dest == 'Swarup Nagar'? 'Swarup Nagar' : '' }}>Swarup Nagar</option>
    <option value="Navagam" {{ $copy->from_dest == 'Navagam' ? 'selected' : '' }}>Navagam</option>
    <option value="Shapar (1)" {{ $copy->from_dest == 'Shapar (1)' ? 'selected' : '' }}>Shapar (1)</option>
    <option value="Shapar (2)" {{ $copy->from_dest == 'Shapar (2)' ? 'selected' : '' }}>Shapar (2)</option>
                                                   </select>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                          </div>
                                          <div class="col-12 col-lg-4">
                                              <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>To</strong>></label>
                                                  <select name="to_dest" id="select" class="form-control">
          <option value="Rajkot" {{ $copy->to_dest == 'Rajkot' ? 'selected' : '' }}>Rajkot </option>                                          
       <option value="Kashmore Gate" {{ $copy->to_dest == 'Kashmore Gate' ? 'selected' : '' }}>Kashmore Gate</option>
    <option value="Dayabasti" {{ $copy->to_dest == 'Dayabasti' ? 'selected' : '' }}>Dayabasti</option>
    <option value="Swarup Nagar" {{ $copy->to_dest == 'Swarup Nagar' ? 'selected' : '' }}>Swarup Nagar</option>
    <option value="Navagam" {{ $copy->to_dest == 'Navagam' ? 'selected' : '' }}>Navagam</option>
    <option value="Shapar (1)" {{ $copy->to_dest == 'Shapar (1)' ? 'selected' : '' }}>Shapar (1)</option>
    <option value="Shapar (2)" {{ $copy->to_dest == 'Shapar (2)' ? 'selected' : '' }}>Shapar (2)</option>
                
                                                   </select>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                          </div>
                                         <div class="col-12 col-lg-4">
                                              <label for="x_card_code" class="control-label mb-1"><strong>Date</strong></label>
                                              <div class="input-group">
                                                  <input name="copy_date" value="{{$copy->copy_date}}" type="text" class="form-control cc-name valid"readonly>
                                                  </div>
                                              </div>
                                          </div>
                                      
                                       <div class="form-group"><label for="company" class=" form-control-label"><strong>GR Number</strong></label><input type="text" id="company" name='gr_no'placeholder="" value="{{$copy->gr_no}}"class="form-control"readonly></div>

                                      <div class="form-group"><label for="company" name='consignor'class=" form-control-label"><strong>Consignor</strong></label><input type="text" id="company" placeholder=""value="{{$copy->consignor}}" name="consignor"class="form-control"></div> 

                                     <div class="form-group"><label for="company" name="" class=" form-control-label"><strong>Address</strong></label><input type="text" id="company" name="nor_adress"placeholder="" value="{{$copy->nor_adress}}"class="form-control"></div>

                                     
                                     <div class="form-group"><label for="company" class=" form-control-label"><strong>GST No.</strong></label><input type="text" id="company"name="nor_gst_no" value="{{$copy->nor_gst_no}}"placeholder="" class="form-control"></div>

                                     <div class="form-group"><label for="company" class=" form-control-label"><strong>Consignee</strong></label><input type="text" id="company" name="consignee" placeholder="" value="{{$copy->consignee}}"class="form-control"></div>

                                     <div class="form-group"><label for="company" class=" form-control-label"><strong>Address</strong></label><input type="text" id="company" placeholder=""name="nee_adress" value="{{$copy->nee_adress}}"class="form-control"></div>

                                     <div class="form-group"><label for="company" class=" form-control-label"><strong>GST No.</strong></label><input type="text" id="company" name="nee_gst_no"placeholder=""value="{{$copy->nee_gst_no}}" class="form-control"></div>
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
                                                    <input  class="form-control" s type="number" name="nugs" value="{{$copy->nugs}}">
                                                </td>

                                                <td scope="row" rowspan="5">
                                                   <select name="meth" id="select" class="form-control">
                                                   <option value="C_R" {{ $copy->meth == 'C_R' ? 'selected' : '' }}>C_R</option>
                                                   <option value="C_B" {{ $copy->meth == 'C_B' ? 'selected' : '' }}>C_B</option>
                                                   <option value="Bags" {{ $copy->meth == 'Bags' ? 'selected' : '' }}>Bags</option>
                                                   
                                                  </select>
                                                               </td>
                                            </tr>
                                            <tr>
                                                <td scope="row" colspan="3" rowspan="4"><textarea name="description" id="textarea-input" value="" rows="9" placeholder="Description" class="form-control">{{$copy->description}}</textarea></textarea></td>
                                                <th>Freigt</th>
                                                <td><input class="form-control sum"  s type="number"value="{{$copy->frieght_amount}}" name="frieght_amount"></td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Sur. Ch.</th>
                                                <td> <input class="form-control sum"  s type="number" value="{{$copy->sur_ch}}" name="sur_ch"></td>
                                                
                                            </tr>
                                            <tr>
                                                <th scope="row">C/R.</th>
                                                <td><input class="form-control sum" s type="number"value="{{$copy->c_r}}" name="c_r"></td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Other</th>
                                                <td><input value="{{$copy->other}}" class="form-control sum" type="number" name="other"></td>
                                            </tr>
                                            <tr>
                                                <th scope="row" colspan="2">Bill Amount : <input class="form-control" value="{{$copy->bill_amount}}"type="number" name="bill_amount"></th>
                                                <th scope="row" colspan="3">E-Way Bill No : <input type="text" value="{{$copy->eway_bill_number}}"class="form-control" name="eway_bill_number"></th>
                                                <th scope="row">BC</th>
                                                <td><input class="form-control sum" class="form-control"  s type="number" value="{{$copy->bc_amount}}" name="bc_amount"></td>
                                                
                                            </tr>
                                            <tr>
                                               <th colspan="2">PM :<input class="form-control"value="{{$copy->pm}}"  type="text" name="pm"></th>
                                               <th>Weight :<input class="form-control"  type="text" name="weight"value="{{$copy->weight}}"></th>
                                               <th><label>To Pay:</label> <input class="form-control abc" value="1" type="checkbox" name="to_pay"{{($copy->to_pay == 1 ? 'checked':'')}}></th>
                                               <th><label>Paid : </label><input  class="form-control abc"  type="checkbox" value="1" name="paid"{{($copy->paid == 1 ? 'checked':'')}} ></th>
                                               <th scope="row">Total</th>
                                                <td><input id="totalsum" class="form-control" type="number" value="{{$copy->total_amount}}" name="total_amount"></td>
                                            </tr>
                                            <tr>
                                                <th colspan="5" rowspan="2">
                                                    Termes & Conditions :
                                                    <p>
                                                        (1) We are not responsible of goods after 6 months of booking date. (2) Goods will not be Deliver without Consignee copy. (3) We are not responsible for damage, shortage or theft in transit. (4) The receipt without date will be cancelled. 
                                                    </p>
                                                </th>
                                                <th colspan="2" rowspan="2"> {{$copy->from_dest}} Office Sign</th>
                                            </tr>
                                        </tbody>
                                        </table>
                                      <!-- <table class="table table-bordered">
                                        <tdead>
                                            <tr>
                                                <th scope="col">Packages</th>
                                                <th scope="col" style="width: 52%" colspan="3">Description of  Goods (As Per Iteam)</th>
                                                <th scope="col">Charge</th>
                                                <th scope="col"  style="width: 20%">Amount Rs.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                             <tr>
                                                <td scope="row" rowspan="5">
                                                    <input  class="form-control" s type="number"value="{{$copy->packeges}}" name="packeges">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td scope="row" colspan="3" rowspan="4"><textarea name="description" id="textarea-input" value="" rows="9" placeholder="Description" class="form-control">{{$copy->description}}</textarea></td>
                                                <th>Freigt</th>
                                                <td><input class="form-control"  s type="number"value="{{$copy->frieght_amount}}" name="frieght_amount"></td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Sur. Ch.</th>
                                                <td> <input class="form-control"value="{{$copy->sur_ch}}"  s type="number" value="20" name="sur_ch"></td>
                                                
                                            </tr>
                                            <tr>
                                                <th scope="row">C/R.</th>
                                                <td><input class="form-control" s type="number"value="{{$copy->c_r}}" name="c_r"></td>
                                            </tr>
                                            <tr>
                                                <th scope="row">GST</th>
                                                <td><input  class="form-control" value="{{$copy->gst_amount}}"type="number" name="gst_amount"></td>
                                            </tr>
                                            <tr>
                                                <th scope="row">PM</th>
                                                <th scope="row">Weight</th>
                                                <th scope="row">Paid At</th>
                                                <th scope="row">T B B At</th>
                                                <th scope="row">BC</th>
                                                <td><input class="form-control" class="form-control"  s type="number" value="{{$copy->bc_amount}}"value="30" name="bc_amount"></td>
                                                
                                            </tr>
                                            <tr>
                                               <td><input class="form-control"  type="text" value="{{$copy->pm}}"name="pm"></td>
                                               <td><input class="form-control"  type="number"value="{{$copy->weight}}" name="weight"></td>
                                               <td><input class="form-control"  type="number"value="{{$copy->paid_at}}" name="paid_at"></td>
                                               <td><input class="form-control"  type="number" value="{{$copy->tbb_at}}"name="tbb_at"></td>
                                               <th scope="row">Total</th>
                                                <td><input  value="{{$copy->total_amount}}"class="form-control" type="number" name="total_amount"></td>

                                            </tr>
                                            <tr>
                                                <th colspan="4" rowspan="2">
                                                    Termes & Conditions :
                                                    <p>
                                                        (1) We are not responsible of goods after 6 months of booking date. (2) Goods will not be Deliver without Consignee copy. (3) We are not responsible for damage, shortage or theft in transit. (4) The receipt without date will be cancelled. 
                                                    </p>
                                                </th>
                                                <th colspan="2" rowspan="2">Sign</th>
                                            </tr>
                                        </tbody>
                                        </table> -->
                                      </div>
                                       <div>
                                          <button id="payment-button" type="submit" class="btn btn-success btn-lg btn-block">
                                            
                                              <span id="payment-button-amount">Update</span>
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
