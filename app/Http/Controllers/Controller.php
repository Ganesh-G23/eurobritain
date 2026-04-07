<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public $response = [
        'status' => 0,
        'msg' => "",
        'error' => "",
        'error_array' => [],
        'data' => [],
    ];
}
