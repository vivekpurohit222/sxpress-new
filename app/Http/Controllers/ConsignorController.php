<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Consignor;

class ConsignorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Consignor::query();

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        $consignors = $query->orderBy('created_at', 'desc')->paginate(10);
        $consignors->appends($request->query());

        return view('admin.category.Consignor.consignor_list', compact('consignors'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.category.Consignor.consignor');
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
            'consignor_name' => 'required|max:191',
            'consignor_code' => 'required|max:20|unique:consignors,consignor_code',
            'gst_no' => 'nullable|max:20',
            'pan_no' => 'nullable|max:20',
            'address' => 'nullable|max:500',
            'city' => 'nullable|max:100',
            'state' => 'nullable|max:100',
            'pincode' => 'nullable|max:10',
            'phone' => 'nullable|max:20',
            'email' => 'nullable|email|max:100',
            'contact_person' => 'nullable|max:100',
            'mobile' => 'nullable|max:20',
            'rate_per_nug' => 'nullable|numeric|min:0',
            'rate_per_kg' => 'nullable|numeric|min:0',
        ], [
            'consignor_name.required' => 'Consignor Name is required',
            'consignor_code.required' => 'Consignor Code is required',
            'consignor_code.unique' => 'Consignor Code already exists',
            'email.email' => 'Invalid email format',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;
        $data['rate_per_nug'] = $request->input('rate_per_nug', 0);
        $data['rate_per_kg'] = $request->input('rate_per_kg', 0);

        Consignor::create($data);

        return redirect('/dash/consignor')->with('success', 'Consignor added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $consignor = Consignor::findOrFail($id);
        return view('admin.category.Consignor.consignor_view', compact('consignor'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $consignor = Consignor::findOrFail($id);
        return view('admin.category.Consignor.consignor_edit', compact('consignor'));
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
        $consignor = Consignor::findOrFail($id);

        $request->validate([
            'consignor_name' => 'required|max:191',
            'consignor_code' => 'required|max:20|unique:consignors,consignor_code,' . $id,
            'gst_no' => 'nullable|max:20',
            'pan_no' => 'nullable|max:20',
            'address' => 'nullable|max:500',
            'city' => 'nullable|max:100',
            'state' => 'nullable|max:100',
            'pincode' => 'nullable|max:10',
            'phone' => 'nullable|max:20',
            'email' => 'nullable|email|max:100',
            'contact_person' => 'nullable|max:100',
            'mobile' => 'nullable|max:20',
            'rate_per_nug' => 'nullable|numeric|min:0',
            'rate_per_kg' => 'nullable|numeric|min:0',
        ], [
            'consignor_name.required' => 'Consignor Name is required',
            'consignor_code.required' => 'Consignor Code is required',
            'consignor_code.unique' => 'Consignor Code already exists',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;
        $data['rate_per_nug'] = $request->input('rate_per_nug', 0);
        $data['rate_per_kg'] = $request->input('rate_per_kg', 0);

        $consignor->update($data);

        return redirect('/dash/consignor')->with('success', 'Consignor updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $consignor = Consignor::findOrFail($id);
        $consignor->delete();

        return redirect('/dash/consignor')->with('success', 'Consignor deleted successfully');
    }

    /**
     * Autocomplete endpoint for AJAX searches.
     * GET /dash/autocomplete/consignor?q=xyz
     * Per SXPRESS_LOGIC_SKILL section 9.
     */
    public function autocomplete(Request $request)
    {
        $q = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = Consignor::where('consignor_name', 'like', '%' . $q . '%')
            ->select('id', 'consignor_name as name', 'address', 'city', 'gst_no', 'phone')
            ->limit(10)
            ->get();

        return response()->json($results);
    }
}