<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicle;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Vehicle::query();

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $vehicles = $query->orderBy('created_at', 'desc')->paginate(10);
        $vehicles->appends($request->query());

        return view('admin.category.Vehicle.vehicle_list', compact('vehicles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.category.Vehicle.vehicle');
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
            'vehicle_number' => 'required|max:20|unique:vehicles,vehicle_number',
            'vehicle_type' => 'nullable|max:50',
            'chassis_no' => 'nullable|max:50',
            'engine_no' => 'nullable|max:50',
            'capacity' => 'nullable|numeric|min:0',
            'capacity_unit' => 'nullable|max:20',
            'insurance_date' => 'nullable|date',
            'tax_date' => 'nullable|date',
            'permit_date' => 'nullable|date',
            'owner_name' => 'nullable|max:191',
            'owner_phone' => 'nullable|max:20',
            'status' => 'nullable|in:active,inactive,under_maintenance',
        ], [
            'vehicle_number.required' => 'Vehicle Number is required',
            'vehicle_number.unique' => 'Vehicle Number already exists',
        ]);

        $data = $request->all();
        $data['is_own'] = $request->has('is_own') ? 1 : 0;

        Vehicle::create($data);

        return redirect('/dash/vehicle')->with('success', 'Vehicle added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        return view('admin.category.Vehicle.vehicle_view', compact('vehicle'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        return view('admin.category.Vehicle.vehicle_edit', compact('vehicle'));
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
        $vehicle = Vehicle::findOrFail($id);

        $request->validate([
            'vehicle_number' => 'required|max:20|unique:vehicles,vehicle_number,' . $id,
            'vehicle_type' => 'nullable|max:50',
            'chassis_no' => 'nullable|max:50',
            'engine_no' => 'nullable|max:50',
            'capacity' => 'nullable|numeric|min:0',
            'capacity_unit' => 'nullable|max:20',
            'insurance_date' => 'nullable|date',
            'tax_date' => 'nullable|date',
            'permit_date' => 'nullable|date',
            'owner_name' => 'nullable|max:191',
            'owner_phone' => 'nullable|max:20',
            'status' => 'nullable|in:active,inactive,under_maintenance',
        ], [
            'vehicle_number.required' => 'Vehicle Number is required',
            'vehicle_number.unique' => 'Vehicle Number already exists',
        ]);

        $data = $request->all();
        $data['is_own'] = $request->has('is_own') ? 1 : 0;

        $vehicle->update($data);

        return redirect('/dash/vehicle')->with('success', 'Vehicle updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();

        return redirect('/dash/vehicle')->with('success', 'Vehicle deleted successfully');
    }
}