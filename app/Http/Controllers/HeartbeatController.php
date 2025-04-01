<?php

namespace App\Http\Controllers;

class HeartbeatController extends Controller
{
    public function heartbeat()
    {
        return response()->noContent();
    }
}
