<?php

namespace App\Http\Controllers\dash;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Gr;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class GrController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {       $copies_list_page='Copies List';
            /*$copies=gr::all();*/
            $ci = Auth::user()->office;
            $copies =DB::table('users')
                ->leftjoin('grs','grs.from_dest','=','office')
                ->select('grs.*','users.office')
                ->where('grs.from_dest', '=',$ci)
                ->get();

            

            return view('admin.category.copies_list',compact('copies','copies_list_page'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $users = auth()->user();
        $ci = Auth::user()->office;
        $gr_no = DB::table('grs')
            ->join('users','users.office','=','from_dest')
            ->select('grs.gr_no')
            ->latest('grs.created_at')->first()->gr_no;

        $office =new User();
        $officecenter = $office->officeall();
        
        // switch ($officecenter) {
        //     case "Rajkot":
        //                     $gr_no = DB::table('grs')
        //                     ->join('users','users.office','=','from_dest')
        //                     ->select('grs.gr_no')
        //                     ->latest('grs.created_at')->first()->gr_no;
        //                 $numeric_id = intval(substr($gr_no, 3)); //retrieve numeric value of 'V001' (1)
        //                 $numeric_id++; //increment
        //                 if(mb_strlen($numeric_id) == 1)
        //                 {
        //                     $zero_string = '0000';
        //                 }elseif(mb_strlen($numeric_id) == 2)
        //                 {
        //                     $zero_string = '000';
        //                 }elseif(mb_strlen($numeric_id) == 3){
        //                     $zero_string = '00';
        //                 }elseif(mb_strlen($numeric_id) == 4){
        //                     $zero_string = '0';
        //                 }else{
        //                     $zero_string = '';
        //                 }
        //                 $grini = substr($gr_no, 0,2);
        //                 if($zero_string=='' and $numeric_id == 10001){
        //                     $grini++;
        //                     $numeric_id= '00001';
        //                 }

        //                 $new_id = $grini.'-'.$zero_string.$numeric_id;
        //                 $date = Carbon::now();
        //                 $date=date('d-m-y');
        //                 return view('admin.category.copies',compact('new_id','date','users'));
        //             break;
        //     case "Navagam":
        //                  /*$gr_no = gr::latest()->first()->gr_no;*/
        //                     $gr_no = DB::table('grs')
        //                     ->join('users','users.office','=','from_dest')
        //                     ->select('grs.gr_no')
        //                     ->latest('grs.created_at')->first()->gr_no;
        //                 if($gr_no == 'Null'){
        //                     $gr_no=='AA-10001';
        //                 }
        //                 $numeric_id = intval(substr($gr_no, 3)); //retrieve numeric value of 'V001' (1)
        //                 $numeric_id++; //increment
        //                 if(mb_strlen($numeric_id) == 1)
        //                 {
        //                     $zero_string = '0000';
        //                 }elseif(mb_strlen($numeric_id) == 2)
        //                 {
        //                     $zero_string = '000';
        //                 }elseif(mb_strlen($numeric_id) == 3){
        //                     $zero_string = '00';
        //                 }elseif(mb_strlen($numeric_id) == 4){
        //                     $zero_string = '0';
        //                 }else{
        //                     $zero_string = '';
        //                 }
        //                 $grini = substr($gr_no, 0,2);
        //                 if($numeric_id == 20000){
        //                     $grini++;
        //                     $numeric_id= '10001';
        //                 }
        //                 $new_id = $grini.'-'.$zero_string.$numeric_id;
        //                 $date = Carbon::now();
        //                 $date=date('d-m-y');
        //                 return view('admin.category.copies',compact('new_id','date','users'));
        //              break;
        //     case "Kashmore Gate":
        //                     /*$gr_no = gr::latest()->first()->gr_no;*/
        //                     $gr_no = DB::table('grs')
        //                     ->join('users','users.office','=','from_dest')
        //                     ->select('grs.gr_no')
        //                     ->latest('grs.created_at')->first()->gr_no;
        //                 if($gr_no == 'Null'){
        //                     $gr_no=='AA-20001';
        //                 }
        //                 $numeric_id = intval(substr($gr_no, 3)); //retrieve numeric value of 'V001' (1)
        //                 $numeric_id++; //increment
        //                 if(mb_strlen($numeric_id) == 1)
        //                 {
        //                     $zero_string = '0000';
        //                 }elseif(mb_strlen($numeric_id) == 2)
        //                 {
        //                     $zero_string = '000';
        //                 }elseif(mb_strlen($numeric_id) == 3){
        //                     $zero_string = '00';
        //                 }elseif(mb_strlen($numeric_id) == 4){
        //                     $zero_string = '0';
        //                 }else{
        //                     $zero_string = '';
        //                 }
        //                 $grini = substr($gr_no, 0,2);
        //                 if($numeric_id == 30001){
        //                     $grini++;
        //                     $numeric_id= '00001';
        //                 }
        //                 $new_id = $grini.'-'.$zero_string.$numeric_id;
        //                 $date = Carbon::now();
        //                 $date=date('d-m-y');
        //                 return view('admin.category.copies',compact('new_id','date','users'));
        //       break;
        //     default:
        //       /*$gr_no = gr::latest()->first()->gr_no;*/
        //         $gr_no = DB::table('grs')
        //             ->join('users','users.office','=','from_dest')
        //             ->select('grs.gr_no')
        //             ->latest('grs.created_at')->first()->gr_no;
        //         if($gr_no == 'Null'){
        //             $gr_no=='AA-30001';
        //         }
        //         $numeric_id = intval(substr($gr_no, 3)); //retrieve numeric value of 'V001' (1)
        //         $numeric_id++; //increment
        //         if(mb_strlen($numeric_id) == 1)
        //         {
        //             $zero_string = '0000';
        //         }elseif(mb_strlen($numeric_id) == 2)
        //         {
        //             $zero_string = '000';
        //         }elseif(mb_strlen($numeric_id) == 3){
        //             $zero_string = '00';
        //         }elseif(mb_strlen($numeric_id) == 4){
        //             $zero_string = '0';
        //         }else{
        //             $zero_string = '';
        //         }
        //         $grini = substr($gr_no, 0,2);
        //         if($numeric_id == 40001){
        //             $grini++;
        //             $numeric_id= '00001';
        //         }
        //         $new_id = $grini.'-'.$numeric_id;
        //         $date = Carbon::now();
        //         $date=date('d-m-y');
        //         return view('admin.category.copies',compact('new_id','date','users'));
        //   }
            if(strcmp($officecenter,"Rajkot")==0){
                /*$gr_no = gr::latest()->first()->gr_no;*/
                $gr_no = DB::table('grs')
                    ->join('users','users.office','=','from_dest')
                    ->select('grs.gr_no')
                    ->latest('grs.created_at')->first()->gr_no;
                $numeric_id = intval(substr($gr_no, 3)); //retrieve numeric value of 'AA-00001' (1)
                $numeric_id++; //increment
                if(mb_strlen($numeric_id) == 1)
                {
                    $zero_string = '0000';
                }elseif(mb_strlen($numeric_id) == 2)
                {
                    $zero_string = '000';
                }elseif(mb_strlen($numeric_id) == 3){
                    $zero_string = '00';
                }elseif(mb_strlen($numeric_id) == 4){
                    $zero_string = '0';
                }else{
                    $zero_string = '';
                }
                $grini = substr($gr_no, 0,2);
                if($zero_string=='' and $numeric_id == 10001){
                    $grini++;
                    $numeric_id= '00001';
                }

                $new_id = $grini.'-'.$zero_string.$numeric_id;
                $date = Carbon::now();
                $date=date('d-m-y');
                return view('admin.category.copies',compact('new_id','date','users'));
            }
            elseif(strcmp($officecenter,"Navagam")==0){
                /*$gr_no = gr::latest()->first()->gr_no;*/
                $gr_no = DB::table('grs')
                    ->join('users','users.office','=','from_dest')
                    ->select('grs.gr_no')
                    ->latest('grs.created_at')->first()->gr_no;
                if($gr_no == 'Null'){
                    $gr_no=='AA-10001';
                }
                $numeric_id = intval(substr($gr_no, 3)); //retrieve numeric value of 'V001' (1)
                $numeric_id++; //increment
                if(mb_strlen($numeric_id) == 1)
                {
                    $zero_string = '0000';
                }elseif(mb_strlen($numeric_id) == 2)
                {
                    $zero_string = '000';
                }elseif(mb_strlen($numeric_id) == 3){
                    $zero_string = '00';
                }elseif(mb_strlen($numeric_id) == 4){
                    $zero_string = '0';
                }else{
                    $zero_string = '';
                }
                $grini = substr($gr_no, 0,2);
                if($numeric_id == 20000){
                    $grini++;
                    $numeric_id= '10001';
                }
                $new_id = $grini.'-'.$zero_string.$numeric_id;
                $date = Carbon::now();
                $date=date('d-m-y');
                return view('admin.category.copies',compact('new_id','date','users'));
            }
            elseif (strcmp($officecenter,"Kashmore Gate")== 0 ){
                /*$gr_no = gr::latest()->first()->gr_no;*/
                $gr_no = DB::table('grs')
                    ->join('users','users.office','=','from_dest')
                    ->select('grs.gr_no')
                    ->latest('grs.created_at')->first()->gr_no;
                if($gr_no == 'Null'){
                    $gr_no=='AA-20001';
                }
                $numeric_id = intval(substr($gr_no, 3)); //retrieve numeric value of 'V001' (1)
                $numeric_id++; //increment
                if(mb_strlen($numeric_id) == 1)
                {
                    $zero_string = '0000';
                }elseif(mb_strlen($numeric_id) == 2)
                {
                    $zero_string = '000';
                }elseif(mb_strlen($numeric_id) == 3){
                    $zero_string = '00';
                }elseif(mb_strlen($numeric_id) == 4){
                    $zero_string = '0';
                }else{
                    $zero_string = '';
                }
                $grini = substr($gr_no, 0,2);
                if($numeric_id == 30001){
                    $grini++;
                    $numeric_id= '00001';
                }
                $new_id = $grini.'-'.$zero_string.$numeric_id;
                $date = Carbon::now();
                $date=date('d-m-y');
                return view('admin.category.copies',compact('new_id','date','users'));
            }
            else{
                /*$gr_no = gr::latest()->first()->gr_no;*/
                $gr_no = DB::table('grs')
                    ->join('users','users.office','=','from_dest')
                    ->select('grs.gr_no')
                    ->latest('grs.created_at')->first()->gr_no;
                if($gr_no == 'Null'){
                    $gr_no=='AA-30001';
                }
                $numeric_id = intval(substr($gr_no, 3)); //retrieve numeric value of 'V001' (1)
                $numeric_id++; //increment
                if(mb_strlen($numeric_id) == 1)
                {
                     $zero_string = '0000';
                }elseif(mb_strlen($numeric_id) == 2)
                {
                    $zero_string = '000';
                }elseif(mb_strlen($numeric_id) == 3){
                    $zero_string = '00';
                }elseif(mb_strlen($numeric_id) == 4){
                    $zero_string = '0';
                }else{
                    $zero_string = '';
                }
                $grini = substr($gr_no, 0,2);
                if($numeric_id == 40001){
                    $grini++;
                    $numeric_id= '00001';
                }
                $new_id = $grini.'-'.$numeric_id;
                $date = Carbon::now();
                $date=date('d-m-y');
                return view('admin.category.copies',compact('new_id','date','users'));
             }
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


        'gr_no' => 'required',
        'from_dest'=> 'required',
        'to_dest'=> 'required',
        'consignor'=> 'required',
        'nor_adress'=> 'required',
        'nor_gst_no'=>'min:15|max:15',
        'consignee'=> 'required',
        'nee_adress'=> 'required',
        'nee_gst_no'=>'min:15|max:15',

         'nugs'=>'required',
         'meth'=>'required',
        'eway_bill_number'=>'required',
        'bill_amount'=>'numeric|required',
        'description'=>'required',

        'pm'=>'required',
        'weight'=>'numeric|required',

        'frieght_amount'=>'numeric|required',
        'sur_ch'=>'numeric|required',
        'c_r'=>'numeric|required',
        'other'=>'numeric|required',
        'bc_amount'=>'numeric|required',
        'total_amount'=>'numeric|required',
],[
       'meth.required'=>"Method of Packages field is Required",
        'from_dest.required'=>"From Field is Required",
        'to_dest.required'=>"To Field is Required",
        'consignor.required'=>"Consignor Name Field is Required",
        'consignor.alpha_num'=>"Consignor Field accpet alpha numeric charaters",
        'nor_adress.required'=>"Consignor Adress Field is Required",
        'nor_gst_no.min(15)'=>"Wrong GST number",
        'nor_gst_no.max(15)'=>"Wrong GST number",
        'consignee.required'=>"Consignee Name Field is Required",
          'description'=>"Description Field is Required",
           'eway_bill_number.required'=>'E Way bill Number Field is Required',
            'bill_amount.required'=>'Bill Amount Number Field is Required',

        'nee_adress'=>"Consignee Adress Field is Required",
        'nee_gst_no.max(15)'=>"Wrong GST Number",
        'nee_gst_no.min(15)'=>"Wrong GST Number",
        'nugs.numberic'=>"Nugs Field accept numberic characters",

        'pm.required'=>"PM Field is Required",
        'weight.required'=>"weight Field is Required",

        'frieght_amount.required'=>"Frieght Amount Fieldis Required",
        'sur_ch.required'=>"Sur ch Field is Required",
        'c_r.required'=>"C R Field is Required",
        'other.required'=>"GST amount Field is Required",
        'bc_amount.required'=>"BC Amount Field is Required",
        'total_amount.required'=>"Total Amount Field accept numberic characters",
        'pm.numberic'=>"PM Field accept numberic characters",
        'weight.numeric'=>"weight Field accept numberic characters",

        'frieght_amount.numeric'=>"Frieght Amount Field accept numberic characters",
        'sur_ch.numberic'=>"Sur ch Field accept numberic characters",
        'c_r.numberic'=>"C R Field accept numberic characters",
        'other.numberic'=>"GST amount Field accept numberic characters",
        'bc_amount.numberic'=>"BC Amount Field accept numberic characters",
        'total_amount.numberic'=>"Total Amount Field accept numberic characters",]);

          $copy=new Gr([

        'gr_no' => $request->post('gr_no'),
        'from_dest'=> $request->post('from_dest'),
        'to_dest'=> $request->post('to_dest'),
        'copy_date'=>$request->post('copy_date'),
        'consignor'=> ucwords($request->post('consignor')),
        'nor_adress'=> $request->post('nor_adress'),
        'nor_gst_no'=> $request->post('nor_gst_no'),
        'consignee'=> ucwords($request->post('consignee')),
        'nee_adress'=> $request->post('nee_adress'),
        'nee_gst_no'=> $request->post('nee_gst_no'),
        'nugs'=> $request->post('nugs'),
       'meth'=>$request->post('meth'),
       'description'=>$request->post('description'),
        'pm'=> $request->post('pm'),
        'weight'=> $request->post('weight'),
        'paid'=> $request->post('paid'),
        'to_pay'=> $request->post('to_pay'),
        'frieght_amount'=> $request->post('frieght_amount'),
        'sur_ch'=> $request->post('sur_ch'),
        'c_r'=> $request->post('c_r'),
        'eway_bill_number'=> $request->post('eway_bill_number'),
        'bill_amount'=> $request->post('bill_amount'),
        'other'=> $request->post('other'),
        'bc_amount'=> $request->post('bc_amount'),
        'total_amount'=> $request->post('total_amount'),


        ]);
        $copy->save();
        return Redirect('dash/gr')->with('success','Copy Added successfully');
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
         $copy=gr::find($id);
       return view('admin.category.copies_print',compact('copy','id'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $copy=Gr::find($id);
       return view('admin.category.copies_edit',compact('copy','id'));
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


        'gr_no' => 'required',
        'from_dest'=> 'required',
        'to_dest'=> 'required',
        'consignor'=> 'required',
        'nor_adress'=> 'required',
        'nor_gst_no'=>'min:15|max:15',
        'consignee'=> 'required',
        'nee_adress'=> 'required',
        'nee_gst_no'=>'min:15|max:15',

         'nugs'=>'required',
'meth'=>'required',
'eway_bill_number'=>'required',
'bill_amount'=>'numeric|required',


        'pm'=>'required',
        'weight'=>'numeric|required',

        'frieght_amount'=>'numeric|required',
        'sur_ch'=>'numeric|required',
        'c_r'=>'numeric|required',
        'other'=>'numeric|required',
        'bc_amount'=>'numeric|required',
        'total_amount'=>'numeric|required',
],[

        'from_dest.required'=>"From Field is Required",
        'to_dest.required'=>"To Field is Required",
        'consignor.required'=>"Consignor Name Field is Required",
        'consignor.alpha_num'=>"Consignor Field accpet alpha numeric charaters",
        'nor_adress.required'=>"Consignor Adress Field is Required",
        'nor_gst_no.min(15)'=>"Wrong GST number",
        'nor_gst_no.max(15)'=>"Wrong GST number",
        'consignee.required'=>"Consignee Name Field is Required",
           'meth.required'=>'Meth of Package Field is Required',
           'eway_bill_number.required'=>'E Way bill Number Field is Required',
            'bill_amount.required'=>'Bill Amount Number Field is Required',

        'nee_adress'=>"Consignee Adress Field is Required",
        'nee_gst_no.max(15)'=>"Wrong GST Number",
        'nee_gst_no.min(15)'=>"Wrong GST Number",
        'nugs.numberic'=>"Nugs Field accept numberic characters",

        'pm.required'=>"PM Field is Required",
        'weight.required'=>"weight Field is Required",

        'frieght_amount.required'=>"Frieght Amount Fieldis Required",
        'sur_ch.required'=>"Sur ch Field is Required",
        'c_r.required'=>"C R Field is Required",
        'other.required'=>"GST amount Field is Required",
        'bc_amount.required'=>"BC Amount Field is Required",
        'total_amount.required'=>"Total Amount Field accept numberic characters",
        'pm.numberic'=>"PM Field accept numberic characters",
        'weight.numeric'=>"weight Field accept numberic characters",

        'frieght_amount.numeric'=>"Frieght Amount Field accept numberic characters",
        'sur_ch.numberic'=>"Sur ch Field accept numberic characters",
        'c_r.numberic'=>"C R Field accept numberic characters",
        'other.numberic'=>"GST amount Field accept numberic characters",
        'bc_amount.numberic'=>"BC Amount Field accept numberic characters",
        'total_amount.numberic'=>"Total Amount Field accept numberic characters",]);

          $copy=gr::find($id);

        $copy->gr_no = $request->get('gr_no');
        $copy->from_dest = $request->get('from_dest');
        $copy->to_dest = $request->get('to_dest');
        $copy->copy_date = $request->get('copy_date');
        $copy->consignor = ucwords($request->get('consignor'));
        $copy->nor_adress =  $request->get('nor_adress');
        $copy->nor_gst_no = $request->get('nor_gst_no');
        $copy->consignee =  ucwords($request->get('consignee')) ;
        $copy->nee_adress = $request->get('nee_adress');
        $copy->nee_gst_no =  $request->get('nee_gst_no');
        $copy->nugs = $request->get('nugs');
         $copy->meth = $request->get('meth');
          $copy->nugs = $request->get('nugs');
          $copy->eway_bill_number = $request->get('eway_bill_number');
          $copy->bill_amount = $request->get('bill_amount');
        $copy->description = Str::ucfirst($request->get('description'));
        $copy->pm = $request->get('pm');
        $copy->weight = $request->get('weight');
        $copy->paid = $request->get('paid');
        $copy->to_pay = $request->get('to_pay');
        $copy->frieght_amount = $request->get('frieght_amount');
        $copy->sur_ch = $request->get('sur_ch');
        $copy->c_r = $request->get('c_r');
        $copy->other = $request->get('other');
        $copy->bc_amount = $request->get('bc_amount');
        $copy->total_amount = $request->get('total_amount');



        $copy->save();
        return Redirect('dash/gr')->with('success','Copy Updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $copy=Gr::find($id);
        $copy->delete();
        return Redirect('dash/gr')->with('success','Copy  deleted successfully');
    }
}

