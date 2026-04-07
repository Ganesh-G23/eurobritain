  <div class="header_strip_section">
    <div class="container-fluid">
      <nav class="navbar">
        <!--navbar-collapse-->
        <div class="navbar-container">

          <ul class="navbar-nav ">
            @php
            $mainCategories = $categories ?? collect([]);
            @endphp

            @foreach($mainCategories->where('parent_id', 0)->sortBy('sequence') as $category)
            @php
            $children = $mainCategories->where('parent_id', $category->id);
            @endphp

            @if($children->count() > 0)
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle {{ request()->is('blog*') && request()->get('category') == $category->slug ? 'active' : '' }}"
                href="javascript:void(0)"
                id="navbarDropdown{{ $category->id }}"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false">
                {{ $category->category_name }}
              </a>
              <ul class="dropdown-menu">
                @foreach($children->sortBy('sequence') as $child)
                <li>
                  <a class="dropdown-item" href="{{ route('web.blog', ['category' => $child->slug]) }}">{{ $child->category_name }}</a>
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
              <a class="nav-link {{ request()->is('videos*') ? 'active' : '' }}" href="{{ route('web.videos') }}"> Videos </a>
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
            @foreach($mainCategories->where('parent_id', 0)->sortBy('sequence') as $category)
            <li>
              <a href="{{ route('web.blog', ['category' => $category->slug]) }}">{{ $category->category_name }}</a>
            </li>
            @endforeach
            <li><a href="{{ route('web.videos') }}">Baby Names</a></li>
            <li><a href="{{ route('web.videos') }}">Videos</a></li>
            <li><a href="{{ route('web.webstories') }}">Web Stories</a></li>
          </ul>
          <div class="bottom-all-links">
            <ul>
              <li><a href="{{url('/disclaimer')}}">Disclaimer</a></li>
              <li><a href="#">Terms &amp; Conditions</a></li>
              <li><a href="#">Privacy Policy</a></li>
              <li><a href="{{url('/contact')}}">Contact Us</a></li>
              <li><a href="#">Author Profiles</a></li>
              <li><a href="#">Archives</a></li>
              <li><a href="#">Complaint</a></li>
            </ul>
          </div>
        </nav>
      </nav>
    </div>
  </div>

  <div>
    <!-- Mobile Sidebar Navigation -->
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