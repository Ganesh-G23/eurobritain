@php $contact = contactus(); @endphp
<header class="header fixed-top navbar-expand-xl">
    <div class="container-fluid">
        <div class="header__main">
            <!-- logo -->
            <div class="logo"> <a class="logo__link logo--dark" href="{{ url('/') }}"> <img
                        src="{{ url('public/web_theme/assets/img/logo/logo-dark.png') }}" alt="" class="logo__img">
                </a> <a class="logo__link logo--light" href="{{ url('/') }}"> <img
                        src="{{ url('public/web_theme/assets/img/logo/logo-white.png') }}" alt=""
                        class="logo__img"> </a> </div>
            <!--/-->

            <!-- header actions -->
            <div class="header__action-items">
                <!--header-social-->
                <ul class="list-inline social-media social-media--layout-one">
                    <li class="social-media__item"> <a href="{{$contact->facebook}}" class="social-media__link"> <i
                                class="bi bi-facebook"></i> </a> </li>
                    <li class="social-media__item"> <a href="{{$contact->instagram}}" class="social-media__link"> <i
                                class="bi bi-instagram"></i> </a> </li>
                    <li class="social-media__item"> <a href="{{$contact->company_url}}" class="social-media__link"><i
                                class="bi bi-twitter-x"></i></a> </li>
                    <li class="social-media__item"> <a href="{{$contact->youtube_url}}" class="social-media__link"><i
                                class="bi bi-youtube"></i></a> </li>
                </ul>

                <!--theme-switch-->
                <div class="theme-switch">
                    <label class="theme-switch__label" for="checkbox">
                        <input type="checkbox" id="checkbox" class="theme-switch__checkbox">
                        <span class="theme-switch__slider round "> <i
                                class="bi bi-sun icon-light theme-switch__icon theme-switch__icon--light"></i> <i
                                class="bi bi-moon icon-dark theme-switch__icon theme-switch__icon--dark"></i> </span>
                    </label>
                </div>

                <!--search-icon-->
                <div class="search-icon"> <a href="#search" class="search-icon__link"> <i
                            class="bi bi-search search-icon__icon"></i> </a> </div>

                <!--navbar-toggler-->
                <button class="navbar-toggler">
                    <div class="hamburger" id="hamburgerBtn"> <img width="30" height="30"
                            src="https://img.icons8.com/ios-filled/100/menu--v1.png" alt="menu--v1" /> </div>
                </button>
            </div>
        </div>
    </div>
    <div class="header_strip_section">
        <div class="container-fluid">
            <nav class="navbar">
                <!--navbar-collapse-->
                <div class="navbar-container">
                    <ul class="navbar-nav ">
                        @php
                        $mainCategories = $categories ?? collect([]);
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('home*') ? 'active' : '' }}"
                                href="{{ route('web.home') }}"> Home </a>
                        </li>
                        @foreach ($mainCategories->where('parent_id', 0)->where('show_at_home', 1)->sortBy('sequence') as $category)
                        @php
                        $children = $mainCategories->where('parent_id', $category->id);
                        @endphp

                        @if ($children->count() > 0)
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->is('blog*') && request()->get('category') == $category->slug ? 'active' : '' }}"
                                href="javascript:void(0)" id="navbarDropdown{{ $category->id }}" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                {{ $category->category_name }}
                            </a>
                            <ul class="dropdown-menu">
                                @foreach ($children->sortBy('sequence') as $child)
                                <li>
                                    <a class="dropdown-item"
                                        href="{{ route('web.blog', ['category' => $child->slug]) }}">{{ $child->category_name }}</a>
                                </li>
                                @endforeach
                            </ul>
                        </li>
                        @else
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('blog*') && request()->get('category') == $category->slug ? 'active' : '' }}"
                                href="{{ route('web.blog', ['category' => $category->slug]) }}">
                                {{ $category->category_name }}
                            </a>
                        </li>
                        @endif
                        @endforeach
                        <!--Videos Link-->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('videos*') ? 'active' : '' }}"
                                href="{{ route('web.videos') }}"> Videos </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('webstories*') ? 'active' : '' }}"
                                href="{{ route('web.webstories') }}"> Web Stories </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('contact-us*') ? 'active' : '' }}"
                                href="{{ route('web.contact') }}"> Contact Us </a>
                        </li>
                        <!--Menu Button-->
                        <li class="nav-item">
                            <a class="nav-link pe-0" href="#">
                                <div class="menu-btn" id="menuBtn"> <span></span> <span></span> <span></span> </div>
                            </a>
                        </li>
                    </ul>
                </div>
                <!-- DROPDOWN (desktop) / SIDE MENU (mobile) -->
                <nav class="menu-box" id="menuBox">
                    <ul>
                        @foreach($mainCategories->where('parent_id', 0)->where('show_at_home', 0)->sortBy('sequence') as $category)
                        <li>
                            <a href="{{ route('web.blog', ['category' => $category->slug]) }}">{{ $category->category_name }}</a>
                        </li>
                        @endforeach
                        <li><a href="{{url('/baby-names')}}">Baby Names</a></li>
                    </ul>
                    <div class="bottom-all-links">
                        <ul>
                            <li><a href="{{url('/disclaimer')}}">Disclaimer</a></li>
                            <li><a href="{{url('/termscondition')}}">Terms &amp; Conditions</a></li>
                            <li><a href="{{url('/privacypolicy')}}">Privacy Policy</a></li>
                            <li><a href="{{url('/contact')}}">Contact Us</a></li>
                            <li><a href="{{url('/aboutus')}}">About Us</a></li>
                            <!-- <li><a href="#">Author Profiles</a></li> -->
                            <!-- <li><a href="#">Archives</a></li> -->
                        </ul>
                    </div>
                </nav>
            </nav>
        </div>
    </div>
    <div>

        <div class="nav-wrap mobile-nav">
            <nav id="push_sidebar">
                <div class="side-logo-close">
                    <div class="logo">
                        <a href="{{ route('web.home') }}">
                            <img src="{{ url('public/web_theme/assets/img/logo/logo-dark.png') }}" alt="" width="140" height="30">
                        </a>
                    </div>
                    <span class="nav-trigger btnMenuClose">✕</span>
                </div>
                <div class="nav-all">
                    <!-- Menu -->
                    <div class="nav-inner">
                        <ul class="nav">
                            @foreach($mainCategories->where('parent_id', 0)->sortBy('sequence') as $category)
                            @php
                            $children = $mainCategories->where('parent_id', $category->id);
                            @endphp

                            @if($children->count() > 0)
                            <li class="has-sub">
                                <a href="#">{{ $category->category_name }} <span class="submenu-icon">▼</span></a>
                                <ul class="sub-menu">
                                    @foreach($children->sortBy('sequence') as $child)
                                    <li><a href="{{ route('web.blog', ['category' => $child->slug]) }}">{{ $child->category_name }}</a></li>
                                    @endforeach
                                </ul>
                            </li>
                            @else
                            <li>
                                <a href="{{ route('web.blog', ['category' => $category->slug]) }}">{{ $category->category_name }}</a>
                            </li>
                            @endif
                            @endforeach

                            <li><a href="{{ route('web.videos') }}">Videos</a></li>
                            <li><a href="{{ route('web.webstories') }}">Web Stories</a></li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</header>