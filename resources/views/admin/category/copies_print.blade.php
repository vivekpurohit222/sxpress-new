<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="{{asset('public/css/copies_print.css')}}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form style="margin: 0%;">
    <section>
    <div style="border-style:solid; padding:1%; border-radius: 15px;">
        <img src="G:\revan\img1.jpg" width="190" height="120" style="float: left;">      
            <div style="margin-left: 20%;">
                <div style="float: left;">
                    Subject to Rajkot Jurisdiction
                </div>
                <div style="float: right;">
                    GST No. 24AFSPJ7382P1ZI 
                </div> 
                    <br>
                    <h1 style="text-align: center; font-size:45px; margin-top:1%; margin-bottom:1%">SAURASHTRA EXPRESS</h1>
                <div style="text-align: center;">
                        <b>Head Office </b>: 2-Patel Nagar, Bhoja Bhagat Street, 50 ft. Road, Rajkot - 360 002 (Gujarat).
                    <br>
                        Contact No. 93750 88088, 97279 00008. 
            </div>
        </div>
    </div>
    </section>



    <section style="margin-top: 1%;">
        <div class="container-1">
            
            <div class="chorus-1" style="margin-right: 1%;">
                <b><h2 class="box">Delhi Office</h2></b>
                    Kashmore Gate : 84878 80088
                <br>
                    Dayabasti : 93125 87200
                <br>
                    Swarup Nagar : 99111 83400
            </div>
        
            <div class="chorus-2" style="margin-right: 1%;">
                <b><h2 class="box">Rajkot Booking Office</h2></b>
                    Navagam : 92278 58642
                <br>
                    Shapar (1) : 98242 84506
                <br>
                    Shapar (2) : 99047 10184
            </div>
        
            <div class="chorus-3" style="margin-right: 1%;">
                <b>
                <h1 style="font-size:36px;">
                    DRIVER COPY
                </h1>
                </b>
            </div>

            <div class="chorus-4">
                <!- put code of barcode here -->
            </div>

        </div>
    </section>

    <section style="margin-top: 1%">
        <div class="container-2">
            <div class="round-1" style="margin-right: 1%;">
                <h2 style="margin-top: -2%; margin-bottom:0%; padding:2%;">
                    <label for="from">From :</label>
                   
                      <label style="color:#303030 ">{{$copy->from_dest}}</label>
                </h2>
            </div>
            <div class="round-2" style="margin-right: 1%;">
                <h2 style="margin-top: -2%; margin-bottom:0%; padding:2%;">
                    <label for="to">To :</label>
                     
                    {{$copy->to_dest}}

                                                  
                </h2>
            </div>
            <div class="round-3">
                <h2 style="margin-top: -2%; margin-bottom:0%; padding:2%;">
                    <label for="date">Date :</label>
                    {{$copy->copy_date}}
                </h2>
            </div>
        </div>
    </section>

    <section>
        <div style="border:solid; border-radius:15px; margin-top:1%; padding-left:2%;">
        <h3>    
            <label for="consignor">Consignor :</label>
                <div>{{$copy->consignor}}</div>
            <br>
            <label for="consignor address">Address :</label>
            {{$copy->nor_adress}}
            <br>
            <label for="consignor GSTI No">GSTI No. :</label>
            {{$copy->nor_gst_no}}
            <br>
            <label for="consignee">Consignee :</label>
            {{$copy->consignee}}
            <br>
            <label for="consignee address">Address :</label>
            {{$copy->nee_adress}}
            <br>            
            <label for="consignee GSTI No">GSTI No. :</label>
            {{$copy->nee_gst_no}}
            <br>
        </h3>
        </div>
    </section>

    <section style="border:solid; border-radius:15px; margin-top:1%;">
        <table rules="all" style="width: 100%;">
            <tr style="padding-top: 1%; height:40px;">
                <th>Packages</th>
                <th>Description of Goods (As Per item)</th>             
                <th>Charge</th>
                <th>Amount Rs.</th>
            </tr>
            <tr>
                <td rowspan="5">{{$copy->packeges}}</td>
                <td rowspan="5">
                    {{$copy->description}}
                </td>
                <td>Freight</td>
                <td>
                    <input class="special" type="text" id="freight" name="freight" size="1">
                </td>
            </tr>
            <tr>
                <td>Sur. Ch.</td>
                <td>20</td>
            </tr>
            <tr>
                <td>C/R.</td>
                <td>
                    <input class="special" type="text" id="cr" name="cr" size="1">
                </td>
            </tr>
            <tr>
                <td>GST</td>
                <td>
                    <input class="special" type="text" id="gst" name="gst" size="5">
                </td>
            </tr>
            <tr>
                <td>BC</td>
                <td>30</td>
            </tr>
        </table>
        <table rules="all" style="width: 100%; ">
                        <tr></tr>
                        <tr>
                            <td style="text-align: center; height:50px; width: 300px;">
                                <label for="pm">PM</label>
                                <input style="margin-left:2%;" class="special" type="text" id="pm" name="pm" size="9">
                            </td>
                            <td style="text-align: center; height:50px; width: 200px;">
                                <label for="weight">Weight</label>
                                <input style="margin-left:2%;" class="special" type="text" id="weight" name="weight" size="9">
                            </td>
                            <td style="text-align: center; height:50px; width: 200px;">
                                <label for="paid at">Paid At</label>
                                <input style="margin-left:2%;" class="special" type="text" id="paid at" name="paid at" size="9">
                            </td>
                            <td style="text-align: center; height:50px; width: 200px;">
                                <label for="tbb at">T B B At</label>
                                <input style="margin-left:2%;" class="special" type="text" id="tbb at" name="tbb at" size="9">
                            </td>
                            <td style="text-align:center; height:50px; width: 421px; ">
                                <label for="tbb at">Total</label>
                                <input style="margin-left:2%;" class="special" type="text" id="cr" name="cr" size="10">
                            </td>
                        </tr>
                        <tr>
                        </tr>
        </table>
        <table rules="all" style="width: 100%; ">
            <tr></tr>
            <tr>
                <td>
                    <div style="float: left;padding:1%;">Terms :
                        (1) We are not responsible of goods after 6 months of booking date. 
                        (2) Goods will not be Deliver without consignee copy.
                        (3) We are not responsible for damage, shortage or theft in transit.
                        (4) The receipt without date will be cancelled.
                    </div>
                </td>
                <td style="width: 414px; opacity:0.2;" >Signature</td>
            </tr>
            <tr></tr>
        </table>
    </section>
    </form>
</body>
</html> 