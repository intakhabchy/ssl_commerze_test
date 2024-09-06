<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(){

        $cartInfo = DB::table('product_carts')
        ->join('products','product_carts.product_id','=','products.id')
        ->where('user_id','=',1)->get();
        return view('cart',['cartInfo'=>$cartInfo]);
    }
}
