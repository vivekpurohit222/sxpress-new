@extends('admin.layout.master')
@section('content')
<!-- <head>  <link rel="stylesheet" href="{{asset('public/admin/css/gate-pass.css')}}"></head>
 -->
             <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Truck Detail</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            <li><a href="{{url('/dash/truckdriver')}}">Truck Driver Details</a></li>
                            <li class="active">Truck Detail</li>
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
                                    <strong class="card-title">Truck Detail</strong>
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
                                   <form action="{{url('/dash/truckdriver/store')}}" method="post" novalidate="novalidate">
                                    @csrf
                                        <div class="row">
                                            
                                            
                                              <div class="col-12">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>Truck No.</strong></label>
                                                <input type="text" id="company" placeholder="GJ 05 JK 7896"value=""style="text-transform:uppercase" name="truck_no" class="form-control">
                                            </div>
                                              </div>
                                              <div class="col-12">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>Driver Name</strong></label>
                                                <input type="text" id="company" placeholder=""value="" name="driver_name" class="form-control">
                                            </div>
                                              </div>
                                              <div class="col-12">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>License No.</strong></label>
                                                <input type="text" style="text-transform:uppercase"id="company" placeholder="GJ14 20160034761"value="" name="license" class="form-control">
                                            </div>
                                              </div>
                                              <div class="col-12 ">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>Address</strong></label>
                                                <input type="text" id="company" placeholder=""value="" name="driver_address" class="form-control">
                                            </div>
                                              </div>
                                              <div class="col-12 col-lg-6">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>Mobile No</strong></label>
                                                <input type="number" id="company" placeholder=""value="" name="mobile_no1" class="form-control">
                                            </div>
                                              </div>
                                              <div class="col-12 col-lg-6">
                                                  <div class="form-group" >
                                                <label for="company" class=" form-control-label"><strong>Other Mobile No</strong></label>
                                                <input type="number" id="company" placeholder=""value="" name="mobile_no2" class="form-control">
                                            </div>
                                              </div>
                                          </div>

                              
                                        </tbody>
                                        </table>
                                        </div>
                                          <div>
                                          <button id="payment-button" type="submit" class="btn btn-success btn-lg btn-block">
                                            
                                              <span id="payment-button-amount">submit</span>
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