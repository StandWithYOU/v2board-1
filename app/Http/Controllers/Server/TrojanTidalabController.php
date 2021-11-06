<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Jobs\ServerLogJob;
use App\Jobs\TrafficFetchJob;
use App\Models\Server;
use App\Models\ServerTrojan;
use App\Models\User;
use App\Utils\CacheKey;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/*
 * Tidal Lab Trojan
 * Github: https://github.com/tokumeikoi/tidalab-trojan
 */

class TrojanTidalabController extends Controller
{
    /**
     * user
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function user(Request $request)
    {
        $reqNodeId = $request->input('node_id');
        $server = ServerTrojan::find($reqNodeId);
        /**
         * @var ServerTrojan $server
         */
        if ($server === null) {
            abort(500, 'fail');
        }

        Cache::put(CacheKey::get(CacheKey::SERVER_TROJAN_LAST_CHECK_AT, $server->getKey()), time(), 3600);
        $result = [];
        $users = $server->findAvailableUsers();
        foreach ($users as $user) {
            /**
             * @var User $user
             */
            $user->setAttribute("trojan_user", [
                "password" => $user->getAttribute(User::FIELD_UUID),
            ]);
            unset($user['uuid']);
            unset($user['email']);
            array_push($result, $user);
        }

        return response([
            'msg' => 'ok',
            'data' => $result,
        ]);
    }

    // 后端提交数据
    public function submit(Request $request)
    {
        // Log::info('serverSubmitData:' . $request->input('node_id') . ':' . file_get_contents('php://input'));
        $reqNodeId = $request->input('node_id');
        $server = ServerTrojan::find($reqNodeId);

        /**
         * @var ServerTrojan $server
         */
        if ($server === null) {
            return response([
                'ret' => 0,
                'msg' => 'server is not found'
            ]);
        }
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);

        if ($data === null || !is_array($data)) {
            return response([
                'ret' => 0,
                'msg' => 'params error'
            ]);
        }

        Cache::put(CacheKey::get(CacheKey::SERVER_TROJAN_ONLINE_USER, $server->getKey()), count($data), 3600);
        Cache::put(CacheKey::get(CacheKey::SERVER_TROJAN_LAST_PUSH_AT, $server->getKey()), time(), 3600);

        foreach ($data as $item) {
            $rate = $server->getAttribute(Server::FIELD_RATE);
            $u = $item[User::FIELD_U] * $rate;
            $d = $item[User::FIELD_D] * $rate;
            $userId = $item['user_id'];
            TrafficFetchJob::dispatch($u, $d, $userId);
            ServerLogJob::dispatch($u, $d, $userId, $server->getKey(), $rate, ServerTrojan::METHOD);
        }


        return response([
            'ret' => 1,
            'msg' => 'ok'
        ]);
    }

    /**
     * config
     *
     * @param Request $request
     */
    public function config(Request $request)
    {
        $reqNodeId = $request->input('node_id');
        $reqLocalPort = $request->input('local_port');
        if (empty($reqNodeId) || empty($reqLocalPort)) {
            abort(500, '参数错误');
        }
        /**
         * @var ServerTrojan $server
         */
        $server = ServerTrojan::find($reqNodeId);
        if ($server === null) {
            abort(500, '服务器未找到');
        }

        try {
            $json = $server->config($reqLocalPort);
            die(json_encode($json, JSON_UNESCAPED_UNICODE));
        } catch (Exception $e) {
            abort(500, $e->getMessage());
        }

    }
}
