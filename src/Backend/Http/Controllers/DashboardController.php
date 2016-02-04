<?php

namespace Polyether\Backend\Http\Controllers;

use Illuminate\Http\Request;
use Etherbase\App\Http\Requests;
use Polyether\Backend\Http\Controllers\Controller;

class DashboardController extends Controller {

    /**
     * Create a new dashboard controller instance.
     *
     * @return void
     */
    public function __construct() {
        // Protect all dashboard routes. Users must be authenticated.
        $this->middleware('auth');
    }

    public function getIndex() {
        return view('backend::home');
    }

}
