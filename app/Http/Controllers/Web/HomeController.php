<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function about()
    {
        return view('web.about');
    }

    public function services()
    {
        return view('web.services');
    }

    public function contact()
    {
        return view('web.contact-us');
    }

    public function index()
    {
        return view('web.index');
    }


    public function login()
    {
        return view('web.login');
    }
}
