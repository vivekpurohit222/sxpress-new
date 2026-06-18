@extends('admin.layout.master')
@section('content')
<!-- <head>  <link rel="stylesheet" href="{{asset('public/admin/css/gate-pass.css')}}"></head>
 -->
             <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1> View Truck Detail</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            <li><a href="{{url('/truckdriver')}}">Truck Driver Details</a></li>
                            <li class="active"> View Truck Detail</li>
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
                                    <strong class="card-title"> View Truck Detail</strong>
                                </div>
                                
                            </div>
                        </div>
                        <div class="card-body">
                          <!-- Credit Card -->
                          <div id="pay-invoice">
                              <div class="card-body">
                                  <div class="card-title">
                                      <h3 class="text-center">SAURASHTRA EXPRESS</h3>
                                      
                                  </div>
                                  <hr>
                               
                                        <div class="row">
                                            
                                            
                                              <div class="col-12">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>Truck No.</strong></label>
                                                <input type="text" id="company" placeholder=""value="{{$truck_driver->truck_no}}"style="text-transform:uppercase" name="truck_no" class="form-control" readonly>
                                            </div>
                                              </div>
                                              <div class="col-12">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>Driver Name</strong></label>
                                                <input type="text" id="company" placeholder=""value="{{$truck_driver->driver_name}}" name="driver_name" class="form-control" readonly>
                                            </div>
                                              </div>
                                              <div class="col-12">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>License No.</strong></label>
                                                <input type="text" style="text-transform:uppercase"id="company" placeholder=""value="{{$truck_driver->license}}" name="license" class="form-control" readonly>
                                            </div>
                                              </div>
                                                <div class="col-12 ">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>Address</strong></label>
                                                <input type="text" id="company" placeholder=""value="{{$truck_driver->driver_address}}" name="driver_address" class="form-control" readonly>
                                            </div>
                                          </div>
                                              <div class="col-12 col-lg-6">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>Mobile No</strong></label>
                                                <input type="number" id="company" placeholder=""value="{{$truck_driver->mobile_no1}}" name="mobile_no1" class="form-control" readonly>
                                            </div>
                                              </div>
                                              <div class="col-12 col-lg-6">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>Other Mobile No</strong></label>
                                                <input type="number" id="company" placeholder=""value="{{$truck_driver->mobile_no2}}" name="mobile_no2" class="form-control" readonly>
                                            </div>
                                              </div>
                                          </div>

                              
                                        </tbody>
                                        </table>
                                        </div>
                                          <div>
                                         
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
