<?php

namespace App\MobileAddon\Controllers;

use Fruitcake\Cors\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\MobileAddon\Middleware\TokenAuth;
use Illuminate\Routing\Controller;
use App\Models\User;
use App\Contracts\Repository\ServerRepositoryInterface as PanelServerRepository;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepository;
use App\Exceptions\Http\Connection\DaemonConnectionException;

class ServersController extends Controller
{
    const LEVELS = [
        'user' => User::FILTER_LEVEL_SUBUSER,
        'admin' => User::FILTER_LEVEL_ADMIN,
        'all' => User::FILTER_LEVEL_ALL,
    ];

    public function __construct()
    {
        $this->middleware([HandleCors::class, TokenAuth::class]);
    }

    public function index(Request $request, PanelServerRepository $panelRepository)
    {
        $request->validate([
            'level' => 'nullable|in:user,admin,all',
        ]);

        $filterLevel = $request->input('level', 'user');

        $servers = $panelRepository->setSearchTerm($request->input('search'))->filterUserAccessServers(
            Auth::user(), $this::LEVELS[$filterLevel], null
        );

        $servers
            ->map(function($server) {
                unset($server['user']);
                unset($server['allocation']);

                if ($server->owner_id == Auth::user()->id) {
                    $server['level'] = 'owner';
                } elseif (Auth::user()->root_admin) {
                    $server['level'] = 'admin';
                } else {
                    $server['level'] = 'subuser';
                }

                if ($server->suspended || !$server->installed) {
                    unset($server['node']);
                    return $server;
                }

                try {
                    $daemonRepository = resolve(DaemonServerRepository::class);
                    $stats = $daemonRepository->setServer($server)->details();
                } catch (\Exception $exception) {
                    $server['utilization'] = [
                        'state' => -1
                    ];
                    unset($server['node']);
                    return $server;
                }

                $object = json_decode($stats->getBody()->getContents());

                $server['utilization'] = [
                    'state' => object_get($object, 'status', 0),
                    'memory' => [
                        'current' => round(object_get($object, 'proc.memory.total', 0) / 1024 / 1024),
                        'limit' => floatval($server->memory),
                    ],
                    'cpu' => [
                        'current' => object_get($object, 'proc.cpu.total', 0),
                        'cores' => object_get($object, 'proc.cpu.cores', []),
                        'limit' => (floatval($server->cpu) == 0) ? sizeof(object_get($object, 'proc.cpu.cores', [])) * 100 : floatval($server->cpu),
                    ],
                    'disk' => [
                        'current' => round(object_get($object, 'proc.disk.used', 0)),
                        'limit' => floatval($server->disk),
                    ],
                ];

                unset($server['node']);
                return $server;
            });

        return response()->json($servers);
    }

}
