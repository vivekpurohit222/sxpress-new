<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\challan;
use App\Models\challan_iteam;
use App\Models\truckdriver;
use App\Models\gr;
use Carbon\Carbon;
use App\Models\User;
use Validator;
use Illuminate\Support\Facades\DB;

class ChallanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {  $challan_list_page='Challan List';
            $challan=challan::all();
            // $copies=$data->sortByDesc('created_at');
             $truck=truckdriver::pluck('truck_no','id');
            return view('admin.category.challan.challan_list',compact('challan','challan_list_page','truck'));
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $date=date('d-m-y');
        $truck_no = $request->get('truck_no');
        $truck_no= DB::table('truckdrivers')->where('truck_no', $truck_no)->first();
        $users = auth()->user();
        // $challan_no="AA-0000";
        $challan_no = challan::latest()->first()->challan_no;
        if($challan_no == 'Null'){
            $challan_no=='AA-0000';
        }
        
        
            $numeric_id = intval(substr($challan_no, 3)); //retrieve numeric value of 'V001' (1)
                  $numeric_id++; //increment
                  if(mb_strlen($numeric_id) == 1)
                  {
                     $zero_string = '000';
                  }elseif(mb_strlen($numeric_id) == 2)
                  {
                     $zero_string = '00';
                  }elseif(mb_strlen($numeric_id) == 3){
                     $zero_string = '0';
                  
                  }else{
                    $zero_string = '';
                  }

                  $number_ch='AA';
                  if($zero_string=='' and $numeric_id == 1001){
                    $number_ch++;
                    $numeric_id= '0001';
                  }

                  $new_id = $number_ch.'-'.$zero_string.$numeric_id;
                  return view('admin.category.challan.challan',compact('new_id','users','truck_no','date'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
     public function getData($gr_no){
    // get records from database
 
      $arr['data'] = gr::where('gr_no', $gr_no)->first();
   
    echo json_encode($arr);
    exit;
  }
  public function challanIteamStore(Request $request)
    {  
          $data = $request->all(); 
        $result = challan_iteam::insert($data);
       
        // $challan_iteam->nugs = $request->nugs;
        // $challan_iteam->meth = $request->meth;
        // $challan_iteam->description = $request->description;
        // $challan_iteam->weight = $request->description;
        // $challan_iteam->paid = $request->description;
        // $challan_iteam->to_pay = $request->description;
        // $challan_iteam->sur_ch = $request->description;
        // $challan_iteam->c_r = $request->c_r;
        // $challan_iteam->other = $request->other; 
        // $challan_iteam->save(); 
       
       
        
            
        if($result){ 

            return response()->json(['success'=>'Challan Added Successfully!', 'status' => true]);
        }

      
    }
         
       public function challanfetchdata($challan_no)
    {

      $arr['data'] = challan_iteam::where('challan_no', $challan_no)->get();
   
    echo json_encode($arr);
    exit;
       
    }
    public function store(Request $request)
    {
        
          $data = $request->all(); 
        $result = challan::insert($data);
         if($result){ 

            return response()->json(['success'=>'Challan Added Successfully!', 'status' => true]);
        }
       
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($challan_no)
    {
       
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($challan_no)
    {   $users = auth()->user();
          $challan_data= challan::where('challan_no', $challan_no)->first();
         $challan_info=challan_iteam::where('challan_no', $challan_no)->get();
       return view('admin.category.challan.challan_edit',compact('challan_info','challan_data','users'));
    }
public function challandelete($id)
{   //For Deleting Users
             // Totally useless line
        $challan = challan_iteam::find($id); // Can chain this line with the next one
       $challan->delete();
    return response()->json([
        'success' => 'challan info has been deleted successfully!'
    ]);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
