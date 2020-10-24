<?php

namespace App\MobileAddon\Controllers;

use Fruitcake\Cors\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\MobileAddon\Middleware\TokenAuth;
use Illuminate\Routing\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware([HandleCors::class, TokenAuth::class]);
    }

    public function info(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'username' => $user->username,
            'name_first' => $user->name_first,
            'name_last' => $user->name_last,
            'root_admin' => $user->root_admin,
            'email' => $user->email,
            'uuid' => $user->uuid,
        ]);
    }
}
