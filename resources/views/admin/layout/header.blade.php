
<!-- <header id="header" class="header">

            <div class="header-menu">

                <div class="col-sm-7">
                    <a id="menuToggle" class="menutoggle pull-left"><i class="fa fa fa-tasks"></i></a> 
                        <button class="search-trigger"><i class="fa fa-search"></i></button>
                        <div class="form-inline">
                            <form class="search-form">
                                <input class="form-control mr-sm-2" type="text" placeholder="Search ..." aria-label="Search">
                                <button class="search-close" type="submit"><i class="fa fa-close"></i></button>
                            </form>
                        </div>
 -->
                        <!-- <div class="dropdown for-notification">
                          <button class="btn btn-secondary dropdown-toggle" type="button" id="notification" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-bell"></i>
                            <span class="count bg-danger">5</span>
                          </button>
                          <div class="dropdown-menu" aria-labelledby="notification">
                            <p class="red">You have 3 Notification</p>
                            <a class="dropdown-item media bg-flat-color-1" href="#">
                                <i class="fa fa-check"></i>
                                <p>Server #1 overloaded.</p>
                            </a>
                            <a class="dropdown-item media bg-flat-color-4" href="#">
                                <i class="fa fa-info"></i>
                                <p>Server #2 overloaded.</p>
                            </a>
                            <a class="dropdown-item media bg-flat-color-5" href="#">
                                <i class="fa fa-warning"></i>
                                <p>Server #3 overloaded.</p>
                            </a>
                          </div>
                        </div>
 -->
                      <!--   <div class="dropdown for-message">
                          <button class="btn btn-secondary dropdown-toggle" type="button"
                                id="message"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="ti-email"></i>
                            <span class="count bg-primary">9</span>
                          </button>
                          <div class="dropdown-menu" aria-labelledby="message">
                            <p class="red">You have 4 Mails</p>
                            <a class="dropdown-item media bg-flat-color-1" href="#">
                                <span class="photo media-left"><img alt="avatar" src="{{asset('public/admin/images/avatar/1.jpg')}}"></span>
                                <span class="message media-body">
                                    <span class="name float-left">Jonathan Smith</span>
                                    <span class="time float-right">Just now</span>
                                        <p>Hello, this is an example msg</p>
                                </span>
                            </a>
                            <a class="dropdown-item media bg-flat-color-4" href="#">
                                <span class="photo media-left"><img alt="avatar" src="{{asset('public/admin/images/avatar/2.jpg')}}"></span>
                                <span class="message media-body">
                                    <span class="name float-left">Jack Sanders</span>
                                    <span class="time float-right">5 minutes ago</span>
                                        <p>Lorem ipsum dolor sit amet, consectetur</p>
                                </span>
                            </a>
                            <a class="dropdown-item media bg-flat-color-5" href="#">
                                <span class="photo media-left"><img alt="avatar" src="{{asset('public/admin/images/avatar/3.jpg')}}"></span>
                                <span class="message media-body">
                                    <span class="name float-left">Cheryl Wheeler</span>
                                    <span class="time float-right">10 minutes ago</span>
                                        <p>Hello, this is an example msg</p>
                                </span>
                            </a>
                            <a class="dropdown-item media bg-flat-color-3" href="#">
                                <span class="photo media-left"><img alt="avatar" src="{{asset('public/admin/images/avatar/4.jpg')}}"></span>
                                <span class="message media-body">
                                    <span class="name float-left">Rachel Santos</span>
                                    <span class="time float-right">15 minutes ago</span>
                                        <p>Lorem ipsum dolor sit amet, consectetur</p>
                                </span>
                            </a>
                          </div>
                        </div> -->
                <!--     </div>
                </div>

                <div class="col-sm-5">
                    <div class="user-area dropdown float-right"> -->
                        <!-- <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <img class="user-avatar rounded-circle" src="{{asset('public/admin/images/admin.jpg')}}" alt="User Avatar">
                        </a> -->
                      <!--   <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::check() ? Auth::user()->name : 'User' }}
                        </a>


                        <div class="user-menu dropdown-menu"> -->
                                <!-- <a class="nav-link" href="#"><i class="fa fa- user"></i>My Profile</a> -->

                              <!--   <a class="nav-link" href="#"><i class="fa fa- user"></i>Notifications <span class="count">13</span></a>

                                <a class="nav-link" href="#"><i class="fa fa -cog"></i>Settings</a>
 -->
                             <!--  <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>


                        </div>
                    </div>

                    <div class="language-select dropdown" id="language-select"> -->
                        <!-- <a class="dropdown-toggle" href="#" data-toggle="dropdown"  id="language" aria-haspopup="true" aria-expanded="true">
                            <i class="flag-icon flag-icon-us"></i>
                        </a>
                        <div class="dropdown-menu" aria-labelledby="language" >
                            <div class="dropdown-item">
                                <span class="flag-icon flag-icon-fr"></span>
                            </div>
                            <div class="dropdown-item">
                                <i class="flag-icon flag-icon-es"></i>
                            </div>
                            <div class="dropdown-item">
                                <i class="flag-icon flag-icon-us"></i>
                            </div>
                            <div class="dropdown-item">
                                <i class="flag-icon flag-icon-it"></i>
                            </div>
                        </div> -->
<!--  --><!--               </div>

                </div>
            </div>

        </header> -->
<header id="header" class="header">

            <div class="header-menu">

                <div class="col-sm-7">
                    <a id="menuToggle" class="menutoggle pull-left"><i class="fa fa fa-tasks"></i></a>
                    <div class="header-left">
                        {{-- Office Impersonation for SuperAdmin --}}
                        @auth
                        @role('SuperAdmin')
                        <form action="{{ route('impersonate.office') }}" method="POST" style="display:inline-block;margin-left:10px">
                            @csrf
                            <select name="office" onchange="this.form.submit()" class="form-control form-control-sm" style="width:auto;display:inline;height:30px;font-size:12px;border:1px solid #4a90d9;">
                                <option value="" disabled {{ !session('impersonating_office') ? 'selected' : '' }}>All Offices</option>
                                @foreach(\App\Models\Branch::active()->orderBy('branch_name')->get() as $branch)
                                <option value="{{ $branch->branch_name }}" {{ session('impersonating_office') == $branch->branch_name ? 'selected' : '' }}>
                                    {{ $branch->branch_name }} ({{ $branch->branch_code }})
                                </option>
                                @endforeach
                            </select>
                        </form>
                        @if(session('impersonating_office'))
                        <form action="{{ route('impersonate.stop') }}" method="POST" style="display:inline-block;margin-left:5px">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning" style="height:30px;font-size:11px;padding:4px 10px">
                                <i class="fa fa-times"></i> Stop
                            </button>
                        </form>
                        @endif
                        @endrole
                        @endauth
                    </div>
                </div>

                <div class="col-sm-5">
                    <div class="user-area dropdown float-right">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ Auth::check() ? Auth::user()->name : 'User' }}
                            @if(session('impersonating_office'))
                                <span class="badge badge-warning" style="font-size:9px">{{ session('impersonating_office') }}</span>
                            @endif
                        </a>
                        <div class="user-menu dropdown-menu">
                                 <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                        </div>
                    </div>
                </div>
            </div>

        </header><!-- /header -->

        {{-- Impersonation Banner --}}
        @if(session('impersonating_office'))
        <div style="background:#f39c12;color:#fff;padding:4px 15px;font-size:12px;text-align:center">
            <i class="fa fa-building"></i> Working as <strong>{{ session('impersonating_office') }}</strong> office — you only see data from this branch.
            <form action="{{ route('impersonate.stop') }}" method="POST" style="display:inline">@csrf
                <button type="submit" style="background:none;border:none;color:#fff;text-decoration:underline;cursor:pointer;font-size:12px">Switch back to all offices</button>
            </form>
        </div>
        @endif