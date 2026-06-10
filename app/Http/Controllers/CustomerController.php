<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(10);
        $customers->appends($request->query());

        return view('admin.category.Customer.customer_list', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.category.Customer.customer');
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
            'customer_name' => 'required|max:191',
            'customer_code' => 'required|max:20|unique:customers,customer_code',
            'customer_type' => 'nullable|in:party,company,individual',
            'gst_no' => 'nullable|max:20',
            'pan_no' => 'nullable|max:20',
            'billing_address' => 'nullable|max:500',
            'billing_city' => 'nullable|max:100',
            'billing_state' => 'nullable|max:100',
            'billing_pincode' => 'nullable|max:10',
            'shipping_address' => 'nullable|max:500',
            'phone' => 'nullable|max:20',
            'email' => 'nullable|email|max:100',
            'contact_person' => 'nullable|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|max:50',
        ], [
            'customer_name.required' => 'Customer Name is required',
            'customer_code.required' => 'Customer Code is required',
            'customer_code.unique' => 'Customer Code already exists',
            'email.email' => 'Invalid email format',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;

        Customer::create($data);

        return redirect('/dash/customer')->with('success', 'Customer added successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        return view('admin.category.Customer.customer_view', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return view('admin.category.Customer.customer_edit', compact('customer'));
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
        $customer = Customer::findOrFail($id);

        $request->validate([
            'customer_name' => 'required|max:191',
            'customer_code' => 'required|max:20|unique:customers,customer_code,' . $id,
            'customer_type' => 'nullable|in:party,company,individual',
            'gst_no' => 'nullable|max:20',
            'pan_no' => 'nullable|max:20',
            'billing_address' => 'nullable|max:500',
            'billing_city' => 'nullable|max:100',
            'billing_state' => 'nullable|max:100',
            'billing_pincode' => 'nullable|max:10',
            'shipping_address' => 'nullable|max:500',
            'phone' => 'nullable|max:20',
            'email' => 'nullable|email|max:100',
            'contact_person' => 'nullable|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|max:50',
        ], [
            'customer_name.required' => 'Customer Name is required',
            'customer_code.required' => 'Customer Code is required',
            'customer_code.unique' => 'Customer Code already exists',
        ]);

        $data = $request->all();
        $data['status'] = $request->has('status') ? 1 : 0;

        $customer->update($data);

        return redirect('/dash/customer')->with('success', 'Customer updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect('/dash/customer')->with('success', 'Customer deleted successfully');
    }
}