<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ url('public/favicon.ico') }}">
    <title>Welcome to EliteGrade</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ url('public/web_theme/assets/css/main.css') }}" rel="stylesheet">
</head>

<body>
    @yield('css')
    <header class="float-start w-100 position-absolute">
        <div class="container p-0">
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid">
                    <a class="navbar-brand" href="{{ url('/') }}"><img src="{{ url('public/web_theme/assets/images/logo.png') }}" alt=""
                            class="img-fluid header-logo"></a>
                    <a href="#" class="mobile btn-main header-btn mode-btn d-lg-none mt-0 themeToggle">🌙</a>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <a class="nav-link active" aria-current="page" href="{{ url('/') }}">Home</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/about') }}">About</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/services') }}">Services</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/contact') }}">Contact</a>
                            </li>
                        </ul>
                        <div class="d-flex" role="search">
                            <a href="#" class="desktop btn-main header-btn mode-btn mt-0 themeToggle">🌙</a>
                            <a href="{{ url('/login') }}" class="btn-main header-btn mt-0">Sign In</a>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    @yield('content')

    <footer>
        <div class="container">
            <img src="{{ url('public/web_theme/assets/images/logo.png') }}" alt="" class="img-fluid">
            <p class="text-center">Copyright © 2026 All rights reserved.</p>
        </div>
    </footer>
    <script src="{{ url('public/web_theme/assets/js/jquery.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const toggleBtns = document.querySelectorAll(".themeToggle");

    toggleBtns.forEach(function(toggleBtn) {

        toggleBtn.addEventListener("click", function() {

            document.body.classList.toggle("dark-mode");

            if (document.body.classList.contains("dark-mode")) {
                toggleBtns.forEach(btn => btn.innerHTML = "☀️");
                localStorage.setItem("theme", "dark");
            } else {
                toggleBtns.forEach(btn => btn.innerHTML = "🌙");
                localStorage.setItem("theme", "light");
            }

        });

    });
    </script>

    <script>
    window.onload = function() {

        const savedTheme = localStorage.getItem("theme");

        if (savedTheme === "dark") {
            document.body.classList.add("dark-mode");

            const buttons = document.getElementsByClassName("themeToggle");

            for (let i = 0; i < buttons.length; i++) {
                buttons[i].innerHTML = "☀️";
            }
        }

    }
    </script>
    @yield('javascript')
</body>
</html>
