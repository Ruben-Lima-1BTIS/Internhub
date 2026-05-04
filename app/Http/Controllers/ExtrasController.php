<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExtrasController extends Controller
{
    public function flyer()
    {
        return view('extras.flyer');
    }

    public function pricingTable()
    {
        return view('extras.pricing_table');
    }
}
