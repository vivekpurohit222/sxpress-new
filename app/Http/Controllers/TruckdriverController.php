<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\truckdriver;

class TruckdriverController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = truckdriver::query();

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        $truck_drivers = $query->orderBy('created_at', 'desc')->paginate(10);
        $truck_drivers->appends($request->query());

        return view('admin.category.TruckDriver.truck_driver_list', compact('truck_drivers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
         return view('admin.category.TruckDriver.truck_driver');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
         $request->validate([

            'driver_name' => 'required',
             'license' => ['required', 'regex:/^(([A-Za-z]{2}[0-9]{2})( )|([A-Za-z]{2}-[0-9]{2}))((19|20)[0-9][0-9])[0-9]{7}$/'],
              'truck_no' => ['required', 'regex:/^[A-Za-z]{2}[ -][0-9]{1,2}(?: [A-Za-z])?(?: [A-Za-z]*)? [0-9]{4}$/'],
            'mobile_no1'=>'nullable|min:10|max:10|regex:/[0-9]{10}/',
            'mobile_no2'=>'nullable|min:10|max:10|regex:/[0-9]{10}/',
            'driver_address'=>'required',

        ],[

        'driver_name.required'=>"Driver Name is Required",
        'truck_no.required'=>"Truck No is Required",
        'truck_no.regex'=>"invalid Truck number.",
        'license.regex'=>"invalid license Number",
        'license.required'=>"License Number is Required",
        'mobile_no1.regex'=>"Wrong mobile number",
        'mobile_no2.regex'=>"Wrong Other Mobile number",
        ]);

           $truck_driver = new truckdriver([

        'driver_name' => ucwords($request->post('driver_name')),

        'truck_no'=>  Str::upper($request->post('truck_no')) ,
        'license'=> Str::upper($request->post('license')) ,
        'mobile_no1'=>$request->post('mobile_no1'),
        'mobile_no2'=> $request->post('mobile_no2'),
        'driver_address'=>$request->post('driver_address'),
        'status' => $request->has('status') ? 1 : 0,

        ]);
                   $truck_driver->save();
        return redirect('/dash/truckdriver')->with('success','Truck Detail Added successfully');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $truck_driver=truckdriver::find($id);
       return view('admin.category.TruckDriver.truck_driver_view',compact('truck_driver','id'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $truck_driver=truckdriver::find($id);
       return view('admin.category.TruckDriver.truck_driver_edit',compact('truck_driver','id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([

        'driver_name' => 'required',
        'license' => ['required', 'regex:/^(([A-Za-z]{2}[0-9]{2})( )|([A-Za-z]{2}-[0-9]{2}))((19|20)[0-9][0-9])[0-9]{7}$/'],
        'truck_no' => ['required', 'regex:/^[A-Za-z]{2}[ -][0-9]{1,2}(?: [A-Za-z])?(?: [A-Za-z]*)? [0-9]{4}$/'],
        'mobile_no1'=>'nullable|min:10|max:10|regex:/[0-9]{10}/',
        'mobile_no2'=>'nullable|min:10|max:10|regex:/[0-9]{10}/',
          'driver_address'=>'required'
        ],[

        'driver_name.required'=>"Driver Name is Required",
        'truck_no.required'=>"Truck No is Required",
        'license.required'=>"License Number is Required",
        'truck_no.regex'=>"invalid Truck number.",
        'license.regex'=>"invalid license Number",
        'mobile_no1.regex'=>"Wrong mobile number",
        'mobile_no2.regex'=>"Wrong Other Mobile number",
        ]);


          $truck_driver=truckdriver::find($id);
         $truck_driver->driver_name = ucwords($request->get('driver_name'));

        $truck_driver->truck_no = Str::upper($request->get('truck_no'));
        $truck_driver->license = Str::upper($request->get('license'));
        $truck_driver->mobile_no1 = $request->get('mobile_no1');
        $truck_driver->mobile_no2 = $request->get('mobile_no2');
     $truck_driver->driver_address= $request->get('driver_address');
     $truck_driver->status = $request->has('status') ? 1 : 0;


        $truck_driver->save();
        return redirect('/dash/truckdriver')->with('success','Truck Detail Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $truck_driver=truckdriver::find($id);
        $truck_driver->delete();
        return redirect('/dash/truckdriver')->with('success','Truck Detail deleted successfully');
    }
}