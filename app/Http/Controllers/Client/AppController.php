<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ServerShadowsocks;
use App\Models\ServerTrojan;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Models\Server;
use Illuminate\Http\Response;
use Symfony\Component\Yaml\Yaml;
use App\Models\User;
use App\Utils\Client\Protocols\Clash;

class AppController extends Controller
{
    /**
     * configV2
     *
     * @param Request $request
     */
    public function config(Request $request)
    {
        /**
         * @var User $user
         */
        $user = $request->user;
        if (!$user->isAvailable()) {
            abort(500, "用户不可用");
        }

        $servers = array_merge(
            Server::configs($user)->toArray(),
            ServerShadowsocks::configs($user)->toArray(),
            ServerTrojan::configs($user)->toArray()
        );

        array_multisort(array_column($servers, 'sort'), SORT_ASC, $servers);


        $config = Yaml::parseFile(resource_path() . '/rules/app.clash.yaml');
        $proxy = [];
        $proxies = [];

        foreach ($servers as $item) {
            if ($item['type'] === 'shadowsocks') {
                array_push($proxy, Clash::buildShadowsocks($user['uuid'], $item));
                array_push($proxies, $item['name']);
            }
            if ($item['type'] === 'v2ray') {
                array_push($proxy, Clash::buildVmess($user['uuid'], $item));
                array_push($proxies, $item['name']);
            }
            if ($item['type'] === 'trojan') {
                array_push($proxy, Clash::buildTrojan($user['uuid'], $item));
                array_push($proxies, $item['name']);
            }
        }

        $config['proxies'] = array_merge($config['proxies'] ?: [], $proxy);
        foreach ($config['proxy-groups'] as $k => $v) {
            $config['proxy-groups'][$k]['proxies'] = array_merge($config['proxy-groups'][$k]['proxies'], $proxies);
        }
        die(Yaml::dump($config));
    }

    /**
     * version
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response|void
     */
    public function version(Request $request)
    {
        if (strpos($request->header('user-agent'), 'tidalab/4.0.0') !== false
            || strpos($request->header('user-agent'), 'tunnelab/4.0.0') !== false
        ) {
            if (strpos($request->header('user-agent'), 'Win64') !== false) {
                return response([
                    'data' => [
                        'version' => config('v2board.windows_version'),
                        'download_url' => config('v2board.windows_download_url')
                    ]
                ]);
            } else {
                return response([
                    'data' => [
                        'version' => config('v2board.macos_version'),
                        'download_url' => config('v2board.macos_download_url')
                    ]
                ]);
            }
            return;
        }
        return response([
            'data' => [
                'windows_version' => config('v2board.windows_version'),
                'windows_download_url' => config('v2board.windows_download_url'),
                'macos_version' => config('v2board.macos_version'),
                'macos_download_url' => config('v2board.macos_download_url'),
                'android_version' => config('v2board.android_version'),
                'android_download_url' => config('v2board.android_download_url')
            ]
        ]);
    }

}
