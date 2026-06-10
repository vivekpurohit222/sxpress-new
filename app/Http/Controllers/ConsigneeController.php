<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Consignee;

class ConsigneeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Consignee::query();

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        $consignees = $query->orderBy('created_at', 'desc')->paginate(10);
        $consignees->appends($request->query());

        return view('admin.category.Consignee.consignee_list', compact('consignees'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.category.Consignee.consignee');
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
            'consignee_name' => 'required|max:191',
            'consignee_code' => 'required|max:20|unique:consignees,consignee_code',
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
        ], [
            'consignee_name.required' => 'Consignee Name is required',
            'consignee_code.required' => 'Consignee Code is required',
            'consignee_code.unique' => 'Consignee Code already exists',
            'email.email' => 'Invalid email format',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;

        Consignee::create($data);

        return redirect('/dash/consignee')->with('success', 'Consignee added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $consignee = Consignee::findOrFail($id);
        return view('admin.category.Consignee.consignee_view', compact('consignee'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $consignee = Consignee::findOrFail($id);
        return view('admin.category.Consignee.consignee_edit', compact('consignee'));
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
        $consignee = Consignee::findOrFail($id);

        $request->validate([
            'consignee_name' => 'required|max:191',
            'consignee_code' => 'required|max:20|unique:consignees,consignee_code,' . $id,
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
        ], [
            'consignee_name.required' => 'Consignee Name is required',
            'consignee_code.required' => 'Consignee Code is required',
            'consignee_code.unique' => 'Consignee Code already exists',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;

        $consignee->update($data);

        return redirect('/dash/consignee')->with('success', 'Consignee updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $consignee = Consignee::findOrFail($id);
        $consignee->delete();

        return redirect('/dash/consignee')->with('success', 'Consignee deleted successfully');
    }

    /**
     * Autocomplete endpoint for AJAX searches.
     * GET /dash/autocomplete/consignee?q=xyz
     * Per SXPRESS_LOGIC_SKILL section 9.
     */
    public function autocomplete(Request $request)
    {
        $q = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = Consignee::where('consignee_name', 'like', '%' . $q . '%')
            ->select('id', 'consignee_name as name', 'address', 'city', 'gst_no', 'phone')
            ->limit(10)
            ->get();

        return response()->json($results);
    }
}