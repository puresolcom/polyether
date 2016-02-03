<?php

namespace Polyether\Backend\Http\Controllers;

use Illuminate\Http\Request;
use Etherbase\App\Http\Requests;
use Polyether\Backend\Http\Controllers\Controller;
use Flash;
use Plugin;

class HomeController extends Controller {

    public function index() {
        $page_title = "Home";
        $page_description = "This is the home page";

        return view('ether::backend.home', compact('page_title', 'page_description'));
    }

}
