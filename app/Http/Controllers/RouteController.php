<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Route;
use App\Models\Station;

class RouteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Route::with(['originStation', 'destinationStation']);

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        $routes = $query->orderBy('created_at', 'desc')->paginate(10);
        $routes->appends($request->query());

        return view('admin.category.Route.route_list', compact('routes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $stations = Station::active()->orderBy('station_name')->get();
        return view('admin.category.Route.route', compact('stations'));
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
            'route_name' => 'required|max:191',
            'route_code' => 'required|max:20|unique:routes,route_code',
            'origin_station_id' => 'nullable|exists:stations,id',
            'destination_station_id' => 'nullable|exists:stations,id',
            'distance_km' => 'nullable|numeric|min:0',
            'duration_hours' => 'nullable|numeric|min:0',
            'base_freight' => 'nullable|numeric|min:0',
            'via_locations' => 'nullable|max:500',
        ], [
            'route_name.required' => 'Route Name is required',
            'route_code.required' => 'Route Code is required',
            'route_code.unique' => 'Route Code already exists',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;

        Route::create($data);

        return redirect('/dash/route')->with('success', 'Route added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $route = Route::with(['originStation', 'destinationStation'])->findOrFail($id);
        return view('admin.category.Route.route_view', compact('route'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $route = Route::findOrFail($id);
        $stations = Station::active()->orderBy('station_name')->get();
        return view('admin.category.Route.route_edit', compact('route', 'stations'));
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
        $route = Route::findOrFail($id);

        $request->validate([
            'route_name' => 'required|max:191',
            'route_code' => 'required|max:20|unique:routes,route_code,' . $id,
            'origin_station_id' => 'nullable|exists:stations,id',
            'destination_station_id' => 'nullable|exists:stations,id',
            'distance_km' => 'nullable|numeric|min:0',
            'duration_hours' => 'nullable|numeric|min:0',
            'base_freight' => 'nullable|numeric|min:0',
            'via_locations' => 'nullable|max:500',
        ], [
            'route_name.required' => 'Route Name is required',
            'route_code.required' => 'Route Code is required',
            'route_code.unique' => 'Route Code already exists',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;

        $route->update($data);

        return redirect('/dash/route')->with('success', 'Route updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $route = Route::findOrFail($id);
        $route->delete();

        return redirect('/dash/route')->with('success', 'Route deleted successfully');
    }

    /**
     * Get route rate by origin and destination.
     * GET /dash/route-rate?from=StationA&to=StationB
     * Per SXPRESS_LOGIC_SKILL section 11 - Used by GR form for auto-filling freight.
     *
     * Supports lookup by:
     * 1. Station names (originStation.station_name, destinationStation.station_name)
     * 2. Route name containing both origin and destination
     */
    public function getRate(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');

        if (!$from || !$to) {
            return response()->json(['error' => 'from and to parameters required'], 400);
        }

        // Try finding by station names first
        $route = Route::whereHas('originStation', function ($q) use ($from) {
                $q->where('station_name', $from);
            })
            ->whereHas('destinationStation', function ($q) use ($to) {
                $q->where('station_name', $to);
            })
            ->where('status', 1)
            ->first();

        // If not found, try by route_name (for routes named like "Rajkot to Surat")
        if (!$route) {
            $route = Route::where('route_name', 'like', '%' . $from . '%')
                ->where('route_name', 'like', '%' . $to . '%')
                ->where('status', 1)
                ->first();
        }

        // If still not found, try reverse direction
        if (!$route) {
            $route = Route::where('route_name', 'like', '%' . $to . '%')
                ->where('route_name', 'like', '%' . $from . '%')
                ->where('status', 1)
                ->first();
        }

        if (!$route) {
            return response()->json(['error' => 'Route not found'], 404);
        }

        return response()->json([
            'distance_km'  => $route->distance_km,
            'rate_per_kg'  => $route->base_freight,
            'route_code'   => $route->route_code,
        ]);
    }
}