<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Jobs\ServerLogJob;
use App\Jobs\TrafficFetchJob;
use App\Models\Server;
use App\Models\User;
use App\Utils\CacheKey;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;



class VmessController extends Controller
{
    /**
     * 后端获取用户User
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function users(Request $request)
    {
        $reqNodeId = $request->input('node_id');
        /**
         * @var Server $server
         */
        $server = Server::find($reqNodeId);
        if ($server === null) {
            return response([
                'msg' => 'false',
                'data' => 'server is not found',
            ]);
        }

        Cache::put(CacheKey::get(CacheKey::SERVER_V2RAY_LAST_CHECK_AT, $server->getKey()), time(), 3600);
        $result = [];
        $users = $server->findAvailableUsers();
        foreach ($users as $user) {
            /**
             * @var User $user
             */
            array_push($result, [User::FIELD_ID => $user->getKey(), User::FIELD_UUID => $user->getAttribute(User::FIELD_UUID)]);
        }

        return response([
            'msg' => 'ok',
            'data' => $result,
        ]);
    }

    /**
     * submit
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function submit(Request $request)
    {
        $reqNodeId = $request->input('node_id');

        /**
         * @var Server $server
         */
        $server = Server::find($reqNodeId);
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

        Cache::put(CacheKey::get(CacheKey::SERVER_V2RAY_ONLINE_USER, $server->getKey()), count($data), 3600);
        Cache::put(CacheKey::get(CacheKey::SERVER_V2RAY_LAST_PUSH_AT, $server->getKey()), time(), 3600);
        foreach ($data as $item) {
            $rate = $server->getAttribute(Server::FIELD_RATE);
            $u = $item[User::FIELD_U] * $rate;
            $d = $item[User::FIELD_D] * $rate;
            $userId = $item['user_id'];
            TrafficFetchJob::dispatch($u, $d, $userId);
            ServerLogJob::dispatch($u, $d, $userId, $server->getKey(), $rate, Server::METHOD);
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
        $reqNodeId = (int)$request->input('node_id');

        if ($reqNodeId <= 0) {
            abort(500, '参数错误');
        }
        /**
         * @var Server $server
         */
        $server = Server::find($reqNodeId);
        if ($server === null) {
            abort(500, 'server is not found');
        }

        $data = $server->makeHidden([
            Server::FIELD_SORT, Server::FIELD_GROUP_ID, Server::FIELD_HOST, Server::FIELD_PORT,
            Server::FIELD_RATE, Server::FIELD_SHOW, Server::FIELD_CREATED_AT, Server::FIELD_UPDATED_AT,
            Server::FIELD_TAGS, Server::FIELD_NAME, Server::FIELD_PARENT_ID, Server::FIELD_NETWORK_SETTINGS,
            Server::FIELD_RULE_SETTINGS
        ]);

        $networkAttribute = sprintf("%s_settings", $server->getAttribute(Server::FIELD_NETWORK));
        $server->setAttribute($networkAttribute, $server->getAttribute(Server::FIELD_NETWORK_SETTINGS));

        $ruleSettings = $server->getAttribute(Server::FIELD_RULE_SETTINGS);
        if ($ruleSettings) {
            $rules = [];
            $ruleDomains = $ruleSettings['domain'] ?? [];
            $ruleProtocols = $ruleSettings['protocol'] ?? [];

            foreach ($ruleDomains as $domain) {
               if (strlen($domain) > 0) {
                   $rule = [];
                   $rule['type'] = "field";
                   $rule['outboundTag'] = "block";
                   $rule['domains'] = [$domain];
                   array_push($rules, $rule);
               }
            }

            foreach ($ruleProtocols as $protocol) {
                if (strlen($protocol) > 0) {
                    $rule = [];
                    $rule['type'] = "field";
                    $rule['outboundTag'] = "block";
                    $rule['protocol'] = [$protocol];
                    array_push($rules, $rule);
                }
            }

            $server->setAttribute("router_settings", [
                "rules" => $rules
            ]);
        }

        $dnsSettings = $server->getAttribute(Server::FIELD_DNS_SETTINGS);
        if ($dnsSettings) {
            $server->setAttribute(Server::FIELD_DNS_SETTINGS, [
                "servers" => $dnsSettings
            ]);
        }
        return response([
            'data' => $data,
        ]);
    }
}
