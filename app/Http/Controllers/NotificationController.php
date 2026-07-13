<?php

namespace App\Http\Controllers;

use App\Services\AlertService;

class NotificationController extends Controller
{
    public function index(AlertService $alertService)
    {
        $alerts = $alertService->collect();

        return view('notifications.index', compact('alerts'));
    }
}
