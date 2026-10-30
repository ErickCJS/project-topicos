<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ProgrammingController extends Controller
{
    public function index(): View
    {
        return view('admin.programming.index');
    }
}
