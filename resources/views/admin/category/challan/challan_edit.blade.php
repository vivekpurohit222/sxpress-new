@extends('admin.layout.master')
@section('content')
  <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Challan</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}"></li>
                            <li><a href="{{url('/dash/gatepass')}}">Avaliable Challan</a></li>
                            <li class="active"> Edit Challan</li>
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
                                    <strong class="card-title">Edit Challan</strong>
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

                            <div class="alert alert-success d-none" id="challan_msg_div">
                                                   <span id="challan_form_msg">
                            </div>

                                  <hr>
                                  <form action="javascript:void(0)" method="post" id="challanform"form="mail" novalidate="novalidate">
                                        <div class="row">
                                            <div class="col-12">
                                                <h5 class="text-center">{{$users->office}}</h5>
                                                <br>
                                            </div>
                                            <div class="col-12 col-lg-4">
                                              <label for="x_card_code" class="control-label mb-1"><strong>Challan No. </strong></label>
                                              <div class="input-group">
                                                  <input name="challan_no" type="text" value="{{$challan_data->challan_no}}" class="form-control cc-name valid" readonly>
                                                  </div>
                                              </div>
                                              <div class="col-lg-4">
                                              </div>
                                              <div class="col-12 col-lg-4">
                                              <label for="x_card_code" class="control-label mb-1"><strong>Date</strong></label>
                                              <div class="input-group">
                                                  <input name="challan_date" value="{{$challan_data->challan_date}}" type="text" class="form-control cc-name valid" readonly>
                                                  </div>
                                              </div>
                                            <div class="col-12 col-lg-4">
                                              <label for="x_card_code" class="control-label mb-1"><strong>Truck No.</strong></label>
                                              <div class="input-group">
                                                  <input name="truck_no" type="text" value="{{$challan_data->truck_no}}"class="form-control cc-name valid" readonly>
                                                  </div>
                                              </div>
                                              <div class="col-12 col-lg-4">
                                                <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>From</strong></label>
                                                 <select name="from_dest" id="select" class="form-control" disabled >
    <option value="Kashmore Gate" {{ $challan_data->from_dest == 'Kashmore Gate' ? 'selected' : '' }}>Kashmore Gate</option>
    <option value="Rajkot" {{ $challan_data->from_dest == 'Rajkot' ? 'selected' : '' }}>Rajkot</option>
    <option value="Dayabasti" {{ $challan_data->from_dest == 'Dayabasti' ? 'selected' : '' }}>Dayabasti</option>
    <option value="Swarup Nagar" {{ $challan_data->from_dest == 'Swarup Nagar'? 'selected' : '' }}>Swarup Nagar</option>
    <option value="Navagam" {{ $challan_data->from_dest == 'Navagam' ? 'selected' : '' }}>Navagam</option>
    <option value="Shapar (1)" {{ $challan_data->from_dest == 'Shapar (1)' ? 'selected' : '' }}>Shapar (1)</option>
    <option value="Shapar (2)" {{ $challan_data->from_dest == 'Shapar (2)' ? 'selected' : '' }}>Shapar (2)</option>
                                                   </select>
                                                    <select name="from_dest" hidden="true" class="form-control"  >
    <option value="Kashmore Gate" {{$challan_data->from_dest == 'Kashmore Gate' ? 'selected' : '' }}>Kashmore Gate</option>
    <option value="Rajkot" {{ $challan_data->from_dest == 'Rajkot' ? 'selected' : '' }}>Rajkot</option>
    <option value="Dayabasti" {{ $challan_data->from_dest == 'Dayabasti' ? 'selected' : '' }}>Dayabasti</option>
    <option value="Swarup Nagar" {{ $challan_data->from_dest == 'Swarup Nagar'? 'selected' : '' }}>Swarup Nagar</option>
    <option value="Navagam" {{ $challan_data->from_dest == 'Navagam' ? 'selected' : '' }}>Navagam</option>
    <option value="Shapar (1)" {{ $challan_data->from_dest == 'Shapar (1)' ? 'selected' : '' }}>Shapar (1)</option>
    <option value="Shapar (2)" {{ $challan_data->from_dest == 'Shapar (2)' ? 'selected' : '' }}>Shapar (2)</option>
                                                   </select>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                              </div>
                                              <div class="col-12 col-lg-4">
                                                <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>To</strong></label>
                                                 <select name="to_dest"  class="form-control">
      <option value="Rajkot" {{  $challan_data->to_dest == 'Rajkot' ? 'selected' : '' }}>Rajkot </option>                                          
  <option value="Kashmore Gate" {{ $challan_data->to_dest == 'Kashmore Gate' ? 'selected' : '' }}>Kashmore Gate</option>
    <option value="Dayabasti" {{ $challan_data->to_dest == 'Dayabasti' ? 'selected' : '' }}>Dayabasti</option>
    <option value="Swarup Nagar" {{ $challan_data->to_dest == 'Swarup Nagar' ? 'selected' : '' }}>Swarup Nagar</option>
    <option value="Navagam" {{ $challan_data->to_dest == 'Navagam' ? 'selected' : '' }}>Navagam</option>
    <option value="Shapar (1)" {{ $challan_data->to_dest == 'Shapar (1)' ? 'selected' : '' }}>Shapar (1)</option>
    <option value="Shapar (2)" {{ $challan_data->to_dest == 'Shapar (2)' ? 'selected' : '' }}>Shapar (2)</option>
                                                   </select>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                              </div>
                                              <div class="col-12 col-lg-4">
                                                <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>Driver's Name</strong></label>
                                                  <div class="input-group">
                                                  <input name="driver_name" type="text"value="{{$challan_data->driver_name}}" class="form-control cc-name valid" readonly>
                                                  </div>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                              </div>
                                              <!-- <div class="col-12 col-lg-4">
                                                <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>Address</strong></label>
                                                  <div class="input-group">
                                                  <input name="" type="text" class="form-control cc-name valid">
                                                  </div>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div> </div>-->
                                              
                                              <div class="col-12 col-lg-4">
                                                <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>LIC NO.</strong></label>
                                                  <div class="input-group">
                                                  <input name="license" value="{{$challan_data->license}}"type="text" class="form-control cc-name valid" readonly>
                                                  </div>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                              </div>
                                              <div class="col-12 col-lg-4">
                                                <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>Owner</strong></label>
                                                  <div class="input-group">
                                                  <input name="owner_name" value="{{$challan_data->owner_name}}"type="text" class="form-control cc-name valid">
                                                  </div>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                              </div>
                                              <div class="col-12 col-lg-12">
                                                <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>Note</strong></label>
                                                  <div class="input-group">
                                                  <textarea name="note" rows="3" class="form-control">{{$challan_data->note}}</textarea>
                                                  </div>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                              </div>
                                              <div class="col-12 col-lg-12">
                                                <div class="form-group">
                                                  <input id="challan_button" type="button" value="update" class="btn btn-success btn-lg float-right ">
                                            
                                             
                                              </div>
                                              </div>
                                              <div class="col-12 col-lg-12">
                                                <div class="form-group">
                                                </div>
                                                </div> 
                                            </div>
                                               
                                               </form>

                                              <table id='userTable' class="table table-striped table-bordered">
                                                
                                            </tr>
                                        <thead>
                                            <tr>
                                                <th scope="col">GR. No.</th>
                                                <th scope="col" >Nos.</th>
                                                <th scope="col" >Methods of Pkgs.</th>
                                                <th scope="col" >DESCRCRIPTION</th>
                                                <th scope="col" >Weight</th>
                                                <th scope="col" >FREIGHT To Pay</th>
                                                <th scope="col" >FREIGHT Paid</th>
                                                <th scope="col" >Service Tax</th>
                                                <th scope="col" >CR</th>
                                                <th scope="col" >Sur Ch.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                           <form id="contact_us" method="post" action="javascript:void(0)">

                                             <div class="alert alert-success d-none" id="msg_div">
                                                   <span id="res_message">
                                             </div>
                                              <tr class="grinfo">
                                                <input name="challan_no"  id="challan_no"type="text" value="{{$challan_data->challan_no}}" class="form-control cc-name valid" hidden>
                                                <td><input id="grsearch" name="gr_no" type="text"class="form-control ">
                                                  </td>
                                                <td><input id="nugs"name="nugs" type="text"class="form-control cc-name valid" readonly></td>
                                                <td><input id="meth" name="meth" type="text"class="form-control cc-name valid" readonly></td>
                                                <td><input id="description" name="description" type="text"class="form-control cc-name valid" readonly></td>
                                                <td><input id="weight" name="weight" type="number"class="form-control cc-name valid" readonly></td>
                                                <td><input id="paid" name="paid" type="number"class="form-control cc-name valid" readonly></td>
                                                <td><input id="to_pay" name="to_pay" type="number"class="form-control cc-name valid" readonly></td>
                                                <td><input id="sur_ch" name="sur_ch" type="number"class="form-control cc-name valid" readonly></td>
                                                <td><input  id="c_r" name="c_r" type="number"class="form-control cc-name valid" readonly></td>
                                                <td><input id="other" name="other" type="number"class="form-control cc-name valid" readonly ></td>
                                            </tr>

                                            <tr>
                                            <td colspan="12">
                                              <input type='button' value='Search' class="btn btn-primary btn-lg float-left"id="fetchgr">
                                                
                                               <input type="button" value="Challan data Submit" id="send_form" class="btn btn-success btn-lg float-right">
                                            </form> 
                                            <input type='text' value="{{$challan_data->challan_no}}" id='challanfetchdata' name='challanfetchdata' placeholder='' hidden>
                                            <input type='button' style="margin-left:20px"class="btn btn-warning btn-lg float-left" value='Fetch All Records' id='fetchChallanAllRecord'>
                                            </td>
                                          
                                            </tr>
                                            
                                            <td colspan="12">
                                              <table border='1' id='challantable' class="table table-striped table-bordered"  style='border-collapse: collapse;'>
                                            <thead>
                                              <tr>
                                                <th scope="col">GR. No.</th>
                                                <th scope="col" >Nos.</th>
                                                <th scope="col" >Methods of Pkgs.</th>
                                                <th scope="col" >DESCRCRIPTION</th>
                                                <th scope="col" >Weight</th>
                                                <th scope="col" >FREIGHT To Pay</th>
                                                <th scope="col" >FREIGHT Paid</th>
                                                <th scope="col" >Service Tax</th>
                                                <th scope="col" >CR</th>
                                                <th scope="col" >Sur Ch.</th>
                                                <th scope="col" >OP</th>
                                            </tr>
                                          </thead>
                                          <tbody class="ch">
                                            
                                          </tbody>
                                          </table>
                                            </td>
                                            
                                        </tbody>
                                        </table>
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


    </div><!-- /#right-panel -->

@endsection