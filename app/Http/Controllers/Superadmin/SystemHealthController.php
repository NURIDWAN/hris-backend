<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\SystemHealthService;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function index(SystemHealthService $healthService): View
    {
        $metrics = $healthService->collect();

        return view('superadmin.system.health', compact('metrics'));
    }
}
