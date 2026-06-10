<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Station;
use App\Models\Branch;

class StationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Station::with('branch');

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        $stations = $query->orderBy('created_at', 'desc')->paginate(10);
        $stations->appends($request->query());
        $branches = Branch::active()->orderBy('branch_name')->get();

        return view('admin.category.Station.station_list', compact('stations', 'branches'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $branches = Branch::active()->orderBy('branch_name')->get();
        return view('admin.category.Station.station', compact('branches'));
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
            'station_name' => 'required|max:191',
            'station_code' => 'required|max:20|unique:stations,station_code',
            'address' => 'nullable|max:500',
            'city' => 'nullable|max:100',
            'state' => 'nullable|max:100',
            'pincode' => 'nullable|max:10',
            'phone' => 'nullable|max:20',
            'email' => 'nullable|email|max:100',
            'contact_person' => 'nullable|max:100',
            'mobile' => 'nullable|max:20',
            'branch_id' => 'nullable|exists:branches,id',
        ], [
            'station_name.required' => 'Station Name is required',
            'station_code.required' => 'Station Code is required',
            'station_code.unique' => 'Station Code already exists',
            'email.email' => 'Invalid email format',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;
        $data['is_warehouse'] = $request->has('is_warehouse') ? 1 : 0;

        Station::create($data);

        return redirect('/dash/station')->with('success', 'Station added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $station = Station::with('branch')->findOrFail($id);
        return view('admin.category.Station.station_view', compact('station'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $station = Station::findOrFail($id);
        $branches = Branch::active()->orderBy('branch_name')->get();
        return view('admin.category.Station.station_edit', compact('station', 'branches'));
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
        $station = Station::findOrFail($id);

        $request->validate([
            'station_name' => 'required|max:191',
            'station_code' => 'required|max:20|unique:stations,station_code,' . $id,
            'address' => 'nullable|max:500',
            'city' => 'nullable|max:100',
            'state' => 'nullable|max:100',
            'pincode' => 'nullable|max:10',
            'phone' => 'nullable|max:20',
            'email' => 'nullable|email|max:100',
            'contact_person' => 'nullable|max:100',
            'mobile' => 'nullable|max:20',
            'branch_id' => 'nullable|exists:branches,id',
        ], [
            'station_name.required' => 'Station Name is required',
            'station_code.required' => 'Station Code is required',
            'station_code.unique' => 'Station Code already exists',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;
        $data['is_warehouse'] = $request->has('is_warehouse') ? 1 : 0;

        $station->update($data);

        return redirect('/dash/station')->with('success', 'Station updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $station = Station::findOrFail($id);
        $station->delete();

        return redirect('/dash/station')->with('success', 'Station deleted successfully');
    }
}