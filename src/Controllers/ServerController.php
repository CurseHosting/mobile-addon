<?php

namespace App\MobileAddon\Controllers;

use Fruitcake\Cors\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\MobileAddon\Middleware\TokenAuth;
use Illuminate\Routing\Controller;
use App\Models\User;
use App\Models\Server;
use App\Contracts\Repository\Daemon\ServerRepositoryInterface as DaemonServerRepository;
use App\Exceptions\Http\Connection\DaemonConnectionException;
use App\Services\DaemonKeys\DaemonKeyProviderService;

class ServerController extends Controller
{
    public function __construct()
    {
        $this->middleware([HandleCors::class, TokenAuth::class]);
    }

    public function get(Request $request, $serverId, DaemonKeyProviderService $daemonKeyProviderService, DaemonServerRepository $daemonRepository)
    {
        $server = Server::findOrFail($serverId);

        try {
            $stats = $daemonRepository->setServer($server)->details();
        } catch (RequestException $exception) {
            throw new DaemonConnectionException($exception);
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
                'limit' => floatval($server->cpu),
            ],
            'disk' => [
                'current' => round(object_get($object, 'proc.disk.used', 0)),
                'limit' => floatval($server->disk),
            ],
        ];

        $dToken = $daemonKeyProviderService->handle($server, Auth::user(), true);

        $server->daemon = [
            'token' => $dToken,
            'fqdn' => $server->node->fqdn,
            'scheme' => $server->node->scheme,
            'daemonListen' => $server->node->daemonListen,
        ];

        unset($server->node);

        return response()->json($server);
    }
}
