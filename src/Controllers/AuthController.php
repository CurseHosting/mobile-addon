<?php

namespace App\MobileAddon\Controllers;

use Fruitcake\Cors\HandleCors;
use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PragmaRX\Google2FA\Google2FA;
use App\MobileAddon\Models\MobileToken;
use App\Models\User;

class AuthController extends Controller
{
    use AuthenticatesUsers;
    protected $lockoutTime;
    protected $maxLoginAttempts;

    public function __construct()
    {
        $this->middleware(HandleCors::class);
        $this->lockoutTime = config('auth.lockout.time');
        $this->maxLoginAttempts = config('auth.lockout.attempts');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $username = $request->input('username');
        $loginField = str_contains($username, '@') ? 'email' : 'username';
        $user = User::where($loginField, $username)->first();
        $uuid = $request->header('X-Device-Identifier');

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            $this->sendLockoutResponse($request);
        }

        if ($user) {
            if (password_verify($request->input('password'), $user->password) && $uuid) {
                if ($user->use_totp) {
                    $appToken = MobileToken::create([
                        'user_id' => $user->id,
                        'uuid' => $uuid,
                        'type' => MobileToken::LOW_VALUE,
                        'expires_at' => now()->addMinutes(5)
                    ]);
                } else {
                    $appToken = MobileToken::updateOrCreate([
                        'user_id' => $user->id,
                        'uuid' => $uuid,
                        'type' => MobileToken::HIGH_VALUE,
                    ], ['expires_at' => now()->addDays(2)]);
                }

                $data = [
                    'instance_name' => config('app.name'),
                    'token' => $appToken->token,
                    'type' => $appToken->type,
                ];
                if ($appToken->type == MobileToken::HIGH_VALUE) {
                    $userData = [
                        'username' => $user->username,
                        'name_first' => $user->name_first,
                        'name_last' => $user->name_last,
                        'root_admin' => $user->root_admin,
                        'email' => $user->email,
                        'uuid' => $user->uuid,
                    ];
                    $data = array_merge($data, $userData);
                }
                return response()->json($data, 201);
            } else {
                $this->incrementLoginAttempts($request);
                event(new Failed(config('auth.defaults.guard'), $user, $request->only(['username', 'password'])));
                return response(null, 401);
            }
        }

        $this->incrementLoginAttempts($request);
        event(new Failed(config('auth.defaults.guard'), null, $request->only(['username', 'password'])));
        return response(null, 401);
    }

    public function exchange(Request $request, Google2FA $google2FA) {

        $request->validate([
            'code' => 'required',
        ]);

        $token = $request->header('X-Token');
        $uuid = $request->header('X-Device-Identifier');
        $code = $request->input('code');

        if ($code && $token && $uuid) {
            if ($appToken = MobileToken::validExchangeFor($uuid)->whereToken($token)->first()) {
                $user = $appToken->user;

                if ($google2FA->verifyKey(decrypt($user->totp_secret), $request->input('code'), config('cursehosting.auth.2fa.window'))) {
                    $newToken = MobileToken::updateOrCreate([
                        'user_id' => $user->id,
                        'uuid' => $uuid,
                        'type' => MobileToken::HIGH_VALUE,
                    ], ['expires_at' => now()->addDays(2)]);

                    $appToken->delete();

                    return response()->json([
                        'token' => $newToken->token,
                        'type' => $newToken->type,
                        'username' => $user->username,
                        'name_first' => $user->name_first,
                        'name_last' => $user->name_last,
                        'root_admin' => $user->root_admin,
                        'email' => $user->email,
                        'uuid' => $user->uuid,
                    ], 201);
                }

                return response(null, 403);
            }
        }

        return response(null, 401);
    }

    public function logout(Request $request)
    {
        $token = $request->header('X-Token');
        $appToken = MobileToken::whereToken($token)->first();

        if ($appToken) {
            $appToken->delete();
        }

        return response(null, 204);
    }

}
