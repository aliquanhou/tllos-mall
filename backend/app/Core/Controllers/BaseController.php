<?php

namespace App\Core\Controllers;

use App\Core\Traits\ApiResponse;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use ApiResponse;
}
