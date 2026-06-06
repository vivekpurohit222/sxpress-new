   
<!--    <script src="{{asset('public/admin/js/vendor/jquery-3.5.1.min.js')}}" defer></script> --><script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

      
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.3/umd/popper.min.js"></script>
    <script src="{{asset('admin/js/plugins.js')}}"></script>
    <script src="{{asset('admin/js/main.js')}}"></script>


    <script src="{{asset('admin/js/lib/chart-js/Chart.bundle.js')}}"></script>
    <script src="{{asset('admin/js/dashboard.js')}}"></script>
    <script src="{{asset('admin/js/widgets.js')}}"></script>
    <script src="{{asset('admin/js/lib/vector-map/jquery.vmap.js')}}"></script>
    <script src="{{asset('admin/js/lib/vector-map/jquery.vmap.min.js')}}"></script>
    <script src="{{asset('admin/js/lib/vector-map/jquery.vmap.sampledata.js')}}"></script>
    <script src="{{asset('admin/js/lib/vector-map/country/jquery.vmap.world.js')}}"></script>
    <script>
        ( function ( $ ) {
            "use strict";

            jQuery( '#vmap' ).vectorMap( {
                map: 'world_en',
                backgroundColor: null,
                color: '#ffffff',
                hoverOpacity: 0.7,
                selectedColor: '#1de9b6',

                enableZoom: true,
                showTooltip: true,
                values: sample_data,
                scaleColors: [ '#1de9b6', '#03a9f5' ],
                normalizeFunction: 'polynomial'
            } );
        } )( jQuery );
    </script> 

 <!--copies_list-->
    <!--  <script src="{{asset('public/admin/js/vendor/jquery-2.1.4.min.js')}}"></script>
    <script src="{{asset('public/admin/js/popper.min.js')}}"></script>
    <script src="{{asset('public/admin/js/plugins.js')}}"></script>
    <script src="{{asset('public/admin/js/main.js')}}"></script> -->


    <script src="{{asset('admin/js/lib/data-table/datatables.min.js')}}"></script>
    <script src="{{asset('admin/js/lib/data-table/dataTables.bootstrap.min.js')}}"></script>
    <script src="{{asset('admin/js/lib/data-table/dataTables.buttons.min.js')}}"></script>
    <script src="{{asset('admin/js/lib/data-table/buttons.bootstrap.min.js')}}"></script>
    <script src="{{asset('admin/js/lib/data-table/jszip.min.js')}}"></script>
    <script src="{{asset('admin/js/lib/data-table/pdfmake.min.js')}}"></script>
    <script src="{{asset('admin/js/lib/data-table/vfs_fonts.js')}}"></script>
    <script src="{{asset('admin/js/lib/data-table/buttons.html5.min.js')}}"></script>
    <script src="{{asset('admin/js/lib/data-table/buttons.print.min.js')}}"></script>
    <script src="{{asset('admin/js/lib/data-table/buttons.colVis.min.js')}}"></script>
    <script src="{{asset('admin/js/lib/data-table/datatables-init.js')}}"></script>

    
   
    <script type="text/javascript">
        $(document).ready(function() {
          $('#bootstrap-data-table-export').DataTable();
        } );
    </script> 
    
   <script src="{{asset('admin/js/lib/chosen/chosen.jquery.min.js')}}" ></script>

    <script type="text/javascript">
         $( document ).ready(function() {
            $('.myselect').chosen();
        });
      </script>
 
 <script type="text/javascript">
     $(".abc").each(function()
{
    $(this).change(function()
    {
        $(".abc").prop('checked',false);
        $(this).prop('checked',true);
    });
});
 </script>

<script>
// we used jQuery 'keyup' to trigger the computation as the user type
$('.sum').keyup(function () {
 
    // initialize the sum (total price) to zero
    var sum = 0;
     
    // we use jQuery each() to loop through all the textbox with 'price' class
    // and compute the sum for each loop
    $('.sum').each(function() {
        sum += Number($(this).val());
    });
     
    // set the computed value to 'totalPrice' textbox
    $('#totalsum').val(sum);
     
});
//challan 
</script>

<script type='text/javascript'>
$(document).ready(function(){

// Search by userid
$('#fetchgr').click(function(){
var gr_no = String($('#grsearch').val().trim());

if(gr_no != null){
fetchRecords(gr_no);
}
});
});
function fetchRecords(id){
$.ajax({
url: 'dash/challan/'+id+'/getData',
type: 'get',
dataType: 'json',
success: function(response){
var len = 0;
if(response['data'] != null){
    $('#gr_no').val(response['data'].gr_no);
    $('#nugs').val(response['data'].nugs); 
    $('#meth').val(response['data'].meth); 
    $('#description').val(response['data'].description); 
    $('#weight').val(response['data'].weight);
    if(response['data'].paid != null)
        $('#paid').val(response['data'].total_amount); 
    else
        $('#paid').val(response['data'].paid);
     if(response['data'].to_pay != null)
        $('#to_pay').val(response['data'].total_amount);
    else
        $('#to_pay').val(response['data'].to_pay);
    // $('#paid').val(response['data'].paid); 
    // $('#to_pay').val(response['data'].to_pay); 
    $('#sur_ch').val(response['data'].sur_ch); 
    $('#c_r').val(response['data'].c_r); 
    $('#other').val(response['data'].other); 

}else{
var tr_str = "<tr>" +
"<td align='center' colspan='4'>No record found.</td>" +
"</tr>";
$("#userTable tbody").append(tr_str);
}
}
});
}
</script>


<script>

//-----------------
$(document).ready(function(){
$('#send_form').click(function(e){
   e.preventDefault();

   /*Ajax Request Header setup*/
   $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
  });

   $('#send_form').html('Sending..');
  
   /* Submit form data using ajax*/
   $.ajax({
    // url: '/project/sxpress/admin/back/challan/challanIteams',

      url: "{{url('dash/challan/challanIteams')}}",
      method: 'post',
      data: $('#contact_us').serialize(),
      success: function(response){
         //------------------------
            $('#send_form').html('Submit');
            $('#res_message').show();
            $('#res_message').html(response.error);
            $('#res_message').html(response.success);
            $('#msg_div').removeClass('d-none');


            document.getElementById("contact_us").reset(); 
        
            setTimeout(function(){
            $('#res_message').hide();
            $('#msg_div').hide();
            },10000);
         //--------------------------
      }
  });
   });
});

//-----------------
</script>

<script>

//-----------------
$(document).ready(function(){
$('#send_form').click(function(e){
   e.preventDefault();

   /*Ajax Request Header setup*/
   $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
  });

   $('#send_form').html('Sending..');
  
   /* Submit form data using ajax*/
   $.ajax({
    // url: '/project/sxpress/admin/back/challan/challanIteams',

      url: "{{url('dash/challan/challanIteams')}}",
      method: 'post',
      data: $('#contact_us').serialize(),
      success: function(response){
         //------------------------
            $('#send_form').html('Submit');
            $('#res_message').show();
            $('#res_message').html(response.error);
            $('#res_message').html(response.success);
            $('#msg_div').removeClass('d-none');


            document.getElementById("contact_us").reset(); 
       
            setTimeout(function(){
            $('#res_message').hide();
            $('#msg_div').hide();
            },10000);
         //--------------------------
      }
  });
   });
});

//-----------------
</script>

<script type='text/javascript'>
$(document).ready(function(){
// Fetch all records

// Search by userid
$('#fetchChallanAllRecord').click(function(){
var challan_no = String($('#challanfetchdata').val().trim());
if(challan_no != null){
fetchchallanRecords(challan_no);
}
});
});
function fetchchallanRecords(id){
$.ajax({
url: 'dash/challan/'+id+'/challanfetchdata',

type: 'get',
dataType: 'json',
success: function(response){


var len = 0;
$('#challantable tbody').empty(); // Empty <tbody>
if(response['data'] != null){
len = response['data'].length;
}
if(len > 0){
for(var i=0; i<len; i++){
var gr_no =  response['data'][i].gr_no;

var nugs =response['data'][i].nugs;
var meth =response['data'][i].meth;
var description =response['data'][i].description;
var weight=response['data'][i].weight;
var paid=response['data'][i].paid;
var to_pay=response['data'][i].to_pay;
var sur_ch=response['data'][i].sur_ch;
var c_r=response['data'][i].c_r;
var other=response['data'][i].other;
var tr_str = "<tr>" +
"<td align='center'>" + gr_no + "</td>" +
"<td align='center'>" + nugs + "</td>" +
"<td align='center'>" + meth + "</td>" +
"<td align='center'>" + description + "</td>" +
"<td align='center'>" + weight + "</td>" +
"<td align='center'>" + paid + "</td>" +
"<td align='center'>" + to_pay + "</td>" +
"<td align='center'>" + sur_ch + "</td>" +
"<td align='center'>" + c_r + "</td>" +
"<td align='center'>" + other + "</td>" +
"<td align='center'>"+"<button class='deleteProduct' data-id="+gr_no+" data-token='{{ csrf_token() }}' >"+"<i class='fa fa-trash'>"+"</i>"+"</button> "+  "</td>"+
"</tr>";
$("#challantable tbody").append(tr_str);
}
}else {
var tr_str = "<tr>" +
"<td align='center' colspan='4'>No record found.</td>" +
"</tr>";
$("#challantable tbody").append(tr_str);
}
}
});
}
</script>

<script>

//-----------------
$(document).ready(function(){
$('#challan_button').click(function(e){
   e.preventDefault();

   /*Ajax Request Header setup*/
   $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
  });

   $('#challan_button').html('Sending..');
  
   /* Submit form data using ajax*/
   $.ajax({
    // url: '/project/sxpress/admin/back/challan/challanIteams',

      url: "{{url('dash/challan/store')}}",
      method: 'post',
      data: $('#challanform').serialize(),
      success: function(response){
         //------------------------
            $('#challan_button').html('Submit');
            $('#challan_form_msg').show();
            $('#challan_form_msg').html(response.error);
            $('#challan_form_msg').html(response.success);
            $('#challan_msg_div').removeClass('d-none');


           
       
            setTimeout(function(){
            $('#challan_form_msg').hide();
            $('#challan_msg_div').hide();
            },10000);
         //--------------------------
      }
  });
   });
});

//-----------------
</script>
<script type="text/javascript">
  $(".deleteProduct").click(function(){
        var id = $(this).data("id");
        var token = $(this).data("token");
        $.ajax(
        {
            url: 'dash/challan/delete/'+id+'',
            type: 'PUT',
            dataType: "JSON",
            data: {
                "id": id,
                "_method": 'DELETE',
                "_token": token,
            },
            success: function ()
            {
                console.log("it Work");
            }
        });

        console.log("It failed");
    });</script>