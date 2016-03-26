<?php
namespace Polyether\Backend\Http\Controllers\Backend;

use Polyether\Backend\Http\Controllers\Controller;
use View;

class HomeController extends Controller
{
    public function getIndex()
    {
        return View::make('backend::home');
    }
}