<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\gatepass;
use App\Models\gr;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
class GatepassController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {    $gr_no = gr::latest()->first()->gr_no;

          $gatepass_list_page='Gate Pass List';
            $gatepass=gatepass::all();
            // $copies=$data->sortByDesc('created_at');
         
            return view('admin.category.Gatepass.gate_pass_list',compact('gatepass','gatepass_list_page','gr_no'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {    $gp_no = gatepass::latest()->first()->gp_no;
            $gp_no++;
            if($gp_no == 1000)
            {
                $gp_no = 0;
            }
              $date = Carbon::now();
           $date=date('d-m-y');
         $gr_no = $request->get('gr_no');
        $gr= DB::table('grs')->where('gr_no', $gr_no)->first();

       
         return view('admin.category.Gatepass.gate_pass',compact('gr','date','gp_no'));
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
        
        'gp_no'=> 'required',
        'gp_date'=> 'required',
        'm_s'=> 'required',
        'from_dest'=> 'required',

        'to_dest'=> 'required',
        'gr_no'=> 'required',    
        'weight'=> 'required',
        'nugs'=> 'required',
        'pm'=> 'required',

        'frieght_amount'=> 'required',
        'labour_amount'=> 'required',
        'other'=> 'required',
        'dc_amount'=> 'required',    
        'total_amount'=> 'required',
        
        
],[     
       
        'from_dest.required'=>"From Field is Required",    
        'to_dest.required'=>"To Field is Required",  
        'm_s.required'=>"MS Name Field is Required", 
      
        
      
        
        
        'pm.required'=>"PM Field is Required",
        'weight.required'=>"weight Field is Required",   
       
        'nugs.required'=>"Nugs At Field is Required",   
        'frieght_amount.required'=>"Frieght Amount Fieldis Required",   
        
        'other.required'=>"Other amount Field is Required",
        'dc_amount.required'=>"BC Amount Field is Required",    
         
       ]);

          $gatepass=new gatepass([
        'gp_no' => $request->post('gp_no'),
        'gr_no' => $request->post('gr_no'),
        'from_dest'=> $request->post('from_dest'),    
        'to_dest'=> $request->post('to_dest'), 
        'gp_date'=>$request->post('gp_date'), 
        'm_s'=> $request->post('m_s'), 
        'pm'=> $request->post('pm'),
        'weight'=> $request->post('weight'),   
        'nugs'=> $request->post('nugs'),  
        'frieght_amount'=> $request->post('frieght_amount'),   
        'labour_amount'=> $request->post('labour_amount'),
        'other'=> $request->post('other'),
        'dc_amount'=> $request->post('dc_amount'),    
        'total_amount'=> $request->post('total_amount'),
        'note'=>$request->post('note'),
        ]);
        $gatepass->save();
        return Redirect('dash/gatepass')->with('success','Gatepass Added successfully');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $gp=gatepass::find($id);
       return view('admin.category.copies_print',compact('gp','id'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $gp=gatepass::find($id);
       return view('admin.category.Gatepass.gate_pass_edit',compact('gp','id'));
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
        
        'gp_no'=> 'required',
        'gp_date'=> 'required',
        'm_s'=> 'required',
        'from_dest'=> 'required',

        'to_dest'=> 'required',
        'gr_no'=> 'required',    
        'weight'=> 'required',
        'nugs'=> 'required',
        'pm'=> 'required',

        'frieght_amount'=> 'required',
        'labour_amount'=> 'required',
        'other'=> 'required',
        'dc_amount'=> 'required',    
        'total_amount'=> 'required',
        
        
],[     
       
        'from_dest.required'=>"From Field is Required",    
        'to_dest.required'=>"To Field is Required",  
        'm_s.required'=>"MS Name Field is Required", 
        'pm.required'=>"PM Field is Required",
        'weight.required'=>"weight Field is Required",   
        'nugs.required'=>"Nugs At Field is Required",   
        'frieght_amount.required'=>"Frieght Amount Fieldis Required",   
        'other.required'=>"other amount Field is Required",
        'dc_amount.required'=>"BC Amount Field is Required",    
         
       ]);
         $gp=gatepass::find($id);

        $gp->gp_no = $request->get('gp_no');
        $gp->from_dest = $request->get('from_dest');    
        $gp->to_dest = $request->get('to_dest'); 
        $gp->gp_date = $request->get('gp_date');
        $gp->m_s = $request->get('m_s'); 
        $gp->gr_no = $request->get('gr_no');
        $gp->nugs = $request->get('nugs'); 
        $gp->labour_amount = $request->get('labour_amount');
        $gp->note = $request->get('note');
        $gp->pm = $request->get('pm');
        $gp->weight = $request->get('weight');   
        $gp->frieght_amount = $request->get('frieght_amount');    
        $gp->gst_amount = $request->get('other');
        $gp->dc_amount = $request->get('dc_amount');    
        $gp->total_amount = $request->get('total_amount');


       
        $gp->save();
        return Redirect('dash/gatepass')->with('success','Gate Pass Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $gp=gatepass::find($id);
        $gp->delete();
        return Redirect('dash/gatepass')->with('success','Gate Pass  deleted successfully');
            }
}
