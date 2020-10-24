<?php

namespace App\MobileAddon\Controllers;

use Illuminate\Routing\Controller;

class InfoController extends Controller
{
    public function index()
    {
        return response()->json([
            'instance_name' => config('app.name'),
            'panel_version' => config('cursehosting.version'),
            'module_version' => config('mobileaddon.version'),
        ]);
    }

}
