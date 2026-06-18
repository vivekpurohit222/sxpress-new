@extends('admin.layout.master')
@section('content')
             <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Edit Truck Detail</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="{{url('/dash')}}">Dashboard</a></li>
                            <li><a href="{{url('/truckdriver')}}">Truck Driver Details</a></li>
                            <li class="active">Edit Truck Detail</li>
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
                                    <strong class="card-title">Edit Truck Detail</strong>
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
                                  <form action="{{url('/truckdriver/'.$id.'/update')}}" method="post" novalidate="novalidate">
                                    {{csrf_field()}}

                                    <input type="hidden" name="_method" value="PATCH"/>
                                        <div class="row">


                                              <div class="col-12 col-lg-6">
                                                  <div class="form-group" >
                                                <label for="truck_no" class=" form-control-label"><strong>Truck No. *</strong></label>
                                                <input type="text" id="truck_no" placeholder="GJ 05 JK 7896" value="{{ old('truck_no', $truck_driver->truck_no) }}" style="text-transform:uppercase" name="truck_no" class="form-control" required>
                                            </div>
                                              </div>
                                              <div class="col-12 col-lg-6">
                                                  <div class="form-group" >
                                                <label for="driver_name" class=" form-control-label"><strong>Driver Name *</strong></label>
                                                <input type="text" id="driver_name" placeholder="" value="{{ old('driver_name', $truck_driver->driver_name) }}" name="driver_name" class="form-control" required>
                                            </div>
                                              </div>
                                              <div class="col-12 col-lg-6">
                                                  <div class="form-group" >
                                                <label for="license" class=" form-control-label"><strong>License No. *</strong></label>
                                                <input type="text" style="text-transform:uppercase" id="license" placeholder="GJ14 20160034761" value="{{ old('license', $truck_driver->license) }}" name="license" class="form-control" required>
                                            </div>
                                              </div>
                                               <div class="col-12 col-lg-6">
                                                  <div class="form-group" >
                                                <label for="driver_address" class=" form-control-label"><strong>Address *</strong></label>
                                                <input type="text" id="driver_address" placeholder="" value="{{ old('driver_address', $truck_driver->driver_address) }}" name="driver_address" class="form-control" required>
                                            </div>
                                          </div>
                                              <div class="col-12 col-lg-6">
                                                  <div class="form-group" >
                                                <label for="mobile_no1" class=" form-control-label"><strong>Mobile No</strong></label>
                                                <input type="text" id="mobile_no1" placeholder="" value="{{ old('mobile_no1', $truck_driver->mobile_no1) }}" name="mobile_no1" class="form-control">
                                            </div>
                                              </div>
                                              <div class="col-12 col-lg-6">
                                                  <div class="form-group" >
                                                <label for="mobile_no2" class=" form-control-label"><strong>Other Mobile No</strong></label>
                                                <input type="text" id="mobile_no2" placeholder="" value="{{ old('mobile_no2', $truck_driver->mobile_no2) }}" name="mobile_no2" class="form-control">
                                            </div>
                                              </div>
                                              <div class="col-12 col-lg-6">
                                                  <div class="form-group" >
                                                    <div class="form-check mt-4 pt-2">
                                                        <input type="checkbox" class="form-check-input" id="status" name="status" {{ $truck_driver->status ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="status">Active</label>
                                                    </div>
                                                </div>
                                              </div>
                                          </div>


                                          <div>
                                          <button id="payment-button" type="submit" class="btn btn-success btn-lg btn-block">
                                              <span id="payment-button-amount">Update</span>
                                              <span id="payment-button-sending" style="display:none;">Sending…</span>
                                          </button>
                                      </div>
                                      </form>
                                      <br>
                                      <a href="{{ url('/truckdriver') }}" class="btn btn-default">Cancel</a>
                                        </div>
                                      </div>
                          </div>
                        </div>
                    </div> <!-- .card -->
            </div><!-- .animated -->
        </div><!-- .content -->
@endsection
