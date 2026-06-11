<!-- Left Panel -->
<aside id="left-panel" class="left-panel">
    <nav class="navbar navbar-expand-sm navbar-default">

        <div class="navbar-header">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#main-menu" aria-controls="main-menu" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fa fa-bars"></i>
            </button>
            <a class="navbar-brand" href="{{url('/dash')}}"><img src="{{asset('admin/images/saurashtraf.png')}}" alt="Logo"></a>
            <a class="navbar-brand hidden" href="{{url('/dash')}}"><img src="{{asset('admin/images/logo2.png')}}" alt="Logo"></a>
        </div>

        <div id="main-menu" class="main-menu collapse navbar-collapse">
            <ul class="nav navbar-nav">

                {{-- 1. Dashboard --}}
                <li>
                    <a href="{{url('/dash')}}"> <i class="menu-icon fa fa-laptop"></i>Dashboard</a>
                </li>

                {{-- 2. GR --}}
                <li>
                    <a href="{{url('/gr')}}"> <i class="menu-icon fa fa-files-o"></i>GR</a>
                </li>

                {{-- 3. Challan --}}
                <li>
                    <a href="{{url('/challan')}}"> <i class="menu-icon fa fa-table"></i>Challan</a>
                </li>

                {{-- 4. Gatepass --}}
                <li>
                    <a href="{{url('/gatepass')}}"> <i class="menu-icon fa fa-check-square"></i>Gatepass</a>
                </li>

                {{-- 5. Freight Memo (Manager+) --}}
                @hasanyrole('SuperAdmin|Admin|Manager')
                <li>
                    <a href="{{url('/frieghtmemo')}}"> <i class="menu-icon fa fa-truck"></i>Freight Memo</a>
                </li>
                @endhasanyrole

                {{-- 6. Reports (Manager+ but scoped to own branch) --}}
                @hasanyrole('SuperAdmin|Admin|Manager')
                <li class="menu-item-has-children dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="menu-icon fa fa-bar-chart"></i>Reports</a>
                    <ul class="sub-menu children dropdown-menu">
                        <li><i class="fa fa-list"></i><a href="{{ url('/dash/reports') }}">All Reports</a></li>
                        <li><i class="fa fa-calendar"></i><a href="{{ url('/dash/reports/daily-booking') }}">Daily Booking</a></li>
                        <li><i class="fa fa-book"></i><a href="{{ url('/dash/reports/gr-register') }}">GR Register</a></li>
                        <li><i class="fa fa-line-chart"></i><a href="{{ url('/dash/reports/revenue') }}">Revenue</a></li>
                        <li><i class="fa fa-clock-o"></i><a href="{{ url('/dash/reports/pending-topay') }}">Pending TO-PAY</a></li>
                        <li><i class="fa fa-truck"></i><a href="{{ url('/dash/reports/pending-delivery') }}">Pending Delivery</a></li>
                        <li><i class="fa fa-file-pdf-o"></i><a href="{{ url('/dash/reports/pending-pod') }}">Pending POD</a></li>
                        @role('SuperAdmin')
                        <li><i class="fa fa-building"></i><a href="{{ url('/dash/reports/branch-performance') }}">Branch Performance</a></li>
                        @endrole
                    </ul>
                </li>
                @endhasanyrole

                {{-- 7. Settings (SuperAdmin ONLY) --}}
                @role('SuperAdmin')
                <li class="menu-item-has-children dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <i class="menu-icon fa fa-cog"></i>Settings</a>
                    <ul class="sub-menu children dropdown-menu">
                        <li><i class="fa fa-user"></i><a href="{{ url('/users') }}">Users</a></li>
                        <li><i class="fa fa-shield"></i><a href="{{ url('/roles') }}">Roles</a></li>
                        <li><i class="fa fa-key"></i><a href="{{ url('/permissions') }}">Permissions</a></li>
                        <li><i class="fa fa-building"></i><a href="{{ url('/branch') }}">Branches</a></li>
                        <li><i class="fa fa-sort-numeric-asc"></i><a href="{{ url('/serial-assign') }}">GR Serial</a></li>
                        <li><i class="fa fa-truck"></i><a href="{{ url('/vehicle') }}">Vehicles</a></li>
                        <li><i class="fa fa-id-card"></i><a href="{{ url('/truckdriver') }}">Drivers</a></li>
                        <li><i class="fa fa-road"></i><a href="{{ url('/route') }}">Routes</a></li>
                        <li><i class="fa fa-map-marker"></i><a href="{{ url('/station') }}">Stations</a></li>
                        <li><i class="fa fa-users"></i><a href="{{ url('/customer') }}">Customers</a></li>
                        <li><i class="fa fa-upload"></i><a href="{{ url('/consignor') }}">Consignors</a></li>
                        <li><i class="fa fa-download"></i><a href="{{ url('/consignee') }}">Consignees</a></li>
                    </ul>
                </li>
                @endrole

            </ul>
        </div>
    </nav>
</aside>
<!-- Left Panel -->
