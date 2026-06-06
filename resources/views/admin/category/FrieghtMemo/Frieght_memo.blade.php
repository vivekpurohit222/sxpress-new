@extends('admin.layout.master')
@section('content')

        <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Frieght Memo </h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/admin')}}">Dashboard</a></li>
                            <li><a href="{{url('/admin/back/frieghtmemo')}}">Available Frieght Memo</a></li>
                            <li class="active">Frieght Memo</li>
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
                                    <strong class="card-title">Frieght Memo</strong>
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
                                  <form action="" method="post" novalidate="novalidate">
                                        <div class="row">
                                            <div class="col-12 col-lg-4">
                                              <label for="x_card_code" class="control-label mb-1"><strong>F. M. No.</strong></label>
                                              <div class="input-group">
                                                  <input name="fm_no" type="text" class="form-control cc-name valid">
                                                  </div>
                                              </div>
                                              <div class="col-12 col-lg-4">
                                                <label for="x_card_code" class="control-label mb-1"><strong>Date</strong></label>
                                                <div class="input-group">
                                                  <input name="fm_date" type="date" class="form-control cc-name valid">
                                                  </div>
                                              </div>
                                              <div class="col-12 col-lg-4">
                                                <label for="x_card_code" class="control-label mb-1"><strong>Truck No.</strong></label>
                                                <select   class="form-control myselect "name="truck_no">
                                          @foreach($truck as $item)
                                             <option value="{{$item}}">{{ $item }}</option>
                                        @endforeach
                                          </select>
                                              </div>
                                          </div>
                                       <div class="row">
                                            <div class="col -12 col-lg-6">
                                              <div class="form-group">
                                                  <label for="cc-exp" class="control-label mb-1"><strong>From</strong></label>
                                                  <select name="from_dest" id="select" class="form-control">
                                                    <option value="Kashmore Gate">Kashmore Gate </option>
                                                    <option value="Dayabasti">Dayabasti</option>
                                                    <option value="Swarup Nagar">Swarup Nagar</option>
                                                    <option value="Navagam">Navagam</option>
                                                    <option value="Shapar (1)">Shapar (1)</option>
                                                    <option value="Shapar (2)">Shapar (2)</option>
                                                   </select>
                                                  <span class="help-block" data-valmsg-for="cc-exp" data-valmsg-replace="true"></span>
                                              </div>
                                          </div>
                                          <div class="col-12 col-lg-6">
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
                                       </div>
                                      <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th scope="col" style="width: 5%"><strong>To.</strong></th>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <th>1</th>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                            </tr>
                                            <tr>
                                                <th>2</th>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                            </tr>
                                            <tr>
                                                <th>3</th>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control"class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                            </tr>
                                            <tr>
                                                <th>4</th>                                             <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                            </tr>
                                            <tr>
                                                <th colspan="2">Total Amount :</th>                    <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                                <td> <input class="form-control" type="text"  name=""></td>
                                            </tr>
                                            <tr>
                                                <th colspan="3" rowspan="5">
                                                Commission Calculation :    
                                                <textarea name="textarea-input" id="textarea-input" rows="10"  class="form-control"></textarea>
                                                </th>
                                                 <th scope="row">Truck Frieght</th>
                                                <td><input class="form-control" type="text"  name=""></td>
                                            </tr>
                                            <tr>
                                                
                                                <th scope="row">Commission</th>
                                                <td><input class="form-control" type="text"  name=""></td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Other Charges</th>
                                                <td><input class="form-control" type="text"  name=""></td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Extra</th>
                                                <td><input class="form-control" type="text"  name=""></td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Balance To S. N.</th>
                                                <td><input class="form-control" type="text"  name=""></td>
                                            </tr>
                                            <tr>
                                                <th colspan="5" > 
                                                   Note: <textarea name="textarea-input" id="textarea-input" rows="4" class="form-control"></textarea>     
                                                </th>
                                            </tr>
                                            <tr>
                                                <th colspan="3">Termes & Conditions :
                                                    <p>
                                                        1. if you fail To arrive over goods within <input type="" style="width: 10%" name=""> days. <br> &nbsp; &nbsp; 
                                                        Rs. 1500/- will be deducted from your Freight as Late Delivery.
                                                    </p>
                                                </th>
                                                <th colspan="2">
                                                    Saurashtra Express
                                                </th>
                                            </tr>
                                        </tbody>
                                        </table>
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